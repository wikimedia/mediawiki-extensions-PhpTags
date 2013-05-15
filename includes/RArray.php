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
			if( isset($reference[$index]) ) {
				return $reference[$index];
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
		if( $this->index === null ) {
			$index = '[]';
		}else{
			$index = "[{$this->index->getValue()}]";
		}
		return $this->value->getName() . $index;
	}

	public function is_set() {
		if( $this->value->is_set() && $this->index !== null ) {
			$reference = &$this->value->getReference();
			return isset( $reference[$this->index->getValue()] );
		}
		return false;
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
