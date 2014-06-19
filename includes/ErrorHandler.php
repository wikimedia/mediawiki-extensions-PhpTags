<?php
namespace PhpTags;

/**
 * Description of ErrorHandler
 *
 * @author pastakhov
 */
class ErrorHandler {

	public static function onError( $errno, $errstr, $errfile, $errline, $object ) {
		$backtrace = debug_backtrace();
		$matches = null;
		if ( true === isset($backtrace[0]['file']) && strpos( $backtrace[0]['file'], 'PhpTags/includes/Runtime.php' ) !== false ) {
			return self::onRuntimeError( $errno, $errstr, $errfile, $errline, $object );
		}

		if ( strpos( $errstr, 'expects parameter' ) !== false ) {
			if (
					false === isset($backtrace[1]['file']) &&
					$backtrace[2]['function'] == "call_user_func_array" &&
					isset($backtrace[3]['class']) && is_subclass_of( $backtrace[3]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects parameter (\\d+) to be (\\w+), (\\w+) given/', $errstr, $matches )
				)
			{
				if ( $backtrace[3]['function'] == '__callStatic' ) {
					Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
							PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER,
							array( substr($backtrace[3]['args'][0], 2), $matches[1], $matches[2], $matches[3])
						);
					return true;
				}
			}
		} elseif( strpos( $errstr, 'expects exactly' ) !== false ) {
			if (
					false === isset($backtrace[1]['file']) &&
					$backtrace[2]['function'] == "call_user_func_array" &&
					isset($backtrace[3]['class']) && is_subclass_of( $backtrace[3]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects exactly (\\d+) (parameter[s]?), (\\d+) given/', $errstr, $matches )
				)
			{
				if ( $backtrace[3]['function'] == '__callStatic' ) {
					Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
							$matches[2] == 'parameter' ? PHPTAGS_EXCEPTION_WARNING_EXPECTS_EXACTLY_PARAMETER : PHPTAGS_EXCEPTION_WARNING_EXPECTS_EXACTLY_PARAMETERS,
							array( substr($backtrace[3]['args'][0], 2), $matches[1], $matches[3] )
						);
					return true;
				}
			}
		}
		return false;
	}

	private static function onRuntimeError( $errno, $errstr, $errfile, $errline, $object ) {
		if ( strpos( $errstr, 'Division by zero' ) !== false ) {
			Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO, null );
			return true;
		}
		$matches = null;
		if ( preg_match('/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches) ) {
			return self::onRuntimeObjectConvertionError( $matches, $object );
		}
	}

	private static function onRuntimeObjectConvertionError( $matches, $object ) {
		if ( isset($object['value'][PHPTAGS_STACK_PARAM]) && is_a($object['value'][PHPTAGS_STACK_PARAM], $matches[1]) ) {
			if ( isset(Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][0]) && is_a($object['value'][PHPTAGS_STACK_PARAM_2], $matches[1]) ) {
				$matches[1] = $object['value'][PHPTAGS_STACK_PARAM_2]->getName();
			} else {
				$matches[1] = $object['value'][PHPTAGS_STACK_PARAM]->getName();
			}
		} else {
			$matches[1] = $object['value'][PHPTAGS_STACK_PARAM_2]->getName();
		}
		Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED, array($matches[1], $matches[2]) );
		return true;
	}

}
