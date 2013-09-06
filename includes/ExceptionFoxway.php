<?php
namespace Foxway;

define( 'FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED', 100 );
define( 'FOXWAY_PHP_FATAL_ERROR_UNSUPPORTED_OPERAND_TYPES', 101 );

/**
 * Error Exception class of Foxway extension.
 *
 * @file ExceptionFoxway.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class ExceptionFoxway extends \Exception {
	protected $params;
	protected $tokenLine;

	function __construct( $params = null, $code = 0, $tokenLine = 0 ) {
		parent::__construct('', $code);
		$this->params = $params;
		$this->tokenLine = $tokenLine;
	}

}
