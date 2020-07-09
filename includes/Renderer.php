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

use Exception;
use MWException;
use Parser;
use PPFrame;
use Title;
use UtfNormal\Validator;

class Renderer {

	static $cacheHit = 0;
	static $memoryHit = 0;
	static $compileHit = 0;
	static $needInitRuntime = true;

	private static $scopes = [];
	private static $nextScopeID = 0;
	/**
	 * Array of PPFrame
	 * @var array
	 */
	private static $frame = [];

	/**
	 * Parser
	 * @var Parser
	 */
	private static $parser;

	private static $bytecodeNeedsUpdate = [];
	private static $bytecodeCache = [];
	private static $bytecodeLoaded = [];
	private static $parserCacheDisabled = false;
	private static $errorCategoryAdded = false;
	public static $globalVariablesScript = [];

	/**
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 */
	public static function runParserFunction( $parser, $frame, $args ) {
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
		$arguments = [ $frameTitleText ] + $frame->getArguments();
		$scope = self::getScopeID( $frame );

		try {
			$bytecode = self::getBytecode( $command, $parser, $frame, $frameTitle, $frameTitleText );
			$result = Runtime::run( $bytecode, $arguments, $scope );
			array_shift( self::$frame );
			$return = implode( $result );
		} catch ( PhpTagsException $exc ) {
			$return = (string) $exc;
		} catch ( MWException $exc ) {
			throw $exc;
		} catch ( Exception $exc ) {
			$return = $exc->getTraceAsString();
		}

		Timer::stop( $parser );

		return Validator::cleanUp( $return );
	}

	public static function runTagHook( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		Timer::start( $parser );

		$frameTitle = $frame->getTitle();
		$frameTitleText = $frameTitle->getPrefixedText();
		$arguments = [ $frameTitleText ] + $frame->getArguments();
		$scope = self::getScopeID( $frame );

		try {
			$bytecode = self::getBytecode( $input, $parser, $frame, $frameTitle, $frameTitleText );
			$result = Runtime::run( $bytecode, $arguments, $scope );
			array_shift( self::$frame );
		} catch ( PhpTagsException $exc ) {
			$result = [ (string) $exc ];
			$parser->addTrackingCategory( 'phptags-compiler-error-category' );
		} catch ( \MWException $exc ) {
			throw $exc;
		} catch ( \Exception $exc ) {
			$result = [ $exc->getTraceAsString() ];
		}

		Timer::stop( $parser );
		$return = self::insertGeneral( $parser, $parser->recursiveTagParse( implode($result), $frame ) );
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
		global $wgPhpTagsBytecodeExptime, $wgPhpTagsCallsCounter;
		$wgPhpTagsCallsCounter++;

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
			return unserialize( self::$bytecodeCache[$revID][$md5Source] );
		}

		if ( $wgPhpTagsBytecodeExptime > 0 && $revID > 0 && false === isset( self::$bytecodeLoaded[$revID] ) ) {
			$cache = \wfGetCache( CACHE_ANYTHING );
			$key = \wfMemcKey( 'PhpTags', $revID );
			$data = $cache->get( $key );
			self::$bytecodeLoaded[$revID] = true;
			if ( $data !== false && $data[0] === Runtime::VERSION ) {
				self::$bytecodeCache[$revID] = $data[1];
				if ( true === isset( self::$bytecodeCache[$revID][$md5Source] ) ) {
					\wfDebugLog( 'PhpTags', 'Cache hiting with key ' . $revID );
					self::$cacheHit++;
					return unserialize( self::$bytecodeCache[$revID][$md5Source] );
				}
			}
			\wfDebugLog( 'PhpTags', 'Cache missing with key ' . $revID );
		}

		$bytecode = serialize( Compiler::compile( $source, $frameTitleText ) );
		self::$bytecodeCache[$revID][$md5Source] = $bytecode;
		if ( $revID > 0 ) { // Don't save bytecode of unsaved pages
			self::$bytecodeNeedsUpdate[$revID][$md5Source] = $bytecode;
		}

