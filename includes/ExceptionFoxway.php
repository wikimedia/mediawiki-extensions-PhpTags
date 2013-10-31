<?php
namespace Foxway;

define( 'FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED', 100 ); // PHP Parse error:  syntax error, unexpected $1
define( 'FOXWAY_PHP_FATAL_ERROR_UNSUPPORTED_OPERAND_TYPES', 101 );
define( 'FOXWAY_PHP_WARNING_DIVISION_BY_ZERO', 102 ); // PHP Warning:  Division by zero
define( 'FOXWAY_PHP_WARNING_EXPECTS_N_PARAMETER_N_GIVEN', 103 );
define( 'FOXWAY_PHP_FATAL_CALL_TO_UNDEFINED_FUNCTION', 104 ); // PHP Fatal error:  Call to undefined function $1()
define( 'FOXWAY_PHP_FATAL_UNABLE_CALL_TO_FUNCTION', 105 ); // $foxwayFunctions[$1] is not callable
define( 'FOXWAY_PHP_FATAL_ERROR_CALL_TO_FUNCTION', 106 ); // Error in $foxwayFunctions[$1]
define( 'FOXWAY_PHP_WARNING_WRONG_PARAMETER_COUNT', 107 ); // PHP Warning:  Wrong parameter count for $1()
define( 'FOXWAY_PHP_FATAL_VALUE_PASSED_BY_REFERENCE', 108 ); // PHP Fatal error:  Only variables can be passed by reference
define( 'FOXWAY_PHP_WARNING_WRONG_DELIMITER', 109 ); // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash
define( 'FOXWAY_PHP_WARNING_NO_ENDING_DELIMITER', 110 ); // PHP Warning:  preg_replace(): No ending delimiter '/' found
define( 'FOXWAY_PHP_WARNING_UNKNOWN_MODIFIER', 111 ); // PHP Warning:  preg_replace(): Unknown modifier 'z'

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
