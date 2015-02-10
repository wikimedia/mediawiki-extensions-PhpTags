<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PhpTags;

/**
 * This class is generic phptags object in the extension PhpTags
 *
 * @file GenericObject.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class GenericObject implements \Iterator {

	protected $name;
	protected $value;

	function __construct( $name, $value = null ) {
		$this->name = $name;
		$this->value = $value;
	}

	public function __call( $name, $arguments ) {
		list ( $callType, $subname ) = explode( '_', $name, 2 );
		switch ( $callType ) {
			case 'm': // method
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD, array($this->name, $subname) );
			case 'p': // property
			case 'b':
				Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY, array($this->name, $subname) ) );
				break;
			default:
				throw new \Exception( $this->name . ': Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	public static function __callStatic( $name, $arguments ) {
		$object = \PhpTags\Hooks::$objectName;
		list ( $callType, $subname ) = explode( '_', $name, 2 );
		switch ( $callType ) {
			case 's': // static method
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD, array($object, $subname) );
			case 'q': // static property
			case 'd':
				throw new PhpTagsException( PhpTagsException::FATAL_ACCESS_TO_UNDECLARED_STATIC_PROPERTY, array($object, $subname) );
			case 'c': // constant
				Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_CLASS_CONSTANT, array($object, $subname) ) );
				break;
			case 'f': // function
				throw new PhpTagsException( PhpTagsException::FATAL_CALLFUNCTION_INVALID_HOOK, array( static::getClassName(), $subname) );
			default:
				throw new \Exception( $object . 'Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	public function isInstanceOf( $class_name ) {
		return self instanceof $class_name;
	}

	public function getValue() {
		return $this->value;
	}

	public function getName() {
		return $this->name;
	}

	/**
	 * It is alias for PHP __toString()
	 * @return string
	 */
	public function toString() {
		throw new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, array( $this->name, 'string' ) );
	}

	public static function getConstantValue( $constantName ) {
		throw new PhpTagsException( PhpTagsException::FATAL_CALLCONSTANT_INVALID_HOOK, array(static::getClassName(), $constantName) );
	}

	public static function getClassName() {
		return __CLASS__;
	}

	// do not allow illegal access to public properties from inside phptag code by using foreach operator
	public function current() {}
	public function key() {}
	public function next() {}
	public function rewind() {}
	public function valid() { return false; }

}
