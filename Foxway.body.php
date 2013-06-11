<?php
/**
 * Main class of Foxway extension.
 *
 * @file Foxway.body.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Foxway {

	static $frames=array();

	public static function render($input, array $args, Parser $parser, PPFrame $frame) {
		global $wgNamespacesWithFoxway;
		if( $wgNamespacesWithFoxway !== true && empty($wgNamespacesWithFoxway[$frame->getTitle()->getNamespace()]) ) {
			return Html::element( 'span', array('class'=>'error'), wfMessage('foxway-disabled-for-namespace', $frame->getTitle()->getNsText())->escaped() );
		}

		$is_debug = isset($args['debug']);
		$return = '';

		$result = Foxway\Interpreter::run(
				$input,
				array_merge((array)$frame->getTitle()->getPrefixedText(),$frame->getArguments()),
				self::getScope($frame),
				$is_debug
			);

		foreach ($result as &$value) {
			if( $value instanceof Foxway\iRawOutput ) {
				$value = (string)$value;
			}
		}

		if( $is_debug ) {
			$parser->getOutput()->addModules('ext.Foxway.Debug');
			$return .= self::insertNoWiki( $parser, array_shift($result) ) . "\n";
		}

		return $return . self::insertGeneral( $parser, $parser->recursiveTagParse(implode($result),$frame) );
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