<?php
namespace PhpTagsObjects;

use PhpTags\GenericObject;

class PhpTagsTestClass extends GenericObject {

	public static function __callStatic( $name, $arguments ) {
		return $name;
	}

	public function __call( $name, $arguments ) {
		return $name;
	}

	/**
	 * @param string $constantName
	 * @return string
	 */
	public static function getConstantValue( $constantName ) {
		return 'I am constant ' . $constantName;
	}

	public function m___construct() {
		return true;
	}
}
