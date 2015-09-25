<?php
namespace PhpTags;
/**
 * The outPrint class of the extension PHP Tags.
 *
 * @file outPrint.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class outPrint implements iRawOutput {
	private $returnValue;
	private $content;
	private $element;
	private $attribs;

	/**
	 *
	 * @param mixed $returnValue
	 * @param string $content
	 * @param bool $raw
	 * @param string|false $element
	 * @param array $attribs
	 * @param array $sheath
	 */
	public function __construct( $returnValue, $content, $raw=false, $element='pre', $attribs = array() ) {
		$this->returnValue = $returnValue;
		$this->content = $raw ? (string)$content : strtr( $content, array('&'=>'&amp;', '<'=>'&lt;') );
		$this->element = $element;
		$this->attribs = $attribs;
	}

	public function __toString() {
		if ( $this->element ) {
			return \Html::rawElement( $this->element, $this->attribs, $this->content );
		}
		return $this->content;
	}

	public function getReturnValue() {
		return $this->returnValue;
	}

	public function placeAsStripItem() {
		$this->content = Renderer::insertNoWiki( $this->content );
		return Renderer::insertStripItem( $this );
	}

}
