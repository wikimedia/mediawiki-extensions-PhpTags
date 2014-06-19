<?php
namespace PhpTags;

define( 'PHPTAGS_EXCEPTION_CHILDRENS_CLASSES', 'C' );

define( 'PHPTAGS_EXCEPTION_FROM_HOOK', 1001 ); // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash

define( 'PHPTAGS_EXCEPTION_NOTICE', 2 );
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE', 2001 ); // PHP Notice:  Undefined variable: $1 in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET', 2002 ); // PHP Notice:  Uninitialized string offset: $1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET', 2003 ); // PHP Notice:  Undefined offset: 4 in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_INDEX', 2004 ); // PHP Notice:  Undefined index: ddd in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CONSTANT', 2005 ); // PHP Notice:  Use of undefined constant $1 - assumed '$1'
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_PROPERTY', 2006 ); // PHP Notice:  Undefined property: DateInterval::$rsss
define( 'PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CLASS_CONSTANT', 2007 ); // PHP Fatal error:  Undefined class constant 'EXCLUDE_START_DATEqqqq'
define( 'PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED', 2008 ); // PHP Notice:  Object of class Exception could not be converted to int

define( 'PHPTAGS_EXCEPTION_WARNING', 3 );
define( 'PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO', 3001 ); // PHP Warning:  Division by zero
define( 'PHPTAGS_EXCEPTION_WARNING_SCALAR_VALUE_AS_ARRAY', 3002 ); // PHP Warning:  Cannot use a scalar value as an array
define( 'PHPTAGS_EXCEPTION_WARNING_INVALID_ARGUMENT_FOR_FOREACH', 3003 ); // PHP Warning:  Invalid argument supplied for foreach()
define( 'PHPTAGS_EXCEPTION_WARNING_RETURNED_INVALID_VALUE', 3004 );
define( 'PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER', 3006 ); // PHP Warning:  func() expects parameter 1 to be array, integer given
define( 'PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT', 3007 ); // PHP Warning:  Wrong parameter count for $1()
define( 'PHPTAGS_EXCEPTION_WARNING_EXPECTS_EXACTLY_PARAMETERS', 3008 ); // PHP Warning:  date_format() expects exactly 2 parameters, 3 given
define( 'PHPTAGS_EXCEPTION_WARNING_EXPECTS_EXACTLY_PARAMETER', 3009 ); // PHP Warning:  date_format() expects exactly 1 parameter, 3 given
define( 'PHPTAGS_EXCEPTION_WARNING_CALLFUNCTION_INVALID_HOOK', 3100 );

define( 'PHPTAGS_EXCEPTION_FATAL', 4 );
define( 'PHPTAGS_EXCEPTION_FATAL_CANNOT_USE_FOR_READING', 4001 ); // PHP Fatal error:  Cannot use [] for reading in Command line code on line 1
define( 'PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY', 4002 ); // PHP Fatal error:  Cannot use string offset as an array
define( 'PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE', 4003 ); // PHP Fatal error:  Only variables can be passed by reference
define( 'PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_FUNCTION', 4004 ); // PHP Fatal error:  Call to undefined function $1()
define( 'PHPTAGS_EXCEPTION_FATAL_NONEXISTENT_HOOK_CLASS', 4005 );
define( 'PHPTAGS_EXCEPTION_FATAL_INVALID_HOOK_CLASS', 4006 );
define( 'PHPTAGS_EXCEPTION_FATAL_LOOPS_LIMIT_REACHED', 4007 );
define( 'PHPTAGS_EXCEPTION_FATAL_CLASS_NOT_FOUND', 4008 ); // PHP Fatal error:  Class '$1' not found
define( 'PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_METHOD', 4009 ); // PHP Fatal error:  Call to undefined method $1::$2()
define( 'PHPTAGS_EXCEPTION_FATAL_OBJECT_NOT_CREATED', 4010 );
define( 'PHPTAGS_EXCEPTION_FATAL_BAD_CLASS_NAME', 4011 ); // PHP Fatal error:  Class name must be a valid object or a string
define( 'PHPTAGS_EXCEPTION_FATAL_MUST_EXTENDS_GENERIC', 4012 );
define( 'PHPTAGS_EXCEPTION_FATAL_CREATEOBJECT_INVALID_CLASS', 4013 );
define( 'PHPTAGS_EXCEPTION_FATAL_NONSTATIC_CALLED_STATICALLY', 4014 ); // PHP Fatal error:  Non-static method DateTime::format() cannot be called statically

define( 'PHPTAGS_EXCEPTION_CATCHABLE_FATAL', 5 );
define( 'PHPTAGS_EXCEPTION_FATAL_OBJECT_COULD_NOT_BE_CONVERTED', 6001 ); //PHP Catchable fatal error:  Object of class stdClass could not be converted to string

define( 'PHPTAGS_EXCEPTION_PARSE', 6 );
define( 'PHPTAGS_EXCEPTION_SYNTAX_ERROR_UNEXPECTED', 6001 ); // PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1

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
class PhpTagsException extends \Exception {
	public $params;
	public $tokenLine;
	public $place;

