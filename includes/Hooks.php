<?php
namespace PhpTags;

use MWException;

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
	const INFO_HOOK_TYPE_STRING = 7;

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
	 * @param string $key
	 */
	public static function addJsonFile( $file, $key = '' ) {
		self::$jsonFiles[] = array( $file, $key . filemtime( $file ) );
	}

	/**
	 * @throws MWException
	 */
	public static function loadData() {
		$data = false;

		$cache = wfGetCache( CACHE_ANYTHING );
		$key = wfMemcKey( 'phptags', 'loadJsonFiles' );
		$cached = $cache->get( $key );
		if ( $cached !== false &&
				$cached['JSONLOADER'] === JsonLoader::VERSION &&
				$cached['jsonFiles'] === self::$jsonFiles &&
				$cached['callbackConstants'] === self::$callbackConstants
		) {
			wfDebugLog( 'PhpTags', __METHOD__ . '() using cache: yes' );
			$data = $cached;
		}
		if ( $data === false ) {
			wfDebugLog( 'PhpTags',  __METHOD__ . '() using cache: NO' );
			$data = JsonLoader::load( self::$jsonFiles );
			$data['constantValues'] += self::loadConstantValues();
			$data['jsonFiles'] = self::$jsonFiles;
			$data['callbackConstants'] = self::$callbackConstants;
			$data['JSONLOADER'] = JsonLoader::VERSION;
			$cache->set( $key, $data );
		}
		self::$jsonFiles = [];
		self::$callbackConstants = [];
		self::$objects = $data['objects'];
		self::$functions = $data['functions'];
		self::$constants = $data['constants'];
		self::$constantValues = $data['constantValues'];
	}

	/**
	 * @return array
	 */
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
		$methodKey = $value[Runtime::B_METHOD_KEY] ?: strtolower( $value[Runtime::B_METHOD] ); // Function or method name

		if ( $value[Runtime::B_HOOK_TYPE] === Runtime::H_FUNCTION ) { // Hook type is function
			if ( isset( self::$functions[$methodKey][0][self::EXPECTS_REFERENCE_PARAMETERS] ) ) {
				$references = self::$functions[$methodKey][0][self::EXPECTS_REFERENCE_PARAMETERS];
			} else {
				$references = false;
			}
		} elseif ( $value[Runtime::B_OBJECT] === false ) { // Hook type is not object
			unset( self::$value[Runtime::B_OBJECT] ); // unlink reference before assign a value!!!
			self::$value[Runtime::B_OBJECT] = false;
			return false;
		} else { // Hook type is object
			$object = $value[Runtime::B_OBJECT][Runtime::B_OBJECT];
			unset( self::$value[Runtime::B_OBJECT] ); // unlink reference before assign a value!!!
			self::$value[Runtime::B_OBJECT] = $object; // self::getCallInfo() waits $object in [Runtime::B_OBJECT] index
			if ( $object instanceof GenericObject ) { // Object has been created
				$objectKey = self::getClassNameByObjectName( $object->getObjectKey() );
			} else { // It is static method of object
				$objectKey = $value[Runtime::B_OBJECT][Runtime::B_OBJECT_KEY] ?: strtolower( $object );
			}
			if ( isset( self::$objects[$objectKey][2][$methodKey][0][self::EXPECTS_REFERENCE_PARAMETERS] ) ) {
				$references = self::$objects[$objectKey][2][$methodKey][0][self::EXPECTS_REFERENCE_PARAMETERS];
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
	 * @return string
	 * @throws PhpTagsException
	 */
	private static function getFunctionClass( $funcKey ) {
		static $functions = array(); // cache of functions

		if ( true === isset( $functions[$funcKey] ) ) { // it is exists in cache
			return $functions[$funcKey];
		}

		if ( false === isset( self::$functions[$funcKey][2] ) ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_FUNCTION );
		}

		$functionClassName = 'PhpTagsObjects\\' . self::$functions[$funcKey][2];
		if ( false === class_exists( $functionClassName ) ) {
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
		$hookType = $value[Runtime::B_HOOK_TYPE];
		if ( $hookType === Runtime::H_GET_CONSTANT ) { // Hook is a constant. Example: echo M_PI;
			return self::callGetConstant( $value[Runtime::B_METHOD] );
		} elseif ( $hookType === Runtime::H_GET_OBJECT_CONSTANT ) { // Hook is a object constant. Example: Foo::MY_BAR
			return self::callGetObjectConstant( $value[Runtime::B_METHOD], $value[Runtime::B_OBJECT], $value[Runtime::B_OBJECT_KEY] );
		}

		$methodKey = $value[Runtime::B_METHOD_KEY] ?: strtolower( $value[Runtime::B_METHOD] );
		switch ( $hookType ) {
			case Runtime::H_FUNCTION: // Hook is a function. Example: echo foo();
				return self::callFunction( $value[Runtime::B_PARAM_2], $methodKey );
			case Runtime::H_GET_STATIC_PROPERTY: // Hook is a static property of a method. Example: echo FOO::$bar;
				return self::callGetStaticProperty( $methodKey, $value[Runtime::B_OBJECT], $value[Runtime::B_OBJECT_KEY] );
			case Runtime::H_GET_OBJECT_PROPERTY: // Hook is a property of a method. Example: $foo = new Foo(); echo $foo->bar;
				return self::callGetObjectProperty( $methodKey, $value[Runtime::B_OBJECT] );
			case Runtime::H_SET_STATIC_PROPERTY: // Example FOO::$bar = true;
				return self::callSetStaticProperty( $value[Runtime::B_PARAM_2], $methodKey, $value[Runtime::B_OBJECT], $value[Runtime::B_OBJECT_KEY] );
			case Runtime::H_SET_OBJECT_PROPERTY: // Example: $foo = new Foo(); $foo->bar = true;
				return self::callSetObjectProperty( $value[Runtime::B_PARAM_2], $methodKey, $value[Runtime::B_OBJECT] );
			case Runtime::H_STATIC_METHOD: // Example: FOO::bar()
				return self::callStaticMethod( $value[Runtime::B_PARAM_2], $methodKey, $value[Runtime::B_OBJECT], $value[Runtime::B_OBJECT_KEY] );
			case Runtime::H_OBJECT_METHOD: // Example: $foo = new Foo(); $foo->bar();
				return self::callObjectMethod( $value[Runtime::B_PARAM_2], $methodKey, $value[Runtime::B_OBJECT] );
		}
		return null;
	}

	private static function checkPermission( $hookType, $objectName, $methodName, $values ) {
		if ( \Hooks::run( 'PhpTagsBeforeCallRuntimeHook', array($hookType, $objectName, $methodName, $values) ) ) {
			return true;
		}

		Runtime::pushException( new HookException( 'banned by administrator' ) );
		return false;
	}

	/**
	 * Get value of the constant
	 * @param string $name Name of the constant
	 * @return mixed
	 * @throws PhpTagsException
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
			if ( !self::checkPermission( Runtime::H_GET_CONSTANT, null, $name, null ) ) {
				return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
			}
			return $className::getConstantValue( $name );
		} elseif ( isset( self::$constantValues[$name] ) || array_key_exists( $name, self::$constantValues ) ) {
			if ( !self::checkPermission( Runtime::H_GET_CONSTANT, null, $name, null ) ) {
				return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
			}
			return self::$constantValues[$name];
		}
		Runtime::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_CONSTANT, $name ) );
		if ( !self::checkPermission( Runtime::H_GET_CONSTANT, null, $name, null ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return $name;
	}

	/**
	 * Call the function's hook
	 * @param array $arguments List of the arguments
	 * @param string|null $funcKey Name of the function in lower case
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callFunction( $arguments, $funcKey = null ) {
		$funcClass = self::getFunctionClass( $funcKey );
		ksort( $arguments );
		self::checkFunctionArguments( $funcKey, $arguments );

		if ( !self::checkPermission( Runtime::H_FUNCTION, null, $funcKey, $arguments ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return call_user_func_array( array($funcClass, "f_$funcKey"), $arguments );
	}

	/**
	 * Call the method of object based on class \PhpTags\GenericObject
	 * @param array $arguments List of the arguments
	 * @param string $methodKey Name of the method in lower case
	 * @param string|GenericObject $object Name of the object or the object of class \PhpTags\GenericObject
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callObjectMethod( $arguments, $methodKey, $object ) {
		if ( false === $object instanceof GenericObject ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALL_FUNCTION_ON_NON_OBJECT );
		}
		$objectKey = $object->getObjectKey();
		ksort( $arguments );
		self::checkObjectArguments( $objectKey, $methodKey, false, $arguments );
		if ( !self::checkPermission( Runtime::H_OBJECT_METHOD, $objectKey, $methodKey, $arguments ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return call_user_func_array( array($object, "m_$methodKey"), $arguments );
	}

	/**
	 * @param array $arguments List of the arguments
	 * @param string $methodKey Name of the method in lower case
	 * @param string|GenericObject $calledObject Name of the object or the object of class \PhpTags\GenericObject
	 * @param string|null $objectKey
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callStaticMethod( $arguments, $methodKey, $calledObject, $objectKey = null ) {
		if ( $calledObject instanceof GenericObject ) {
			$objectKey = $calledObject->getObjectKey();
		} elseif( !$objectKey ) {
			$objectKey = strtolower( $calledObject );
		}

		ksort( $arguments );
		self::checkObjectArguments( $objectKey, $methodKey, true, $arguments );
		$className = self::getClassNameByObjectName( $objectKey );
		if ( !self::checkPermission( Runtime::H_STATIC_METHOD, $objectKey, $methodKey, $arguments ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return call_user_func_array( array($className, "s_$methodKey"), $arguments );
	}

	/**
	 * @param string $name
	 * @param GenericObject|string $object
	 * @param string|null $objectKey
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callGetObjectConstant( $name, $object, $objectKey = null ) {
		if ( $object instanceof GenericObject ) {
			$objectKey = $object->getObjectKey();
		} elseif ( !$objectKey ) {
			$objectKey = strtolower( $object );
		}

		$className = self::getClassNameByObjectName( $objectKey );
		$handler = "c_$name";
		if ( !self::checkPermission( Runtime::H_GET_OBJECT_CONSTANT, $objectKey, $name, null ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return $className::$handler();
	}

	/**
	 * Call the property of object based on class \PhpTags\GenericObject
	 * @param string $propertyKey Name of the property in lower case
	 * @param GenericObject|string $object Name of the object or the object of class \PhpTags\GenericObject
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callGetObjectProperty( $propertyKey, $object ) {
		if ( !$object instanceof GenericObject ) {
			throw new PhpTagsException( PhpTagsException::NOTICE_GET_PROPERTY_OF_NON_OBJECT );
		}
		$handler = "p_$propertyKey";
		if ( !self::checkPermission( Runtime::H_GET_OBJECT_PROPERTY, $object->getObjectKey(), $propertyKey, null ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return $object->$handler();
	}

	/**
	 * @param string $propertyKey
	 * @param GenericObject|string $object
	 * @param string|null $objectKey
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callGetStaticProperty( $propertyKey, $object, $objectKey = null ) {
		if ( $object instanceof GenericObject ) {
			$objectKey = $object->getObjectKey();
		} elseif ( !$objectKey ) {
			$objectKey = strtolower( $object );
		}

		$className = self::getClassNameByObjectName( $objectKey );
		$handler = "q_$propertyKey";
		if ( !self::checkPermission( Runtime::H_GET_STATIC_PROPERTY, $objectKey, $propertyKey, null ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return $className::$handler();
	}

	/**
	 *
	 * @param string $name
	 * @param GenericObject $object
	 * @param mixed $value
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function callSetObjectsProperty( $name, $object, $value ) {
		$oldValue = self::$value;
		self::$value = array(
			Runtime::B_HOOK_TYPE => Runtime::H_SET_OBJECT_PROPERTY,
			Runtime::B_METHOD => $name,
			Runtime::B_METHOD_KEY => null,
			Runtime::B_OBJECT => $object,
		);

		$return = self::callSetObjectProperty( $value, strtolower( $name ), $object );

		self::$value = $oldValue;
		return $return;
	}

	/**
	 * @since 5.2.0
	 * @param mixed $value
	 * @param string $propertyKey
	 * @param GenericObject $object
	 * @return mixed
	 * @throws PhpTagsException
	 */
	private static function callSetObjectProperty( $value, $propertyKey, $object ) {
		if ( false === $object instanceof GenericObject ) {
			Runtime::pushException( new PhpTagsException( PhpTagsException::WARNING_ATTEMPT_TO_ASSIGN_PROPERTY ) );
			return null;
		}
		$objectKey = $object->getObjectKey();
		$validValue = self::getValidPropertyValue( $objectKey, $propertyKey, false, $value );
		$handler = 'b_' . $propertyKey;
		if ( !self::checkPermission( Runtime::H_SET_OBJECT_PROPERTY, $objectKey, $propertyKey, $value ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return $object->$handler( $validValue );
	}

	/**
	 * @param $value
	 * @param string $propertyKey
	 * @param GenericObject|string $object
	 * @param string|null $objectKey
	 * @return array|bool|mixed|null
	 * @throws PhpTagsException
	 */
	private static function callSetStaticProperty( $value, $propertyKey, $object, $objectKey = null ) {
		if ( $object instanceof GenericObject ) {
			$objectKey = $object->getObjectKey();
		} elseif( !$objectKey ) {
			$objectKey = strtolower( $object );
		}

		$className = self::getClassNameByObjectName( $objectKey );
		$validValue = self::getValidPropertyValue( $objectKey, $propertyKey, true, $value );
		$handler = "d_$propertyKey";
		if ( !self::checkPermission( Runtime::H_SET_STATIC_PROPERTY, $objectKey, $propertyKey, $value ) ) {
			return self::getCallInfo( self::INFO_RETURNS_ON_FAILURE );
		}
		return $className::$handler( $validValue );
	}

	/**
	 *
	 * @param array $arguments
	 * @param string $calledObjectName
	 * @param string $objectKey Object name in lower case
	 * @return GenericObject
	 * @throws PhpTagsException
	 */
	public static function createObject( $arguments, $calledObjectName, $objectKey = null ) {
		if ( !$objectKey ) {
			$objectKey = strtolower( $calledObjectName );
		}

		$oldValue = self::$value;
		self::$value = array(
			Runtime::B_HOOK_TYPE => Runtime::H_NEW_OBJECT,
			Runtime::B_METHOD => '__construct',
			Runtime::B_METHOD_KEY => '__construct',
			Runtime::B_PARAM_2 => $arguments,
			Runtime::B_OBJECT => $calledObjectName,
			Runtime::B_OBJECT_KEY => $objectKey,
			);

		$className = self::getClassNameByObjectName( $objectKey );
		$newObject = new $className( self::getCallInfo( self::INFO_ORIGINAL_OBJECT_NAME ), $objectKey );
		ksort( $arguments );

		try {
			self::checkObjectArguments( $objectKey, '__construct', false, $arguments );
			if ( !self::checkPermission( Runtime::H_SET_STATIC_PROPERTY, $objectKey, '__construct', $arguments ) ) {
				throw new PhpTagsException( PhpTagsException::FATAL_OBJECT_NOT_CREATED, '' );
			}
			call_user_func_array( array($newObject, 'm___construct'), $arguments );
		} catch ( \PhpTags\PhpTagsException $exc) {
			throw $exc;
		} catch ( \Exception $exc ) {
			list(, $message) = explode( ': ', $exc->getMessage(), 2 );
			if ( $message == '' ) {
				$message = $exc->getMessage();
			}
			throw new PhpTagsException( PhpTagsException::FATAL_OBJECT_NOT_CREATED, $message );
		}
		self::$value = $oldValue;
		return $newObject;
	}

	/**
	 * @param string $objectKey
	 * @return string
	 * @throws PhpTagsException
	 */
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
	 * @param mixed $value
	 * @return GenericObject
	 * @throws PhpTagsException
	 */
	public static function getObjectWithValue( $objectName, $value ) {
		$objectKey = strtolower( $objectName );
		$className = self::getClassNameByObjectName( $objectKey );
		return new $className( $objectName, $objectKey, $value );
	}

	/**
	 * @return array
	 */
	public static function getDefinedFunctions() {
		return self::$functions;
	}

	/**
	 * @param string $funcKey
	 * @param array $arguments
	 * @return bool
	 * @throws PhpTagsException
	 */
	private static function checkFunctionArguments( $funcKey, $arguments ) {
		if ( isset( self::$functions[$funcKey] ) ) {
			return self::checkArguments( self::$functions[$funcKey][0], $arguments );
		}
		throw new PhpTagsException( PhpTagsException::FATAL_CALL_TO_UNDEFINED_FUNCTION );
	}

	/**
	 * @param string $objectKey
	 * @param string $methodKey
	 * @param bool $isStatic
	 * @param array $arguments
	 * @return bool
	 * @throws PhpTagsException
	 */
	private static function checkObjectArguments( $objectKey, $methodKey, $isStatic, $arguments ) {
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

	/**
	 * @param array $expects
	 * @param array $arguments
	 * @return bool
	 * @throws PhpTagsException
	 */
	private static function checkArguments( array $expects, array $arguments ) {
		$argCount = count( $arguments );
		if( true === isset( $expects[self::EXPECTS_EXACTLY_PARAMETERS] ) && $argCount != $expects[self::EXPECTS_EXACTLY_PARAMETERS] ) {
			throw new PhpTagsException( PhpTagsException::WARNING_EXPECTS_EXACTLY_PARAMETER, array($expects[self::EXPECTS_EXACTLY_PARAMETERS], $argCount) );
		} else {
			if ( true == isset( $expects[self::EXPECTS_MAXIMUM_PARAMETERS] ) && $argCount > $expects[self::EXPECTS_MAXIMUM_PARAMETERS] ) {
				throw new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_AT_MOST_PARAMETERS, array($expects[self::EXPECTS_MAXIMUM_PARAMETERS], $argCount) );
			}
			if ( true == isset( $expects[self::EXPECTS_MINIMUM_PARAMETERS] ) && $argCount < $expects[self::EXPECTS_MINIMUM_PARAMETERS] ) {
				throw new PhpTagsException( PhpTagsException::WARNING_EXPECTS_AT_LEAST_PARAMETER, array($expects[self::EXPECTS_MINIMUM_PARAMETERS], $argCount) );
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

		if ( $error !== false ) {
			$type = $arguments[$i] instanceof GenericObject ? $arguments[$i]->getName() : gettype( $arguments[$i] );
			throw new PhpTagsException(
					PhpTagsException::WARNING_EXPECTS_PARAMETER,
					array( $i+1, $error, $type )
				);
		}
		return true;
	}

	/**
	 * @param string $objectKey
	 * @param string $propertyKey
	 * @param bool $isStatic
	 * @param mixed $value
	 * @return array|bool|float|int|GenericObject|string|null
	 * @throws PhpTagsException
	 */
	private static function getValidPropertyValue( $objectKey, $propertyKey, $isStatic, $value ) {
		$point = $isStatic === false ? 3 : 4;
		if ( isset(self::$objects[$objectKey][$point][$propertyKey]) ) {
			$expect = self::$objects[$objectKey][$point][$propertyKey][0];
		} elseif( $isStatic === false ) {
			throw new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_PROPERTY );
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

		if ( $error !== false ) {
			throw new PhpTagsException( PhpTagsException::NOTICE_EXPECTS_PROPERTY, array($error, gettype( $value )) );
		}
		return $value;
	}

	/**
	 * @param string $objectKey
	 * @param string $propertyKey
	 * @return bool
	 */
	public static function hasProperty( $objectKey, $propertyKey ) {
		return isset( self::$objects[$objectKey][3][$propertyKey] );
	}

	/**
	 * @param null|string $key
	 * @return mixed
	 */
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

	/**
	 * @return array
	 */
	private static function fillCallInfo() {
		$value = self::$value;
		$hookType = $value[Runtime::B_HOOK_TYPE];
		if ( $hookType === Runtime::H_GET_CONSTANT || $hookType === Runtime::H_FUNCTION ) {
			$calledObjectName = false;
			$originalObjectName = false;
		} else {
			$object = $value[Runtime::B_OBJECT];
			if ( $object instanceof GenericObject ) {
				$originalObjectName = $calledObjectName = $object->getName();
				$objectKey = $object->getObjectKey();
			} else {
				$calledObjectName = $object;
				$objectKey = $value[Runtime::B_OBJECT_KEY] ?: strtolower( $calledObjectName );
				$originalObjectName = isset( self::$objects[$objectKey][5] ) ? self::$objects[$objectKey][5] : $calledObjectName;
			}
		}

		$calledHookName = $value[Runtime::B_METHOD];
		$hookKey = $value[Runtime::B_METHOD_KEY] ?: strtolower( $calledHookName );
		$returnsOnFailure = null;

		switch ( $value[Runtime::B_HOOK_TYPE] ) {
			case Runtime::H_GET_CONSTANT: // Hook is a constant. Example: echo M_PI;
				$originalHookName = $calledHookName; // Constants are case sensitive
				$originalFullName = $originalHookName;
				$hookTypeString = 'constant';
				break;
			case Runtime::H_GET_OBJECT_CONSTANT: // Hook is a object constant. Example: Foo::MY_BAR
				$originalHookName = $calledHookName; // Constants are case sensitive
				$originalFullName = $originalObjectName . '::' . $originalHookName;
				$hookTypeString = 'object constant';
				break;
			case Runtime::H_NEW_OBJECT:
				$originalHookName = $hookKey; // it is '__construct'
				$originalFullName = $originalObjectName . '::' . $originalHookName . '()';
				$hookTypeString = 'construct';
				break;
			case Runtime::H_FUNCTION: // Hook is a function. Example: echo foo();
				if ( isset( self::$functions[$hookKey] ) ) {
					$originalHookName = self::$functions[$hookKey][1];
					$returnsOnFailure = self::$functions[$hookKey][3];
				} else {
					$originalHookName = $calledHookName;
				}
				$originalFullName = $originalHookName . '()';
				$hookTypeString = 'function';
				break;
			case Runtime::H_GET_STATIC_PROPERTY: // Hook is static property of object. Example: echo FOO::$bar;
			case Runtime::H_SET_STATIC_PROPERTY: // Example FOO::$bar = true;
				$originalHookName = isset( self::$objects[$objectKey][4][$hookKey][1] ) ? self::$objects[$objectKey][4][$hookKey][1] : ( isset( self::$objects[$objectKey][3][$hookKey][1] ) ? self::$objects[$objectKey][3][$hookKey][1] : $calledHookName ) ;
				$originalFullName = $originalObjectName . '::$' . $originalHookName;
				$hookTypeString = 'static property';
				break;
			case Runtime::H_GET_OBJECT_PROPERTY: // Hook is property of object. Example: $foo = new Foo(); echo $foo->bar;
			case Runtime::H_SET_OBJECT_PROPERTY: // Example: $foo = new Foo(); $foo->bar = true;
				$originalHookName = isset( self::$objects[$objectKey][3][$hookKey][1] ) ? self::$objects[$objectKey][3][$hookKey][1] : ( isset( self::$objects[$objectKey][4][$hookKey][1] ) ? self::$objects[$objectKey][4][$hookKey][1] : $calledHookName ) ;
				$originalFullName = $originalObjectName . '->' . $originalHookName;
				$hookTypeString = 'property';
				break;
			case Runtime::H_STATIC_METHOD: // Example: FOO::bar()
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
				$hookTypeString = 'static method';
				break;
			case Runtime::H_OBJECT_METHOD: // Example: $foo = new Foo(); $foo->bar();
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
				$hookTypeString = 'method';
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
			self::INFO_HOOK_TYPE_STRING => $hookTypeString,
		);
	}

}
