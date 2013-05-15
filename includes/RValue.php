<?php
namespace Foxway;
/**
 * RValue class for Runtime of Foxway extension.
 *
 * @file RValue.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class RValue {
	protected $value;

	public function __construct($value) {
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

}
