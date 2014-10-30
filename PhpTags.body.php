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

	static $DebugLoops = false;
	static $compileTime = 0;
	static $time = 0;
	static $cacheHit = 0;
	static $memoryHit = 0;
	static $compileHit = 0;

	static $globalVariablesScript = array();

	private static $bytecodeNeedsUpdate = array();
	private static $frames=array();
	private static $needInitRuntime = true;
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
		$scope = self::getScope( $frame );

		try {
			$bytecode = self::getBytecode( $command, $parser, $frame, $frameTitle, $frameTitleText, $time );
			$result = \PhpTags\Runtime::run( $bytecode, $arguments, $scope );
			$return = implode( $result );
		} catch ( \PhpTags\PhpTagsException $exc ) {
			$return = (string) $exc;
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
		$return = false;

		$frameTitle = $frame->getTitle();
		$frameTitleText = $frameTitle->getPrefixedText();
		$arguments = array( $frameTitleText ) + $frame->getArguments();
		$scope = self::getScope( $frame );

		try {
			$bytecode = self::getBytecode( $input, $parser, $frame, $frameTitle, $frameTitleText, $time );
			$result = \PhpTags\Runtime::run( $bytecode, $arguments, $scope );
		} catch ( \PhpTags\PhpTagsException $exc ) {
			self::$time += $parser->mOutput->getTimeSinceStart( 'cpu' ) - $time;
			return (string) $exc;
		} catch ( Exception $exc ) {
			self::$time += $parser->mOutput->getTimeSinceStart( 'cpu' ) - $time;
			return $exc->getTraceAsString();
		}
		self::$time += $parser->mOutput->getTimeSinceStart( 'cpu' ) - $time;

		if( true === isset( $result[0] ) ) {
			$return = self::insertGeneral(
					$parser,
					$parser->recursiveTagParse( implode($result), $frame )
				);
		}

		wfProfileOut( __METHOD__ );
		return \UtfNormal::cleanUp( $return );
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

		$frameID = $frameTitle->getArticleID();
		$md5Source = md5( $source );

		self::initialize( $parser, $frame, $frameTitle );

		if ( true === isset( self::$bytecodeCache[$frameID][$md5Source] ) ) {
			self::$memoryHit++;
			return self::$bytecodeCache[$frameID][$md5Source];
		}

		if ( $wgPhpTagsBytecodeExptime > 0 && $frameID > 0 && false === isset( self::$bytecodeLoaded[$frameID] ) ) {
			$cache = wfGetCache( CACHE_ANYTHING );
			$key = wfMemcKey( 'phptags', $frameID, PHPTAGS_RUNTIME_RELEASE );
			$data = $cache->get( $key );
			self::$bytecodeLoaded[$frameID] = true;
			if ( false !== $data ) {
				$arrayData = unserialize( $data );
				self::$bytecodeCache[$frameID] = $arrayData;
				if ( true === isset( self::$bytecodeCache[$frameID][$md5Source] ) ) {
					self::$cacheHit++;
					return self::$bytecodeCache[$frameID][$md5Source];
				}
			}
		}

		$bytecode = \PhpTags\Compiler::compile( $source, $frameTitleText );
		self::$bytecodeCache[$frameID][$md5Source] = $bytecode;
		self::$bytecodeNeedsUpdate[$frameID][$md5Source] = unserialize( serialize( $bytecode ) );

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
			\wfRunHooks( 'PhpTagsRuntimeFirstInit' );
			\PhpTags\Runtime::$loopsLimit = $wgPhpTagsMaxLoops;
			self::$needInitRuntime = false;
		}

		\PhpTags\Runtime::$transit[PHPTAGS_TRANSIT_PARSER] = $parser;
		\PhpTags\Runtime::$transit[PHPTAGS_TRANSIT_PPFRAME] = $frame;
	}

	public static function onOutputPageParserOutput() {
		wfProfileIn( __METHOD__ );
		global $wgPhpTagsCounter, $wgPhpTagsBytecodeExptime;

		if ( $wgPhpTagsCounter > 0 ) {
			if ( $wgPhpTagsBytecodeExptime > 0 && self::$bytecodeNeedsUpdate ) {
				self::updateBytecodeCache();
			}
			PhpTags::reset();
		}

		wfProfileOut( __METHOD__ );
	}

	private static function updateBytecodeCache() {
		global $wgPhpTagsBytecodeExptime;

		$cache = wfGetCache( CACHE_ANYTHING );
		foreach ( self::$bytecodeNeedsUpdate as $frameID => $dataArray ) {
			$key = wfMemcKey( 'phptags', $frameID, PHPTAGS_RUNTIME_RELEASE );
			$data = serialize( $dataArray );
			$cache->set( $key, $data, $wgPhpTagsBytecodeExptime );
		}
		self::$bytecodeNeedsUpdate = array();
	}

	/**
	 *
	 * @param Article $article
	 */
	public static function clearBytecodeCache( &$article ) {
		wfProfileIn( __METHOD__ );

		$frameID = $article->getTitle()->getArticleID();
		$key = wfMemcKey( 'phptags', $frameID, PHPTAGS_RUNTIME_RELEASE );
		$cache = wfGetCache( CACHE_ANYTHING );
		$cache->delete( $key );

		wfProfileOut( __METHOD__ );
	}

	public static function reset() {
		global $wgPhpTagsCounter;
		$wgPhpTagsCounter = 0;
		PhpTags\Runtime::reset();
		self::$bytecodeCache = array();
		self::$bytecodeLoaded = array();
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

	/**
	 * @see Parser::insertStripItem()
	 * @param Parser $parser
	 * @param string $text
	 * @return string
	 */
	private static function insertNoWiki( $parser, $text ) {
		// @see Parser::insertStripItem()
		$rnd = "{$parser->mUniqPrefix}-item-{$parser->mMarkerIndex}-" . Parser::MARKER_SUFFIX;
		$parser->mMarkerIndex++;
		$parser->mStripState->addNoWiki( $rnd, $text );
		return $rnd;
	}

	private static function getScope( PPFrame $frame ) {
		foreach ( self::$frames as &$value ) {
			if ( $value[0] === $frame ) {
				return $value[1];
			}
		}
		$scope=count( self::$frames );
		self::$frames[] = array( $frame, $scope );
		return $scope;
	}

	public static function getCodeMirrorMode( &$mode, &$module ) {
		$mode['tag']['phptag'] = 'text/x-php';
	}

	public static function onMakeGlobalVariablesScript( array &$vars ) {
		foreach ( self::$globalVariablesScript as $key=> $value ) {
			$vars["ext.phptags.$key"] = $value;
		}
	}

}
