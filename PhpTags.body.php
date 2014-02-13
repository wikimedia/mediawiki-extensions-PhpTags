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

	private static $frames=array();
	private static $needInitRuntime = true;

	/**
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 */
	public static function renderFunction( $parser, $frame, $args ) {
		global $wgPhpTagsTime, $wgPhpTagsMaxLoops;
		$time = microtime(true);

		$is_banned = self::isBanned($frame);
		if ( $is_banned ) {
			return $is_banned;
		}

		if ( self::$needInitRuntime ) {
			\wfRunHooks( 'PhpTagsRuntimeFirstInit' );
			\PhpTags\Runtime::$loopsLimit = $wgPhpTagsMaxLoops;
			self::$needInitRuntime = false;
		}

		$command = array_shift($args);
		if( count($args) > 0 ) {
			foreach ($args as &$value) {
				$value = $frame->expand( $value );
			}
			$command = "echo $command (" . implode(',', $args) . ');';
		}

		try {
			$titleText = $frame->getTitle()->getPrefixedText();
			$compiler = new PhpTags\Compiler();
			$bytecode = $compiler->compile($command, $titleText);

			self::$compileTime += microtime(true) - $time;

			$result = \PhpTags\Runtime::run(
					$bytecode,
					array_merge( (array)$titleText, $frame->getArguments() ),
					self::getScope( $frame ),
					array( PHPTAGS_TRANSIT_PARSER=>&$parser, PHPTAGS_TRANSIT_PPFRAME=>&$frame )
					);
			$return = implode( $result );
		} catch (\PhpTags\ExceptionPhpTags $exc) {
			$return = (string) $exc;
		} catch (Exception $exc) {
			$return = $exc->getTraceAsString();
		}

		$wgPhpTagsTime += microtime(true) - $time;
		return \UtfNormal::cleanUp($return);
	}

	public static function render($input, array $args, Parser $parser, PPFrame $frame) {
		global $wgPhpTagsTime, $wgPhpTagsMaxLoops;
		$time = microtime(true);

		$is_banned = self::isBanned($frame);
		if ( $is_banned ) {
			return $is_banned;
		}

		if ( self::$needInitRuntime ) {
			\wfRunHooks( 'PhpTagsRuntimeFirstInit' );
			\PhpTags\Runtime::$loopsLimit = $wgPhpTagsMaxLoops;
			self::$needInitRuntime = false;
		}

		$is_debug = isset($args['debug']);
		$return = false;

		try {
			$titleText = $frame->getTitle()->getPrefixedText();
			$compiler = new PhpTags\Compiler();
			$bytecode = $compiler->compile($input, $titleText);

			self::$compileTime += microtime(true) - $time;

			$result = \PhpTags\Runtime::run(
					$bytecode,
					array_merge( (array)$titleText, $frame->getArguments() ),
					self::getScope( $frame ),
					array( PHPTAGS_TRANSIT_PARSER=>&$parser, PHPTAGS_TRANSIT_PPFRAME=>&$frame )
					);
		} catch ( \PhpTags\ExceptionPhpTags $exc ) {
			$wgPhpTagsTime += microtime(true) - $time;
			return (string) $exc;
		} catch ( Exception $exc ) {
			$wgPhpTagsTime += microtime(true) - $time;
			return $exc->getTraceAsString();
		}

		if( $is_debug ) {
			$parser->getOutput()->addModules('ext.php.Debug');
			if( self::$DebugLoops ) {
				$parser->getOutput()->addModules('ext.php.DebugLoops');
			}
			$return .= self::insertNoWiki( $parser, array_shift($result) ) . "\n";
		}

		if( count($result) > 0 ) {
			//$return .= Sanitizer::removeHTMLtags(implode($result));
			$return .= self::insertGeneral( $parser, $parser->recursiveTagParse(implode($result),$frame) );
		}

		$wgPhpTagsTime += microtime(true) - $time;
		return \UtfNormal::cleanUp($return);
	}

	public static function isBanned(PPFrame $frame) {
		global $wgPhpTagsNamespaces;
		if ( $wgPhpTagsNamespaces !== true && !in_array($frame->getTitle()->getNamespace(), $wgPhpTagsNamespaces) ) {
			return Html::element(
					'span', array( 'class'=>'error' ),
					wfMessage( 'phptags-disabled-for-namespace', $frame->getTitle()->getNsText() )->text()
				);
		}
//		if ( $wgPhpTagsPermittedTime !== true && self::$time >= \PhpTags\Runtime::$permittedTime ) {
//			return Html::element( 'span', array('class'=>'error'),
//				wfMessage( 'phptags-fatal-error-max-execution-time' )
//					->numParams( \PhpTags\Runtime::$permittedTime )
//					->params( $frame->getTitle()->getPrefixedText() )
//					->text()
//			);
//		}
		return false;
	}

	/**
	 *
	 * @param Parser $parser
	 * @param string $text
	 * @return string
	 */
	private static function insertGeneral(Parser &$parser, &$text) {
		return $parser->insertStripItem( $text );
	}

	/**
	 * @see Parser::insertStripItem()
	 * @param Parser $parser
	 * @param string $text
	 * @return string
	 */
	private static function insertNoWiki(Parser &$parser, &$text) {
		// @see Parser::insertStripItem()
		$rnd = "{$parser->mUniqPrefix}-item-{$parser->mMarkerIndex}-" . Parser::MARKER_SUFFIX;
		$parser->mMarkerIndex++;
		$parser->mStripState->addNoWiki( $rnd, $text );
		return $rnd;
	}

	private static function getScope(PPFrame $frame) {
		foreach (self::$frames as &$value) {
			if( $value[0] === $frame ) {
				return $value[1];
			}
		}
		$scope=count(self::$frames);
		self::$frames[] = array($frame, $scope);
		return $scope;
	}

}
