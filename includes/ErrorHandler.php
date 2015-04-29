<?php
namespace PhpTags;

/**
 * Description of ErrorHandler
 *
 * @author pastakhov
 */
class ErrorHandler {

	public static function onError( $errno, $errstr, $errfile, $errline, $errcontext, $object = false ) {
		if ( $object === false ) { // @todo wfIsHHVM()
			return self::onPhpError( $errno, $errstr, $errfile );
		}
		return self::onHhvmError( $errno, $errstr, $errfile );
	}

	private static function onPhpError( $errno, $errstr, $errfile ) {
		$backtrace = debug_backtrace();
		if ( true === isset($backtrace[1]['file']) && strpos( $backtrace[1]['file'], 'PhpTags/includes/Runtime.php' ) !== false ) {
			if ( strpos( $errstr, 'Division by zero' ) !== false ) {
				Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null ) );
				return true;
			}
			$matches = null;
			if ( preg_match('/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches) ) {
				return self::onRuntimeObjectConvertionError( $matches );
			}
		}
		return false;
	}

	private static function onRuntimeObjectConvertionError( $matches ) {
		$object = Runtime::getCurrentOperator();
		static $previousOperator = false;
		if ( isset($object[PHPTAGS_STACK_PARAM]) && is_a($object[PHPTAGS_STACK_PARAM], $matches[1]) ) {
			if ( $previousOperator === $object && is_a($object[PHPTAGS_STACK_PARAM_2], $matches[1]) ) {
				$matches[1] = $object[PHPTAGS_STACK_PARAM_2]->getName();
			} else {
				$matches[1] = $object[PHPTAGS_STACK_PARAM]->getName();
			}
		} else {
			$matches[1] = $object[PHPTAGS_STACK_PARAM_2]->getName();
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($matches[1], $matches[2]) ) );
		$previousOperator = $object;
		return true;
	}

	private static function onHhvmError( $errno, $errstr, $errfile ) {
		$backtrace = debug_backtrace();
		$matches = null;
		if ( false !== strpos( $errfile, 'PhpTags/includes/Runtime.php' ) ) {
			if ( strpos( $errstr, 'Division by zero' ) !== false ) {
				Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null ) );
				return true;
			}
			$matches = null;
			if ( preg_match('/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches) ) {
				return self::onRuntimeObjectConvertionError( $matches );
			}
		}
		return false;
	}

}
