<?php
namespace Foxway;
/**
 * Runtime class of Foxway extension.
 *
 * @file Debug.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Debug implements \ArrayAccess, iRawOutput {

	protected $_container = array();

	public function offsetExists($offset)
    {
        return isset($this->_container[$offset]);
    }

	public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->_container[$offset] : null;
    }

	public function offsetSet($offset, $value) {
		$html = self::getHTMLbyToken($value);

		if (is_null($offset)) {
            $this->_container[] = $html;
        } else {
            $this->_container[$offset] = $html;
        }
	}

	public function offsetUnset($offset)
    {
        unset($this->_container[$offset]);
    }

	private static function getHTMLbyToken( $token ) {
		if ( is_array($token) ) {
			list($id, $t) = $token;
			$text = strtr( $t, array('&'=>'&amp;', '<'=>'&lt;') );
			$tokenName = token_name($id);
		} else {
			$id = $token;
			$text = strtr( $id, array('&'=>'&amp;', '<'=>'&lt;') );
			$tokenName = false;
		}

		$class = false;
		switch ($id) {
			case T_COMMENT:
			case T_DOC_COMMENT:
				$class = 'foxway_comment';
				$text = str_replace("\n", "<br />\n", $text);
				break;
			case T_WHITESPACE:
				$text = str_replace("\n", "<br />\n", $text);
				break;
			case '"':
				$text = '"';
				// break is not necessary here
			case T_CONSTANT_ENCAPSED_STRING:
			case T_ENCAPSED_AND_WHITESPACE:
				$class = 'foxway_string';
				break;
			case T_NUM_STRING:
			case T_LNUMBER:
			case T_DNUMBER:
				$class = 'foxway_number';
				break;
			case T_INT_CAST: // (int)
			case T_DOUBLE_CAST: // (double)
			case T_STRING_CAST: // (string)
			case T_ARRAY_CAST: // (array)
			case T_BOOL_CAST: // (bool)
			case T_UNSET_CAST: // (unset)
			case T_ECHO:
			case T_PRINT:
			case T_IF:
			case T_ELSE:
			case T_ELSEIF:
			case T_ARRAY:
			case T_STATIC:
			case T_GLOBAL:
			case T_ISSET:
			case T_UNSET:
			case T_EMPTY:
				$class = 'foxway_construct';
				break;
			case T_VARIABLE:
				$class = 'foxway_variable';
				break;
			case T_STRING:
				if( strcasecmp($text, 'true') == 0 || strcasecmp($text, 'false') == 0 || strcasecmp($text, 'null') == 0) {
					$class = 'foxway_construct';
				}
				break;
			case 'skip':
				$class = 'foxway_skipped';
				$text = ' { ... } ';
				$id = T_EMPTY;
				break;
			case T_CLOSE_TAG:
				return '';
		}

		$attribs = array();
		if( $class !== false ) {
			$attribs['class'] = $class;
		}
		if( $tokenName ) {
			$attribs['title'] = $tokenName;
		}
		if( count($attribs) > 0 ) {
			return \Html::rawElement( 'span', $attribs, $text );
		}
		return $text;
	}

	public function addCommandResult(RuntimeDebug $runtime) {
		$debug = \FormatJson::encode( $runtime->getDebug() );
		$this->_container[] = \Html::rawElement(
				'span',
				array('class'=>'foxway_runtime', 'data'=>$debug),
				'&nbsp;R&nbsp;'
				);
	}

	public function __toString() {
		return \Html::rawElement( 'table', array('class'=>'foxway_debug'),
				\Html::rawElement( 'tr', array(), \Html::element( 'th', array(), 'Debug view' ) ) .
				\Html::rawElement( 'tr', array(), \Html::rawElement( 'td', array(), implode('', $this->_container) ) )
				);
	}
}

