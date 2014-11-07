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
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY, array($this->name, $subname) );
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
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_CLASS_CONSTANT, array($object, $subname) );
				break;
			default:
				throw new \Exception( $object . 'Call to undefined method ' . __CLASS__ . "::$name()" );
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
	 * @deprecated since version 3.3.0
	 * @return \Parser
	 */
	public function getParser() {
		return \PhpTags\Runtime::getParser();
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

	public static function checkArguments( $object, $method, &$arguments, $expects = false ) {
		if ( false === $expects ) {
			return true;
		}

		$argCount = count( $arguments );
		if( true === isset( $expects[Hooks::EXPECTS_EXACTLY_PARAMETERS] ) && $argCount != $expects[Hooks::EXPECTS_EXACTLY_PARAMETERS] ) {
			return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER,
					array( "$object::$method", $expects[Hooks::EXPECTS_EXACTLY_PARAMETERS], $argCount )
				);
		} else {
			if ( true == isset( $expects[Hooks::EXPECTS_MAXIMUM_PARAMETERS] ) && $argCount > $expects[Hooks::EXPECTS_MAXIMUM_PARAMETERS] ) {
				return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_AT_MOST_PARAMETERS,
					array( "$object::$method", $expects[Hooks::EXPECTS_MAXIMUM_PARAMETERS], $argCount )
				);
			}
			if ( true == isset( $expects[Hooks::EXPECTS_MINIMUM_PARAMETERS] ) && $argCount < $expects[Hooks::EXPECTS_MINIMUM_PARAMETERS] ) {
				return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETERS,
					array( "$object::$method", $expects[Hooks::EXPECTS_MINIMUM_PARAMETERS], $argCount )
				);
			}
		}

		$error = false;
		for ( $i = 0; $i < $argCount; $i++ ) {
			if ( true === isset( $expects[$i] ) ) {
				if ( is_numeric( $expects[$i] ) ) {
					switch ( $expects[$i] ) {
						case Hooks::TYPE_NUMERIC:
							if ( true === is_float( $arguments[$i] ) ) {
								$arguments[$i] = floatval( $arguments[$i] );
							} elseif( true === is_int( $arguments[$i] ) ) {
								$arguments[$i] = intval( $arguments[$i] );
							} else {
								$error = 'numeric';
								break 2;
							}
							break;
						case Hooks::TYPE_INT:
							if ( false === is_numeric($arguments[$i]) ) {
								$error = 'integer';
								break 2;
							}
							$arguments[$i] = (int)$arguments[$i];
							break;
						case Hooks::TYPE_FLOAT:
							if ( false === is_numeric($arguments[$i]) ) {
								$error = 'float';
								break 2;
							}
							$arguments[$i] = (float)$arguments[$i];
							break;
						case Hooks::TYPE_STRING:
							if ( false === is_string( $arguments[$i] ) ) {
								$error = 'string';
								break 2;
							}
							break;
						case Hooks::TYPE_ARRAY:
							if ( false === is_array( $arguments[$i] ) ) {
								$error = 'array';
								break 2;
							}
							break;
						case Hooks::TYPE_SCALAR:
							if ( false === is_scalar( $arguments[$i] ) ) {
								$error = 'scalar';
								break 2;
							}
							break;
						case Hooks::TYPE_NOT_OBJECT:
							if ( true === is_object( $arguments[$i] ) ) {
								$error = 'not object';
								break 2;
							}
							break;
						case Hooks::TYPE_BOOL:
							$arguments[$i] = (bool)$arguments[$i];
							break;
					}
				} elseif ( false === $arguments[$i] instanceof GenericObject || $arguments[$i]->name != $expects[$i] ) {
					$error = $expects[$i];
					break;
				}
			}
		}

		if ( $error === false ) {
			return true;
		}

		return new PhpTagsException(
				PhpTagsException::WARNING_EXPECTS_PARAMETER,
				array( "$object::$method", $i+1, $error, gettype( $arguments[$i] ) )
			);
	}

	// do not allow illegal access to public properties from inside phptag code by using foreach operator
	public function current() {}
	public function key() {}
	public function next() {}
	public function rewind() {}
	public function valid() { return false; }

}
