<?php

/**
 * The main class of the extension PhpTags.
 *
 * @file PhpTags.body.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class PhpTags {

	static $compileTime = 0;
	static $time = 0;
	static $cacheHit = 0;
	static $memoryHit = 0;
	static $compileHit = 0;
	static $needInitRuntime = true;

	static $globalVariablesScript = array();

	private static $bytecodeNeedsUpdate = array();
	private static $frames=array();
	private static $bytecodeCache = array();
	private static $bytecodeLoaded = array();

	/**
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 */
	public static function renderFunction( $parser, $frame, $args ) {
		wfProfileIn( __METHOD__ );
		$time = $parser->mOutput->getTimeSinceStart( 'cpu' );

		$command = array_shift($args);
		if ( count( $args ) > 0 ) {
			foreach ( $args as &$value ) {
				$value = $frame->expand( $value );
			}
			$command = "echo $command (" . implode( ',', $args ) . ');';
		} elseif ( preg_match( '/^\S+$/', $command ) == 1 ) {
			$command = "echo $command;";
		}

		$frameTitle = $frame->getTitle();
		$frameTitleText = $frameTitle->getPrefixedText();
		$arguments = array( $frameTitleText ) + $frame->getArguments();
		$scope = self::getScopeID( $frame );

		try {
			$bytecode = self::getBytecode( $command, $parser, $frame, $frameTitle, $frameTitleText, $time );
			$result = \PhpTags\Runtime::run( $bytecode, $arguments, $scope );
			$return = implode( $result );
		} catch ( \PhpTags\PhpTagsException $exc ) {
			$return = (string) $exc;
		} catch ( MWException $exc ) {
			throw $exc;
		} catch ( Exception $exc ) {
			$return = $exc->getTraceAsString();
		}

		self::$time += $parser->mOutput->getTimeSinceStart( 'cpu' ) - $time;

		wfProfileOut( __METHOD__ );
		return \UtfNormal::cleanUp( $return );
	}

	public static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
		wfProfileIn( __METHOD__ );
		$time = $parser->mOutput->getTimeSinceStart( 'cpu' );

		$frameTitle = $frame->getTitle();
		$frameTitleText = $frameTitle->getPrefixedText();
		$arguments = array( $frameTitleText ) + $frame->getArguments();
		$scope = self::getScopeID( $frame );

		try {
			$bytecode = self::getBytecode( $input, $parser, $frame, $frameTitle, $frameTitleText, $time );
			$result = \PhpTags\Runtime::run( $bytecode, $arguments, $scope );
		} catch ( \PhpTags\PhpTagsException $exc ) {
			$result = array( (string) $exc );
			$parser->addTrackingCategory( 'phptags-compiler-error-category' );
		} catch ( MWException $exc ) {
			throw $exc;
		} catch ( Exception $exc ) {
			$result = array( $exc->getTraceAsString() );
		}

		self::$time += $parser->mOutput->getTimeSinceStart( 'cpu' ) - $time;
		$return = self::insertGeneral( $parser, $parser->recursiveTagParse( implode($result), $frame ) );
		wfProfileOut( __METHOD__ );
		return $return;
	}

	/**
	 *
	 * @global int $wgPhpTagsBytecodeExptime
	 * @param string $source
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Title $frameTitle
	 * @param string $frameTitleText
	 * @param int $time
	 * @return array
	 */
	private static function getBytecode( $source, $parser, $frame, $frameTitle, $frameTitleText, $time ) {
		global $wgPhpTagsBytecodeExptime, $wgPhpTagsCounter;
		$wgPhpTagsCounter++;

		static $parserTitle = false;
		if ( $parserTitle === false ) {
			$parserTitle = $parser->getTitle();
		}
		$revID = $parserTitle === $frameTitle ? $parser->getRevisionId() : $frameTitle->getLatestRevID();
		$md5Source = md5( $source );

		self::initialize( $parser, $frame, $frameTitle );

		if ( true === isset( self::$bytecodeCache[$revID][$md5Source] ) ) {
			\wfDebug( "[phptags] Memory hiting with key $revID" );
			self::$memoryHit++;
			return self::$bytecodeCache[$revID][$md5Source];
		}

		if ( $wgPhpTagsBytecodeExptime > 0 && $revID > 0 && false === isset( self::$bytecodeLoaded[$revID] ) ) {
			$cache = \wfGetCache( CACHE_ANYTHING );
			$key = \wfMemcKey( 'PhpTags', $revID );
			$data = $cache->get( $key );
			self::$bytecodeLoaded[$revID] = true;
			if ( $data !== false && $data[0] === PHPTAGS_RUNTIME_RELEASE ) {
				self::$bytecodeCache[$revID] = $data[1];
				if ( true === isset( self::$bytecodeCache[$revID][$md5Source] ) ) {
					\wfDebug( "[phptags] Cache hiting with key $revID" );
					self::$cacheHit++;
					return self::$bytecodeCache[$revID][$md5Source];
				}
			}
			\wfDebug( "[phptags] Cache missing with key $revID" );
		}

		$bytecode = \PhpTags\Compiler::compile( $source, $frameTitleText );
		self::$bytecodeCache[$revID][$md5Source] = $bytecode;
		if ( $revID > 0 ) { // Don't save bytecode of unsaved pages
			self::$bytecodeNeedsUpdate[$revID][$md5Source] = unserialize( serialize( $bytecode ) );
		}

		self::$compileHit++;
		self::$compileTime += $parser->mOutput->getTimeSinceStart( 'cpu' ) - $time;
		return $bytecode;
	}

	/**
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @throws \PhpTags\PhpTagsException
	 * @return null
	 */
	private static function initialize( $parser, $frame, $frameTitle ) {
		global $wgPhpTagsNamespaces, $wgPhpTagsMaxLoops;
		if ( true !== $wgPhpTagsNamespaces && false === isset( $wgPhpTagsNamespaces[$frameTitle->getNamespace()] ) ) {
			throw new \PhpTags\PhpTagsException( \PhpTags\PhpTagsException::FATAL_DENIED_FOR_NAMESPACE, $frameTitle->getNsText() );
		}

		if ( true === self::$needInitRuntime ) {
			\wfDebug( '[phptags] ' . __METHOD__ . '() runs hook PhpTagsRuntimeFirstInit' );
			\wfRunHooks( 'PhpTagsRuntimeFirstInit' );
			\PhpTags\Hooks::loadData();
			\PhpTags\Runtime::$loopsLimit = $wgPhpTagsMaxLoops;
			self::$needInitRuntime = false;
		}

		\PhpTags\Runtime::$parser = $parser;
		\PhpTags\Runtime::$frame = $frame;
	}

	private static function updateBytecodeCache() {
		global $wgPhpTagsBytecodeExptime;

		$cache = \wfGetCache( CACHE_ANYTHING );
		foreach ( self::$bytecodeNeedsUpdate as $revID => $data ) {
			$key = wfMemcKey( 'PhpTags', $revID );
			$cache->set( $key, array(PHPTAGS_RUNTIME_RELEASE, $data), $wgPhpTagsBytecodeExptime );
			\wfDebug( "[phptags] Save compiled bytecode to cache with key $revID" );
		}
		self::$bytecodeNeedsUpdate = array();
	}

	public static function reset() {
		self::writeLimitReport();

		global $wgPhpTagsCounter;
		$wgPhpTagsCounter = 0;
		PhpTags\Runtime::reset();
		self::$bytecodeCache = array();
		self::$bytecodeLoaded = array();
		self::$globalVariablesScript = array();
	}

	/**
	 *
	 * @param Parser $parser
	 * @param string $text
	 * @return string
	 */
	private static function insertGeneral( $parser, $text ) {
		return $parser->insertStripItem( $text );
	}

	private static function getScopeID( PPFrame $frame ) {
		foreach ( self::$frames as $value ) {
			if ( $value[0] === $frame ) {
				return $value[1];
			}
		}
		static $scope = 0;
		self::$frames[] = array( $frame, $scope );
		return $scope++;
	}

	/**
	 *
	 * @param array $extResources
	 * @param array $extMode
	 */
	public static function onCodeMirrorGetAdditionalResources( &$extResources, &$extMode ) {
		$extResources['scripts']['lib/codemirror/mode/php/php.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/htmlmixed/htmlmixed.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/xml/xml.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/javascript/javascript.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/css/css.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/clike/clike.js'] = true;

		$extMode['tag']['phptag'] = 'text/x-php';

		return true;
	}

	public static function onPhpTagsRuntimeFirstInit() {
		\PhpTags\Hooks::addJsonFile( __DIR__ . '/PhpTags.json', PHPTAGS_VERSION );
		return true;
	}

	public static function writeLimitReport() {
		global $wgPhpTagsCounter, $wgPhpTagsLimitReport;

		$time = self::$time;
		$compileTime = self::$compileTime;
		$wgPhpTagsLimitReport = sprintf(
				'PhpTags usage count: %d
Runtime : %.3f sec
Compiler: %.3f sec ( usage: %d, cache: %d, memory: %d )
Total   : %.3f sec
',
				$wgPhpTagsCounter,
				$time - $compileTime,
				$compileTime,
				self::$compileHit,
				self::$cacheHit,
				self::$memoryHit,
				$time
			);
		return true;
	}

	public static function onParserAfterTidy( &$parser, &$text ) {
		global $wgPhpTagsBytecodeExptime;
		wfProfileIn( __METHOD__ );

		if ( self::$globalVariablesScript ) {
			$vars = array();
			foreach ( self::$globalVariablesScript as $key=> $value ) {
				$vars["ext.phptags.$key"] = $value;
			}
			$text .= Html::inlineScript(
				ResourceLoader::makeLoaderConditionalScript(
					ResourceLoader::makeConfigSetScript( $vars )
				)
			);
		}
		if ( $wgPhpTagsBytecodeExptime > 0 && self::$bytecodeNeedsUpdate ) {
			self::updateBytecodeCache();
		}
		self::reset();

		wfProfileOut( __METHOD__ );
		return true;
	}

}
