<?php
namespace Foxway;

define( 'FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED', 100 ); // PHP Parse error:  syntax error, unexpected $1
define( 'FOXWAY_PHP_FATAL_ERROR_UNSUPPORTED_OPERAND_TYPES', 101 );
define( 'FOXWAY_PHP_WARNING_DIVISION_BY_ZERO', 102 ); // PHP Warning:  Division by zero
define( 'FOXWAY_PHP_WARNING_EXPECTS_N_PARAMETER_N_GIVEN', 103 );
define( 'FOXWAY_PHP_FATAL_CALL_TO_UNDEFINED_FUNCTION', 104 ); // PHP Fatal error:  Call to undefined function $1()
define( 'FOXWAY_PHP_FATAL_UNABLE_CALL_TO_FUNCTION', 105 ); // $foxwayFunctions[$1][$2] is not callable
define( 'FOXWAY_PHP_FATAL_ERROR_CALL_TO_FUNCTION', 106 ); // Error in $foxwayFunctions[$1]
define( 'FOXWAY_PHP_WARNING_WRONG_PARAMETER_COUNT', 107 ); // PHP Warning:  Wrong parameter count for $1()
define( 'FOXWAY_PHP_FATAL_VALUE_PASSED_BY_REFERENCE', 108 ); // PHP Fatal error:  Only variables can be passed by reference
define( 'FOXWAY_PHP_WARNING_WRONG_DELIMITER', 109 ); // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash
define( 'FOXWAY_PHP_WARNING_NO_ENDING_DELIMITER', 110 ); // PHP Warning:  preg_replace(): No ending delimiter '/' found
define( 'FOXWAY_PHP_WARNING_UNKNOWN_MODIFIER', 111 ); // PHP Warning:  preg_replace(): Unknown modifier 'z'
define( 'FOXWAY_PHP_NOTICE_UNDEFINED_VARIABLE', 112 ); // PHP Notice:  Undefined variable: $1
define( 'FOXWAY_PHP_NOTICE_UNINIT_STRING_OFFSET', 113 ); // PHP Notice:  Uninitialized string offset: $1
define( 'FOXWAY_PHP_NOTICE_UNDEFINED_CONSTANT', 114 ); // PHP Notice:  Use of undefined constant $1 - assumed '$1'
define( 'FOXWAY_PHP_FATAL_CANNOT_UNSET_STRING_OFFSETS', 115 ); // PHP Fatal error:  Cannot unset string offsets
define( 'FOXWAY_PHP_WARNING_INVALID_ARGUMENT_FOR_FOREACH', 116 ); // PHP Warning:  Invalid argument supplied for foreach()
// PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 73 bytes)
// PHP Fatal error:  Maximum execution time of 30 seconds exceeded
// PHP Warning:  Variable passed to each() is not an array or object


/**
 * Error Exception class of Foxway extension.
 *
 * @file ExceptionFoxway.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class ExceptionFoxway extends \Exception {
	public $params;
	public $tokenLine;
	public $place;

	function __construct( $params = null, $code = 0, $tokenLine = 0, $place = '' ) {
		parent::__construct('', $code);
		$this->params = $params;
		$this->tokenLine = $tokenLine;
		$this->place = $place != '' ? : 'Command line code';
	}

	function __toString() {
		$params = $this->params;
		$line = $this->tokenLine;
		$place = $this->place;

		switch ( $this->code ) {
			case FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED:
				$message = 'HP Parse error:  syntax error, unexpected ' . ( is_string($params) ? $params : token_name($params) );
				break;
			case FOXWAY_PHP_WARNING_DIVISION_BY_ZERO:
				$message = "PHP Warning:  Division by zero";
				break;
			case FOXWAY_PHP_FATAL_CALL_TO_UNDEFINED_FUNCTION:
				$message = "PHP Fatal error:  Call to undefined function $params()";
				break;
			case FOXWAY_PHP_FATAL_UNABLE_CALL_TO_FUNCTION:
				$message = "PHP Fatal error:  \$foxwayFunctions[{$params[0]}][{$params[1]}] is not callable";
				break;
			case FOXWAY_PHP_FATAL_ERROR_CALL_TO_FUNCTION:
				$message = "PHP Fatal error:  Error at \$foxwayFunctions[$params]";
				break;
			case FOXWAY_PHP_WARNING_WRONG_PARAMETER_COUNT:
				$message = "PHP Warning:  Wrong parameter count for $params()";
				break;
			case FOXWAY_PHP_NOTICE_UNDEFINED_VARIABLE:
				$message = "PHP Notice:  Undefined variable: $params";
				break;
			case FOXWAY_PHP_NOTICE_UNINIT_STRING_OFFSET:
				$message = "PHP Notice:  Uninitialized string offset: $params";
				break;
			case FOXWAY_PHP_NOTICE_UNDEFINED_CONSTANT:
				$message = "PHP Notice:  Use of undefined constant $params - assumed '$params'";
				break;
			case FOXWAY_PHP_FATAL_CANNOT_UNSET_STRING_OFFSETS:
				$message = 'PHP Fatal error:  Cannot unset string offsets';
				break;
			case FOXWAY_PHP_WARNING_INVALID_ARGUMENT_FOR_FOREACH:
				$message = 'PHP Warning:  Invalid argument supplied for foreach()';
				break;
			default:
				$message = "PHP Fatal error:  Undefined error, code {$this->code}";
				break;
		}
		//return "$message in $place on line $line\n";
		return \Html::element( 'span', array('class'=>'error'), "$message in $place on line $line" );
	}
}

