<?php
namespace Foxway;
/**
 * Interpreter class of Foxway extension.
 *
 * @file Interpreter.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Interpreter {

	private static $skipTokenIds = array(
		T_WHITESPACE,
		T_COMMENT,
		T_DOC_COMMENT,
	);

	public static function run($source, $is_debug = false) {
		$tokens = token_get_all("<?php $source ?>");
		$return = "";
		$debug = array();
		$expected = false;
		$expectListParams = false;
		$expectCurlyClose = false;
		$expectQuotesClose = false;
		$line = 1;
		$runtime = new Runtime();

		foreach ($tokens as $token) {
			if ( is_string($token) ) {
				$id = $token;
				if($is_debug) {
					$debug[] = $token;
				}
			} else {
				list($id, $text, $line) = $token;
			}

			if( $expected && in_array($id, self::$skipTokenIds) === false && in_array($id, $expected) === false) {
				$id_str = is_string($id) ? "' $id '" : token_name($id);
				$return .= '<br><span class="error">' . wfMessage( 'foxway-php-syntax-error-unexpected', $id_str, $line )->escaped() . '</span>';
				break;
			}

			switch ($id) {
				case T_COMMENT:
				case T_DOC_COMMENT:
					if($is_debug) {
						$debug[] = '<span style="color:#969696" title="'. token_name($id) . '">' . str_replace("\n", "<br />\n", htmlspecialchars($text) ) . '</span>';
					}
					break;
				case T_WHITESPACE:
					if($is_debug) {
						$debug[] = str_replace("\n", "<br />\n", $text);
					}
					break;
				case '"':
					if($is_debug) {
						array_pop($debug);
						$debug[] = '<span style="color:#CE7B00">"</span>';
					}
					if( $expectQuotesClose ) {
						$expectQuotesClose = false;
					} else {
						$expectQuotesClose = true;
					}
					break;
				case ';':
					$return .= $runtime->getCommandResult($debug);
					$expectListParams = false;
					$expected = false;
					break;
				case '=':
					$runtime->setVariableOperator('=');
					$expected = array(
						T_CONSTANT_ENCAPSED_STRING,
						T_ENCAPSED_AND_WHITESPACE,
						T_VARIABLE,
						T_CURLY_OPEN,
						'"',
						';',
						);
					break;
				case '.':
					$expected = array(
						T_CONSTANT_ENCAPSED_STRING,
						T_ENCAPSED_AND_WHITESPACE,
						T_VARIABLE,
						T_CURLY_OPEN,
						'"',
						';',
						);
						$runtime->addOperator('.');
					break;
				case ',':
					$expected = array(
						T_CONSTANT_ENCAPSED_STRING,
						T_ENCAPSED_AND_WHITESPACE,
						T_VARIABLE,
						T_CURLY_OPEN,
						'"',
						// ';',
						);
					break;
				case '}':
					if( $expectCurlyClose ) {
						$expectCurlyClose = false;
						$expected = array(
							T_CONSTANT_ENCAPSED_STRING,
							T_ENCAPSED_AND_WHITESPACE,
							T_VARIABLE,
							T_CURLY_OPEN,
							'"',
							';',
							);
					} else {
						$return .= '<br><span class="error">' . wfMessage( 'foxway-php-syntax-error-unexpected', '\' } \'', $line )->escaped() . '</span>';
						break 2;
					}
					break;
				case T_ECHO:
					if($is_debug) {
						$i = array_push($debug, $text)-1;
					} else {
						$i = false;
					}
					$runtime->addCommand('echo', $i);
					//@todo:
					/*$expected = array(
						T_START_HEREDOC,
						T_DNUMBER,
						T_LNUMBER,
						T_STRING_CAST,
						T_INT_CAST,
						T_FUNCTION,
						);*/
					$expectListParams = true;
					$expected = array(
						T_CONSTANT_ENCAPSED_STRING,
						T_ENCAPSED_AND_WHITESPACE,
						T_VARIABLE,
						T_CURLY_OPEN,
						'"',
						';',
						);
					break;
				case T_CONSTANT_ENCAPSED_STRING:
					if($is_debug) {
						$debug[] = '<span style="color:#CE7B00" title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
					}
					$is_apostrophe = substr($text, 0, 1) == '\'' ? true : false;
					$string = substr($text, 1, -1);
					$runtime->addParam( self::process_slashes($string, $is_apostrophe) );
					$expected = array(
						';',
						'.',
						);
					if($expectListParams){
						$expected[] = ',';
					}
					break;
				case T_ENCAPSED_AND_WHITESPACE:
					if($is_debug) {
						$debug[] = '<span style="color:#CE7B00" title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
					}
					$runtime->addParam( self::process_slashes($text, false) );
					$runtime->addOperator('.');
					break;
				case T_VARIABLE:
					if( $expected && in_array(T_VARIABLE, $expected) ) {
						$value = $runtime->getVariable( substr($text, 1) );
						$runtime->addParam($value);
						if( $expectCurlyClose ) {
							$expected = array( '}' );
						} else {
							$expected = array( ';' );
						}
						if( $expectListParams ) {
							$expected[] = ',';
						}
						if( $expectQuotesClose ) {
							$expected[] = '"';
						}
						if($is_debug) {
							if( is_null($value) ) {
								$debug[] = '<span style="color:red" title="'.token_name($id).' = '.htmlspecialchars( var_export($value, true) ).'">' . $text . '</span>';
							} else {
								$debug[] = '<span style="color:#6D3206" title="'.token_name($id).' = '.htmlspecialchars( var_export($value, true) ).'">' . $text . '</span>';
							}
						}
					} else {
						if($is_debug) {
							$i = array_push($debug, $text)-1;
						} else {
							$i = false;
						}
						$runtime->setVariable($text, $i);
						$expected = array('=');
					}
					break;
				case T_CURLY_OPEN:
					if($is_debug) {
						$debug[] = '{';
					}
					$expectCurlyClose = true;
					$expected = array(
						T_VARIABLE,
						);
					break;
				default:
					if($is_debug) {
						$debug[] = '<span title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
					}
					break;
			}
		}

		if( $is_debug ) {
			$return = implode('', $debug) . '<HR>' . $return;
		}
		return $return;
	}

	private static function process_slashes($string, $is_apostrophe) {
		if( $is_apostrophe ) {
			//					(\\)*+\'				\\
			$pattern = array('/(\\\\\\\\)*+\\\\\'/', '/\\\\\\\\/');
			$replacement = array('$1\'', '\\');
		} else {
			//						(\\)*+\"			\n			\r			\t		\v			\$			\\
			$pattern = array('/(\\\\\\\\)*+\\\\"/',  '/\\\\n/', '/\\\\r/', '/\\\\t/', '/\\\\v/', '/\\\\$/', '/\\\\\\\\/');
			$replacement = array('$1"', "\n", "\r", "\t", "\v", '$', '\\');
		}
		return preg_replace($pattern, $replacement, $string);
	}

}
