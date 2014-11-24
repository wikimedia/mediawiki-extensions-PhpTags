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
		$matches = null;
		if ( true === isset($backtrace[1]['file']) && strpos( $backtrace[1]['file'], 'PhpTags/includes/Runtime.php' ) !== false ) {
			return self::onRuntimeError( $errno, $errstr, $errfile, $errline, $object );
		}

		if ( strpos( $errstr, 'expects parameter' ) !== false ) {
			if (
					false === isset($backtrace[2]['file']) &&
					$backtrace[3]['function'] == "call_user_func_array" &&
					isset($backtrace[4]['class']) && is_subclass_of( $backtrace[4]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects parameter (\\d+) to be (\\w+), (\\w+) given/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[4]['function'] == '__callStatic' ? $backtrace[4]['args'][0] : $backtrace[4]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						PhpTagsException::WARNING_EXPECTS_PARAMETER,
						array( $function, $matches[1], $matches[2], $matches[3])
					);
				return true;
			}
		} elseif( strpos( $errstr, 'expects exactly' ) !== false ) {
			if (
					false === isset($backtrace[2]['file']) &&
					$backtrace[3]['function'] == "call_user_func_array" &&
					isset($backtrace[4]['class']) && is_subclass_of( $backtrace[4]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects exactly (\\d+) (parameter[s]?), (\\d+) given/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[4]['function'] == '__callStatic' ? $backtrace[4]['args'][0] : $backtrace[4]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						$matches[2] == 'parameter' ? PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER : PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETERS,
						array( $function, $matches[1], $matches[3] )
					);
				return true;
			}
		} elseif( strpos( $errstr, 'expects at least' ) !== false ) {
			if (
					false === isset($backtrace[2]['file']) &&
					$backtrace[3]['function'] == "call_user_func_array" &&
					isset($backtrace[4]['class']) && is_subclass_of( $backtrace[4]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects at least (\\d+) (parameter[s]?), (\\d+) given/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[4]['function'] == '__callStatic' ? $backtrace[4]['args'][0] : $backtrace[4]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						$matches[2] == 'parameter' ? PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETER : PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETERS,
						array( $function, $matches[1], $matches[3] )
					);
				return true;
			}
		} elseif( strpos( $errstr, 'could not be converted' ) !== false ) {
			if (
					false === isset($backtrace[2]['file']) &&
					$backtrace[3]['function'] == "call_user_func_array" &&
					isset($backtrace[4]['class']) && is_subclass_of( $backtrace[4]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches )
				)
			{
				foreach ( $object['arguments'] as $arg ) {
					if ( $arg instanceof GenericObject && get_class($arg) == $matches[1] ) {
						$matches[1] = $arg->getName();
					}
				}
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						PhpTagsException::NOTICE_OBJECT_CONVERTED,
						array( $matches[1], $matches[2] )
					);
				return true;
			}
		}
		return false;
	}

	private static function onRuntimeError( $errno, $errstr, $errfile, $errline, $object ) {
		if ( strpos( $errstr, 'Division by zero' ) !== false ) {
			Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null );
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
		Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($matches[1], $matches[2]) );
		return true;
	}

	private static function onHhvmError( $errno, $errstr, $errfile, $errline, $object) {
		$backtrace = debug_backtrace();
		$matches = null;
		if ( false !== strpos( $errfile, 'PhpTags/includes/Runtime.php' ) ) {
			if ( strpos( $errstr, 'Division by zero' ) !== false ) {
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null );
				return true;
			}
			$matches = null;
			if ( preg_match('/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches) ) {
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($matches[1], $matches[2]) );
				return true;
			}
		}

		if ( strpos( $errstr, 'expects parameter' ) !== false ) {
			if (
					isset($backtrace[3]['class']) && is_subclass_of( $backtrace[3]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects parameter (\\d+) to be (\\w+), (\\w+) given/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[3]['function'] == '__callStatic' ? $backtrace[3]['args'][0] : $backtrace[3]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						PhpTagsException::WARNING_EXPECTS_PARAMETER,
						array( $function, $matches[1], $matches[2], $matches[3])
					);
				return true;
			}
		} elseif( strpos( $errstr, 'expects exactly' ) !== false ) {
			if (
					isset($backtrace[3]['class']) && is_subclass_of( $backtrace[3]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects exactly (\\d+) (parameter[s]?), (\\d+) given/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[3]['function'] == '__callStatic' ? $backtrace[3]['args'][0] : $backtrace[3]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						$matches[2] == 'parameter' ? PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER : PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETERS,
						array( $function, $matches[1], $matches[3] )
					);
				return true;
			}
		} elseif( strpos( $errstr, 'expects at least' ) !== false ) {
			if (
					isset($backtrace[3]['class']) && is_subclass_of( $backtrace[3]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/expects at least (\\d+) (parameter[s]?), (\\d+) given/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[3]['function'] == '__callStatic' ? $backtrace[3]['args'][0] : $backtrace[3]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						$matches[2] == 'parameter' ? PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETER : PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETERS,
						array( $function, $matches[1], $matches[3] )
					);
				return true;
			}
		} elseif( strpos( $errstr, 'Too many arguments for' ) !== false ) { // Too many arguments for date_format(), expected 2
			if (
					isset($backtrace[3]['class']) && is_subclass_of( $backtrace[3]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/Too many arguments for \\w+\(\), expected (\\d+)/', $errstr, $matches )
				)
			{
				$function = substr( $backtrace[3]['function'] == '__callStatic' ? $backtrace[3]['args'][0] : $backtrace[3]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::WARNING_TOO_MANY_ARGUMENTS, array( $function, $matches[0] ) );
				return true;
			}
		} elseif( strpos( $errstr, 'could not be converted' ) !== false ) {
			if (
					isset($object[1]['class']) && is_subclass_of( $object[1]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/^Object of class ([\\w:\\\\]+) could not be converted to (\\w+).*?/', $errstr, $matches )
				)
			{

				foreach ( $object[0]['args'] as $arg ) {
					if ( $arg instanceof GenericObject && get_class($arg) == $matches[1] ) {
						$matches[1] = $arg->getName();
					}
				}
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
						PhpTagsException::NOTICE_OBJECT_CONVERTED,
						array( $matches[1], $matches[2] )
					);
				return true;
			}
		} elseif ( strpos( $errstr, 'Unexpected object type' ) !== false ) {
			if (
					isset($object[1]['class']) && is_subclass_of( $object[1]['class'], 'PhpTags\\GenericFunction') &&
					preg_match( '/Unexpected object type (\\w+)/', $errstr, $matches )
				)
			{
				$function = substr( $object[1]['function'] == '__callStatic' ? $object[2]['args'][0][0] : $object[2]['function'], 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::FATAL_UNEXPECTED_OBJECT_TYPE, array( $function, $matches[0] ) );
				var_dump( 'TRUE' );
				return true;
			}
		}
		return false;
	}

}
