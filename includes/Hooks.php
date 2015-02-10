<?php
namespace PhpTags;

/**
 * @todo Description
 *
 * @file Hooks.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Hooks {

	const EXPECTS_EXACTLY_PARAMETERS = '=';
	const EXPECTS_MAXIMUM_PARAMETERS = '<';
	const EXPECTS_MINIMUM_PARAMETERS = '>';
	const EXPECTS_REFERENCE_PARAMETERS = 'r';
	const EXPECTS_VALUE_N = 'N';
	const TYPE_MIXED = 2;
	const TYPE_CALLBACK = 3;
	const TYPE_ARRAY = 4;
	const TYPE_STRING = 5;
	const TYPE_SCALAR = 6;
	const TYPE_INT = 7;
	const TYPE_FLOAT = 8;
	const TYPE_NOT_OBJECT = 9;
	const TYPE_BOOL = 10;

	const INFO_OBJECT_CONSTANTS = 'c';
	const INFO_OBJECT_PROPERTIES = 'p';
	const INFO_STATIC_PROPERTIES = 'q';
	const INFO_OBJECT_METHODS = 'm';
	const INFO_STATIC_METHODS = 's';

	/**
	 * When accessing static objects, the object name is placed here.
	 * It is necessary to be able to get the name of the object in the call handler
	 * @var string
	 */
	public static $objectName;

	/**
	 * Array of constant's values
	 * self::$constantValues[ constant_name ] = constant_value
	 * @var array
	 */
	 private static $constantValues = array();

	/**
	 * Array of constant's hooks
	 * self::$constants[ constant_name ] = class of constant for calling
	 * @var array
	 */
	private static $constants = array();

	/**
	 * Array of function's hooks
	 * self::$functions[ function_name ] = class of function for calling
	 * @var array
	 */
	private static $functions = array();

	/**
	 * Array of object's hooks
	 * @var array
	 */
	private static $objects = array();

	/**
	 * List of json files for loading
	 * @var array
	 */
	private static $jsonFiles = array();

	/**
	 * List of callback functions for filling constant values
	 * @var array
	 */
	private static $callbackConstants = array();

	/**
	 *
	 * @param callback $callback
	 * @param string $key
	 */
	public static function addCallbackConstantValues( $callback, $key ) {
		self::$callbackConstants[] = array( $callback, $key );
	}

	/**
	 *
	 * @param string $file
	 */
	public static function addJsonFile( $file, $key = '' ) {
		self::$jsonFiles[] = array( $file, $key . filemtime( $file ) );
	}

	public static function loadData() {
		$data = false;

		$cache = wfGetCache( CACHE_ANYTHING );
		$key = wfMemcKey( 'phptags', 'loadJsonFiles' );
		$cached = $cache->get( $key );
		if ( $cached !== false &&
				$cached['JSONLOADER'] === PHPTAGS_JSONLOADER_RELEASE  &&
				$cached['jsonFiles'] === self::$jsonFiles &&
				$cached['callbackConstants'] === self::$callbackConstants ) {
			\wfDebug( __METHOD__ . '() using cache: yes' );
			$data = $cached;
		}
		if ( $data === false ) {
			\wfDebug( __METHOD__ . '() using cache: NO' );
			$data = JsonLoader::load( self::$jsonFiles );
			$data['constantValues'] += self::loadConstantValues();
			$data['jsonFiles'] = self::$jsonFiles;
			$data['callbackConstants'] = self::$callbackConstants;
			$data['JSONLOADER'] = PHPTAGS_JSONLOADER_RELEASE;
			$cache->set( $key, $data );
		}
		self::$jsonFiles = null;
		self::$callbackConstants = null;
		self::$objects = $data['objects'];
		self::$functions = $data['functions'];
		self::$constants = $data['constants'];
		self::$constantValues = $data['constantValues'];
	}

	private static function loadConstantValues() {
		$return = array();
		foreach ( self::$callbackConstants as $value ) {
			$return = call_user_func( $value[0] ) + $return;
		}
		return $return;
	}

	/**
	 * Returns information about what is expected as argument, value or a variable reference.
	 * @param integer $number Ordinal number of the argument
	 * @param string $name Name of function or method
	 * @param mixed $object Object, name of object or false for functions
	 * @return mixed True if argument should be passed by reference, 1 if argument can be value
	 * @throws PhpTagsException
	 */
	public static function getReferenceInfo( $number, $type, $name, $object ) {
		if ( $type == PHPTAGS_HOOK_FUNCTION ) { // It is function
			if ( isset( self::$functions[$name][0][self::EXPECTS_REFERENCE_PARAMETERS] ) ) {
				$references = self::$functions[$name][0][self::EXPECTS_REFERENCE_PARAMETERS];
			} else {
				$references = false;
			}
		} else {
			if ( $object instanceof GenericObject ) { // Object has been created
				$className = self::getClassNameByObjectName( $object->getName() );
			} else { // It is static method of object
				$className = $object;
			}
			if ( isset( self::$objects[$className][2][$name][0][self::EXPECTS_REFERENCE_PARAMETERS] ) ) {
				$references = self::$objects[$className][2][$name][0][self::EXPECTS_REFERENCE_PARAMETERS];
			} else {
				$references = false;
			}
		}

		if ( $references === false || $references === true ) {
			return $references;
		} elseif ( is_array($references) ) {
			if ( isset( $references[$number] ) || array_key_exists( $number, $references ) ) {
				return $references[$number];
			} elseif ( isset($references[self::EXPECTS_VALUE_N]) || array_key_exists( self::EXPECTS_VALUE_N, $references ) ) {
				return $references[self::EXPECTS_VALUE_N];
			}
			return false;
		}
		return (bool)( 1 << $number & $references );
	}

	/**
	 * Get information about the function
	 * @staticvar array $functions Cache of functions
	 * @param string $name Name of function
	 * @return array 0 - name of class, 1 - references info
	 * @throws PhpTagsException
	 */
	private static function getFunctionClass( $name, $originalName ) {
		static $functions = array(); // cache of functions
		if( true === isset( $functions[$name] )  ) { // it is exists in cache
			return $functions[$name];
		}

		if ( false === isset( self::$functions[$name][2] ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_FUNCTION, $originalName );
		}

		$functionClassName = 'PhpTagsObjects\\' . self::$functions[$name][2];
		if( false === class_exists( $functionClassName ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_NONEXISTENT_HOOK_CLASS, array($originalName, $functionClassName) );
		}
		if ( false === is_subclass_of( $functionClassName, 'PhpTags\\GenericObject' ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_INVALID_HOOK_CLASS, array($originalName, $functionClassName) );
		}

		$functions[$name] = $functionClassName;
		return $functionClassName;
	}

	/**
	 * Call a hook of PhpTags
	 * @param int $type Hook type
	 * @param mixed $arguments Array or boolean false for the constants
	 * @param mixed $name Name of constant or function or method
	 * @param mixed $object boolean false for the functions or the object or object's name
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function callHook( $type, $arguments, $name, $object ) {
		switch ( $type ) {
			case PHPTAGS_HOOK_GET_CONSTANT: // Hook is a constant. Example: echo M_PI;
				return self::callGetConstant( $name );
			case PHPTAGS_HOOK_FUNCTION: // Hook is a function. Example: echo foo();
				return self::callFunction( $arguments, $name );
			case PHPTAGS_HOOK_GET_OBJECT_CONSTANT:
				return self::callGetObjectsConstant( $name, $object );
			case PHPTAGS_HOOK_GET_STATIC_PROPERTY: // Hook is a static property of a method. Example: echo FOO::bar;
				return self::callGetStaticProperty( $name, $object );
			case PHPTAGS_HOOK_GET_OBJECT_PROPERTY: // Hook is a property of a method. Example: $foo = new Foo(); echo $foo->bar;
				return self::callGetObjectsProperty( $name, $object );
			case PHPTAGS_HOOK_SET_STATIC_PROPERTY: // Example FOO::bar = true;
				return self::callSetStaticProperty( $name, $object, $arguments );
			case PHPTAGS_HOOK_SET_OBJECT_PROPERTY: // Example: $foo = new Foo(); $foo->bar = true;
				return self::callSetObjectsProperty( $name, $object, $arguments );
			case PHPTAGS_HOOK_STATIC_METHOD: // Example: FOO::bar()
				return self::callStaticMethod( $arguments, $name, $object );
			case PHPTAGS_HOOK_OBJECT_METHOD: // Example: $foo = new Foo(); $foo->bar();
				return self::callObjectsMethod( $arguments, $name, $object );
		}
	}

	/**
	 * Get value of the constant
	 * @param string $name Name of the constant
	 * @return mixed
	 */
	private static function callGetConstant( $name ) {
		static $constants = array(); // cache of called constants

		if ( isset ( self::$constants[$name] ) ) {
			$className = 'PhpTagsObjects\\' . self::$constants[$name];

			if( false === isset( $constants[$className] )  ) { // it is not exists in cache
				// Need to check this class
				if( false === class_exists( $className ) ) {
					throw new PhpTagsException( PhpTagsException::FATAL_NONEXISTENT_CONSTANT_CLASS, array($name, $className) );
				}
				if ( false === is_subclass_of( $className, 'PhpTags\\GenericObject' ) ) {
					throw new PhpTagsException( PhpTagsException::FATAL_INVALID_CONSTANT_CLASS, array($name, $className) );
				}
				$constants[$className] = true; // add to cache
			}
			return $className::getConstantValue( $name );
		} elseif ( isset( self::$constantValues[$name] ) || array_key_exists( $name, self::$constantValues ) ) {
			return self::$constantValues[$name];
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_CONSTANT, $name ) );
		return $name;
	}

	/**
	 * Call the function's hook
	 * @param array $arguments List of the arguments
	 * @param string $name Name of the function
	 * @return mixed
	 */
	private static function callFunction( $arguments, $name ) {
		$funcName = strtolower( $name );
		$funcClass = self::getFunctionClass( $funcName, $name );
		$e = self::checkFunctionArguments( $funcName, $name, $arguments );
		if ( $e === true ) {
			ksort( $arguments );
			return call_user_func_array( array($funcClass, "f_$name"), $arguments );
		}
		return self::getReturnsOnFailure( $funcName, $e ); // returns onfailure field from json
	}

	/**
	 * Call the method of object based on class \PhpTags\GenericObject
	 * @param array $arguments List of the arguments
	 * @param string $name Name of the method
	 * @param mixed $object Name of the object or the object of class \PhpTags\GenericObject
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callObjectsMethod( $arguments, $name, $object ) {
		if ( $object instanceof GenericObject ) {
			ksort( $arguments );
			$e = self::checkObjectArguments( $object->getName(), $name, false, $arguments );
			if ( $e === true ) {
				return call_user_func_array( array($object, "m_$name"), $arguments );
			}
			if ( $e instanceof \PhpTags\PhpTagsException ) {
				Runtime::pushException( $e );
			}
			return null;
		}
		throw new PhpTagsException( PhpTagsException::FATAL_CALL_FUNCTION_ON_NON_OBJECT, $name );
	}

	private static function callStaticMethod( $arguments, $name, $object ) {
		self::$objectName = $object;
		ksort( $arguments );
		$e = self::checkObjectArguments( $object, $name, true, $arguments );
		if ( $e === true ) {
			$className = self::getClassNameByObjectName( $object );
			return call_user_func_array( array($className, "s_$name"), $arguments );
		}
		if ( $e instanceof \PhpTags\PhpTagsException ) {
			Runtime::pushException( $e );
		}
		return null;
	}

	public static function callGetObjectsConstant( $name, $object ) {
		self::$objectName = $object;
		$className = self::getClassNameByObjectName( $object );
		$handler = "c_$name";
		return $className::$handler();
	}

	/**
	 * Call the property of object based on class \PhpTags\GenericObject
	 * @param string $name Name of the property
	 * @param mixed $object Name of the object or the object of class \PhpTags\GenericObject
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function callGetObjectsProperty( $name, $object ) {
		if ( $object instanceof GenericObject ) {
			$objectName = $object->getName();
			$handler = "p_$name";
			return $object->$handler();
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_GET_PROPERTY_OF_NON_OBJECT, null ) );
	}

	public static function callGetStaticProperty( $name, $object ) {
		self::$objectName = $object;
		$className = self::getClassNameByObjectName( $object );
		$handler = "q_$name";
		return $className::$handler();
	}

	public static function callSetObjectsProperty( $name, $object, $value ) {
		if ( $object instanceof GenericObject ) {
			$handler = "b_$name";
			$validValue = self::getValidPropertyValue( $object->getName(), $name, false, $value );
			if ( $validValue instanceof PhpTagsException ) {
				Runtime::pushException( $validValue );
				return;
			}
			return $object->$handler( $validValue );
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_ATTEMPT_TO_ASSIGN_PROPERTY, null ) );
	}

	public static function callSetStaticProperty( $name, $object, $value ) {
		self::$objectName = $object;
		$className = self::getClassNameByObjectName( $object );
		$handler = "d_$name";
		$validValue = self::getValidPropertyValue( $object, $name, true, $value );
		if ( $validValue instanceof PhpTagsException ) {
			Runtime::pushException( $validValue );
			return;
		}
		return $className::$handler( $validValue );
	}

	/**
	 *
	 * @param array $arguments
	 * @param string $objectName
	 * @param bool $showException
	 * @return \PhpTags\GenericObject
	 */
	public static function createObject( $arguments, $objectName, $showException = true ) {
		$className = self::getClassNameByObjectName( $objectName );
		$object = new $className( $objectName );
		ksort( $arguments );

		try {
			$e = self::checkObjectArguments( $objectName, '__construct', false, $arguments );
			if ( $e === true && call_user_func_array( array($object, 'm___construct'), $arguments ) === true ) {
				return $object;
			}
			if ( $e instanceof \PhpTags\PhpTagsException ) {
				Runtime::pushException( $e );
			}
		} catch ( \PhpTags\PhpTagsException $exc) {
			Runtime::pushException( $exc );
		} catch ( \Exception $exc ) {
			if ( $showException ) {
				list(, $message) = explode( ': ', $exc->getMessage(), 2 );
				if ( $message == '' ) {
					$message = $exc->getMessage();
				}
				Runtime::pushException( new PhpTagsException( PhpTagsException::FATAL_OBJECT_NOT_CREATED, array( $objectName, $message ) ) );
			}
		}
		return false;
	}

	private static function getClassNameByObjectName( $objectName ) {
		static $cache = array();
		$name = strtolower( $objectName );
		if ( true === isset( $cache[$name] ) ) {
			return $cache[$name];
		}

		if ( false === isset( self::$objects[$name] ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CLASS_NOT_FOUND, $objectName );
		}

		$className = '\\PhpTagsObjects\\' . self::$objects[$name][0];
		if ( false === class_exists( $className ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CREATEOBJECT_INVALID_CLASS, array($className, $name) );
		}
		if ( false === is_subclass_of( $className, 'PhpTags\\GenericObject' ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_MUST_EXTENDS_GENERIC, $className );
		}

		$cache[$name] = $className;
		return $className;
	}

	/**
	 * Create new PhpTags class $name with value $value
	 * @param string $name
	 * @param array $value
	 * @return \PhpTags\GenericObject
	 */
	public static function getObjectWithValue( $name, $value ) {
		$className = self::getClassNameByObjectName( $name );
		return new $className( $name, $value );
	}

	public static function getDefinedFunctions() {
		return self::$functions;
	}

	private static function checkFunctionArguments( $funcName, $name, $arguments ) {
		if ( isset( self::$functions[$funcName] ) ) {
			$f = self::$functions[$funcName];
			$expects = $f[0];
			$originalName = $f[1];
		} else {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_FUNCTION, $name );
		}
		return self::checkArguments( $originalName, $expects, $arguments );
	}

	/*
	 *
	 */
	private static function checkObjectArguments( $objectName, $methodName, $isStatic, $arguments ) {
		$object = strtolower( $objectName );
		$method = strtolower( $methodName );
		$point = $isStatic === false ? 1 : 2;
		if ( isset(self::$objects[$object][$point][$method]) ) {
			$m = self::$objects[$object][$point][$method];
		} elseif ( $isStatic === false && isset(self::$objects[$object][2][$method]) ) { // static method called nonstatic
			$m = self::$objects[$object][2][$method];
		} elseif ( $isStatic !== false && isset(self::$objects[$object][1][$method]) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_NONSTATIC_CALLED_STATICALLY, array( self::getOriginalObjectName($object), $methodName) );
		} else {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD, array( self::getOriginalObjectName($object), $methodName) );
		}
		$expects = $m[0];
		$originalName = self::$objects[$object][5] . '::' . $m[1];
		return self::checkArguments( $originalName, $expects, $arguments );
	}

	private static function checkArguments( $originalName, $expects, $arguments ) {
		$argCount = count( $arguments );
		if( true === isset( $expects[self::EXPECTS_EXACTLY_PARAMETERS] ) && $argCount != $expects[self::EXPECTS_EXACTLY_PARAMETERS] ) {
			return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER,
					array( $originalName, $expects[self::EXPECTS_EXACTLY_PARAMETERS], $argCount )
				);
		} else {
			if ( true == isset( $expects[self::EXPECTS_MAXIMUM_PARAMETERS] ) && $argCount > $expects[self::EXPECTS_MAXIMUM_PARAMETERS] ) {
				return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_AT_MOST_PARAMETERS,
					array( $originalName, $expects[self::EXPECTS_MAXIMUM_PARAMETERS], $argCount )
				);
			}
			if ( true == isset( $expects[self::EXPECTS_MINIMUM_PARAMETERS] ) && $argCount < $expects[self::EXPECTS_MINIMUM_PARAMETERS] ) {
				return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETER,
					array( $originalName, $expects[self::EXPECTS_MINIMUM_PARAMETERS], $argCount )
				);
			}
		}

		$error = false;
		for ( $i = 0; $i < $argCount; $i++ ) {
			$exp = isset( $expects[$i] ) ? $expects[$i] : $expects[self::EXPECTS_VALUE_N];
			switch ( $exp ) {
				case self::TYPE_INT:
					if ( false === is_int($arguments[$i]) ) {
						$error = 'integer';
						break 2;
					}
					break;
				case self::TYPE_FLOAT:
					if ( false === is_numeric($arguments[$i]) ) {
						$error = 'float';
						break 2;
					}
					break;
				case self::TYPE_STRING:
					if ( false === is_string( $arguments[$i] ) ) {
						$error = 'string';
						break 2;
					}
					break;
				case self::TYPE_ARRAY:
					if ( false === is_array( $arguments[$i] ) ) {
						$error = 'array';
						break 2;
					}
					break;
				case self::TYPE_SCALAR:
					if ( false === is_scalar( $arguments[$i] ) ) {
						$error = 'scalar';
						break 2;
					}
					break;
				case self::TYPE_NOT_OBJECT:
					if ( true === is_object( $arguments[$i] ) ) {
						$error = 'not object';
						break 2;
					}
					break;
				case self::TYPE_BOOL:
					if ( false === is_bool( $arguments[$i] ) ) {
						$error = 'boolean';
						break 2;
					}
					break;
				case self::TYPE_MIXED:
					break;
				default:
					if ( false === $arguments[$i] instanceof GenericObject || $arguments[$i]->getName() != $exp ) {
						$error = $exp;
						break 2;
					}
					break;
			}
		}

		if ( $error === false ) {
			return true;
		}

		return new PhpTagsException(
				PhpTagsException::WARNING_EXPECTS_PARAMETER,
				array( $originalName, $i+1, $error, gettype( $arguments[$i] ) )
			);
	}

	private static function getValidPropertyValue( $objectName, $propertyName, $isStatic, $value ) {
		$object = strtolower( $objectName );
		$property = strtolower( $propertyName );
		$point = $isStatic === false ? 3 : 4;
		if ( isset(self::$objects[$object][$point][$property]) ) {
			$p = self::$objects[$object][$point][$property];
			$expect = $p[0];
			$originalName = $p[1];
		} elseif( $isStatic === false ) {
			return new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY, array( self::getOriginalObjectName( $object ), $propertyName ) );
		} else {
			throw new PhpTagsException( PhpTagsException::FATAL_ACCESS_TO_UNDECLARED_STATIC_PROPERTY, array( self::getOriginalObjectName( $object ), $propertyName ) );
		}

		if ( $value === null ) {
			return null;
		}

		$error = false;
		switch ( $expect ) {
			case self::TYPE_INT:
				if ( is_numeric( $value ) ) {
					return (int)$value;
				}
				$error = 'integer';
				break;
			case self::TYPE_FLOAT:
				if ( is_numeric( $value ) ) {
					return (float)$value;
				}
				$error = 'float';
				break;
			case self::TYPE_STRING:
				if ( is_string( $value ) ) {
					return (string)$value;
				}
				$error = 'string';
				break;
			case self::TYPE_ARRAY:
				if ( false === is_array( $value ) ) {
					$error = 'array';
				}
				break;
			case self::TYPE_SCALAR:
				if ( false === is_scalar( $value ) ) {
					$error = 'scalar';
				}
				break;
			case self::TYPE_NOT_OBJECT:
				if ( is_object( $value ) ) {
					$error = 'not object';
				}
				break;
			case self::TYPE_BOOL:
				return (bool)$value;
			case self::TYPE_MIXED:
				break;
			default:
				if ( false === $value instanceof GenericObject || $value->getName() != $expect ) {
					$error = $expect;
				}
				break;
		}

		if ( $error === false ) {
			return $value;
		}

		return new \PhpTags\PhpTagsException(
				\PhpTags\PhpTagsException::NOTICE_EXPECTS_PROPERTY,
				array( "{$object}->{$originalName}", $error, gettype( $value ) )
			);
	}

	public static function getReturnsOnFailure( $funcName, $e = null ) {
		if ( $e instanceof \PhpTags\PhpTagsException ) {
			Runtime::pushException( $e );
		}
		return self::$functions[$funcName][3];
	}

	public static function getOriginalObjectName( $object ) {
		return self::$objects[$object][5];
	}

}