		self::$compileHit++;
		Timer::addCompileTime( $parser );
		return unserialize( $bytecode );
	}

	/**
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Title $frameTitle
	 * @return null
	 * @throws PhpTagsException
	 */
	private static function initialize( $parser, $frame, $frameTitle ) {
		global $wgPhpTagsNamespaces, $wgPhpTagsMaxLoops;
		if ( $wgPhpTagsNamespaces !== true && !( $wgPhpTagsNamespaces[$frameTitle->getNamespace()] ?? false ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_DENIED_FOR_NAMESPACE, $frameTitle->getNsText() );
		}

		if ( true === self::$needInitRuntime ) {
			wfDebug( 'PhpTags: Run hook PhpTagsRuntimeFirstInit' );
			\Hooks::run( 'PhpTagsRuntimeFirstInit' );
			Hooks::loadData();
			Runtime::$loopsLimit = $wgPhpTagsMaxLoops;
			self::$needInitRuntime = false;
		}

		self::$parser = $parser;
		array_unshift( self::$frame, $frame );
	}

	private static function updateBytecodeCache() {
		global $wgPhpTagsBytecodeExptime;

		$cache = wfGetCache( CACHE_ANYTHING );
		foreach ( self::$bytecodeNeedsUpdate as $revID => $data ) {
			$key = wfMemcKey( 'PhpTags', $revID );
			$cache->set( $key, [ Runtime::VERSION, $data ], $wgPhpTagsBytecodeExptime );
			wfDebugLog( 'PhpTags', 'Save compiled bytecode to cache with key ' . $revID );
		}
		self::$bytecodeNeedsUpdate = [];
	}

	public static function reset() {
		self::writeLimitReport();

		global $wgPhpTagsCallsCounter;
		$wgPhpTagsCallsCounter = 0;

		Runtime::reset();
		Timer::reset();
		self::$bytecodeCache = [];
		self::$bytecodeLoaded = [];
		self::$parserCacheDisabled = false;
		self::$errorCategoryAdded = false;
		self::$scopes = [];
		self::$nextScopeID = 0;
	}

	/**
	 *
	 * @param Parser $parser
	 * @param string $text
	 * @return string
	 */
	private static function insertGeneral( Parser $parser, $text ) {
		return $parser->insertStripItem( $text );
	}

	public static function getScopeID( PPFrame $frame ) {
		foreach ( self::$scopes as $value ) {
			if ( $value[0] === $frame ) {
				return $value[1];
			}
		}
		self::$scopes[] = [ $frame, self::$nextScopeID ];
		return self::$nextScopeID++;
	}

	public static function writeLimitReport() {
		global $wgPhpTagsCallsCounter, $wgPhpTagsLimitReport;

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
				$wgPhpTagsCallsCounter,
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

		$scriptVariables = self::getScriptVariable();

		if ( $scriptVariables ) {
			$vars = [];
			foreach ( $scriptVariables as $key => $value ) {
				$vars["ext.phptags.$key"] = $value;
			}
			$text = \Html::inlineScript(
				\ResourceLoader::makeLoaderConditionalScript(
					\ResourceLoader::makeConfigSetScript( $vars )
				)
			) . $text;
		}
		if ( $wgPhpTagsBytecodeExptime > 0 && self::$bytecodeNeedsUpdate ) {
			self::updateBytecodeCache();
		}
		self::reset();

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

	/**
	 *
	 * @see Parser::insertStripItem()
	 * @param string $text
	 * @return string
	 */
	public static function insertNoWiki( $text ) {
		$parser = self::$parser;
		if ( $parser === null ) { // skip in php unit tests
			return $text;
		}
		$rnd = \Parser::MARKER_PREFIX . "-phptags-{$parser->mMarkerIndex}-" . \Parser::MARKER_SUFFIX;
		$parser->mMarkerIndex++;
		$parser->mStripState->addNoWiki( $rnd, $text );
		return $rnd;
	}


	public static function insertStripItem( $text ) {
		$parser = self::$parser;
		if ( $parser === null ) { // skip in php unit tests
			return $text;
		}
		return $parser->insertStripItem( $text );
	}

	public static function getScriptVariable( array $path = [] ) {
		$parser = self::getParser();
		if ( !$parser ) {
			return null;
		}
		$data = $parser->getOutput()->getExtensionData( 'PhpTagsVariable' );
		if ( !$data ) {
			return null;
		}
		$ret = &$data;

		$path = (array)$path;
		if ( !$path ) {
			return $ret;
		}
		$latest = array_pop( $path );

		foreach ( $path as $k ) {
			if ( !isset( $ret[$k] ) ) {
				return null;
			} elseif ( !is_array( $ret[$k] ) ) {
				return null;
			}
			$ret = &$ret[$k];
		}
		return $ret[$latest] ?? null;
	}

	public static function setScriptVariable( array $path, $value ) {
		$path = (array)$path;
		if ( !$path ) {
			return false;
		}
		$latest = array_pop( $path );

		$parser = self::getParser();
		$parserOutput = $parser->getOutput();
		$data = $parserOutput->getExtensionData( 'PhpTagsVariable' );
		if ( !$data ) {
			$data = [];
		}
		$ret = &$data;

		foreach ( $path as $k ) {
			if ( !isset( $ret[$k] ) ) {
				$ret[$k] = [];
			} elseif ( !is_array( $ret[$k] ) ) {
				return false;
			}
			$ret = &$ret[$k];
		}
		$ret[$latest] = $value;
		$parserOutput->setExtensionData( 'PhpTagsVariable', $data );
		return true;
	}

}

class Timer {
	private static $times = [];
	private static $runTime = 0;
	private static $compile = 0;
	private static $reset = false; // allows to make a postponed reset

	public static function start( $parser ) {
		array_unshift( self::$times, $parser->mOutput->getTimeSinceStart( 'cpu' ) );
	}

	public static function stop( $parser ) {
		if ( !isset( self::$times[1] ) ) { // count the latest stop calling only
			self::$runTime += $parser->mOutput->getTimeSinceStart( 'cpu' ) - self::$times[0];
		}
		array_shift( self::$times );

		if ( self::$reset && !self::$times ) { // make a postponed reset
			self::$reset = false;
			self::$runTime = 0;
			self::$compile = 0;
		}
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
		if ( self::$times ) { // the stop function was not called
			self::$reset = true; // postpone the real reset until the stop function is called
			return;
		}
		self::realReset();
	}

	private static function realReset() {
		self::$times = [];
		self::$runTime = 0;
		self::$compile = 0;
		if ( self::$reset ) {
			self::$reset = false;
		}
	}

}
