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
		return Foxway\Interpreter::run($input, true);
	}
}