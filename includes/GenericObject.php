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
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD, array($this->name, $subname) );
			case 'p_': // property
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY, array($this->name, $subname) );
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
				throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD, array($object, $subname) );
			case 'c_': // constant
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_CLASS_CONSTANT, array($object, $subname) );
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
		throw new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, array( $this->name, 'string' ) );
	}

	protected static function pushException( $exception, $arguments ) {
		Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( $exception, $arguments );
	}

	public static function checkArguments( $object, $method, $arguments, $expects = false ) {
		if ( false === $expects ) {
			return true;
		}

		$argCount = count( $arguments );
		if( true === isset( $expects[Hooks::EXPECTS_EXACTLY_PARAMETERS] ) && $argCount != $expects[Hooks::EXPECTS_EXACTLY_PARAMETERS] ) {
			Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER,
					array( "$object::$method", $expects[Hooks::EXPECTS_EXACTLY_PARAMETERS], $argCount )
				);
			return false;
		}

		$error = false;
		for ( $i = 0; $i < $argCount; $i++ ) {
			if ( true === isset( $expects[$i] ) ) {
				switch ( $expects[$i] ) {
					case Hooks::TYPE_NUMBER:
						if ( false === is_numeric( $arguments[$i] ) ) {
							$error = 'number';
							break 2;
						}
						break;
				}
			}
		}

		if ( $error === false ) {
			return true;
		}

		Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException(
				PhpTagsException::WARNING_EXPECTS_PARAMETER,
				array( "$object::$method", $i+1, $error, gettype( $arguments[$i] ) )
			);
		return false;
	}

}
