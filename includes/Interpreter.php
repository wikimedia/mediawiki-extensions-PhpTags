<?php
namespace Foxway;

define( 'FOXWAY_ENDBLOCK', 0 );
define( 'FOXWAY_ENDIF', 1 );
define( 'FOXWAY_ELSE' , 2 );
define( 'FOXWAY_VALUE', 3 );

/**
 * Interpreter class of Foxway extension.
 *
 * @file Interpreter.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Interpreter {

	protected static $skipTokenIds = array(
		T_WHITESPACE,
		T_COMMENT,
		T_DOC_COMMENT,
	);

	protected static $arrayOperators = array(
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
		',',
	);

	protected static $arrayParams = array(
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
		T_ARRAY,	// array()
	);

	public static function run($source, $is_debug = false) {
		$tokens = self::getTokens($source);

		$return = array();
		$debug = $is_debug ? new Debug() : false;
		$blocks = array();
		$expected = false;
		$expectVoidParams = false;
		$expectCurlyClose = false;
		$expectQuotesClose = false;
		$parenthesesLevels = array(0);
		$expectListParams = array(-1);
		$commandsEmbedded = 0;
		$curlyLever = 0;
		$IfIndex = false;
		$incrementVariable = false;
		$commandResult = null;
		$tokenLine = 1;

		if( $debug ) {
			$runtime = new RuntimeDebug();
		} else {
			$runtime = new Runtime();
		}

		$operators = $runtime->getOperators();

		$countTokens = count($tokens);
		for( $index = 0; $index < $countTokens; $index++ ){
			$token = &$tokens[$index];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id, $text, $tokenLine) = $token;
			}

			if( $expected && in_array($id, self::$skipTokenIds) === false && in_array($id, $expected) === false) {
				\MWDebug::log( '$expected = ' . var_export($expected, true));
				$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
				break;
			}

			switch ($id) {
				case ';':
					// TODO: check parenthess level???
					$commandResult = $runtime->getCommandResult();
					break;
				case ',':
					if ( $parenthesesLevels[$commandsEmbedded] != $expectListParams[$commandsEmbedded] ) {
						$r = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						$return[] = $r;
						break 2;
					}
					$runtime->addOperator($id);
					break;
				case '(':
					$parenthesesLevels[$commandsEmbedded]++;
					$runtime->addOperator( $id );
					break;
				case ')':
					$parenthesesLevels[$commandsEmbedded]--;
					$commandResult = $runtime->addOperator( $id );
					if( $parenthesesLevels[$commandsEmbedded] == 0 ) {
						unset($expected[array_search(')', $expected)]);
					}
					break;
				case '?':
					$result = $runtime->addOperator( $id ) ? true : false;
					if( !isset($blocks[$index]) ) {
						$r = self::findTernaryIndexes($tokens, $blocks, $index);
						if( $r instanceof ErrorMessage ) {
							$return[] = $r;
							break 2;
						}
					}
					$elseIndex = $blocks[$index][FOXWAY_ELSE];
					if( $result ) {
						$blocks[$elseIndex][FOXWAY_VALUE] = false;
						// just go next
					} else {
						$blocks[$elseIndex][FOXWAY_VALUE] = true;
						$expected = array( ':' );
						$index = $elseIndex-1;
						if( $debug ) {
							$debug[] = $token;
							$debug->addCommandResult($runtime);
							$debug[] = 'skip';
						}
						continue 2;
					}
					break;
				case ':':
					if( !isset($blocks[$index]) ) {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					if( $blocks[$index][FOXWAY_VALUE] == false ) { // for true just go next
						$index = $blocks[$index][FOXWAY_ENDBLOCK]-1;
						$expected = array('?', ',',';');
						if( $debug ) {
							$debug[] = $token;
							$debug[] = 'skip';
						}
						continue 2;
					}
					break;
				case T_INC: // ++
				case T_DEC: // --
					if( !is_array($tokens[$index-1]) || (is_array($tokens[$index-1]) && $tokens[$index-1][0] != T_VARIABLE) ) {
						$incrementVariable = true;
						$expected = array( T_VARIABLE );
					} else {
						$expected = self::$arrayOperators;
					}
					$runtime->addOperator($id);
					break;
				case T_ELSEIF:
					$commandsEmbedded++;
					// break is not necessary here
				case T_ELSE:
					if( isset($blocks[$index]) ) {
						$commandResult = array($id, $blocks[$index][FOXWAY_VALUE]);
					} else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				case T_IF:
					$IfIndex = $index;
					// break is not necessary here
				case T_ARRAY:
					$expected = array('(');
					// break is not necessary here
				case T_ECHO:
					$runtime->addCommand($id, $index);
					$commandsEmbedded++;
					$parenthesesLevels[$commandsEmbedded] = 0;
					$expectListParams[$commandsEmbedded] = 0; // Allow echo "one", "two", "three";
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
						if( $expectCurlyClose ) {
							$expected = array( '}' );
						} else {
							$expected = array_merge(
									self::$arrayOperators,
									array(
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
									)
							);
							if( $parenthesesLevels[$commandsEmbedded] ) {
								$expected[] = ')';
							}
							if( $incrementVariable ) {
								$incrementVariable = false;
							} else {
								$expected[] = T_INC; // ++
								$expected[] = T_DEC; // --
							}
						}
						if( $expectQuotesClose ) {
							$expected[] = T_VARIABLE; // Allow: echo "$s$s";
							$expected[] = T_CURLY_OPEN; // Allow: echo "$s{$s}";
							$expected[] = '"';
							$runtime->addOperator('.');
						}
					} else {
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
							',',
							);
					}
					$runtime->addParam( "\0$text" );
					break;
				case T_STRING:
					if( strcasecmp($text, 'true') == 0 ) {
						$runtime->addParam( true );
					} elseif( strcasecmp($text, 'false') == 0 ) {
						$runtime->addParam( false );
					} else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				default:
					if( in_array($id, $operators) ) {
						$runtime->addOperator($id);
					}
					break;
			}

			/***********************  DEBUG  *********************************/
			if( $debug ) {
				$debug[] = $token;
				if( ($id == ';' && is_null($commandResult)) || $id == '?' ) {
					$debug->addCommandResult($runtime);
				}
			}

			/*****************  COMMAND RESULT  *******************************/
			if( !is_null($commandResult) ) {
				if( $commandResult instanceof ErrorMessage ) {
					$return[] = $commandResult;
				}elseif( is_array($commandResult) ) {
					list($command, $result) = $commandResult;
					if( $debug && $command != T_ELSEIF && $command != T_ELSE ) {
						$debug->addCommandResult($runtime);
					}
					switch ($command) {
						case T_ECHO:
							$commandsEmbedded--;
							$return[] = $result;
							break;
						case T_IF:
							$commandsEmbedded--;
							if( !isset($blocks[$IfIndex]) ) {
								if( self::findIfElseIndexes($tokens, $blocks, $IfIndex, $index) !== true ) {
									$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, '$end');
									break 2;
								}
							}
							$curBlock = $blocks[$IfIndex];
							$elseIndex = $curBlock[FOXWAY_ELSE];
							if( $result ) {
								if( $elseIndex ) {
									$blocks[$elseIndex][FOXWAY_VALUE] = false;
								}
								$expected = false;
								// just go next
							} else {
								// skip next statement
								// find 'else'
								if( $elseIndex ) {
									$blocks[$elseIndex][FOXWAY_VALUE] = true;
									$expected = array( T_ELSE, T_ELSEIF );
								} else {
									$expected = false;
								}
								$index = $curBlock[FOXWAY_ENDBLOCK];
								$commandResult = null;
								if( $debug ) {
									//$debug[] = $token;
									$debug[] = 'skip';
								}
								continue 2;
							}
							break;
						case T_ELSEIF:
							$commandsEmbedded--;
							if( $result ) { // chek for IF
								// this code from previus swith( $id ) case T_IF: & case T_ECHO:
								$IfIndex = $index;
								$expected = array('(');
								$runtime->addCommand(T_IF);
								break;
							}
							// break is not necessary here
						case T_ELSE:
							if( $result == false ) { // for true just go next
								// skip next statement
								// find end of block
								if( !isset($blocks[$index][FOXWAY_ENDIF]) ) {
									if( self::findIfElseIndexes($tokens, $blocks, $index, $index+1) !== true ) {
										$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, '$end');
										break 2;
									}
								}
								$index = $blocks[$index][FOXWAY_ENDIF];
								$commandResult = null;
								$expected = false;
								if( $debug ) {
									//$debug[] = $token;
									$debug[] = 'skip';
								}
								continue 2;
							}
							break;
					}
				}
				$commandResult = null;
			}

			/*****************   EXPECT  PHASE  ONE  **************************/
			switch ($id) {
				case ';':
				case T_ELSE:
					$expected = false;
					break;
				case '"':
					if( $expectQuotesClose === false ) {
						$runtime->addOperator('"(');
						$runtime->addParam('');
						$expectQuotesClose = true;
						$expected = array(T_ENCAPSED_AND_WHITESPACE, T_CURLY_OPEN, T_VARIABLE, '"');
						break;
					} else {
						$runtime->addOperator('")');
						$expectQuotesClose = false;
					}
					// break is not necessary here
				case T_CONSTANT_ENCAPSED_STRING:
				case T_LNUMBER:
				case T_DNUMBER:
				case T_STRING:
					$expected = self::$arrayOperators;
					if( $parenthesesLevels[$commandsEmbedded] ) {
						$expected[] = ')';
					}
					break;
				case '(':
					if( $expected != array('(') ) {
						break;
					}
					// break is not necessary here
				case T_ECHO:
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
					$expected = self::$arrayParams;
					break;
				case ':':
					$expected = self::$arrayParams;
					$expected[] = '?';
					break;
				case T_CURLY_OPEN:
					if( $expectQuotesClose === true ) {
						$expectCurlyClose = true;
						$expected = array( T_VARIABLE );
					} else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
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
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				case '{':
					$curlyLever++;
					break;
			}

			/*****************   EXPECT  PHASE  TWO  **************************/
			switch ($id) {
				case T_ARRAY:
					$expectVoidParams = true;
					break;
				case '(':
					if( $expectVoidParams ) {
						$expected[] = ')';
					}
					break;
			}
		}
		if( $debug ) {
			array_unshift($return, $debug);
		}
		return $return;
	}

	protected static function process_slashes($string, $is_apostrophe) {
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

	protected static function findTernaryIndexes( &$tokens, &$blocks, $index ) {
		$embedded = 0;
		$count = count($tokens);
		for( $i = $index+1; $i < $count; $i++ ) { // find ternary separator ':'
			$token = $tokens[$i];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id, , $tokenLine) = $token;
			}
			switch ($id) {
				case '?': // is embedded ternary operator
					$embedded++;
					break;
				case ':':
					if( $embedded > 0 ) { // were the embedded ternary operator?
						$embedded--;
					} else { /************************ EXIT HERE ***********************************/
						$elseIndex = $i;
						$blocks[$index][FOXWAY_ELSE] = $i;
						break 2; // found the required separator
					}        /************************ EXIT HERE ***********************************/
					break;
				case ',':
				case ';':
				case T_IF: // This should not occur here, syntax error
					return new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
					break;
			}
		}
		for( ; $i < $count; $i++ ) { // find end of ternary operator
			switch ( $tokens[$i] ) {
				case ',':
				case ';':
				case '?':
					$blocks[$elseIndex][FOXWAY_ENDBLOCK] = $i;
					break 2;
			}
		}
		if( $i == $count ) {
			return new ErrorMessage(__LINE__, $tokenLine, '$end');
		}
	}

	protected static function findIfElseIndexes(&$tokens, &$blocks, $ifIndex, $index) {
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
						if( !isset($blocks[$i][FOXWAY_ENDIF]) && self::findIfElseIndexes($tokens, $blocks, $i, self::findLastParenthesis($tokens, $i)) !== true ) {
							return false;
						}
						$i = $blocks[$i][FOXWAY_ENDIF];
						break 2;
					}
					break;
			}
		}
		if( $i == $count ) {
			return false; // end of block not find
		}
		$blocks[$ifIndex][FOXWAY_ENDBLOCK] = $i;

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
				case T_ELSEIF:
					if( !isset($blocks[$i]) && self::findIfElseIndexes($tokens, $blocks, $i, self::findLastParenthesis($tokens, $i)) !== true ) {
						return false;
					}
					$blocks[$ifIndex][FOXWAY_ELSE] = $i; // We fount T_ELSE or T_ELSEIF
					if( isset($blocks[$i][FOXWAY_ENDIF]) ) {
						$blocks[$ifIndex][FOXWAY_ENDIF] = $blocks[$i][FOXWAY_ENDIF];
						break 2;
					}
					$i = $blocks[$i][FOXWAY_ENDBLOCK];
					break;
				case T_ELSE:
					if( !isset($blocks[$i]) && self::findIfElseIndexes($tokens, $blocks, $i, $i+1) !== true ) {
						return false;
					}
					$blocks[$ifIndex][FOXWAY_ELSE] = $i; // We fount T_ELSE or T_ELSEIF
					$blocks[$ifIndex][FOXWAY_ENDIF] = $blocks[$i][FOXWAY_ENDBLOCK];
					break 2; //              Exit
				default: // ELSE not exists
					$blocks[$ifIndex][FOXWAY_ELSE] = false;
					$blocks[$ifIndex][FOXWAY_ENDIF] = $blocks[$ifIndex][FOXWAY_ENDBLOCK];
					break 2;
			}
		}
		return true;
	}

	protected static function findLastParenthesis(&$tokens, $index) {
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

	private static function getTokens($source) {
		$tokens = token_get_all("<?php $source ?>");

		// remove open tag
		array_shift($tokens);

		// remove first T_WHITESPACE
		if( is_array($tokens[0]) && $tokens[0][0] == T_WHITESPACE ) {
			array_shift($tokens);
		}

		return $tokens;
	}

}
