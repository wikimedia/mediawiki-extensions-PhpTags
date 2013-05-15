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

	/**
	 * Render function used in hook ParserFirstCallInit
	 *
	 * @param Parser $parser
	 * @return string
	 */
	public static function renderParserFunction(Parser &$parser) {
		$params = func_get_args();
		array_shift( $params );

		if( count($params) < 2 ) {
			return '<span class="error">' . wfMessage( 'foxway-not-enough-parameters' )->escaped() . '</span>';
		}

		$action = strtolower( $params[0] );
		switch ($action) {
			case 'set':
				$matches = array();
				if( preg_match('/^\s*([^=]+)\s*=\s*(.+)\s*$/si', $params[1], &$matches) ) {
				$propertyName = $matches[1];
				$propertyValue = $matches[2];
				return \Foxway\ORM::SetProperty($propertyName, $propertyValue);
				break;
			}
				break;
			default:
				return '<span class="error">' . wfMessage( 'foxway-unknown-action', $action )->escaped() . '</span>';
				break;
		}
	}

	public static function render($input, array $args, Parser $parser, PPFrame $frame) {
		$is_debug = isset($args['debug']);
		$return = '';

		$result = Foxway\Interpreter::run( $input, $frame->getArguments(), array(), $is_debug );

		foreach ($result as &$value) {
			if( $value instanceof Foxway\iRawOutput ) {
				$value = (string)$value;
			}
		}

		if( $is_debug ) {
			$parser->getOutput()->addModules('ext.Foxway.Debug');
			$return .= self::insertNoWiki( $parser, array_shift($result) ) . "\n";
		}

		return $return . self::insertGeneral( $parser, $parser->recursiveTagParse(implode('', $result),$frame) );
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
}