<?php
namespace Foxway;
/**
 * ROutput class of Foxway extension.
 *
 * @file ROutput.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class ROutput extends RValue implements iRawOutput {
	private $string;
	private $element;
	protected $value;

	public function __construct( $value, $string, $element=null) {
		$this->value = $value;
		$this->string = (string)$string;
		$this->element = $element;
	}

	public function __toString() {
		if( $this->element !== null ){
			return \Html::element( $this->element, array(), $this->string ) . "\n";
		}
		return $this->string . "\n";
	}
}
