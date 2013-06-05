<?php
namespace Foxway;
/**
 * FVariable class implements variable handling Functions for Foxway extension.
 *
 * @file FVariable.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 * @method RValue f_boolval(array $arguments) Get the boolean value of a variable
 * @method RValue f_doubleval(array $arguments) doubleval — Alias of floatval()
 * @method RValue f_floatval(array $arguments) Get float value of a variable
 * @method RValue f_gettype(array $arguments) Get the type of a variable
 * @method RValue f_is_array(array $arguments) Finds whether a variable is an array
 * @method RValue f_is_bool(array $arguments) Finds out whether a variable is a boolean
 * @method RValue f_is_double(array $arguments) is_double — Alias of is_float()
 * @method RValue f_is_float(array $arguments) Finds whether the type of a variable is float
 * @method RValue f_is_int(array $arguments) Find whether the type of a variable is integer
 * @method RValue f_is_integer(array $arguments) is_integer — Alias of is_int()
 * @method RValue f_is_long(array $arguments) is_long — Alias of is_int()
 * @method RValue f_is_null(array $arguments) Finds whether a variable is NULL
 * @method RValue f_is_numeric(array $arguments) Finds whether a variable is a number or a numeric string
 * @method RValue f_is_real(array $arguments) is_real — Alias of is_float()
 * @method RValue f_is_scalar(array $arguments) Finds whether a variable is a scalar
 * @method RValue f_is_string(array $arguments) Find whether the type of a variable is string
 * @method RValue f_strval(array $arguments) Get string value of a variable
 */
class FVariable extends BaseFunction {

	public static function __callStatic($name, $arguments) {
		if( count($arguments) != 1 ) {
			return self::wrongParameterCount(__FUNCTION__, __LINE__);
		}
		$arg = &$arguments[0][0];

		switch ($name) {
			case 'f_boolval':
				$return = (bool)$arg;
				break;
			case 'f_empty':
				$return = empty($arg);
				break;
			case 'f_doubleval':
			case 'f_floatval':
				$return = floatval($arg);
				break;
			case 'f_gettype':
				$return = gettype($arg);
				break;
			case 'f_is_array':
				$return = is_array($arg);
				break;
			case 'f_is_bool':
				$return = is_bool($arg);
				break;
			case 'f_is_double':
			case 'f_is_float':
			case 'f_is_real':
				$return = is_float($arg);
				break;
			case 'f_is_int':
			case 'f_is_integer':
			case 'f_is_long':
				$return = is_int($arg);
				break;
			case 'f_is_null':
				$return = is_null($arg);
				break;
			case 'f_is_numeric':
				$return = is_numeric($arg);
				break;
			case 'f_is_scalar':
				$return = is_scalar($arg);
				break;
			case 'f_is_string':
				$return = is_string($arg);
				break;
			case 'f_strval':
				$return = strval($arg);
				break;
			default:
				return self::callUnknownMethod(__FUNCTION__, __LINE__);
				break;
		}
		return new RValue($return);
	}

	/**
	 * Get the integer value of a variable
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_intval($arguments) {
		$return = null;
		switch ( count($arguments) ) {
			case 1:
				$return = intval($arguments[0]);
				break;
			case 2:
				$return = intval($arguments[0], (int)$arguments[1]);
				break;
			default:
				return self::wrongParameterCount( __FUNCTION__, __LINE__ );
				break;
		}
		return new RValue($return);
	}

	/**
	 * Determine if a variable is set and is not NULL
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_isset($arguments) {
		foreach ( $arguments as $value ) {
			if( $value === null ) {
				return new RValue(false);
			}
		}
		return new RValue(true);
	}

	/**
	 * Prints human-readable information about a variable
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_print_r($arguments) {
		if( count($arguments) == 1 || (count($arguments) == 2 && $arguments[1] == false) ) {
			return new ROutput( true, print_r($arguments[0], true), 'pre' );
		}
		if( count($arguments) == 2 && $arguments[1] == true ) {
			return new RValue( print_r($arguments[0], true) );
		}
		return self::wrongParameterCount( __FUNCTION__, __LINE__ );
	}

	/**
	 * Set the type of a variable
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_settype($arguments) {
		if( count($arguments) != 2 ) {
			return self::wrongParameterCount( __FUNCTION__, __LINE__ );
		}
		if( get_class($arguments[0]) != 'Foxway\\RVariable' ) {
			return self::onlyVariablesCanBePassedByReference( __FUNCTION__, __LINE__ );
		}
		return new RValue( settype($arguments[0]->getReference(), (string)$arguments[1]) );
	}

	/**
	 * Unset a given variable
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_unset($arguments) {
		foreach ( $arguments as $value ) {
			if( $value instanceof RVariable ) {
				$value->un_set();
			}
		}
		return new RValue(null);
	}

	/**
	 * Dumps information about a variable
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_var_dump($arguments) {
		if( count($arguments) == 0 ) {
			return self::wrongParameterCount( __FUNCTION__, __LINE__ );
		}
		ob_start();
		call_user_func_array('var_dump', $arguments);
		return new ROutput( null, ob_get_clean(), 'pre' );
	}

	/**
	 * Outputs or returns a parsable string representation of a variable
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_var_export($arguments) {
		if( count($arguments) == 1 || (count($arguments) == 2 && $arguments[1] == false) ) {
			return new ROutput( null, var_export($arguments[0], true), 'pre' );
		}
		if( count($arguments) == 2 && $arguments[1] == true ) {
			return new RValue( var_export($arguments[0], true) );
		}
		return self::wrongParameterCount( __FUNCTION__, __LINE__ );
	}

}
