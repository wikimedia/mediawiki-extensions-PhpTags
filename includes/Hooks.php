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
	 * Set the values of the constants
	 * @param array $constantValues
	 */
	public static function setConstantValues( array $constantValues ) {
		self::$constantValues += $constantValues;
	}

	/**
	 * Set the hooks of the constants
	 * @param string $className
	 * @param array $constantNames
	 */
	public static function setConstants( $className, array $constantNames ) {
		self::$constants += array_fill_keys( $constantNames, $className );
	}

	/**
	 * Set the hooks of the functions
	 * @param string $className Name of the class that will be used for processing the functions
	 * @param array $functionNames List of the functions
	 */
	public static function setFunctions( $className, array $functionNames ) {
		self::$functions += array_fill_keys( $functionNames, $className );
	}

	/**
	 * Set
	 * @param array $objects
	 */
	public static function setObjects( array $objects ) {
		self::$objects += $objects;
	}

	/**
	 * Returns information about what is expected as argument, value or a variable reference.
	 * @param integer $number Ordinal number of the argument
	 * @param string $name Name of function or method
	 * @param mixed $object Object, name of object or false for functions
	 * @return mixed True if argument should be passed by reference, 1 if argument can be value
	 * @throws PhpTagsException
	 */
	public static function getReferenceInfo( $number, $name, $object ) {
		if ( $object === false ) { // It is function
			$info = self::getFunctionInfo( $name );
			$references = $info[1];
		} elseif ( $object instanceof GenericObject ) { // Object has been created
			$references =  $object->getMethodReferences( $name );
		} else { // It is static method of object
			$references = false; // @todo
		}

		if( $references === false || $references === true ) {
			return $references;
		} elseif ( is_array($references) ) {
			if( isset($references[$number]) || array_key_exists($number, $references) ) {
				return $references[$number];
			} elseif( isset($references[PHPTAGS_HOOK_VALUE_N]) || array_key_exists(PHPTAGS_HOOK_VALUE_N, $references) ) {
				return $references[PHPTAGS_HOOK_VALUE_N];
			}
			return false;
		}
		return (bool)( 1 << ($number-1) & $references );
	}

	/**
	 * Get information about the function
	 * @staticvar array $functions Cache of functions
	 * @param string $name Name of function
	 * @return array 0 - name of class, 1 - references info
	 * @throws PhpTagsException
	 */
	private static function getFunctionInfo( $name ) {
		static $functions = array(); // cache of functions
		if( true === isset( $functions[$name] )  ) { // it is exists in cache
			return $functions[$name];
		}

		if ( false === isset( self::$functions[$name] ) ) {
			throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_FUNCTION, $name );
		}

		$functionClassName = self::$functions[$name];
		if( false === class_exists( $functionClassName ) ) {
			throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_NONEXISTENT_HOOK_CLASS, array($name, $functionClassName) );
		}
		if ( false === is_subclass_of( $functionClassName, 'PhpTags\\GenericFunction' ) ) {
			throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_INVALID_HOOK_CLASS, array($name, $functionClassName) );
		}

		$hookInfo = array( $functionClassName, $functionClassName::getFunctionReferences($name) );
		$functions[$name] = $hookInfo;
		return $hookInfo;
	}

	/**
	 * Call a hook like as constant or function or object's method of PhpTags
	 * @param mixed $arguments Array or boolean false for the constants
	 * @param mixed $name Name of constant or function or method
	 * @param mixed $object boolean false for the functions or the object or object's name
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function callHook( $arguments, $name, $object ) {
		// it is a constant or a function or a method of an object
		if ( $arguments === false ) { // it is a constant or a property of an object
			if ( $object === false ) { // it is a constant
				return self::callConstant( $name );
			} else { // it is a property of an object
				return self::callObjectsProperty( $name, $object );
			}
		}
		// it is a function or a method of an object
		if ( $object === false ) { // it is a function
			return self::callFunction( $arguments, $name );
		}
		// it is a method of an object
		return self::callObjectsMethod( $arguments, $name, $object );
	}

	/**
	 * Get value of the constant
	 * @param string $name Name of the constant
	 * @return mixed
	 */
	private static function callConstant( $name ) {
		if ( isset(self::$constantValues[$name]) || array_key_exists($name, self::$constantValues) ) {
			return self::$constantValues[$name];
		}
		Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CONSTANT, $name );
		return $name;
	}

	/**
	 * Call the function's hook
	 * @param array $arguments List of the arguments
	 * @param string $name Name of the function
	 * @return mixed
	 */
	private static function callFunction( $arguments, $name ) {
		$functionInfo = self::getFunctionInfo( $name );
		ksort( $arguments );
		return call_user_func_array( array($functionInfo[0], "f_$name"), $arguments );
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
			return call_user_func_array( array($object, "m_$name"), $arguments );
		}
		// it is calling of static method
		$className = self::getClassNameByObjectName( $object );
		$arguments[] = $object;
		return call_user_func_array( array($className, "s_$name"), $arguments );
	}

	/**
	 * Call the property of object based on class \PhpTags\GenericObject
	 * @param string $name Name of the property
	 * @param mixed $object Name of the object or the object of class \PhpTags\GenericObject
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function callObjectsProperty( $name, $object ) {
		if ( $object instanceof GenericObject ) {
			return call_user_func( array($object, "p_$name") );
		}
		// it is calling of static property
		$className = self::getClassNameByObjectName( $object );
		return call_user_func( array($className, "c_$name"), $object );
	}

	public static function createObject( $arguments, $name, $showException = true ) {
		$className = self::getClassNameByObjectName( $name );
		$object = new $className( $name );

		try {
			if ( true === call_user_func_array( array($object, 'm___construct'), $arguments ) ) {
				return $object;
			}
		} catch ( \Exception $exc ) {
			if ( $showException ) {
				list(, $message) = explode( ': ', $exc->getMessage(), 2 );
				Runtime::$transit[PHPTAGS_TRANSIT_EXCEPTION][] = new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_OBJECT_NOT_CREATED, array( $name, $message ) );
			}
		}

		return false;
	}

	private static function getClassNameByObjectName( $name ) {
		static $cache = array();
		if ( true === isset( $cache[$name] ) ) {
			return $cache[$name];
		}

		if ( false === isset( self::$objects[$name] ) ) {
			throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_CLASS_NOT_FOUND, $name );
		}

		$className = '\\PhpTagsObjects\\' . self::$objects[$name];
		if ( false === class_exists( $className ) ) {
			throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_CREATEOBJECT_INVALID_CLASS, array($className, $name) );
		}

		$classParens = class_parents( $className );
		if ( false === isset($classParens['PhpTags\\GenericObject']) ) {
			throw new PhpTagsException( PHPTAGS_EXCEPTION_FATAL_MUST_EXTENDS_GENERIC, $className );
		}

		$cache[$name] = $className;
		return $className;
	}

	public static function getObjectWithValue( $name, $value ) {
		$className = self::getClassNameByObjectName( $name );
		return new $className( $name, $value );
	}

	public static function getDefinedFunctions() {
		return self::$functions;
	}

}