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
 * @file AnyObject.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class GenericObject {

	protected $name;
	protected $value;

	function __construct( $name, $value = null ) {
		$this->name = $name;
		$this->value = $value;
	}

	public function __call( $name, $arguments ) {
		$callType = substr( $name, 0, 2 );
		$subname = substr($name, 2);
		switch ( $callType ) {
			case 'm_': // method
				throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_METHOD, array($this->name, $subname) );
			case 'p_': // property
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_PROPERTY, array($this->name, $subname) );
				break;
			default:
				throw new \Exception( 'Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	public static function __callStatic( $name, $arguments ) {
		$object = array_pop( $arguments );
		$callType = substr( $name, 0, 2 );
		$subname = substr($name, 2);
		switch ( $callType ) {
			case 's_': // static method
				throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_METHOD, array($object, $subname) );
			case 'c_': // constant
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CLASS_CONSTANT, array($object, $subname) );
				break;
			default:
				throw new \Exception( 'Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	public function getMethodReferences( $method_name ) {
		return false;
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
		return 'object';
	}

}
