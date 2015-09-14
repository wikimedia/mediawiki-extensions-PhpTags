<?php
namespace PhpTagsObjects;

class PhpTagsTestClass extends \PhpTags\GenericObject {

	public static function __callStatic( $name, $arguments ) {
		return $name;
	}

	public function __call( $name, $arguments ) {
		return $name;
	}

	public static function getConstantValue( $constantName ) {
		return 'I am constant ' . $constantName;
	}

	public function m___construct() {
		return true;
	}
}