	function __construct( $code = 0, $arguments = null, $tokenLine = 0, $place = '' ) {
		parent::__construct('', $code);
		$this->params = $arguments;
		$this->tokenLine = $tokenLine;
		$this->place = $place != '' ? $place : 'Command line code';
	}

	function __toString() {
		$arguments = $this->params;
		$line = $this->tokenLine;
		$place = $this->place;

		switch ( $this->code ) {
			case PHPTAGS_EXCEPTION_SYNTAX_ERROR_UNEXPECTED:
				$message = 'syntax error, unexpected \'' . ( is_string($arguments[0]) ? $arguments[0] : token_name($arguments[0]) ) . '\'';
				array_shift( $arguments );
				if ( $arguments ) {
					$message .= ", expecting " . implode( ", ", $arguments );
				}
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE:
				$message = "Undefined variable: $arguments";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CANNOT_USE_FOR_READING:
				$message = 'Cannot use [] for reading';
				break;
			case PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO:
				$message = "Division by zero";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET:
				$message = "Uninitialized string offset: $arguments";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET:
				$message = "Undefined offset: $arguments";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_INDEX:
				$message = "Undefined index: $arguments";
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
				$message = "Use of undefined constant $arguments - assumed '$arguments'";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_PROPERTY:
				$message = "Undefined property: {$arguments[0]}::\${$arguments[1]}";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CLASS_CONSTANT:
				$message = "Undefined class constant: {$arguments[0]}::{$arguments[1]}";
				break;
			case PHPTAGS_EXCEPTION_WARNING_RETURNED_INVALID_VALUE:
				// @todo
				$message = "constant, function or object '$arguments' returns invalid value";
				break;
			case PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE:
				$message = "Only variables can be passed by reference";
				break;
			case PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER:
				$message = "{$arguments[0]}() expects parameter {$arguments[1]} to be {$arguments[2]}, {$arguments[3]} given";
				break;
			case PHPTAGS_EXCEPTION_WARNING_EXPECTS_EXACTLY_PARAMETERS:
				$message = "{$arguments[0]}() expects exactly {$arguments[1]} parameters, {$arguments[2]} given";
				break;
			case PHPTAGS_EXCEPTION_WARNING_EXPECTS_EXACTLY_PARAMETER:
				$message = "{$arguments[0]}() expects exactly {$arguments[1]} parameter, {$arguments[2]} given";
				break;
			case PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED:
				$message = "Object of class {$arguments[0]} could not be converted to {$arguments[1]}";
				break;
			case PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT:
				$message = "Wrong parameter count for $arguments()";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_FUNCTION:
				$message = "Call to undefined function $arguments()";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_METHOD:
				$message = "Call to undefined method {$arguments[0]}::{$arguments[1]}()";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CLASS_NOT_FOUND:
				$message = "Class $arguments not found";
				break;
			case PHPTAGS_EXCEPTION_FATAL_NONEXISTENT_HOOK_CLASS:
				$message = "For the function {$arguments[0]} was registered nonexistent hook class {$arguments[1]}";
				break;
			case PHPTAGS_EXCEPTION_FATAL_INVALID_HOOK_CLASS:
				$message = "For the function {$arguments[0]} was registered invalid hook class {$arguments[1]}";
				break;
			case PHPTAGS_EXCEPTION_WARNING_CALLFUNCTION_INVALID_HOOK:
				$message = "Class {$arguments[0]} has registered hook for function {$arguments[1]}, but one has no information about how to process it.";
				break;
			case PHPTAGS_EXCEPTION_FATAL_CREATEOBJECT_INVALID_CLASS:
				$message = "Cannot find class {$arguments[0]} for create object {$arguments[1]}";
				break;
			case PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS:
				$message = 'Cannot unset string offsets';
				break;
			case PHPTAGS_EXCEPTION_FROM_HOOK:
				$message = $arguments[0];
				$this->code = $arguments[1] * 1000;
				break;
			case PHPTAGS_EXCEPTION_FATAL_LOOPS_LIMIT_REACHED:
				$message = 'Maximum number of allowed loops reached';
				break;
			case PHPTAGS_EXCEPTION_FATAL_OBJECT_NOT_CREATED:
				$message = "Object {$arguments[0]} has not been created with message \"{$arguments[1]}\"";
				break;
			case PHPTAGS_EXCEPTION_FATAL_BAD_CLASS_NAME:
				$message = 'Class name must be a valid object or a string';
				break;
			case PHPTAGS_EXCEPTION_FATAL_MUST_EXTENDS_GENERIC:
				$message = "Class $arguments must extends class '\\PhpTags\\GenericObject'";
				break;
			case PHPTAGS_EXCEPTION_FATAL_OBJECT_COULD_NOT_BE_CONVERTED:
				$message = "Object of class {$arguments[0]} could not be converted to {$arguments[1]}";
				break;
			case PHPTAGS_EXCEPTION_FATAL_NONSTATIC_CALLED_STATICALLY:
				$message = "Non-static method {$arguments[0]}::{$arguments[1]}() cannot be called statically";
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
			case PHPTAGS_EXCEPTION_CATCHABLE_FATAL:
				$messageType = 'Catchable fatal error';
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

