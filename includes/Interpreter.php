<?php
namespace Foxway;

define( 'FOX_ENDBLOCK', 0);
define( 'FOX_ENDIF', 1);
define( 'FOX_ELSE' , 2);
define( 'FOX_VALUE', 3);

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

	private static $arrayOperators = array(
		';',
		'.',
		'+',
		'-',
		'*',
		'/',
		'%',
		'&',
		'|',
		'^',
		T_SL,						// <<
		T_SR,						// >>
		T_ENCAPSED_AND_WHITESPACE,	// " $a"
		'<',
		'>',
		T_IS_SMALLER_OR_EQUAL,		// <=
		T_IS_GREATER_OR_EQUAL,		// >=
		T_IS_EQUAL,					// ==
		T_IS_NOT_EQUAL,				// !=
		T_IS_IDENTICAL,				// ===
		T_IS_NOT_IDENTICAL,			// !==
		'?',
		':',
		'}',
		);

	public static function run($source, $is_debug = false) {
		$tokens = token_get_all("<?php $source ?>");
		\MWDebug::log( "\$tokens WHILE " . var_export($tokens,true) );
		
		$return = "";
		$debug = array();
		$blocks = array();
		$expected = false;
		$expectListParams = false;
		$expectCurlyClose = false;
		$expectQuotesClose = false;
		$expectTernarySeparators = 0;
		$parenthesesLevel = 0;
		$curlyLever = 0;
		$IfIndex = false;
		$incrementVariable = false;
		$variableName = null;
		$variableValue = null;
		$commandResult = null;
		$line = 1;
		$runtime = new Runtime();

		$countTokens = count($tokens);
		for( $index = 0; $index < $countTokens; $index++ ){
			$token = $tokens[$index];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id, $text, $line) = $token;
			}

			\MWDebug::log( "$index WHILE " . var_export($token,true) );

			if( $expected && in_array($id, self::$skipTokenIds) === false && in_array($id, $expected) === false) {
				$id_str = is_string($id) ? "' $id '" : token_name($id);
				$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', $id_str, $line )->escaped() . '</span>';
				break;
			}

			switch ($id) {
				case ';':
					// TODO: check parenthess level???
					$commandResult = $runtime->getCommandResult($debug);
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
					if( $runtime->parenthesesClose() ) {
						$commandResult = $runtime->getCommandResult($debug);
					}
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
				case '<':
				case '>':
				case T_IS_SMALLER_OR_EQUAL: // <=
				case T_IS_GREATER_OR_EQUAL: // >=
				case T_IS_EQUAL: // ==
				case T_IS_NOT_EQUAL: // !=
				case T_IS_IDENTICAL: // ===
				case T_IS_NOT_IDENTICAL: // !==
						$runtime->addOperator( $id );
					break;
				case '?':
					if( $runtime->getMathResult() ) { // true
						$expectTernarySeparators++; // just go next
					} else { // false, to skip to the ternary operator separator
						$tmp_skip = 0; // it to parse the syntax of nested ternary operators
						$skipedTokens = 0; // it for debug messages and check correct syntax
						for( $index++; $index < $countTokens; $index++ ){
							$token = $tokens[$index];
							if ( is_string($token) ) {
								$id = $token;
							} else {
								list($id, $text, $line) = $token;
							}
							//\MWDebug::log( var_export($token, true) );
							switch ($id) {
								case '?': // is embedded ternary operator
									$tmp_skip++; // will skip ternary separators
									break;
								case ':':
									if( $tmp_skip > 0 ) { // were the embedded ternary operator?
										$tmp_skip--;
									} else { /************************ EXIT HERE ***********************************/
										break 2; // found the required separator, we go from here and just go next
									}        /************************ EXIT HERE ***********************************/
									break;
								case T_WHITESPACE:
								case T_COMMENT:
								case T_DOC_COMMENT:
									break; // just ignore, does not affect the count skipped operators
								case ',':
								case ';':
								case T_IF: // This should not occur here, syntax error
									$return .= $return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', is_string($id) ? "' $id '" : token_name($id), $line )->escaped() . '</span>';
									break 4;
								default :
									$skipedTokens++; // Increments the counter skipped operators
									break;
							}
						}
						if($is_debug) {
							$debug[] = ' <span title="FALSE">?</span><span style="color:#969696" title="Skiped tokens: '.$skipedTokens.'"> ... </span>';
						}
						if( $index == $countTokens ) {
							$return .= $return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', '$end', $line )->escaped() . '</span>';
							break 2;
						}
						//just go next
					}
					break;
				case ':':
					if( $expectTernarySeparators > 0 ) { // Yes, we waited this
						// Here we need to find the end of the current ternary operator and skip other operators
						$expectTernarySeparators--;
						$skipedTokens = 0; // it for debug messages and check correct syntax
						for( $index++; $index < $countTokens; $index++ ){
							$token = $tokens[$index];
							if ( is_string($token) ) {
								$id = $token;
							} else {
								list($id, $text, $line) = $token;
							}
							//\MWDebug::log( var_export($token, true) );
							switch ($id) {
								case ':':
									if( $expectTernarySeparators > 0 ) { // This ternary operator is nested and this separator owned by a parent
										$expectTernarySeparators--; // note that found it. is to control the syntax
										break;
									} else {
										break 2; // is a violation of the syntax, exit the loop. After the loop has to go error
									}
								case ',':
								case ';':
								case '?':
									if( $skipedTokens > 0 ) {
										if($is_debug) {
											$debug[] = ':<span style="color:#969696" title="Skiped tokens: '.$skipedTokens.'"> ... </span>';
										}
										$index--;
										/************************ EXIT HERE ***********************************/
										continue 4; // We found the end of the ternary operator, and now go further
										/************************ EXIT HERE ***********************************/
									}
									// break is not necessary here
								case T_IF:
									$return .= $return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', is_string($id) ? "' $id '" : token_name($id), $line )->escaped() . '</span>';
									break 4;
								case T_WHITESPACE:
								case T_COMMENT:
								case T_DOC_COMMENT:
									break; // just ignore
								default :
									$skipedTokens++;
									break;
							}
						}
						$return .= $return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', '$end', $line )->escaped() . '</span>';
						break 2;
					}
					// If we are here, then we do not expect to find separator ternary
					$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', $id, $line )->escaped() . '</span>';
					break 2;
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
						$expected = self::$arrayOperators;
						if($expectListParams && $parenthesesLevel == 0){
							$expected[] = ',';
						}
					}
					break;
				case T_ELSE:
					if( isset($blocks[$index]) ) {
						$commandResult = array(T_ELSE, $blocks[$index][FOX_VALUE]);
					} else {
						$id_str = is_string($id) ? "' $id '" : token_name($id);
						$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', $id_str, $line )->escaped() . '</span>';
						break 2;
					}
					break;
				case T_IF:
					$IfIndex = $index;
					$expected = array('(');
					// break is not necessary here
				case T_ECHO:
					if($is_debug) {
						$i = array_push($debug, $text)-1;
					} else {
						$i = false;
					}
					$runtime->addCommand($id, $i);
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
				case T_ENCAPSED_AND_WHITESPACE: // " $a"
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
							$expected = self::$arrayOperators;
							if( $variableName !== false ){
								$expected[] = T_INC;
								$expected[] = T_DEC;
							}
							if( $parenthesesLevel ) {
								$expected[] = ')';
							}
						}
						if( $expectListParams && $parenthesesLevel == 0 ) {
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
				case T_STRING:
					if( strcasecmp($text, 'true') == 0 ) {
						$runtime->addParam( true );
					} elseif( strcasecmp($text, 'false') == 0 ) {
						$runtime->addParam( false );
					} else {
						$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', "'$text'", $line )->escaped() . '</span>';
						break 2;
					}
					break;
			}
			if( $id != T_VARIABLE && $id != T_INC && $id != T_DEC ) {
				$incrementVariable = false;
			}

			/*****************  COMMAND RESULT  *******************************/
			if( !is_null($commandResult) ) {
				if( is_string($commandResult) ) {
					$return .= $commandResult;
				}elseif( is_array($commandResult) ) {
					list($command, $result) = $commandResult;
					switch ($command) {
						case T_ECHO:
							$return .= $result;
							break;
						case T_IF:
							if( !isset($blocks[$IfIndex]) ) {
								if( self::findIfElseIndexes($tokens, $blocks, $IfIndex, $index) !== true ) {
									$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', '$end', $line )->escaped() . '</span>';
									break 2;
								}
							}
							$curBlock = $blocks[$IfIndex];
							$elseIndex = $curBlock[FOX_ELSE];
							if( $result ) {
								if( $elseIndex ) {
									$blocks[$elseIndex][FOX_VALUE] = false;
								}
								$expected = false;
								// just go next
							} else {
								// skip next statement
								if($is_debug) {
									$debug[] = ')<span style="color:#969696"> ... </span>;';
								}
								// find 'else'
								if( $elseIndex ) {
									$blocks[$elseIndex][FOX_VALUE] = true;
									$expected = array( T_ELSE, T_ELSEIF );
								} else {
									$expected = false;
								}
								$index = $curBlock[FOX_ENDBLOCK];
								//\MWDebug::log( var_export($tokens[$endBlockIndex], true) );
								$commandResult = null;
								continue 2;
							}
							break;
						case T_ELSE:
							if( $result ) {
								//$expected = false;
								// just go next
							} else {
								// skip next statement
								// find end of block
								if( !isset($blocks[$index][FOX_ENDBLOCK]) ) {
									if( self::findIfElseIndexes($tokens, $blocks, $index, $index+1) !== true ) {
										$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', '$end', $line )->escaped() . '</span>';
										break 2;
									}
								}
								$index = $blocks[$index][FOX_ENDBLOCK];
								$commandResult = null;
								//$expected = false;
							}
							break;
					}
				}
				$commandResult = null;
			}

			/*****************   EXPECT   *************************************/

			switch ($id) {
				case ';':
				case T_ELSE:
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
				case T_STRING:
					$expected = self::$arrayOperators;
					if($expectListParams && $parenthesesLevel == 0){
						$expected[] = ',';
					}
					if( $parenthesesLevel ) {
						$expected[] = ')';
					}
					break;
				case T_ECHO:
					$expectListParams = true;
					// break is not necessary here
				case '(': // TODO: remove $id == '(' &&
					if( $id == '(' && $expected != array('(') ) {
						break;
					}
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
				case '<':
				case '>':
				case T_IS_SMALLER_OR_EQUAL: // <=
				case T_IS_GREATER_OR_EQUAL: // >=
				case T_IS_EQUAL: // ==
				case T_IS_NOT_EQUAL: // !=
				case T_IS_IDENTICAL: // ===
				case T_IS_NOT_IDENTICAL: // !==
				case '?':
				case ':':
					$expected = array(
						T_CONSTANT_ENCAPSED_STRING, // "foo" or 'bar'
						T_ENCAPSED_AND_WHITESPACE, // " $a"
						T_LNUMBER, // 123, 012, 0x1ac
						T_DNUMBER, // 0.12
						T_VARIABLE, // $foo
						T_STRING,
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
						$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', '\' { \'', $line )->escaped() . '</span>';
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
					} elseif( $curlyLever ) {
						$curlyLever++;
					}else {
						$return .= '<br><span class="error" title="' . __LINE__ . '">' . wfMessage( 'foxway-php-syntax-error-unexpected', '\' } \'', $line )->escaped() . '</span>';
						break 2;
					}
					break;
				case '{':
					$curlyLever++;
					break;
			}

			/*****************   DEBUG INFO   *********************************/
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
					case T_ELSE:
						$debug[] = '<span style="color:#0000E6" title="'. ($result ? 'if is FALSE, do next' : 'if is TRUE, ignore this') . '">' . htmlspecialchars($text) . '</span>';
						if( !$result ) {
							$debug[] = '<span style="color:#969696"> ... </span>;';
						}
						break;
					case T_ECHO:
					case T_IF:
					case T_VARIABLE:
						break;
					case '?':
						$debug[] = '<span title="TRUE">?</span>';
						break;
					case T_STRING:
					if( strcasecmp($text, 'true') == 0 || strcasecmp($text, 'false') == 0 ) {
						$debug[] = '<span style="color:#0000E6" title="'. token_name($id) . '">' . htmlspecialchars($text) . '</span>';
						break;
					}
					// break is not necessary here
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

	private static function findIfElseIndexes(&$tokens, &$blocks, $ifIndex, $index) {
		$count = count($tokens);

		$nestedBlocks = 0;
		for( $i = $index; $i < $count; $i++ ) { // find end of block
			$token = $tokens[$i];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id) = $token;
			}
			switch( $id ) {
				case '{':
					$nestedBlocks++;
					break;
				case '}':
					$nestedBlocks--;
					// break is not necessary here
				case ';':
					if($nestedBlocks == 0 ) {
						break 2;
					}
					break;
				case T_IF:
					if( $nestedBlocks == 0 ) {
						if( !isset($blocks[$i][FOX_ENDIF]) ) {
							if( self::findIfElseIndexes($tokens, $blocks, $i, self::findLastParenthesis($tokens, $i)) !== true ) {
								return false;
							}
						}
						$i = $blocks[$i][FOX_ENDIF];
						break 2;
					}
					break;
			}
		}
		if( $i == $count-1 ) {
			return false; // end of block not find
		}
		$blocks[$ifIndex][FOX_ENDBLOCK] = $i;

		//$else = false;
		for( $i++; $i < $count; $i++ ) { // find T_ELSE or T_ELSEIF
			$token = $tokens[$i];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id) = $token;
			}
			switch ($id) {
				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_WHITESPACE:
					break; // ignore it
				//case T_ELSEIF:
				case T_ELSE:
					if( self::findIfElseIndexes($tokens, $blocks, $i, $i+1) !== true ) {
						return false;
					}
					$blocks[$ifIndex][FOX_ELSE] = $i; // We fount T_ELSE or T_ELSEIF
					$blocks[$ifIndex][FOX_ENDIF] = $blocks[$i][FOX_ENDBLOCK];
					\MWDebug::log( 'T_ELSE ' . var_export($blocks[$ifIndex], true));
					break 2; //              Exit
				default: // ELSE not exists
					$blocks[$ifIndex][FOX_ELSE] = false;
					$blocks[$ifIndex][FOX_ENDIF] = $blocks[$ifIndex][FOX_ENDBLOCK];
					//$blocks[$ifIndex][FOX_ENDIF] = $blocks[$index][FOX_ENDBLOCK];
					\MWDebug::log( 'default ' . var_export($blocks[$ifIndex], true));
					break 2;
			}
		}
		//$blocks[$ifIndex][FOX_ELSE] = $else;
		\MWDebug::log( "function findIfElseIndexes(\$tokens, $ifIndex, $index) @ count = $count, i = $i, " . var_export($blocks[$ifIndex], true) );
		return true;
	}

	private static function findLastParenthesis(&$tokens, $index) {
		$parenthesesLevel = 0;
		$count = count($tokens);
		for( $i = $index; $i < $count; $i++ ) {
			switch ($tokens[$i]) {
				case '(':
					$parenthesesLevel++;
					break;
				case ')':
					$parenthesesLevel--;
					if ( $parenthesesLevel == 0 ) {
						break 2;
					}
			}
		}
		return $i;
	}

}
