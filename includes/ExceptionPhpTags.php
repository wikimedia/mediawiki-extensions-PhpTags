<?php
namespace PhpTags;

define( 'PHPTAGS_EXCEPTION_SYNTAX_ERROR_UNEXPECTED', 1001 ); // PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE', 1002 ); // PHP Notice:  Undefined variable: $1 in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_FATAL_CANNOT_USE_FOR_READING', 1003 ); // PHP Fatal error:  Cannot use [] for reading in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO', 1004 ); // PHP Warning:  Division by zero
define( 'PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET', 1005 ); // PHP Notice:  Uninitialized string offset: $1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET', 1006 ); // PHP Notice:  Undefined offset: 4 in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY', 1007 ); // PHP Fatal error:  Cannot use string offset as an array
define( 'PHPTAGS_EXCEPTION_WARNING_SCALAR_VALUE_AS_ARRAY', 1008 ); // PHP Warning:  Cannot use a scalar value as an array

// pcre
define( 'PHPTAGS_EXCEPTION_WARNING_WRONG_DELIMITER', 2009 ); // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash
define( 'PHPTAGS_EXCEPTION_WARNING_NO_ENDING_DELIMITER', 2010 ); // PHP Warning:  preg_replace(): No ending delimiter '/' found
define( 'PHPTAGS_EXCEPTION_WARNING_UNKNOWN_MODIFIER', 111 ); // PHP Warning:  preg_replace(): Unknown modifier 'z'

define( 'PHPTAGS_SYNTAX_ERROR_UNEXPECTED', 100 ); // PHP Parse error:  syntax error, unexpected $1
define( 'PHPTAGS_FATAL_ERROR_UNSUPPORTED_OPERAND_TYPES', 101 );
define( 'PHPTAGS_WARNING_EXPECTS_N_PARAMETER_N_GIVEN', 103 );
define( 'PHPTAGS_FATAL_CALL_TO_UNDEFINED_FUNCTION', 104 ); // PHP Fatal error:  Call to undefined function $1()
define( 'PHPTAGS_FATAL_UNABLE_CALL_TO_FUNCTION', 105 ); // $foxwayFunctions[$1][$2] is not callable
define( 'PHPTAGS_FATAL_ERROR_CALL_TO_FUNCTION', 106 ); // Error in $foxwayFunctions[$1]
define( 'PHPTAGS_WARNING_WRONG_PARAMETER_COUNT', 107 ); // PHP Warning:  Wrong parameter count for $1()
define( 'PHPTAGS_FATAL_VALUE_PASSED_BY_REFERENCE', 108 ); // PHP Fatal error:  Only variables can be passed by reference
define( 'PHPTAGS_NOTICE_UNDEFINED_CONSTANT', 114 ); // PHP Notice:  Use of undefined constant $1 - assumed '$1'
define( 'PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS', 115 ); // PHP Fatal error:  Cannot unset string offsets
define( 'PHPTAGS_WARNING_INVALID_ARGUMENT_FOR_FOREACH', 116 ); // PHP Warning:  Invalid argument supplied for foreach()
// PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 73 bytes)
// PHP Fatal error:  Maximum execution time of 30 seconds exceeded
// PHP Warning:  Variable passed to each() is not an array or object
// PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1



/**
 * The error exception class of the extension PHP Tags.
 *
 * @file ExceptionPhpTags.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class ExceptionPhpTags extends \Exception {
	public $params;
	public $tokenLine;
	public $place;

	function __construct( $code = 0, $params = null, $tokenLine = 0, $place = '' ) {
		parent::__construct('', $code);
		$this->params = $params;
		$this->tokenLine = $tokenLine;
		$this->place = $place != '' ? $place : 'Command line code';
	}

	function __toString() {
		$params = $this->params;
		$line = $this->tokenLine;
		$place = $this->place;

		switch ( $this->code ) {
			case PHPTAGS_EXCEPTION_SYNTAX_ERROR_UNEXPECTED:
				$message = 'HP Parse error:  syntax error, unexpected \'' . ( is_string($params[0]) ? $params[0] : token_name($params[0]) ) . '\'';
				array_shift( $params );
				if ( $params ) {
					$message .= ", expecting '" . implode( "', '", $params ) . "'";
				}
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE:
				$message = "PHP Notice:  Undefined variable: $params";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CANNOT_USE_FOR_READING:
				$message = 'PHP Fatal error:  Cannot use [] for reading';
				break;
			case PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO:
				$message = "PHP Warning:  Division by zero";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET:
				$message = "PHP Notice:  Uninitialized string offset: $params";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET:
				$message = "PHP Notice:  Undefined offset: $params";
				break;
			case PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY:
				$message = "PHP Fatal error:  Cannot use string offset as an array";
				break;
			case PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY:
				$message = "PHP Warning:  Cannot use a scalar value as an array";
				break;
			
			// pcre
			case PHPTAGS_EXCEPTION_WARNING_WRONG_DELIMITER:
				$message = "PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash";
				break;
			case PHPTAGS_EXCEPTION_WARNING_NO_ENDING_DELIMITER:
				$message = "PHP Warning:  preg_replace(): No ending delimiter '{$params[0]}' found";
				break;
			case PHPTAGS_EXCEPTION_WARNING_UNKNOWN_MODIFIER:
				$message = "PHP Warning:  preg_replace(): Unknown modifier '{$params[0]}'";
				break;


			case PHPTAGS_FATAL_CALL_TO_UNDEFINED_FUNCTION:
				$message = "PHP Fatal error:  Call to undefined function $params()";
				break;
			case PHPTAGS_FATAL_UNABLE_CALL_TO_FUNCTION:
				$message = "PHP Fatal error:  \$foxwayFunctions[{$params[0]}][{$params[1]}] is not callable";
				break;
			case PHPTAGS_FATAL_ERROR_CALL_TO_FUNCTION:
				$message = "PHP Fatal error:  Error at \$foxwayFunctions[$params]";
				break;
			case PHPTAGS_WARNING_WRONG_PARAMETER_COUNT:
				$message = "PHP Warning:  Wrong parameter count for $params()";
				break;
			case PHPTAGS_NOTICE_UNDEFINED_CONSTANT:
				$message = "PHP Notice:  Use of undefined constant $params - assumed '$params'";
				break;
			case PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS:
				$message = 'PHP Fatal error:  Cannot unset string offsets';
				break;
			case PHPTAGS_WARNING_INVALID_ARGUMENT_FOR_FOREACH:
				$message = 'PHP Warning:  Invalid argument supplied for foreach()';
				break;
			default:
				$message = "PHP Fatal error:  Undefined error, code {$this->code}";
				break;
		}
		//return "$message in $place on line $line\n";
		return \Html::element( 'span', array('class'=>'error'), "$message in $place on line $line" ) . '<br />';
	}
}

