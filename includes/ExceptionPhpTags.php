<?php
namespace PhpTags;

define( 'PHPTAGS_EXCEPTION_FROM_HOOK', 1001 ); // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash

define( 'PHPTAGS_EXCEPTION_NOTICE', 2 );
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE', 2001 ); // PHP Notice:  Undefined variable: $1 in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET', 2002 ); // PHP Notice:  Uninitialized string offset: $1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET', 2003 ); // PHP Notice:  Undefined offset: 4 in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_INDEX', 2004 ); // PHP Notice:  Undefined index: ddd in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CONSTANT', 2005 ); // PHP Notice:  Use of undefined constant $1 - assumed '$1'
define( 'PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED', 2006 ); // PHP Notice:  Object of class Exception could not be converted to int

define( 'PHPTAGS_EXCEPTION_WARNING', 3 );
define( 'PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO', 3001 ); // PHP Warning:  Division by zero
define( 'PHPTAGS_EXCEPTION_WARNING_SCALAR_VALUE_AS_ARRAY', 3002 ); // PHP Warning:  Cannot use a scalar value as an array
define( 'PHPTAGS_EXCEPTION_WARNING_INVALID_ARGUMENT_FOR_FOREACH', 3003 ); // PHP Warning:  Invalid argument supplied for foreach()
define( 'PHPTAGS_EXCEPTION_WARNING_RETURNED_INVALID_VALUE', 3004 );
define( 'PHPTAGS_EXCEPTION_WARNING_INVALID_HOOK', 3005 );
define( 'PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER', 3006 ); // PHP Warning:  func() expects parameter 1 to be array, integer given
define( 'PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT', 3007 ); // PHP Warning:  Wrong parameter count for $1()

define( 'PHPTAGS_EXCEPTION_FATAL', 4 );
define( 'PHPTAGS_EXCEPTION_FATAL_CANNOT_USE_FOR_READING', 4001 ); // PHP Fatal error:  Cannot use [] for reading in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY', 4002 ); // PHP Fatal error:  Cannot use string offset as an array
define( 'PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE', 4003 ); // PHP Fatal error:  Only variables can be passed by reference
define( 'PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_FUNCTION', 4004 ); // PHP Fatal error:  Call to undefined function $1()
define( 'PHPTAGS_EXCEPTION_FATAL_NONEXISTENT_HOOK_CLASS', 4005 );
define( 'PHPTAGS_EXCEPTION_FATAL_INVALID_HOOK_CLASS', 4006 );
define( 'PHPTAGS_EXCEPTION_FATAL_LOOPS_LIMIT_REACHED', 4007 );

define( 'PHPTAGS_EXCEPTION_PARSE', 5 );
define( 'PHPTAGS_EXCEPTION_SYNTAX_ERROR_UNEXPECTED', 5001 ); // PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1

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
define( 'PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS', 115 ); // PHP Fatal error:  Cannot unset string offsets

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
				$message = 'syntax error, unexpected \'' . ( is_string($params[0]) ? $params[0] : token_name($params[0]) ) . '\'';
				array_shift( $params );
				if ( $params ) {
					$message .= ", expecting " . implode( ", ", $params );
				}
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE:
				$message = "Undefined variable: $params";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CANNOT_USE_FOR_READING:
				$message = 'Cannot use [] for reading';
				break;
			case PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO:
				$message = "Division by zero";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET:
				$message = "Uninitialized string offset: $params";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET:
				$message = "Undefined offset: $params";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_INDEX:
				$message = "Undefined index: $params";
				break;
			case PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY:
				$message = "Cannot use string offset as an array";
				break;
			case PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY:
				$message = "Cannot use a scalar value as an array";
				break;
			case PHPTAGS_EXCEPTION_WARNING_INVALID_ARGUMENT_FOR_FOREACH:
				$message = 'Invalid argument supplied for foreach()';
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CONSTANT:
				$message = "Use of undefined constant $params - assumed '$params'";
				break;
			case PHPTAGS_EXCEPTION_WARNING_RETURNED_INVALID_VALUE:
				// @todo
				$message = "constant, function or object '$params' returns an invalid value";
				break;
			case PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE:
				$message = "Only variables can be passed by reference";
				break;
			case PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER:
				$message = "{$params[0]} expects parameter {$params[1]} to be {$params[2]}, {$params[3]} given";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED:
				$message = "Object of class {$params[0]} could not be converted to {$params[1]}";
				break;
			case PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT:
				$message = "Wrong parameter count for $params()";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_FUNCTION:
				$message = "Call to undefined function $params()";
				break;
			case PHPTAGS_EXCEPTION_FATAL_NONEXISTENT_HOOK_CLASS:
				$message = "For the function {$params[0]} was registered nonexistent hook class {$params[1]}";
				break;
			case PHPTAGS_EXCEPTION_FATAL_INVALID_HOOK_CLASS:
				$message = "For the function {$params[0]} was registered invalid hook class {$params[1]}";
				break;
			case PHPTAGS_EXCEPTION_WARNING_INVALID_HOOK:
				$message = "The hook '{$params[0]}' was registered for the class '{$params[1]}', but one does not contain information about how to handle it";
				break;
			case PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS:
				$message = 'Cannot unset string offsets';
				break;
			case PHPTAGS_EXCEPTION_FROM_HOOK:
				$message = $params[0];
				$this->code = $params[1] * 1000;
				break;
			case PHPTAGS_EXCEPTION_FATAL_LOOPS_LIMIT_REACHED:
				$message = 'Maximum number of allowed loops reached';
				break;
			default:
				$message = "Undefined error, code {$this->code}";
				$this->code = PHPTAGS_EXCEPTION_FATAL * 1000;
				break;
		}

		switch ( intval($this->code / 1000) ) {
			case PHPTAGS_EXCEPTION_NOTICE:
				$messageType = 'Notice';
				break;
			case PHPTAGS_EXCEPTION_WARNING:
				$messageType = 'Warning';
				break;
			case PHPTAGS_EXCEPTION_FATAL:
				$messageType = 'Fatal error';
				break;
			case PHPTAGS_EXCEPTION_PARSE:
				$messageType = 'Parse error';
				break;
			default:
				$messageType = 'Undefined error';
				break;
		}
		//return "$message in $place on line $line\n";
		return \Html::element( 'span', array('class'=>'error'), "PhpTags $messageType:  $message in $place on line $line" ) . '<br />';
	}
}

