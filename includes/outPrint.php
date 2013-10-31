<?php
namespace Foxway;
/**
 * outPrint class of Foxway extension.
 *
 * @file outPrint.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class outPrint implements iRawOutput {
	public $returnValue=null;
	private $contents;
	private $raw;
	private $element;
	private $attribs;

	public function __construct( $returnValue, $contents, $raw=false, $element='pre', $attribs = array() ) {
		$this->returnValue = $returnValue;
		$this->raw = $raw;
		$this->contents = (string)$contents;
		$this->element = $element;
		$this->attribs = $attribs;
	}

	public function __toString() {
		if( $this->element !== null ){
			if( $this->raw ) {
				return \Html::rawElement( $this->element, array(), $this->contents ) . "\n";
			}else{
				return \Html::element( $this->element, array(), $this->contents ) . "\n";
			}
		}
		return $this->raw ? "{$this->contents}\n" : strtr( $this->contents, array('&'=>'&amp;', '<'=>'&lt;') ) . "\n";
	}
}
