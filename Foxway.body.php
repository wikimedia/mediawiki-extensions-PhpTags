<?php

require_once __DIR__ . '/Settings.php';

/**
 * Main class of Foxway extension.
 *
 * @file Foxway.body.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Foxway {

	static $DebugLoops = false;
	static $startTime = false;

	static $frames=array();

	/**
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 */
	public static function renderFunction( $parser, $frame, $args ) {
		self::$startTime = microtime(true);

		$is_banned = self::isBanned($frame);
		if( $is_banned ) {
			return $is_banned;
		}

		$command = array_shift($args);
		if( count($args) > 0 ) {
			foreach ($args as &$value) {
				$value = $frame->expand( $value );
			}
			$command = "echo $command (" . implode(',', $args) . ');';
		}
		/*$result = Foxway\Interpreter::run(
				$command,
				array($frame->getTitle()->getPrefixedText()),
				self::getScope($frame)
				);*/
		try {
			$result = Foxway\Runtime::runSource(
			   $command,
			   array($frame->getTitle()->getPrefixedText()),
			   self::getScope($frame)
			   );
			$return = implode($result);
		} catch (Exception $exc) {
			$return = $exc->getTraceAsString();
		}

		\Foxway\Runtime::$time += microtime(true) - self::$startTime;
		return \UtfNormal::cleanUp($return);
	}

	public static function render($input, array $args, Parser $parser, PPFrame $frame) {
		self::$startTime = microtime(true);

		$is_banned = self::isBanned($frame);
		if( $is_banned ) {
			return $is_banned;
		}

		$is_debug = isset($args['debug']);
		$return = false;

		/*
		$result = Foxway\Interpreter::run(
				$input,
				array_merge((array)$frame->getTitle()->getPrefixedText(),$frame->getArguments()),
				self::getScope($frame),
				$is_debug
			);*/

		try {
			$result = Foxway\Runtime::runSource(
					$input,
					array_merge((array)$frame->getTitle()->getPrefixedText(),$frame->getArguments()),
					self::getScope($frame)
					);
		} catch (Exception $exc) {
			\Foxway\Runtime::$time += microtime(true) - self::$startTime;
			return $exc->getTraceAsString();
		}

		if( $is_debug ) {
			$parser->getOutput()->addModules('ext.Foxway.Debug');
			if( self::$DebugLoops ) {
				$parser->getOutput()->addModules('ext.Foxway.DebugLoops');
			}
			$return .= self::insertNoWiki( $parser, array_shift($result) ) . "\n";
		}

		if( count($result) > 0 ) {
			//$return .= Sanitizer::removeHTMLtags(implode($result));
			$return .= self::insertGeneral( $parser, $parser->recursiveTagParse(implode($result),$frame) );
		}

		\Foxway\Runtime::$time += microtime(true) - self::$startTime;
		return \UtfNormal::cleanUp($return);
	}

	public static function isBanned(PPFrame $frame) {
		if( \Foxway\Runtime::$allowedNamespaces !== true && empty(\Foxway\Runtime::$allowedNamespaces[$frame->getTitle()->getNamespace()]) ) {
			return Html::element( 'span', array('class'=>'error'), wfMessage('foxway-disabled-for-namespace', $frame->getTitle()->getNsText())->escaped() );
		}
		if(\Foxway\Runtime::$permittedTime !== true && \Foxway\Runtime::$time >= \Foxway\Runtime::$permittedTime ) {
			return Html::element( 'span', array('class'=>'error'),
				wfMessage( 'foxway-php-fatal-error-max-execution-time' )
					->numParams( \Foxway\Runtime::$permittedTime )
					->params( $frame->getTitle()->getPrefixedText() )
					->text()
			);
		}
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
