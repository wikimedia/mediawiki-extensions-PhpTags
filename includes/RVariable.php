<?php
namespace Foxway;
/**
 * RVariable class for Runtime of Foxway extension.
 *
 * @file RVariable.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class RVariable extends RValue {
	private $variablesArray;

	public function __construct( $name, &$variablesArray ) {
		$this->value = $name;
		$this->variablesArray = &$variablesArray;
	}

	public function getValue() {
		return isset($this->variablesArray[$this->value]) ? $this->variablesArray[$this->value] : null;
	}

	public function setValue($value) {
		$this->variablesArray[$this->value] = $value;
	}

	public function doOperation($operator, $param) {
		$reference = &$this->getReference();
		switch ($operator) {
			case '=':
				$reference = $param;
				break;
			case T_CONCAT_EQUAL:// .=
				$reference .= $param;
				break;
			case T_PLUS_EQUAL:// +=
				$reference += $param;
				break;
			case T_MINUS_EQUAL:// -=
				$reference -= $param;
				break;
			case T_MUL_EQUAL: // *=
				$reference *= $param;
				break;
			case T_DIV_EQUAL: // /=
				if( $param == 0 ) { // Division by zero
					$reference = false;
				} else {
					$reference /= $param;
				}
				break;
			case T_MOD_EQUAL: // %=
				if( $param == 0 ) { // Division by zero
					$reference = false;
				} else {
					$reference %= $param;
				}
				break;
			case T_AND_EQUAL:// &=
				$reference &= $param;
				break;
			case T_OR_EQUAL:// |=
				$reference |= $param;
				break;
			case T_XOR_EQUAL:// ^=
				$reference ^= $param;
				break;
			case T_SL_EQUAL:// <<=
				$reference <<= $param;
				break;
			case T_SR_EQUAL:// >>=
				$reference >>= $param;
				break;
			case T_INC: // ++
				if( $param ) {
					return new RValue( $reference++ );
				} else {
					return new RValue( ++$reference );
				}
				break;
			case T_DEC: // --
				if( $param) {
					return new RValue( $reference-- );
				} else {
					return new RValue( --$reference );
				}
				break;
		}
		return new RValue( $reference );
	}

	public function &getReference() {
		$name = $this->value;
		$variablesArray = &$this->variablesArray;
		if( !isset($variablesArray[$name]) ) {
			$variablesArray[$name] = null;
		}
		return $variablesArray[$name];
	}

	public function getName() {
		return $this->value;
	}

	public function is_set() {
		return isset($this->variablesArray[$this->value]);
	}

	public function un_set() {
		if( $this->value != 'GLOBALS' ) {
			unset($this->variablesArray[$this->value]);
		}
	}

}
