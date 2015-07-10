<?php
namespace PhpTags;

/**
 * The main class of the extension PhpTags.
 *
 * @file Renderer.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Renderer {

	static $cacheHit = 0;
	static $memoryHit = 0;
	static $compileHit = 0;
	static $needInitRuntime = true;

	static $globalVariablesScript = array();

	private static $scopes = array();
	private static $nextScopeID = 0;
	/**
	 * Array of PPFrame
	 * @var array
	 */
	private static $frame = array();

	/**
	 * Parser
	 * @var \Parser
	 */
	private static $parser;

	private static $bytecodeNeedsUpdate = array();
	private static $bytecodeCache = array();
	private static $bytecodeLoaded = array();
	private static $parserCacheDisabled = false;
	private static $errorCategoryAdded = false;

	/**
	 *
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @param array $args
	 */
	public static function runParserFunction( $parser, $frame, $args ) {
		wfProfileIn( __METHOD__ );
		Timer::start( $parser );

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
			$bytecode = self::getBytecode( $command, $parser, $frame, $frameTitle, $frameTitleText );
			$result = Runtime::run( $bytecode, $arguments, $scope );
			$return = implode( $result );
		} catch ( PhpTagsException $exc ) {
			$return = (string) $exc;
		} catch ( \MWException $exc ) {
			throw $exc;
		} catch ( \Exception $exc ) {
			$return = $exc->getTraceAsString();
		}

		Timer::stop( $parser );

		wfProfileOut( __METHOD__ );
		return \UtfNormal::cleanUp( $return );
	}

	public static function runTagHook( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		wfProfileIn( __METHOD__ );
		Timer::start( $parser );

		$frameTitle = $frame->getTitle();
		$frameTitleText = $frameTitle->getPrefixedText();
		$arguments = array( $frameTitleText ) + $frame->getArguments();
		$scope = self::getScopeID( $frame );

		try {
			$bytecode = self::getBytecode( $input, $parser, $frame, $frameTitle, $frameTitleText );
			$result = Runtime::run( $bytecode, $arguments, $scope );
		} catch ( PhpTagsException $exc ) {
			$result = array( (string) $exc );
			$parser->addTrackingCategory( 'phptags-compiler-error-category' );
		} catch ( \MWException $exc ) {
			throw $exc;
		} catch ( \Exception $exc ) {
			$result = array( $exc->getTraceAsString() );
		}

		Timer::stop( $parser );
		$return = self::insertGeneral( $parser, $parser->recursiveTagParse( implode($result), $frame ) );
		wfProfileOut( __METHOD__ );
		return $return;
	}

	/**
	 *
	 * @global int $wgPhpTagsBytecodeExptime
	 * @param string $source
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @param \Title $frameTitle
	 * @param string $frameTitleText
	 * @return array
	 */
	private static function getBytecode( $source, $parser, $frame, $frameTitle, $frameTitleText ) {
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
			\wfDebugLog( 'PhpTags', 'Memory hiting with key ' . $revID );
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
					\wfDebugLog( 'PhpTags', 'Cache hiting with key ' . $revID );
					self::$cacheHit++;
					return self::$bytecodeCache[$revID][$md5Source];
				}
			}
			\wfDebugLog( 'PhpTags', 'Cache missing with key ' . $revID );
		}

		$bytecode = Compiler::compile( $source, $frameTitleText );
		self::$bytecodeCache[$revID][$md5Source] = $bytecode;
		if ( $revID > 0 ) { // Don't save bytecode of unsaved pages
			self::$bytecodeNeedsUpdate[$revID][$md5Source] = unserialize( serialize( $bytecode ) );
		}

		self::$compileHit++;
		Timer::addCompileTime( $parser );
		return $bytecode;
	}

	/**
	 *
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @throws \PhpTags\PhpTagsException
	 * @return null
	 */
	private static function initialize( $parser, $frame, $frameTitle ) {
		global $wgPhpTagsNamespaces, $wgPhpTagsMaxLoops;
		if ( true !== $wgPhpTagsNamespaces && false === isset( $wgPhpTagsNamespaces[$frameTitle->getNamespace()] ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_DENIED_FOR_NAMESPACE, $frameTitle->getNsText() );
		}

		if ( true === self::$needInitRuntime ) {
			\wfDebugLog( 'PhpTags', 'Run hook PhpTagsRuntimeFirstInit' );
			\wfRunHooks( 'PhpTagsRuntimeFirstInit' );
			Hooks::loadData();
			Runtime::$loopsLimit = $wgPhpTagsMaxLoops;
			self::$needInitRuntime = false;
		}

		self::$parser = $parser;
		array_unshift( self::$frame, $frame );
	}

	private static function updateBytecodeCache() {
		global $wgPhpTagsBytecodeExptime;

		$cache = \wfGetCache( CACHE_ANYTHING );
		foreach ( self::$bytecodeNeedsUpdate as $revID => $data ) {
			$key = wfMemcKey( 'PhpTags', $revID );
			$cache->set( $key, array(PHPTAGS_RUNTIME_RELEASE, $data), $wgPhpTagsBytecodeExptime );
			\wfDebugLog( 'PhpTags', 'Save compiled bytecode to cache with key ' . $revID );
		}
		self::$bytecodeNeedsUpdate = array();
	}

	public static function reset() {
		self::writeLimitReport();

		global $wgPhpTagsCounter;
		$wgPhpTagsCounter = 0;
		Runtime::reset();
		Timer::reset();
		self::$bytecodeCache = array();
		self::$bytecodeLoaded = array();
		self::$globalVariablesScript = array();
		self::$parserCacheDisabled = false;
		self::$errorCategoryAdded = false;
		self::$scopes = array();
		self::$nextScopeID = 0;
	}

	/**
	 *
	 * @param \Parser $parser
	 * @param string $text
	 * @return string
	 */
	private static function insertGeneral( \Parser $parser, $text ) {
		return $parser->insertStripItem( $text );
	}

	public static function getScopeID( \PPFrame $frame ) {
		foreach ( self::$scopes as $value ) {
			if ( $value[0] === $frame ) {
				return $value[1];
			}
		}
		self::$scopes[] = array( $frame, self::$nextScopeID );
		return self::$nextScopeID++;
	}

	public static function writeLimitReport() {
		global $wgPhpTagsCounter, $wgPhpTagsLimitReport;

		$time = Timer::getRunTime();
		$compileTime = Timer::getCompileTime();
		$wgPhpTagsLimitReport = sprintf(
				'-------------------- PhpTags Extension --------------------
PhpTags usage count: %d
Runtime : %.3f sec
Compiler: %.3f sec ( usage: %d, cache: %d, memory: %d )
Total   : %.3f sec
-----------------------------------------------------------
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

	public static function onParserAfterTidy( $parser, &$text ) {
		global $wgPhpTagsBytecodeExptime;
		wfProfileIn( __METHOD__ );

		if ( self::$globalVariablesScript ) {
			$vars = array();
			foreach ( self::$globalVariablesScript as $key=> $value ) {
				$vars["ext.phptags.$key"] = $value;
			}
			$text .= \Html::inlineScript(
				\ResourceLoader::makeLoaderConditionalScript(
					\ResourceLoader::makeConfigSetScript( $vars )
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

	/**
	 * Returns Parser
	 * @return \Parser
	 */
	public static function getParser() {
		return self::$parser;
	}

	/**
	 * Returns Frame
	 * @return \PPFrame
	 */
	public static function getFrame() {
		return self::$frame[0];
	}

	/**
	 * Set a flag in the output object indicating that the content is dynamic and
	 * shouldn't be cached.
	 * @global \OutputPage $wgOut
	 * @staticvar boolean $done
	 * @return null
	 */
	public static function disableParserCache() {
		if ( self::$parserCacheDisabled === true ) {
			return;
		}

		global $wgOut;

		self::$parser->disableCache();
		$wgOut->enableClientCache( false );
		self::$parserCacheDisabled = true;
	}

	public static function addRuntimeErrorCategory() {
		if ( self::$errorCategoryAdded === true || self::$parser === null ) {
			return;
		}

		self::$parser->addTrackingCategory( 'phptags-runtime-error-category' );
		self::$errorCategoryAdded = true;
	}

	/**
	 * Increment the expensive function count
	 * @param string $functionName
	 * @return null
	 * @throws PhpTagsException
	 */
	public static function incrementExpensiveFunctionCount() {
		if ( true !== self::$parser->incrementExpensiveFunctionCount() ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALLED_MANY_EXPENSIVE_FUNCTION );
		}
	}

}

class Timer {
	private static $times = array();
	private static $runTime = 0;
	private static $compile = 0;

	public static function start( $parser ) {
		array_unshift( self::$times, $parser->mOutput->getTimeSinceStart( 'cpu' ) );
	}

	public static function stop( $parser ) {
		if ( false === isset(self::$times[1]) ) {
			self::$runTime += $parser->mOutput->getTimeSinceStart( 'cpu' ) - self::$times[0];
		}
		array_shift( self::$times );
	}

	public static function addCompileTime( $parser ) {
		self::$compile += $parser->mOutput->getTimeSinceStart( 'cpu' ) - self::$times[0];
	}

	public static function getRunTime() {
		return self::$runTime;
	}

	public static function getCompileTime() {
		return self::$compile;
	}

	public static function reset() {
		self::$times = array();
		self::$runTime = 0;
		self::$compile = 0;
	}

}
