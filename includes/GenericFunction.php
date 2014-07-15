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
		$functionName = substr( $name, 2 );
		throw new PhpTagsException( PhpTagsException::WARNING_CALLFUNCTION_INVALID_HOOK, array(get_called_class(), $functionName) );
	}

	public static function getConstantValue( $constantName ) {
		throw new PhpTagsException( PhpTagsException::WARNING_CALLCONSTANT_INVALID_HOOK, array(get_called_class(), $name) );
	}

	public static function getFunctionReferences( $function_name ) {
		return false;
	}

}
