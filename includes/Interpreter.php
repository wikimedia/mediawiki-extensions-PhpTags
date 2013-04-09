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
		$parenthesesLevel = 0;
		$incrementVariable = false;
		$variableName = null;
		$variableValue = null;
		$line = 1;
		$runtime = new Runtime();

		foreach ($tokens as $token) {
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id, $text, $line) = $token;
			}

			if( $expected && in_array($id, self::$skipTokenIds) === false && in_array($id, $expected) === false) {
				$id_str = is_string($id) ? "' $id '" : token_name($id);
				$return .= '<br><span class="error">' . wfMessage( 'foxway-php-syntax-error-unexpected', $id_str, $line )->escaped() . '</span>';
				break;
			}

			//\MWDebug::log( var_export($token,true) );

			switch ($id) {
				case ';':
					$return .= $runtime->getCommandResult($debug);
					break;
				case ',':
					$runtime->separateParams();
					break;
				case '(':
					$parenthesesLevel++;
					$runtime->parenthesesOpen();
					break;
				case ')':
					$parenthesesLevel--;
					$runtime->parenthesesClose();
					if( $parenthesesLevel == 0 ) {
						unset($expected[array_search(')', $expected)]);
					}
					break;
				case '=':
				case T_CONCAT_EQUAL:	// .=
				case T_PLUS_EQUAL:		// +=
				case T_MINUS_EQUAL:		// -=
				case T_MUL_EQUAL:		// *=
				case T_DIV_EQUAL:		// /=
				case T_MOD_EQUAL:		// %=
				case T_AND_EQUAL:		// &=
				case T_OR_EQUAL:		// |=
				case T_XOR_EQUAL:		// ^=
				case T_SL_EQUAL:		// <<
				case T_SR_EQUAL:		// >>
					$runtime->setVariableOperator( $id );
					break;
				case '.':
				case '+':
				case '-':
				case '*':
				case '/':
				case '%':
				case '&':
				case '|':
				case '^':
				case T_SL: // <<
				case T_SR: // >>
				case '~':
				case T_INT_CAST: // (int)
				case T_DOUBLE_CAST: // (double)
				case T_STRING_CAST: // (string)
				case T_ARRAY_CAST: // (array)
				case T_BOOL_CAST: // (bool)
						$runtime->addOperator( $id );
					break;
				case T_INC: // ++
				case T_DEC: // --
					if( $incrementVariable === false ) {
						$incrementVariable = $id;
						$expected = array( T_VARIABLE );
					} else {
						$variableValue = $runtime->getVariableValue( $variableName );
						if( $id == T_INC ) {
							$variableValue++;
						} else {
							$variableValue--;
						}
						$runtime->setVariableValue($variableName, $variableValue);
						$expected = array( ';', '.', '+', '-', '*', '/', '%', '&', '|', '^', T_SL, T_SR ); // same as for case T_LNUMBER:
						if($expectListParams){
							$expected[] = ',';
						}
					}
					break;
				case T_ECHO:
					if($is_debug) {
						$i = array_push($debug, $text)-1;
					} else {
						$i = false;
					}
					$runtime->addCommand('echo', $i);
					break;
				case T_CONSTANT_ENCAPSED_STRING:
					$is_apostrophe = substr($text, 0, 1) == '\'' ? true : false;
					$string = substr($text, 1, -1);
					$runtime->addParam( self::process_slashes($string, $is_apostrophe) );
					break;
				case T_LNUMBER:
					$runtime->addParam( (integer)$text );
					break;
				case T_DNUMBER:
					$runtime->addParam( (float)$text );
					break;
				case T_ENCAPSED_AND_WHITESPACE:
					if( $expectQuotesClose ) {
						$runtime->addOperator('.');
					}
					$runtime->addParam( self::process_slashes($text, false) );
					break;
				case T_VARIABLE:
					if( $expected && in_array(T_VARIABLE, $expected) ) {
						$variableName = substr($text, 1);
						$variableValue = $runtime->getVariableValue( $variableName );
						if( $incrementVariable == T_INC || $incrementVariable == T_DEC ) {
							if( $incrementVariable == T_INC ) {
								$variableValue++;
							} else {
								$variableValue--;
							}
							$runtime->setVariableValue( $variableName, $variableValue);
							$variableName = false;
						} else {
							$incrementVariable = $variableName;
						}
						if( $expectCurlyClose ) {
							$expected = array( '}' );
						} else {
							$expected = array( ';', '.', '+', '-', '*', '/', '%', '&', '|', '^', T_SL, T_SR, T_ENCAPSED_AND_WHITESPACE );
							if( $variableName !== false ){
								$expected[] = T_INC;
								$expected[] = T_DEC;
							}
							if( $parenthesesLevel ) {
								$expected[] = ')';
							}
						}
						if( $expectListParams ) {
							$expected[] = ',';
						}
						if( $expectQuotesClose ) {
							$expected[] = T_VARIABLE; //echo "$s$s";
							$expected[] = '"';
							$runtime->addOperator('.');
						}
						$runtime->addParam($variableValue);
						if($is_debug) {
							if( is_null($variableValue) ) {
								$debug[] = '<span style="color:red" title="'.token_name($id).' = '.htmlspecialchars( var_export($variableValue, true) ).'">' . $text . '</span>';
							} else {
								$debug[] = '<span style="color:#6D3206" title="'.token_name($id).' = '.htmlspecialchars( var_export($variableValue, true) ).'">' . $text . '</span>';
							}
						}
					} else {
						if($is_debug) {
							$i = array_push($debug, $text)-1;
						} else {
							$i = false;
						}
						$variableName = substr($text, 1);
						$runtime->setVariable( $variableName, $i );
						$incrementVariable = $variableName;
						$expected = array(
							'=',
							T_CONCAT_EQUAL,
							T_PLUS_EQUAL,
							T_MINUS_EQUAL,
							T_MUL_EQUAL,
							T_DIV_EQUAL,
							T_MOD_EQUAL,
							T_AND_EQUAL,
							T_OR_EQUAL,
							T_XOR_EQUAL,
							T_SL_EQUAL,
							T_SR_EQUAL,
							T_INC,
							T_DEC,
							);
					}
					break;
			}
			if( $id != T_VARIABLE && $id != T_INC && $id != T_DEC ) {
				$incrementVariable = false;
			}

			switch ($id) {
				case ';':
					$expectListParams = false;
					$expected = false;
					break;
				case '"':
					if( $expectQuotesClose === false ) {
						$runtime->addParam('');
						$expectQuotesClose = true;
						$expected = array(T_ENCAPSED_AND_WHITESPACE, T_CURLY_OPEN, T_VARIABLE, '"');
						break;
					} else {
						$expectQuotesClose = false;
					}
					// break is not necessary here
				case T_CONSTANT_ENCAPSED_STRING:
				case T_LNUMBER:
				case T_DNUMBER:
					$expected = array( ';', '.', '+', '-', '*', '/', '%', '&', '|', '^', T_SL, T_SR ); // same as for case T_INC:
					if($expectListParams){
						$expected[] = ',';
					}
					if( $parenthesesLevel ) {
						$expected[] = ')';
					}
					break;
				case T_ECHO:
					$expectListParams = true;
					// break is not necessary here
				case ',':
				case '=':
				case T_CONCAT_EQUAL:	// .=
				case T_PLUS_EQUAL:		// +=
				case T_MINUS_EQUAL:		// -=
				case T_MUL_EQUAL:		// *=
				case T_DIV_EQUAL:		// /=
				case T_MOD_EQUAL:		// %=
				case T_AND_EQUAL:		// &=
				case T_OR_EQUAL:		// |=
				case T_XOR_EQUAL:		// ^=
				case T_SL_EQUAL:		// <<=
				case T_SR_EQUAL:		// >>=
				case '.':
				case '+':
				case '-':
				case '*':
				case '/':
				case '%':
				case '&':
				case '|':
				case '^':
				case T_SL: // <<
				case T_SR: // >>
					$expected = array(
						T_CONSTANT_ENCAPSED_STRING, // "foo" or 'bar'
						T_ENCAPSED_AND_WHITESPACE, // " $a"
						T_LNUMBER, // 123, 012, 0x1ac
						T_DNUMBER, // 0.12
						T_VARIABLE, // $foo
						T_INC, // ++
						T_DEC, // --
						T_CURLY_OPEN, // {
						'"',
						'-',
						'+',
						'(',
						'~',
						T_INT_CAST, // (int)
						T_DOUBLE_CAST, // (double)
						T_STRING_CAST, // (string)
						T_ARRAY_CAST, // (array)
						T_BOOL_CAST, // (bool)
						);
					break;
				case T_CURLY_OPEN:
					if( $expectQuotesClose === true ) {
						$expectCurlyClose = true;
						$expected = array( T_VARIABLE );
					} else {
						$return .= '<br><span class="error">' . wfMessage( 'foxway-php-syntax-error-unexpected', '\' { \'', $line )->escaped() . '</span>';
						break 2;
					}
					break;
				case '}':
					if( $expectCurlyClose ) {
						$expectCurlyClose = false;
						$expected = array(
							T_CONSTANT_ENCAPSED_STRING,
							T_ENCAPSED_AND_WHITESPACE,
							//T_LNUMBER,
							//T_DNUMBER,
							T_VARIABLE,
							T_CURLY_OPEN,
							'"',
							);
					} else {
						$return .= '<br><span class="error">' . wfMessage( 'foxway-php-syntax-error-unexpected', '\' } \'', $line )->escaped() . '</span>';
						break 2;
					}
					break;
			}

			// Debug info
			if($is_debug) {
				switch ($id) {
					case T_COMMENT:
					case T_DOC_COMMENT:
						$debug[] = '<span style="color:#969696" title="'. token_name($id) . '">' . str_replace("\n", "<br />\n", htmlspecialchars($text) ) . '</span>';
						break;
					case T_WHITESPACE:
						$debug[] = str_replace("\n", "<br />\n", $text);
						break;
					case '"':
						$text = '"';
						// break is not necessary here
					case T_CONSTANT_ENCAPSED_STRING:
					case T_ENCAPSED_AND_WHITESPACE:
						$debug[] = '<span style="color:#CE7B00" title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
						break;
					case T_LNUMBER:
					case T_DNUMBER:
						$debug[] = '<span style="color:#FF00FF" title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
						break;
					case T_INT_CAST: // (int)
					case T_DOUBLE_CAST: // (double)
					case T_STRING_CAST: // (string)
					case T_ARRAY_CAST: // (array)
					case T_BOOL_CAST: // (bool)
						$debug[] = '<span style="color:#0000E6" title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
						break;
					case T_ECHO:
					case T_VARIABLE:
						break;
					default:
						if( is_array($token) ) {
							$debug[] = '<span title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
						} else {
							$debug[] = $id;
						}
						break;
				}
			}
		}

		if( $is_debug ) {
			$return = '<nowiki>' . implode('', $debug) . "</nowiki><HR>\n" . $return;
		}
		return $return;
	}

	private static function process_slashes($string, $is_apostrophe) {
		if( $is_apostrophe ) {
			//					(\\)*+\'				\\
			$pattern = array('/(\\\\\\\\)*+\\\\\'/', '/\\\\\\\\/');
			$replacement = array('$1\'', '\\');
		} else {
			//						(\\)*+\"				(\\)*+\n				(\\)*+\r				(\\)*+\t			(\\)*+\v				(\\)*+\$			\\
			$pattern = array('/(\\\\\\\\)*+\\\\"/',  '/(\\\\\\\\)*+\\\\n/', '/(\\\\\\\\)*+\\\\r/', '/(\\\\\\\\)*+\\\\t/', '/(\\\\\\\\)*+\\\\v/', '/(\\\\\\\\)*+\\\\\$/', '/\\\\\\\\/');
			$replacement = array('$1"', "\n", "\r", "\t", "\v", '$', '\\');
		}
		return preg_replace($pattern, $replacement, $string);
	}

}
