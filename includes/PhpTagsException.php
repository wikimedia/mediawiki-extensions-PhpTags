<?php
namespace PhpTags;

use Html;

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
	protected $hookCallInfo;

	/**
	 * PhpTagsException constructor.
	 * @param int $code
	 * @param mixed $arguments
	 * @param null|int $tokenLine
	 * @param string $place
	 */
	public function __construct( $code = 0, $arguments = null, $tokenLine = null, $place = '' ) {
		parent::__construct('', $code);
		$this->params = $arguments;
		$this->tokenLine = $tokenLine;
		$this->place = $place != '' ? $place : 'Command line code';
		$this->hookCallInfo = Hooks::getCallInfo();
	}

	/**
	 * @return bool
	 */
	public function isFatal() {
		return intval( $this->code / 1000 ) > self::EXCEPTION_WARNING;
	}

	/**
	 * @return bool
	 */
	public function isCatchable() {
		return intval( $this->code / 1000 ) !== self::EXCEPTION_FATAL;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$arguments = $this->params;
		$originalFullName = $this->hookCallInfo[Hooks::INFO_ORIGINAL_FULL_NAME] ?? '';

		switch ( $this->code ) {
			case self::PARSE_SYNTAX_ERROR_UNEXPECTED:
				$message = 'syntax error, unexpected \'' . ( is_string($arguments[0]) ? $arguments[0] : token_name($arguments[0]) ) . '\'';
				array_shift( $arguments );
				if ( $arguments ) {
					$message .= ", expecting " . implode( ", ", $arguments );
				}
				break;
			case self::PARSE_ERROR_EXPRESSION_IN_STATIC:
				$message = "syntax error, expressions are not permitted if you declare static variable";
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
				$message = "Undefined property: $originalFullName";
				break;
			case self::NOTICE_UNDEFINED_CLASS_CONSTANT:
				$message = "Undefined class constant: $originalFullName";
				break;
			case self::WARNING_RETURNED_INVALID_VALUE:
				// @todo
				$message = "constant, function or object '$arguments' returns invalid value";
				break;
			case self::FATAL_VALUE_PASSED_BY_REFERENCE:
				$message = "Only variables can be passed by reference";
				break;
			case self::WARNING_EXPECTS_PARAMETER:
				$message = "$originalFullName expects parameter {$arguments[0]} to be {$arguments[1]}, {$arguments[2]} given";
				break;
			case self::NOTICE_EXPECTS_PROPERTY:
				$message = "$originalFullName expects property to be {$arguments[0]}, {$arguments[1]} given";
				break;
			case self::FATAL_UNEXPECTED_OBJECT_TYPE: // = 4021; // Fatal error: Unexpected object type stdClass. in
				$message = "$originalFullName Unexpected object type {$arguments[0]}";
				break;
			case self::WARNING_EXPECTS_EXACTLY_PARAMETERS:
				$message = "$originalFullName expects exactly {$arguments[0]} parameters, {$arguments[1]} given";
				break;
			case self::WARNING_EXPECTS_EXACTLY_PARAMETER:
				$message = "$originalFullName expects exactly {$arguments[0]} parameter, {$arguments[1]} given";
				break;
			case self::WARNING_EXPECTS_AT_LEAST_PARAMETERS:
				$message = "$originalFullName expects at least {$arguments[0]} parameters, {$arguments[1]} given";
				break;
			case self::WARNING_EXPECTS_AT_LEAST_PARAMETER:
				$message = "$originalFullName expects at least {$arguments[0]} parameter, {$arguments[1]} given";
				break;
			case self::WARNING_TOO_MANY_ARGUMENTS: //Warning: Too many arguments for date_format(), expected 2
				$message = "Too many arguments for $originalFullName, expected $arguments";
				break;
			case self::NOTICE_OBJECT_CONVERTED:
			case self::FATAL_OBJECT_COULD_NOT_BE_CONVERTED:
				$message = "Object of class {$arguments[0]} could not be converted to {$arguments[1]}";
				break;
			case self::NOTICE_ARRAY_TO_STRING:
				$message = "Array to string conversion";
				break;
			case self::FATAL_CALL_TO_UNDEFINED_FUNCTION:
				$message = "Call to undefined function $originalFullName";
				break;
			case self::FATAL_CALL_TO_UNDEFINED_METHOD:
				$message = "Call to undefined method $originalFullName";
				break;
			case self::FATAL_CLASS_NOT_FOUND:
				$message = "Class \"$arguments\" not found";
				break;
			case self::FATAL_NONEXISTENT_HOOK_CLASS:
				$message = "For the function $originalFullName was registered nonexistent hook class $arguments";
				break;
			case self::FATAL_INVALID_HOOK_CLASS:
				$message = "For the function $originalFullName was registered invalid hook class $arguments";
				break;
			case self::FATAL_NONEXISTENT_CONSTANT_CLASS:
				$message = "For the constant $originalFullName was registered nonexistent hook class $arguments";
				break;
			case self::FATAL_INVALID_CONSTANT_CLASS:
				$message = "For the constant $originalFullName was registered invalid hook class $arguments";
				break;
			case self::FATAL_CALLFUNCTION_INVALID_HOOK:
				$message = "Class $arguments registered hook for function $originalFullName, but it has no information how to process it.";
				break;
			case self::FATAL_CALLCONSTANT_INVALID_HOOK:
				$message = "Class $arguments registered hook for constant $originalFullName, but has no information how to process it.";
				break;
			case self::FATAL_CREATEOBJECT_INVALID_CLASS:
				$message = "Cannot find class $arguments for create object " . $this->hookCallInfo[Hooks::INFO_ORIGINAL_OBJECT_NAME];
				break;
			case self::FATAL_CANNOT_UNSET_STRING_OFFSETS:
				$message = 'Cannot unset string offsets';
				break;
			case self::FATAL_LOOPS_LIMIT_REACHED:
				$message = 'Maximum number of allowed loops reached';
				break;
			case self::FATAL_OBJECT_NOT_CREATED:
				$message = 'Object ' . $this->hookCallInfo[Hooks::INFO_ORIGINAL_OBJECT_NAME] . " has not been created with message \"$arguments\"";
				break;
			case self::FATAL_MUST_EXTENDS_GENERIC:
				$message = "Class $arguments must extends class '\\PhpTags\\GenericObject'";
				break;
			case self::FATAL_NONSTATIC_CALLED_STATICALLY: // @todo have not used
				$message = "Non-static method $originalFullName cannot be called statically";
				break;
			case self::FATAL_CALLED_MANY_EXPENSIVE_FUNCTION:
				$message = "Too many expensive function calls, last is $originalFullName";
				break;
			case self::FATAL_WRONG_BREAK_LEVELS:
				$message = "Cannot break/continue $arguments levels";
				break;
			case self::NOTICE_GET_PROPERTY_OF_NON_OBJECT:
				$message = 'Trying to get property ' . $this->hookCallInfo[Hooks::INFO_ORIGINAL_HOOK_NAME] . ' of non-object';
				break;
			case self::WARNING_ATTEMPT_TO_ASSIGN_PROPERTY:
				$message = 'Attempt to assign property ' . $this->hookCallInfo[Hooks::INFO_ORIGINAL_HOOK_NAME] . ' of non-object';
				break;
			case self::FATAL_CALL_FUNCTION_ON_NON_OBJECT:
				$message = 'Call to a member function ' . $this->hookCallInfo[Hooks::INFO_ORIGINAL_HOOK_NAME] . '() on a non-object';
				break;
			case self::FATAL_ACCESS_TO_UNDECLARED_STATIC_PROPERTY:
				$message = "Access to undeclared static property: $originalFullName";
				break;
			case self::WARNING_EXPECTS_AT_MOST_PARAMETERS:
				$message = "$originalFullName expects at most $arguments[0] parameters, $arguments[1] given";
				break;
			case self::FATAL_DENIED_FOR_NAMESPACE:
				$message = wfMessage( 'phptags-disabled-for-namespace', $arguments )->text();
				break;
			case self::WARNING_ILLEGAL_OFFSET_TYPE:
				$message = 'Illegal offset type';
				break;
			case self::FATAL_CANNOT_USE_OBJECT_AS_ARRAY:
				$message = 'Cannot use object as array';
				break;
			case self::FATAL_UNSUPPORTED_OPERAND_TYPES:
				$message = 'Unsupported operand types';
				break;
			case self::FATAL_INTERNAL_ERROR:
				$message = 'Unexpected behavior of PhpTags (Internal Error):' . $arguments;
				break;
			case self::WARNING_NON_NUMERIC_VALUE:
				$message = 'A non-numeric value encountered';
				break;
			case self::DEPRECATED_INVALID_CHARACTERS:
				$message= 'Invalid characters passed for attempted conversion, these have been ignored';
				break;
			default:
				$message = "Undefined error, code {$this->code}";
				$this->code = self::EXCEPTION_FATAL * 1000;
				break;
		}

		return $this->formatMessage( $message, intval( $this->code / 1000 ) );
	}

	protected function formatMessage( $message, $errorLevel ) {
		$line = $this->tokenLine;
		$place = $this->place;

		switch ( $errorLevel ) {
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
			case self::EXCEPTION_DEPRECATED:
				$messageType = 'Deprecated';
				break;
			default:
				$messageType = 'Undefined error';
				break;
		}

		$messageTrimed = trim( preg_replace( '/\s+/', ' ', $message ) );
		//return "$messageTrimed in $place on line $line\n";
		return Html::element( 'span', [ 'class'=>'error' ], "PhpTags $messageType:  $messageTrimed in $place on line $line" ) . '<br />';

	}

	const EXCEPTION_NOTICE = 2;
	const NOTICE_UNDEFINED_VARIABLE = 2001; // PHP Notice:  Undefined variable: $1 in Command line code on line 1
	const NOTICE_UNINIT_STRING_OFFSET = 2002; // PHP Notice:  Uninitialized string offset: $1
	const NOTICE_UNDEFINED_OFFSET = 2003;  // PHP Notice:  Undefined offset: 4 in Command line code on line 1
	const NOTICE_UNDEFINED_INDEX = 2004;  // PHP Notice:  Undefined index: ddd in Command line code on line 1
	const NOTICE_UNDEFINED_CONSTANT = 2005;  // PHP Notice:  Use of undefined constant $1 - assumed '$1'
	const NOTICE_UNDEFINED_PROPERTY = 2006;  // PHP Notice:  Undefined property: DateInterval::$rsss
	const NOTICE_UNDEFINED_CLASS_CONSTANT = 2007;  // PHP Fatal error:  Undefined class constant 'EXCLUDE_START_DATEqqqq'
	const NOTICE_OBJECT_CONVERTED = 2008;  // PHP Notice:  Object of class Exception could not be converted to int
	const NOTICE_GET_PROPERTY_OF_NON_OBJECT = 2009; // PHP Notice:  Trying to get property of non-object
	const NOTICE_EXPECTS_PROPERTY = 2010;
	const NOTICE_ARRAY_TO_STRING = 2011; // PHP Notice:  Array to string conversion

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
	const WARNING_ATTEMPT_TO_ASSIGN_PROPERTY = 3012; // PHP Warning:  Attempt to assign property of non-object
	const WARNING_EXPECTS_AT_MOST_PARAMETERS = 3013; // PHP Warning:  round() expects at most 3 parameters, 4 given
	const WARNING_TOO_MANY_ARGUMENTS = 3014; //Warning: Too many arguments for date_format(), expected 2
	const WARNING_ILLEGAL_OFFSET_TYPE = 3015; // PHP Warning:  Illegal offset type
	const WARNING_NON_NUMERIC_VALUE = 3016; // PHP Warning:  A non-numeric value encountered

	const EXCEPTION_FATAL = 4;
	const FATAL_CANNOT_USE_FOR_READING = 4001;  // PHP Fatal error:  Cannot use [] for reading in Command line code on line 1
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
	const FATAL_CALLED_MANY_EXPENSIVE_FUNCTION = 4018;
	const FATAL_CALL_FUNCTION_ON_NON_OBJECT = 4019; // PHP Fatal error:  Call to a member function doo() on a non-object
	const FATAL_ACCESS_TO_UNDECLARED_STATIC_PROPERTY = 4020; // PHP Fatal error:  Access to undeclared static property: F::$rsrr
	const FATAL_UNEXPECTED_OBJECT_TYPE = 4021; // Fatal error: Unexpected object type stdClass. in
	const FATAL_WRONG_BREAK_LEVELS = 4022; // PHP Fatal error:  Cannot break/continue 4 levels
	const FATAL_CANNOT_USE_OBJECT_AS_ARRAY = 4023; // Cannot use object of type %%%%%% as array
	const FATAL_UNSUPPORTED_OPERAND_TYPES = 4024; // Unsupported operand types, example: [1] + 1

	const FATAL_DENIED_FOR_NAMESPACE = 4500;
	const FATAL_CALLFUNCTION_INVALID_HOOK = 4501;
	const FATAL_CALLCONSTANT_INVALID_HOOK = 4502;

	const FATAL_INTERNAL_ERROR = 4999; // Unexpected behavior

	const EXCEPTION_CATCHABLE_FATAL = 5;
	const FATAL_OBJECT_COULD_NOT_BE_CONVERTED = 5001;  //PHP Catchable fatal error:  Object of class stdClass could not be converted to string

	const EXCEPTION_PARSE = 6;
	const PARSE_SYNTAX_ERROR_UNEXPECTED = 6001;  // PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1
	const PARSE_ERROR_EXPRESSION_IN_STATIC = 6100; // syntax error, expressions are not permitted if you declare static variable

	const EXCEPTION_DEPRECATED = 7;
	const DEPRECATED_INVALID_CHARACTERS = 7001;

// PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 73 bytes)
// PHP Fatal error:  Maximum execution time of 30 seconds exceeded
// PHP Warning:  Variable passed to each() is not an array or object
// PHP Parse error:  syntax error, unexpected $end, expecting ',' or ';' in Command line code on line 1

}
