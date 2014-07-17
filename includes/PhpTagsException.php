<?php
namespace PhpTags;

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
			case self::PARSE_SYNTAX_ERROR_UNEXPECTED:
				$message = 'syntax error, unexpected \'' . ( is_string($arguments[0]) ? $arguments[0] : token_name($arguments[0]) ) . '\'';
				array_shift( $arguments );
				if ( $arguments ) {
					$message .= ", expecting " . implode( ", ", $arguments );
				}
				break;
			case self::NOTICE_UNDEFINED_VARIABLE:
				$message = "Undefined variable: $arguments";
				break;
			case self::FATAL_CANNOT_USE_FOR_READING:
				$message = 'Cannot use [] for reading';
				break;
			case self::WARNING_DIVISION_BY_ZERO:
				$message = "Division by zero";
				break;
			case self::NOTICE_UNINIT_STRING_OFFSET:
				$message = "Uninitialized string offset: $arguments";
				break;
			case self::NOTICE_UNDEFINED_OFFSET:
				$message = "Undefined offset: $arguments";
				break;
			case self::NOTICE_UNDEFINED_INDEX:
				$message = "Undefined index: $arguments";
				break;
			case self::FATAL_STRING_OFFSET_AS_ARRAY:
				$message = "Cannot use string offset as an array";
				break;
			case self::WARNING_SCALAR_VALUE_AS_ARRAY: //@todo have not used
				$message = "Cannot use a scalar value as an array";
				break;
			case self::WARNING_INVALID_ARGUMENT_FOR_FOREACH:
				$message = 'Invalid argument supplied for foreach()';
				break;
			case self::NOTICE_UNDEFINED_CONSTANT:
				$message = "Use of undefined constant $arguments - assumed '$arguments'";
				break;
			case self::NOTICE_UNDEFINED_PROPERTY:
				$message = "Undefined property: {$arguments[0]}::\${$arguments[1]}";
				break;
			case self::NOTICE_UNDEFINED_CLASS_CONSTANT:
				$message = "Undefined class constant: {$arguments[0]}::{$arguments[1]}";
				break;
			case self::WARNING_RETURNED_INVALID_VALUE:
				// @todo
				$message = "constant, function or object '$arguments' returns invalid value";
				break;
			case self::FATAL_VALUE_PASSED_BY_REFERENCE:
				$message = "Only variables can be passed by reference";
				break;
			case self::WARNING_EXPECTS_PARAMETER:
				$message = "{$arguments[0]}() expects parameter {$arguments[1]} to be {$arguments[2]}, {$arguments[3]} given";
				break;
			case self::WARNING_EXPECTS_EXACTLY_PARAMETERS:
				$message = "{$arguments[0]}() expects exactly {$arguments[1]} parameters, {$arguments[2]} given";
				break;
			case self::WARNING_EXPECTS_EXACTLY_PARAMETER:
				$message = "{$arguments[0]}() expects exactly {$arguments[1]} parameter, {$arguments[2]} given";
				break;
			case self::WARNING_EXPECTS_AT_LEAST_PARAMETERS:
				$message = "{$arguments[0]}() expects at least {$arguments[1]} parameters, {$arguments[2]} given";
				break;
			case self::WARNING_EXPECTS_AT_LEAST_PARAMETER:
				$message = "{$arguments[0]}() expects at least {$arguments[1]} parameter, {$arguments[2]} given";
				break;
			case self::NOTICE_OBJECT_CONVERTED:
				$message = "Object of class {$arguments[0]} could not be converted to {$arguments[1]}";
				break;
			case self::FATAL_CALL_TO_UNDEFINED_FUNCTION:
				$message = "Call to undefined function $arguments()";
				break;
			case self::FATAL_CALL_TO_UNDEFINED_METHOD:
				$message = "Call to undefined method {$arguments[0]}::{$arguments[1]}()";
				break;
			case self::FATAL_CLASS_NOT_FOUND:
				$message = "Class \"$arguments\" not found";
				break;
			case self::FATAL_NONEXISTENT_HOOK_CLASS:
				$message = "For the function {$arguments[0]} was registered nonexistent hook class {$arguments[1]}";
				break;
			case self::FATAL_INVALID_HOOK_CLASS:
				$message = "For the function {$arguments[0]} was registered invalid hook class {$arguments[1]}";
				break;
			case self::FATAL_NONEXISTENT_CONSTANT_CLASS:
				$message = "For the constant {$arguments[0]} was registered nonexistent hook class {$arguments[1]}";
				break;
			case self::FATAL_INVALID_CONSTANT_CLASS:
				$message = "For the constant {$arguments[0]} was registered invalid hook class {$arguments[1]}";
				break;
			case self::WARNING_CALLFUNCTION_INVALID_HOOK:
				$message = "Class {$arguments[0]} has registered hook for function {$arguments[1]}, but one has no information about how to process it.";
				break;
			case self::WARNING_CALLCONSTANT_INVALID_HOOK:
				$message = "Class {$arguments[0]} has registered hook for constant {$arguments[1]}, but one has no information about how to process it.";
				break;
			case self::FATAL_CREATEOBJECT_INVALID_CLASS:
				$message = "Cannot find class {$arguments[0]} for create object {$arguments[1]}";
				break;
			case self::FATAL_CANNOT_UNSET_STRING_OFFSETS:
				$message = 'Cannot unset string offsets';
				break;
			case self::EXCEPTION_FROM_HOOK:
				$message = $arguments[0];
				$this->code = $arguments[1] * 1000;
				break;
			case self::FATAL_LOOPS_LIMIT_REACHED:
				$message = 'Maximum number of allowed loops reached';
				break;
			case self::FATAL_OBJECT_NOT_CREATED:
				$message = "Object {$arguments[0]} has not been created with message \"{$arguments[1]}\"";
				break;
			case self::FATAL_MUST_EXTENDS_GENERIC:
				$message = "Class $arguments must extends class '\\PhpTags\\GenericObject'";
				break;
			case self::FATAL_OBJECT_COULD_NOT_BE_CONVERTED:
				$message = "Object of class {$arguments[0]} could not be converted to {$arguments[1]}";
				break;
			case self::FATAL_NONSTATIC_CALLED_STATICALLY: // @todo have not used
				$message = "Non-static method {$arguments[0]}::{$arguments[1]}() cannot be called statically";
				break;
			default:
				$message = "Undefined error, code {$this->code}";
				$this->code = self::EXCEPTION_FATAL * 1000;
				break;
		}

		switch ( intval($this->code / 1000) ) {
			case self::EXCEPTION_NOTICE:
				$messageType = 'Notice';
				break;
			case self::EXCEPTION_WARNING:
				$messageType = 'Warning';
				break;
			case self::EXCEPTION_FATAL:
				$messageType = 'Fatal error';
				break;
			case self::EXCEPTION_CATCHABLE_FATAL:
				$messageType = 'Catchable fatal error';
				break;
			case self::EXCEPTION_PARSE:
				$messageType = 'Parse error';
				break;
			default:
				$messageType = 'Undefined error';
				break;
		}
		//return "$message in $place on line $line\n";
		return \Html::element( 'span', array('class'=>'error'), "PhpTags $messageType:  $message in $place on line $line" ) . '<br />';
	}

	const EXCEPTION_FROM_HOOK = 1001; // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash

	const EXCEPTION_NOTICE = 2;
	const NOTICE_UNDEFINED_VARIABLE = 2001; // PHP Notice:  Undefined variable: $1 in Command line code on line 1
	const NOTICE_UNINIT_STRING_OFFSET = 2002; // PHP Notice:  Uninitialized string offset: $1
	const NOTICE_UNDEFINED_OFFSET = 2003;  // PHP Notice:  Undefined offset: 4 in Command line code on line 1
	const NOTICE_UNDEFINED_INDEX = 2004;  // PHP Notice:  Undefined index: ddd in Command line code on line 1
	const NOTICE_UNDEFINED_CONSTANT = 2005;  // PHP Notice:  Use of undefined constant $1 - assumed '$1'
	const NOTICE_UNDEFINED_PROPERTY = 2006;  // PHP Notice:  Undefined property: DateInterval::$rsss
	const NOTICE_UNDEFINED_CLASS_CONSTANT = 2007;  // PHP Fatal error:  Undefined class constant 'EXCLUDE_START_DATEqqqq'
	const NOTICE_OBJECT_CONVERTED = 2008;  // PHP Notice:  Object of class Exception could not be converted to int

	const EXCEPTION_WARNING = 3;
	const WARNING_DIVISION_BY_ZERO = 3001;  // PHP Warning:  Division by zero
	const WARNING_SCALAR_VALUE_AS_ARRAY = 3002;  // PHP Warning:  Cannot use a scalar value as an array
	const WARNING_INVALID_ARGUMENT_FOR_FOREACH = 3003;  // PHP Warning:  Invalid argument supplied for foreach()
	const WARNING_RETURNED_INVALID_VALUE = 3004;
	const WARNING_EXPECTS_PARAMETER = 3006;  // PHP Warning:  func() expects parameter 1 to be array, integer given
	// const WARNING_WRONG_PARAMETER_COUNT = 3007;  // PHP Warning:  Wrong parameter count for $1()
	const WARNING_EXPECTS_EXACTLY_PARAMETERS = 3008;  // PHP Warning:  date_format() expects exactly 2 parameters, 3 given
	const WARNING_EXPECTS_EXACTLY_PARAMETER = 3009;  // PHP Warning:  date_format() expects exactly 1 parameter, 3 given
	const WARNING_EXPECTS_AT_LEAST_PARAMETERS = 3010;
	const WARNING_EXPECTS_AT_LEAST_PARAMETER = 3011;  // PHP Warning:  sprintf() expects at least 1 parameter, 0 given

	const WARNING_CALLFUNCTION_INVALID_HOOK = 3900;
	const WARNING_CALLCONSTANT_INVALID_HOOK = 3901;

	const EXCEPTION_FATAL = 4;
	const FATAL_CANNOT_USE_FOR_READING = 4001;  // PHP Fatal error:  Cannot use [] for reading in Command line code on line 1
	const FATAL_STRING_OFFSET_AS_ARRAY = 4002;  // PHP Fatal error:  Cannot use string offset as an array
	const FATAL_VALUE_PASSED_BY_REFERENCE = 4003;  // PHP Fatal error:  Only variables can be passed by reference
	const FATAL_CALL_TO_UNDEFINED_FUNCTION = 4004;  // PHP Fatal error:  Call to undefined function $1()
	const FATAL_NONEXISTENT_HOOK_CLASS = 4005;
	const FATAL_INVALID_HOOK_CLASS = 4006;
	const FATAL_LOOPS_LIMIT_REACHED = 4007;
	const FATAL_CLASS_NOT_FOUND = 4008;  // PHP Fatal error:  Class '$1' not found
	const FATAL_CALL_TO_UNDEFINED_METHOD = 4009;  // PHP Fatal error:  Call to undefined method $1::$2()
	const FATAL_OBJECT_NOT_CREATED = 4010;
	//const FATAL_BAD_CLASS_NAME = 4011;  // PHP Fatal error:  Class name must be a valid object or a string
	const FATAL_MUST_EXTENDS_GENERIC = 4012;
	const FATAL_CREATEOBJECT_INVALID_CLASS = 4013;
	const FATAL_NONSTATIC_CALLED_STATICALLY = 4014;  // PHP Fatal error:  Non-static method DateTime::format() cannot be called statically
	const FATAL_NONEXISTENT_CONSTANT_CLASS = 4015;
	const FATAL_INVALID_CONSTANT_CLASS = 4016;
	const FATAL_CANNOT_UNSET_STRING_OFFSETS = 4017; // PHP Fatal error:  Cannot unset string offsets

	const EXCEPTION_CATCHABLE_FATAL = 5;
	const FATAL_OBJECT_COULD_NOT_BE_CONVERTED = 5001;  //PHP Catchable fatal error:  Object of class stdClass could not be converted to string

	const EXCEPTION_PARSE = 6;
	const PARSE_SYNTAX_ERROR_UNEXPECTED = 6001;  // PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1

	// pcre
	const WARNING_WRONG_DELIMITER = 2009;  // PHP Warning:  preg_replace(): Delimiter must not be alphanumeric or backslash
	const WARNING_NO_ENDING_DELIMITER = 2010;  // PHP Warning:  preg_replace(): No ending delimiter '/' found
	const WARNING_UNKNOWN_MODIFIER = 111;  // PHP Warning:  preg_replace(): Unknown modifier 'z'

// PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 73 bytes)
// PHP Fatal error:  Maximum execution time of 30 seconds exceeded
// PHP Warning:  Variable passed to each() is not an array or object
// PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1

}
