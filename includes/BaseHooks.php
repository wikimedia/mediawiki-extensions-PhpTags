<?php
namespace PhpTags;

define( 'PHPTAGS_HOOK_INVOKE', '=' );
define( 'PHPTAGS_HOOK_GROUP', 'G' );
define( 'PHPTAGS_HOOK_VALUE_N', 'N' );
define( 'PHPTAGS_HOOK_VALUE_TYPE', 0 );
define( 'PHPTAGS_HOOK_NEED_LINK', 1 );
define( 'PHPTAGS_HOOK_DEFAULT_VALUE', 2 );
define( 'PHPTAGS_HOOK_RETURNS_ON_FAIL', 2 );

define( 'PHPTAGS_TYPE_ARRAY', 'a' );
define( 'PHPTAGS_TYPE_BOOL', 'b' );
define( 'PHPTAGS_TYPE_INT', 'i' );
define( 'PHPTAGS_TYPE_NUMBER', 'n' );
define( 'PHPTAGS_TYPE_VOID', 'v' );
define( 'PHPTAGS_TYPE_MIXED', 'm' );
define( 'PHPTAGS_TYPE_FLOAT', 'f' );

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

	public static function onFunctionHook( $name, $params, &$transit ) {
		if ( !isset(static::$functions_definition[$name]) ) {
			throw new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_INVALID_HOOK, array(static::getClassName(), $name) );
		}
		$definition = static::$functions_definition[$name];
		$args = array();
		$d = 0;
		for ( $i=0, $c=count($params); $i < $c; $i++ ) {
			$d = $i + 1;
			if ( !isset($definition[$d]) ) {
				if ( isset($definition[PHPTAGS_HOOK_VALUE_N]) ) {
					$d = PHPTAGS_HOOK_VALUE_N;
				} else {
					$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT, $name );
					return;
				}
			}

			if ( $definition[$d][PHPTAGS_HOOK_NEED_LINK] ) {
				if ( $params[$i][PHPTAGS_STACK_COMMAND] != T_VARIABLE ) {
					if ( $definition[$d][PHPTAGS_HOOK_NEED_LINK] === true ) {
						throw new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE );
					} else {
						$args[$i] = $params[$i][PHPTAGS_STACK_RESULT];
					}
				} else {
					if ( !array_key_exists($params[$i][PHPTAGS_STACK_PARAM], $transit[PHPTAGS_TRANSIT_VARIABLES]) ) {
						$transit[PHPTAGS_TRANSIT_VARIABLES][ $params[$i][PHPTAGS_STACK_PARAM] ] = null;
					}
					$args[$i] = &$transit[PHPTAGS_TRANSIT_VARIABLES][ $params[$i][PHPTAGS_STACK_PARAM] ];
					if ( isset($params[$i][PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]
						foreach ( $params[$i][PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
							if ( is_array($args[$i]) ) {
								if ( !array_key_exists($v[PHPTAGS_STACK_RESULT], $args[$i]) ) {
									$args[$i][ $v[PHPTAGS_STACK_RESULT] ] = null;
								}
								$args[$i] = &$args[$i][ $v[PHPTAGS_STACK_RESULT] ];
							} else {
								throw new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_VALUE_PASSED_BY_REFERENCE );
							}
						}
					}
				}
			} else {
				$args[$i] = $params[$i][PHPTAGS_STACK_RESULT];
			}

			switch ( $definition[$d][PHPTAGS_HOOK_VALUE_TYPE] ) {
				case PHPTAGS_TYPE_ARRAY:
					if ( !is_array($args[$i]) ) {
						$transit[PHPTAGS_TRANSIT_EXCEPTION][] =	new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_EXPECTS_PARAMETER, array($name, $i+1, 'array', gettype($args[$i])) );
						return $definition[0][PHPTAGS_HOOK_RETURNS_ON_FAIL];
					}
					break;
				case PHPTAGS_TYPE_INT:
				case PHPTAGS_TYPE_FLOAT:
					if ( is_object($args[$i]) ) {
						// @todo object name
						$transit[PHPTAGS_TRANSIT_EXCEPTION][] =	new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_OBJECT_CONVERTED, array('unknown', 'int') );
						unset( $args[$i] );
						$args[$i] = 1;
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
			if ( !isset($definition[$d]) || !array_key_exists(PHPTAGS_HOOK_DEFAULT_VALUE, $definition[$d]) ) {
				if ( !isset($definition[$d]) && isset($definition[PHPTAGS_HOOK_INVOKE][PHPTAGS_HOOK_VALUE_N]) ) {
					$d = PHPTAGS_HOOK_VALUE_N;
					break;
				}
				$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_WRONG_PARAMETER_COUNT, $name );
				return;
			}
			$args[$i] = $definition[$d][PHPTAGS_HOOK_DEFAULT_VALUE];
			$i++;
		}
		return static::$definition[PHPTAGS_HOOK_INVOKE][$d]( $args, $transit );
	}

}
