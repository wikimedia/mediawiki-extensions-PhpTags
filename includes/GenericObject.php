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

	protected $objectName;
	protected $value;

	/**
	 * The object description key in \PhpTags\Hooks::$objects
	 * @var string
	 */
	protected $objectKey;

	/**
	 * Constructor of new PhpTags object
	 * @param string $objectName Original object name
	 * @param string $objectKey The object description key in \PhpTags\Hooks::$objects
	 * @param mixed $value The initial value for new object, default is NULL
	 */
	function __construct( $objectName, $objectKey, $value = null ) {
		$this->objectName = $objectName;
		$this->objectKey = $objectKey;
		$this->value = $value;
	}

	public function __call( $name, $arguments ) {
		switch ( $name[0] ) {
			case 'm': // method
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD );
			case 'p': // property
			case 'b':
				Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY ) );
				break;
			default:
				throw new \Exception( $this->objectName . ': Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	public static function __callStatic( $name, $arguments ) {
		switch ( $name[0] ) {
			case 's': // static method
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD );
			case 'q': // static property
			case 'd':
				throw new PhpTagsException( PhpTagsException::FATAL_ACCESS_TO_UNDECLARED_STATIC_PROPERTY );
			case 'c': // constant
				Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_CLASS_CONSTANT ) );
				break;
			case 'f': // function
				throw new PhpTagsException( PhpTagsException::FATAL_CALLFUNCTION_INVALID_HOOK, get_called_class() );
			default:
				throw new \Exception( Hooks::getCallInfo( Hooks::INFO_ORIGINAL_OBJECT_NAME ) . ': Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	public function isInstanceOf( $class_name ) {
		return self instanceof $class_name;
	}

	public function getValue() {
		return $this->value;
	}

	public function getName() {
		return $this->objectName;
	}

	/**
	 * Retunrs key of object description in \PhpTags\Hooks::$objects
	 * @return string
	 */
	public function getObjectKey() {
		return $this->objectKey;
	}

	/**
	 * It does same as native PHP method __toString()
	 * @return string
	 */
	public function toString() {
		// By default PhpTags objects have no __toString() method
		throw new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, array($this->objectName, 'string') );
	}

	public static function getConstantValue( $constantName ) {
		throw new PhpTagsException( PhpTagsException::FATAL_CALLCONSTANT_INVALID_HOOK, get_called_class() );
	}

	/**
	 * @deprecated since you should use get_called_class()
	 */
	public static function getClassName() {
		return get_called_class();
	}

	protected static function pushExceptionExpectsParameter( $index, $expect, $value) {
		$type = $value instanceof self ? $value->getName() : gettype( $value );
		Runtime::pushException(	new PhpTagsException( PhpTagsException::WARNING_EXPECTS_PARAMETER, array($index, $expect, $type) ) );
		return \PhpTags\Hooks::getCallInfo( \PhpTags\Hooks::INFO_RETURNS_ON_FAILURE );
	}

	// It doesn't allow illegal access to public properties inside phptag code by using foreach operator
	public function current() {}
	public function key() {}
	public function next() {}
	public function rewind() {}
	public function valid() { return false; }

}
