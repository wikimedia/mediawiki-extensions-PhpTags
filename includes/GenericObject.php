<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PhpTags;

use Exception;

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

	/**
	 * @param $name
	 * @param $arguments
	 * @throws PhpTagsException
	 * @throws Exception
	 */
	public function __call( $name, $arguments ) {
		switch ( $name[0] ) {
			case 'm': // method
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD );
			case 'p': // property
			case 'b':
				Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY ) );
				break;
			default:
				throw new Exception( $this->objectName . ': Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @throws PhpTagsException
	 * @throws Exception
	 */
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
				throw new Exception( Hooks::getCallInfo( Hooks::INFO_ORIGINAL_OBJECT_NAME ) . ': Call to undefined method ' . __CLASS__ . "::$name()" );
		}
	}

	/**
	 * @param $class_name
	 * @return bool
	 */
	public function isInstanceOf( $class_name ) {
		return self instanceof $class_name;
	}

	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns value for operator (array) and functions var_dump and etc...
	 * @since 5.9
	 * @return array
	 */
	public function getDumpValue() {
		return (array)('(' . Runtime::R_DUMP_OBJECT . ' <' . $this->getName() . '>)');
	}

	/**
	 * Returns object's name
	 * @return string
	 */
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
	 * @throws PhpTagsException
	 */
	public function toString() {
		// By default PhpTags objects have no __toString() method
		throw new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ $this->objectName, 'string' ] );
	}

	/**
	 * @param $constantName
	 * @throws PhpTagsException
	 */
	public static function getConstantValue( $constantName ) {
		throw new PhpTagsException( PhpTagsException::FATAL_CALLCONSTANT_INVALID_HOOK, get_called_class() );
	}

	/**
	 * @param $index
	 * @param $expect
	 * @param $value
	 * @return mixed
	 */
	protected static function pushExceptionExpectsParameter( $index, $expect, $value) {
		$type = $value instanceof self ? $value->getName() : gettype( $value );
		Runtime::pushException(	new PhpTagsException( PhpTagsException::WARNING_EXPECTS_PARAMETER, [ $index, $expect, $type ] ) );
		return Hooks::getCallInfo( Hooks::INFO_RETURNS_ON_FAILURE );
	}

	// It doesn't allow illegal access to public properties inside phptags code through using foreach operator
	#[\ReturnTypeWillChange]
	public function current() {}
	#[\ReturnTypeWillChange]
	public function key() {}
	#[\ReturnTypeWillChange]
	public function next() {}
	#[\ReturnTypeWillChange]
	public function rewind() {}
	#[\ReturnTypeWillChange]
	public function valid() { return false; }

}
