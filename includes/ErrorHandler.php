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
			return self::onPhpError( $errno, $errstr, $errfile, $errline, $errcontext );
		}
		return self::onHhvmError( $errno, $errstr, $errfile, $errline, $object );
	}

	private static function onPhpError( $errno, $errstr, $errfile, $errline, $object) {
		$backtrace = debug_backtrace();
		if ( true === isset($backtrace[1]['file']) && strpos( $backtrace[1]['file'], 'PhpTags/includes/Runtime.php' ) !== false ) {
			return self::onRuntimeError( $errno, $errstr, $errfile, $errline, $object );
		}

		return false;
	}

	private static function onRuntimeError( $errno, $errstr, $errfile, $errline, $object ) {
		if ( strpos( $errstr, 'Division by zero' ) !== false ) {
			Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null ) );
			return true;
		}
		$matches = null;
		if ( preg_match('/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches) ) {
			return self::onRuntimeObjectConvertionError( $matches, $object );
		}
	}

	private static function onRuntimeObjectConvertionError( $matches, $object ) {
		if ( isset($object['value'][PHPTAGS_STACK_PARAM]) && is_a($object['value'][PHPTAGS_STACK_PARAM], $matches[1]) ) {
			if ( Runtime::getExceptions() && is_a($object['value'][PHPTAGS_STACK_PARAM_2], $matches[1]) ) {
				$matches[1] = $object['value'][PHPTAGS_STACK_PARAM_2]->getName();
			} else {
				$matches[1] = $object['value'][PHPTAGS_STACK_PARAM]->getName();
			}
		} else {
			$matches[1] = $object['value'][PHPTAGS_STACK_PARAM_2]->getName();
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($matches[1], $matches[2]) ) );
		return true;
	}

	private static function onHhvmError( $errno, $errstr, $errfile, $errline, $object) {
		$backtrace = debug_backtrace();
		$matches = null;
		if ( false !== strpos( $errfile, 'PhpTags/includes/Runtime.php' ) ) {
			if ( strpos( $errstr, 'Division by zero' ) !== false ) {
				Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null ) );
				return true;
			}
			$matches = null;
			if ( preg_match('/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches) ) {
				Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($matches[1], $matches[2]) ) );
				return true;
			}
		}

		return false;
	}

}
