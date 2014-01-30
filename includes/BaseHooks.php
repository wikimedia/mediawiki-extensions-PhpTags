<?php
namespace PhpTags;

define( 'PHPTAGS_HOOK_INVOKE', '=' );
define( 'PHPTAGS_HOOK_VALUE_TYPE', 0 );
define( 'PHPTAGS_HOOK_NEED_LINK', 1 );
define( 'PHPTAGS_HOOK_DEFAULT_VALUE', 2 );
define( 'PHPTAGS_HOOK_RETURNS_ON_FAIL', 1 );

define( 'PHPTAGS_TYPE_ARRAY', 'a' );
define( 'PHPTAGS_TYPE_INT', 'i' );

/**
 * This class is base for all constants, functions and objects hooks in the extension PhpTags
 *
 * @file BaseHooks.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
abstract class BaseHooks {
	protected static $functions_definition = array();

	abstract static function getClassName();

	public static function onFunctionHook( $name, $params ) {
		if ( !isset(static::$functions_definition[$name]) ) {
			return new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_INVALID_HOOK, array($name, static::getClassName()) );
		}
		$definition = static::$functions_definition[$name];
		$args = array();
		for ( $i=0, $c=count($params); $i < $c; $i++ ) {
			if ( !isset($definition[$i+1]) ) {
				Runtime::addException(
						new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT, $name )
					);
				return;
			}

			if ( $definition[$i+1][PHPTAGS_HOOK_NEED_LINK] ) {
				if ( $params[$i][PHPTAGS_STACK_COMMAND] != T_VARIABLE ) {
					return new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE );
				}
				$args[$i] = &$params[$i][PHPTAGS_STACK_RESULT];
			} else {
				$args[$i] = $params[$i][PHPTAGS_STACK_RESULT];
			}
			
			switch ( $definition[$i+1][PHPTAGS_HOOK_VALUE_TYPE] ) {
				case PHPTAGS_TYPE_ARRAY:
					if ( !is_array($args[$i]) ) {
						Runtime::addException(
								new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER, array($name, $i+1, 'array', gettype($args[$i])) )
							);
						return $definition[0][PHPTAGS_HOOK_RETURNS_ON_FAIL];
					}
					break;
				case PHPTAGS_TYPE_INT:
					if ( is_object($args[$i]) ) {
						Runtime::addException(
								 // @todo unknown
								new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED, array('unknown', 'int') )
							);
						unset( $args[$i] );
						$args[$i] = 1;
					}
					break;
				default:
					break;
			}
		}
		while ( !isset($definition[PHPTAGS_HOOK_INVOKE][$i]) ) {
			if ( !isset($definition[$i+1][PHPTAGS_HOOK_DEFAULT_VALUE]) ) {
				Runtime::addException(
						new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT, $name )
					);
				return;
			}
			$args[$i] = $definition[$i+1][PHPTAGS_HOOK_DEFAULT_VALUE];
			$i++;
		}
		return call_user_func_array( 'static::' . $definition[PHPTAGS_HOOK_INVOKE][$i], $args );
	}
}