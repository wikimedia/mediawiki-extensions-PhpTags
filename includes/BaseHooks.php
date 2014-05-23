<?php
namespace PhpTags;

define( 'PHPTAGS_HOOK_INVOKE', '=' );
define( 'PHPTAGS_HOOK_GROUP', 'G' );
define( 'PHPTAGS_HOOK_VALUE_N', 'N' );
define( 'PHPTAGS_HOOK_VALUE_TYPE', 0 );
define( 'PHPTAGS_HOOK_NEED_LINK', 1 );
define( 'PHPTAGS_HOOK_RETURNS_ON_FAIL', 2 );
define( 'PHPTAGS_HOOK_CONTAINS_LINKS', 'L' );
define( 'PHPTAGS_HOOK_DEFAULT_VALUES', 'D' );

define( 'PHPTAGS_MIXED_LINKS', null );
define( 'PHPTAGS_NO_LINKS', false );
define( 'PHPTAGS_ONLY_LINKS', true );

define( 'PHPTAGS_TYPE_ARRAY', 'a' );
define( 'PHPTAGS_TYPE_BOOL', 'b' );
define( 'PHPTAGS_TYPE_FLOAT', 'f' );
define( 'PHPTAGS_TYPE_INT', 'i' );
define( 'PHPTAGS_TYPE_MIXED', 'm' );
define( 'PHPTAGS_TYPE_NUMBER', 'n' );
define( 'PHPTAGS_TYPE_STRING', 's' );
define( 'PHPTAGS_TYPE_VOID', 'v' );

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

	protected static function getClassName() {
		return get_called_class();
	}

	public static function getFunctionInfo( $name ) {
		if ( !isset(static::$functions_definition[$name]) ) {
			throw new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_INVALID_HOOK, array(static::getClassName(), $name) );
		}
		$definition = static::$functions_definition[$name];
		return isset( $definition[PHPTAGS_HOOK_CONTAINS_LINKS] ) ? $definition[PHPTAGS_HOOK_CONTAINS_LINKS] : false;
	}

	public static function onFunctionHook( $name, $params, &$transit ) {
		if ( !isset(static::$functions_definition[$name]) ) {
			throw new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_INVALID_HOOK, array(static::getClassName(), $name) );
		}
		$definition = static::$functions_definition[$name];
		$d = 0;
		for ( $i=0, $c=count($params); $i < $c; $i++ ) {
			$d = $i + 1;

			if ( isset($definition[$d]) ) {
				$valueType = $definition[$d];
			} elseif( isset($definition[PHPTAGS_HOOK_VALUE_N]) ) {
				$valueType = $definition[$d];
			} else {
				$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT, $name );
				return;
			}
			switch ( $valueType ) {
				case PHPTAGS_TYPE_ARRAY:
					if ( !is_array($params[$i]) ) {
						$transit[PHPTAGS_TRANSIT_EXCEPTION][] =	new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER, array($name, $d, 'array', gettype($params[$i])) );
						return $definition[0][PHPTAGS_HOOK_RETURNS_ON_FAIL];
					}
					break;
				case PHPTAGS_TYPE_INT:
				case PHPTAGS_TYPE_FLOAT:
					if ( is_object($params[$i]) ) {
						// @todo object name
						$transit[PHPTAGS_TRANSIT_EXCEPTION][] =	new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED, array('unknown', 'int') );
						unset( $params[$i] );
						$params[$i] = 1;
					}
					break;
				case PHPTAGS_TYPE_MIXED:
					break;
				default:
					// @todo Exception
					break;
			}
		}

		while ( !isset($definition[PHPTAGS_HOOK_INVOKE][$i]) ) {
			$d = $i + 1;
			if ( isset($definition[PHPTAGS_HOOK_DEFAULT_VALUES]) && array_key_exists($d, $definition[PHPTAGS_HOOK_DEFAULT_VALUES]) ) {
				$params[$i] = $definition[PHPTAGS_HOOK_DEFAULT_VALUES][$d];
				$i++;
				continue;
			}
			if ( isset($definition[PHPTAGS_HOOK_INVOKE][PHPTAGS_HOOK_VALUE_N]) ) {
				$d = PHPTAGS_HOOK_VALUE_N;
				break;
			}
			$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT, $name );
			return;
		}
		return static::$definition[PHPTAGS_HOOK_INVOKE][$d]( $params, $transit );
	}

}
