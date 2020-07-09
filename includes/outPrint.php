<?php
namespace PhpTags;

use Html;

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
	 */
	public function __construct( $returnValue, $content, $raw=false, $element='pre', $attribs = [] ) {
		$this->returnValue = $returnValue;
		$this->content = $raw ? (string)$content : strtr( $content, [ '&'=>'&amp;', '<'=>'&lt;' ] );
		$this->element = $element;
		$this->attribs = $attribs;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		if ( $this->element ) {
			return Html::rawElement( $this->element, $this->attribs, $this->content );
		}
		return $this->content;
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
		$this->content = Renderer::insertNoWiki( $this->content );
		return Renderer::insertStripItem( $this );
	}

}
