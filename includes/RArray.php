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
class RArray extends RVariable {

	/**
	 *
	 * @var RVariable
	 */
	protected $value;

	/**
	 *
	 * @var RValue
	 */
	private $index;

	public function __construct(RValue $value, $index) {
		$this->value = $value;
		$this->index = $index;
	}

	public function getValue() {
		if( $this->value->is_set() && $this->index !== null ) {
			$reference = &$this->value->getReference();
			$index = $this->index->getValue();

			// @todo this do not work correctly for PHP 5.3, only for 5.4 see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
			/*if( isset($reference[$index]) ) {
				return $reference[$index];
			}
			 * OR return @ $reference[$index];
			 */
			if( is_array($reference) ) {
				return @ $reference[$index];
			}
			if( is_string($reference) && (!is_string($index) || (string)(int)$index == $index) ) {
				return @ $reference[(int)$index];
			}
		}
		return null;
	}

	public function setValue($value) {
		$reference = &$this->value->getReference();
		if( $this->index === null ) {
			$reference[] = $value;
		} else {
			$reference[$this->index->getValue()] = $value;
		}
	}

	public function &getReference() {
		$reference = &$this->value->getReference();
		if( $this->index === null ) {
			$value = null;
			$reference[] = &$value;
			return $value;
		}
		$index = $this->index->getValue();
		if( !isset($reference[$index]) ) {
			$reference[$index]=null;
		}
		return $reference[$index];
	}

	public function getName() {
		$index = $this->index === null ? '[]' : "[{$this->index->getValue()}]";
		return $this->value->getName() . $index;
	}

	public function is_set() {
		if( $this->value->is_set() && $this->index !== null ) {
			$reference = &$this->value->getReference();

			// @todo this do not work correctly for PHP 5.3, only for 5.4 see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
			//return isset( $reference[$this->index->getValue()] );
			$index = $this->index->getValue();
			if( is_array($reference) ) {
				return isset($reference[$index]);
			}
			if( is_string($reference) && (!is_string($index) || (string)(int)$index == $index) ) {
				return (int)$index < strlen($reference) && (int)$index > 0;
			}
		}
		return false;
	}

	public function un_set() {
		if( $this->value->is_set() && $this->index !== null ) {
			$reference = &$this->value->getReference();
			unset($reference[$this->index->getValue()]);
		}
	}

	/**
	 *
	 * @return RVariable
	 */
	public function getParent() {
		return $this->value;
	}

	public function getIndex() {
		return $this->index;
	}

}
