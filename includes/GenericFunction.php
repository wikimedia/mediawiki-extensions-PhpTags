<?php
namespace PhpTags;

define( 'PHPTAGS_HOOK_VALUE_N', 'N' );

/**
 * @todo Description of GenericFunction
 *
 * @file GenericFunction.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class GenericFunction {

	public static function __callStatic( $name, $arguments ) {
		$function_name = substr( $name, 2 );
		throw new PhpTagsException( PHPTAGS_EXCEPTION_WARNING_CALLFUNCTION_INVALID_HOOK, array(static::getClassName(), $function_name) );
	}

	public static function getFunctionReferences( $function_name ) {
		return false;
	}

}
