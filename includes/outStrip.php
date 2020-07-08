<?php
namespace PhpTags;
/**
 * The outStrip class of the extension PHP Tags.
 *
 * @file outPrint.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class outStrip implements iRawOutput {
	private $returnValue;
	private $strip;

	/**
	 *
	 * @param mixed $returnValue
	 * @param string $strip
	 */
	public function __construct( $returnValue, $strip ) {
		$this->returnValue = $returnValue;
		$this->strip = $strip;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->strip;
	}

	/**
	 * @return mixed
	 */
	public function getReturnValue() {
		return $this->returnValue;
	}

	/**
	 * @return string
	 */
	public function placeAsStripItem() {
		return $this->strip;
	}

}
