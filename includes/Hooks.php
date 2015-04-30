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
	const TYPE_NONOBJECT = 9;
	const TYPE_BOOL = 10;

	const INFO_HOOK_TYPE = 0;
	const INFO_CALLED_HOOK_NAME = 1;
	const INFO_ORIGINAL_HOOK_NAME = 2;
	const INFO_CALLED_OBJECT_NAME = 3;
	const INFO_ORIGINAL_OBJECT_NAME = 4;
	const INFO_ORIGINAL_FULL_NAME = 5;
	const INFO_RETURNS_ON_FAILURE = 6;

	/**
	 * Value passed from class Runtime
	 * Used for function getCallInfo()
	 * @var array
	 */
	private static $value;

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
			\wfDebug( '[phptags] ' . __METHOD__ . '() using cache: yes' );
			$data = $cached;
		}
		if ( $data === false ) {
			\wfDebug( '[phptags] ' .  __METHOD__ . '() using cache: NO' );
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
	 * Hook type in $value[PHPTAGS_STACK_HOOK_TYPE]
	 * Function or method name in $value[PHPTAGS_STACK_PARAM]
	 * Object name in $value[PHPTAGS_STACK_PARAM_3][PHPTAGS_STACK_PARAM_3] if [PHPTAGS_STACK_PARAM_3] is not FALSE
	 * @param integer $number // ordinal number of the argument, zero based
	 * @param array $value
	 * @return mixed True if argument should be passed by reference, 1 if argument can be value
	 * @throws PhpTagsException
	 */
	public static function getReferenceInfo( $number, $value ) {
		self::$value = $value;
		$name = $value[PHPTAGS_STACK_PARAM]; // Function or method name

		if ( $value[PHPTAGS_STACK_HOOK_TYPE] === PHPTAGS_HOOK_FUNCTION ) { // Hook type is function
			if ( isset( self::$functions[$name][0][self::EXPECTS_REFERENCE_PARAMETERS] ) ) {
				$references = self::$functions[$name][0][self::EXPECTS_REFERENCE_PARAMETERS];
			} else {
				$references = false;
			}
		} else { // Hook type is object
			$object = $value[PHPTAGS_STACK_PARAM_3] === false ? false : $value[PHPTAGS_STACK_PARAM_3][PHPTAGS_STACK_PARAM_3];
			unset( self::$value[PHPTAGS_STACK_PARAM_3] ); // unlink reference before assign a value!!!
			self::$value[PHPTAGS_STACK_PARAM_3] = $object; // self::getCallInfo() waits $object in PHPTAGS_STACK_PARAM_3
			if ( $object instanceof GenericObject ) { // Object has been created
				$className = self::getClassNameByObjectName( $object->getObjectKey() );
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
	 * @param string $funcKey Name of function
	 * @return array 0 - name of class, 1 - references info
	 * @throws PhpTagsException
	 */
	private static function getFunctionClass( $funcKey ) {
		static $functions = array(); // cache of functions
		if( true === isset( $functions[$funcKey] )  ) { // it is exists in cache
			return $functions[$funcKey];
		}

		if ( false === isset( self::$functions[$funcKey][2] ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_FUNCTION );
		}

		$functionClassName = 'PhpTagsObjects\\' . self::$functions[$funcKey][2];
		if( false === class_exists( $functionClassName ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_NONEXISTENT_HOOK_CLASS, $functionClassName );
		}
		if ( false === is_subclass_of( $functionClassName, 'PhpTags\\GenericObject' ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_INVALID_HOOK_CLASS, $functionClassName );
		}

		$functions[$funcKey] = $functionClassName;
		return $functionClassName;
	}

	/**
	 * Call a hook of PhpTags
	 * Hook type in $value[PHPTAGS_STACK_HOOK_TYPE]
	 * Function or method name in $value[PHPTAGS_STACK_PARAM]
	 * Function or method arguments in $value[PHPTAGS_STACK_PARAM_2]
	 * Object or object name in $value[PHPTAGS_STACK_PARAM_3]
	 * @param array $value
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function callHook( $value ) {
		self::$value = $value; // Remember to function getCallInfo
		$name = $value[PHPTAGS_STACK_PARAM];

		switch ( $value[PHPTAGS_STACK_HOOK_TYPE] ) {
			case PHPTAGS_HOOK_GET_CONSTANT: // Hook is a constant. Example: echo M_PI;
				return self::callGetConstant( $name );
			case PHPTAGS_HOOK_FUNCTION: // Hook is a function. Example: echo foo();
				return self::callFunction( $value[PHPTAGS_STACK_PARAM_2], $name );
			case PHPTAGS_HOOK_GET_OBJECT_CONSTANT: // Hook is a object constant. Example: Foo::MY_BAR
				return self::callGetObjectsConstant( $name, $value[PHPTAGS_STACK_PARAM_3] );
			case PHPTAGS_HOOK_GET_STATIC_PROPERTY: // Hook is a static property of a method. Example: echo FOO::$bar;
				return self::callGetStaticProperty( $name, $value[PHPTAGS_STACK_PARAM_3] );
			case PHPTAGS_HOOK_GET_OBJECT_PROPERTY: // Hook is a property of a method. Example: $foo = new Foo(); echo $foo->bar;
				return self::callGetObjectsProperty( $name, $value[PHPTAGS_STACK_PARAM_3] );
			case PHPTAGS_HOOK_SET_STATIC_PROPERTY: // Example FOO::$bar = true;
				return self::callSetStaticProperty( $name, $value[PHPTAGS_STACK_PARAM_3], $value[PHPTAGS_STACK_PARAM_2] );
			case PHPTAGS_HOOK_SET_OBJECT_PROPERTY: // Example: $foo = new Foo(); $foo->bar = true;
				return self::callSetObjectsProperty( $name, $value[PHPTAGS_STACK_PARAM_3], $value[PHPTAGS_STACK_PARAM_2] );
			case PHPTAGS_HOOK_STATIC_METHOD: // Example: FOO::bar()
				return self::callStaticMethod( $value[PHPTAGS_STACK_PARAM_2], $name, $value[PHPTAGS_STACK_PARAM_3] );
			case PHPTAGS_HOOK_OBJECT_METHOD: // Example: $foo = new Foo(); $foo->bar();
				return self::callObjectsMethod( $value[PHPTAGS_STACK_PARAM_2], $name, $value[PHPTAGS_STACK_PARAM_3] );
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
					throw new PhpTagsException( PhpTagsException::FATAL_NONEXISTENT_CONSTANT_CLASS, $className );
				}
				if ( false === is_subclass_of( $className, 'PhpTags\\GenericObject' ) ) {
					throw new PhpTagsException( PhpTagsException::FATAL_INVALID_CONSTANT_CLASS, $className );
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
	 * @param string $calledFuncName Name of the function
	 * @return mixed
	 */
	private static function callFunction( $arguments, $calledFuncName ) {
		$funcKey = strtolower( $calledFuncName );
		$funcClass = self::getFunctionClass( $funcKey );
		$e = self::checkFunctionArguments( $funcKey, $arguments );
		if ( $e === true ) {
			ksort( $arguments );
			return call_user_func_array( array($funcClass, "f_$calledFuncName"), $arguments );
		}
		Runtime::pushException( $e );
		return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
	}

	/**
	 * Call the method of object based on class \PhpTags\GenericObject
	 * @param array $arguments List of the arguments
	 * @param string $calledMethodName Name of the method
	 * @param mixed $calledObject Name of the object or the object of class \PhpTags\GenericObject
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callObjectsMethod( $arguments, $calledMethodName, $calledObject ) {
		if ( $calledObject instanceof GenericObject ) {
			ksort( $arguments );
			$e = self::checkObjectArguments( $calledObject->getObjectKey(), $calledMethodName, false, $arguments );
			if ( $e === true ) {
				return call_user_func_array( array($calledObject, "m_$calledMethodName"), $arguments );
			}
			if ( $e instanceof \PhpTags\PhpTagsException ) {
				Runtime::pushException( $e );
			}
			Runtime::pushException( $e );
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		throw new PhpTagsException( PhpTagsException::FATAL_CALL_FUNCTION_ON_NON_OBJECT );
	}

	private static function callStaticMethod( $arguments, $calledMethodName, $calledObject ) {
		ksort( $arguments );

		if ( $calledObject instanceof GenericObject ) {
			$objectKey = $calledObject->getObjectKey();
		} else {
			$objectKey = strtolower( $calledObject );
		}

		$e = self::checkObjectArguments( $objectKey, $calledMethodName, true, $arguments );

		if ( $e === true ) {
			$className = self::getClassNameByObjectName( $objectKey );
			return call_user_func_array( array($className, "s_$calledMethodName"), $arguments );
		}
		if ( $e instanceof \PhpTags\PhpTagsException ) {
			Runtime::pushException( $e );
		}
		Runtime::pushException( $e );
		return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
	}

	public static function callGetObjectsConstant( $name, $object ) {
		if ( $object instanceof GenericObject ) {
			$objectKey = $object->getObjectKey();
		} else {
			$objectKey = strtolower( $object );
		}

		$className = self::getClassNameByObjectName( $objectKey );
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
			$handler = "p_$name";
			return $object->$handler();
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_GET_PROPERTY_OF_NON_OBJECT ) );
	}

	public static function callGetStaticProperty( $name, $object ) {
		if ( $object instanceof GenericObject ) {
			$objectKey = $object->getObjectKey();
		} else {
			$objectKey = strtolower( $object );
		}

		$className = self::getClassNameByObjectName( $objectKey );
		$handler = "q_$name";
		return $className::$handler();
	}

	public static function callSetObjectsProperty( $name, $object, $value ) {
		if ( $object instanceof GenericObject ) {
			$handler = "b_$name";
			$validValue = self::getValidPropertyValue( $object->getObjectKey(), $name, false, $value );
			if ( $validValue instanceof PhpTagsException ) {
				Runtime::pushException( $validValue );
				return;
			}
			return $object->$handler( $validValue );
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_ATTEMPT_TO_ASSIGN_PROPERTY ) );
	}

	public static function callSetStaticProperty( $name, $object, $value ) {
		if ( $object instanceof GenericObject ) {
			$objectKey = $object->getObjectKey();
		} else {
			$objectKey = strtolower( $object );
		}

		$className = self::getClassNameByObjectName( $objectKey );
		$handler = "d_$name";
		$validValue = self::getValidPropertyValue( $objectKey, $name, true, $value );
		if ( $validValue instanceof PhpTagsException ) {
			Runtime::pushException( $validValue );
			return;
		}
		return $className::$handler( $validValue );
	}

	/**
	 *
	 * @param array $arguments
	 * @param string $calledObjectName
	 * @param bool $showException
	 * @return \PhpTags\GenericObject
	 */
	public static function createObject( $arguments, $calledObjectName, $showException = true ) {
		self::$value = array(
			PHPTAGS_STACK_HOOK_TYPE => PHPTAGS_HOOK_NEW_OBJECT,
			PHPTAGS_STACK_PARAM => '__construct',
			PHPTAGS_STACK_PARAM_2 => $arguments,
			PHPTAGS_STACK_PARAM_3 => $calledObjectName,
			);

		$objectKey = strtolower( $calledObjectName );
		$className = self::getClassNameByObjectName( $objectKey );
		$newObject = new $className( $calledObjectName, $objectKey );
		ksort( $arguments );

		try {
			$e = self::checkObjectArguments( $objectKey, '__construct', false, $arguments );
			if ( $e === true && call_user_func_array( array($newObject, 'm___construct'), $arguments ) === true ) {
				return $newObject;
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
				Runtime::pushException( new PhpTagsException( PhpTagsException::FATAL_OBJECT_NOT_CREATED, $message ) );
			}
		}
		return false;
	}

	private static function getClassNameByObjectName( $objectKey ) {
		static $cache = array();
		if ( isset( $cache[$objectKey] ) ) {
			return $cache[$objectKey];
		}

		if ( false === isset( self::$objects[$objectKey] ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CLASS_NOT_FOUND, self::getCallInfo( self::INFO_CALLED_OBJECT_NAME ) );
		}

		$className = '\\PhpTagsObjects\\' . self::$objects[$objectKey][0];
		if ( false === class_exists( $className ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CREATEOBJECT_INVALID_CLASS, $className );
		}
		if ( false === is_subclass_of( $className, 'PhpTags\\GenericObject' ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_MUST_EXTENDS_GENERIC, $className );
		}

		$cache[$objectKey] = $className;
		return $className;
	}

	/**
	 * Create new PhpTags class $objectName with value $value
	 * @param string $objectName
	 * @param array $value
	 * @return \PhpTags\GenericObject
	 */
	public static function getObjectWithValue( $objectName, $value ) {
		$objectKey = strtolower( $objectName );
		$className = self::getClassNameByObjectName( $objectKey );
		return new $className( $objectName, $objectKey, $value );
	}

	public static function getDefinedFunctions() {
		return self::$functions;
	}

	private static function checkFunctionArguments( $funcKey, $arguments ) {
		if ( isset( self::$functions[$funcKey] ) ) {
			return self::checkArguments( self::$functions[$funcKey][0], $arguments );
		}
		throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_FUNCTION );
	}

	/*
	 *
	 */
	private static function checkObjectArguments( $objectKey, $calledMethodName, $isStatic, $arguments ) {
		$methodKey = strtolower( $calledMethodName );
		$point = $isStatic === false ? 1 : 2;
		if ( isset(self::$objects[$objectKey][$point][$methodKey]) ) {
			$m = self::$objects[$objectKey][$point][$methodKey];
		} elseif ( $isStatic === false && isset(self::$objects[$objectKey][2][$methodKey]) ) { // static method called nonstatic
			$m = self::$objects[$objectKey][2][$methodKey];
		} elseif ( $isStatic !== false && isset(self::$objects[$objectKey][1][$methodKey]) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_NONSTATIC_CALLED_STATICALLY );
		} else {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_METHOD );
		}
		return self::checkArguments( $m[0], $arguments );
	}

	private static function checkArguments( $expects, $arguments ) {
		$argCount = count( $arguments );
		if( true === isset( $expects[self::EXPECTS_EXACTLY_PARAMETERS] ) && $argCount != $expects[self::EXPECTS_EXACTLY_PARAMETERS] ) {
			return new PhpTagsException( PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER, array($expects[self::EXPECTS_EXACTLY_PARAMETERS], $argCount) );
		} else {
			if ( true == isset( $expects[self::EXPECTS_MAXIMUM_PARAMETERS] ) && $argCount > $expects[self::EXPECTS_MAXIMUM_PARAMETERS] ) {
				return new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_AT_MOST_PARAMETERS, array($expects[self::EXPECTS_MAXIMUM_PARAMETERS], $argCount) );
			}
			if ( true == isset( $expects[self::EXPECTS_MINIMUM_PARAMETERS] ) && $argCount < $expects[self::EXPECTS_MINIMUM_PARAMETERS] ) {
				return new PhpTagsException( PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETER, array($expects[self::EXPECTS_MINIMUM_PARAMETERS], $argCount) );
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
				case self::TYPE_NONOBJECT:
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

		$type = $arguments[$i] instanceof GenericObject ? $arguments[$i]->getName() : gettype( $arguments[$i] );
		return new PhpTagsException(
				PhpTagsException::WARNING_EXPECTS_PARAMETER,
				array( $i+1, $error, $type )
			);
	}

	private static function getValidPropertyValue( $objectKey, $calledPropertyName, $isStatic, $value ) {
		$propertyKey = strtolower( $calledPropertyName );
		$point = $isStatic === false ? 3 : 4;
		if ( isset(self::$objects[$objectKey][$point][$propertyKey]) ) {
			$expect = self::$objects[$objectKey][$point][$propertyKey][0];
		} elseif( $isStatic === false ) {
			return new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY );
		} else {
			throw new PhpTagsException( PhpTagsException::FATAL_ACCESS_TO_UNDECLARED_STATIC_PROPERTY );
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
			case self::TYPE_NONOBJECT:
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

		return new PhpTagsException( PhpTagsException::NOTICE_EXPECTS_PROPERTY, array($error, gettype( $value )) );
	}

	public static function hasProperty( $objectKey, $propertyName ) {
		$propertyKey = strtolower( $propertyName );
		return isset( self::$objects[$objectKey][3][$propertyKey] );
	}

	public static function getCallInfo( $key  = null ) {
		static $value = null, $return = null;

		if ( $value !== self::$value ) {
			$return = self::fillCallInfo();
			$value = self::$value;
		}

		if ( $key === null ) {
			return $return;
		}
		return isset( $return[$key] ) ? $return[$key] : false;
	}

	private static function fillCallInfo() {
		$value = self::$value;
		$hookType = $value[PHPTAGS_STACK_HOOK_TYPE];
		if ( $hookType === PHPTAGS_HOOK_GET_CONSTANT || $hookType === PHPTAGS_HOOK_FUNCTION ) {
			$calledObjectName = false;
			$originalObjectName = false;
		} else {
			$object = $value[PHPTAGS_STACK_PARAM_3];
			if ( $object instanceof GenericObject ) {
				$originalObjectName = $calledObjectName = $object->getName();
				$objectKey = $object->getObjectKey();
			} else {
				$calledObjectName = $object;
				$objectKey = strtolower( $calledObjectName );
				$originalObjectName = isset( self::$objects[$objectKey][5] ) ? self::$objects[$objectKey][5] : $calledObjectName;
			}
		}

		$calledHookName = $value[PHPTAGS_STACK_PARAM];
		$hookKey = strtolower( $calledHookName );
		$returnsOnFailure = null;
		$objectAim = false;
		$objectAim_2 = false;

		switch ( $value[PHPTAGS_STACK_HOOK_TYPE] ) {
			case PHPTAGS_HOOK_GET_CONSTANT: // Hook is a constant. Example: echo M_PI;
				$originalHookName = $calledHookName; // Constants are case sensitive
				$originalFullName = $originalHookName;
				break;
			case PHPTAGS_HOOK_GET_OBJECT_CONSTANT: // Hook is a object constant. Example: Foo::MY_BAR
				$originalHookName = $calledHookName; // Constants are case sensitive
				$originalFullName = $originalObjectName . '::' . $originalHookName;
				break;
			case PHPTAGS_HOOK_NEW_OBJECT:
				$originalHookName = $hookKey; // it is '__construct'
				$originalFullName = $originalObjectName . '::' . $originalHookName . '()';
				break;
			case PHPTAGS_HOOK_FUNCTION: // Hook is a function. Example: echo foo();
				if ( isset( self::$functions[$hookKey] ) ) {
					$originalHookName = self::$functions[$hookKey][1];
					$returnsOnFailure = self::$functions[$hookKey][3];
				} else {
					$originalHookName = $calledHookName;
				}
				$originalFullName = $originalHookName . '()';
				break;
			case PHPTAGS_HOOK_GET_STATIC_PROPERTY: // Hook is a static property of a method. Example: echo FOO::$bar;
			case PHPTAGS_HOOK_SET_STATIC_PROPERTY: // Example FOO::$bar = true;
				$originalHookName = isset( self::$objects[$objectKey][4][$hookKey][1] ) ? self::$objects[$objectKey][4][$hookKey][1] : ( isset( self::$objects[$objectKey][3][$hookKey][1] ) ? self::$objects[$objectKey][3][$hookKey][1] : $calledHookName ) ;
				$originalFullName = $originalObjectName . '::$' . $originalHookName;
				break;
			case PHPTAGS_HOOK_GET_OBJECT_PROPERTY: // Hook is a property of a method. Example: $foo = new Foo(); echo $foo->bar;
			case PHPTAGS_HOOK_SET_OBJECT_PROPERTY: // Example: $foo = new Foo(); $foo->bar = true;
				$originalHookName = isset( self::$objects[$objectKey][3][$hookKey][1] ) ? self::$objects[$objectKey][3][$hookKey][1] : ( isset( self::$objects[$objectKey][4][$hookKey][1] ) ? self::$objects[$objectKey][4][$hookKey][1] : $calledHookName ) ;
				$originalFullName = $originalObjectName . '->' . $originalHookName;
				break;
			case PHPTAGS_HOOK_STATIC_METHOD: // Example: FOO::bar()
				if ( isset( self::$objects[$objectKey][2][$hookKey] ) ) {
					$tmp = self::$objects[$objectKey][2][$hookKey];
				} elseif ( isset( self::$objects[$objectKey][1][$hookKey] ) ) {
					$tmp = self::$objects[$objectKey][1][$hookKey];
				} else {
					$tmp = false;
					$originalHookName = $calledHookName;
				}
				if ( $tmp !== false ) {
					$originalHookName = $tmp[1];
					$returnsOnFailure = $tmp[2];
				}
				$originalFullName = $originalObjectName . '::' . $originalHookName . '()';
				break;
			case PHPTAGS_HOOK_OBJECT_METHOD: // Example: $foo = new Foo(); $foo->bar();
				if ( isset( self::$objects[$objectKey][1][$hookKey] ) ) {
					$tmp = self::$objects[$objectKey][1][$hookKey];
				} elseif ( isset( self::$objects[$objectKey][2][$hookKey] ) ) {
					$tmp = self::$objects[$objectKey][2][$hookKey];
				} else {
					$tmp = false;
					$originalHookName = $calledHookName;
				}
				if ( $tmp !== false ) {
					$originalHookName = $tmp[1];
					$returnsOnFailure = $tmp[2];
				}
				$originalFullName = $originalObjectName . '->' . $originalHookName . '()';
				break;
		}

		return array(
			self::INFO_HOOK_TYPE => $hookType,
			self::INFO_CALLED_HOOK_NAME => $calledHookName,
			self::INFO_ORIGINAL_HOOK_NAME => $originalHookName,
			self::INFO_CALLED_OBJECT_NAME => $calledObjectName,
			self::INFO_ORIGINAL_OBJECT_NAME => $originalObjectName,
			self::INFO_ORIGINAL_FULL_NAME => $originalFullName,
			self::INFO_RETURNS_ON_FAILURE => $returnsOnFailure,
		);
	}

}
