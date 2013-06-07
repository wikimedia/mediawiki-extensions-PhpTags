<?php
namespace Foxway;

define( 'FOXWAY_ENDBLOCK', 0 );
define( 'FOXWAY_ENDIF', 1 );
define( 'FOXWAY_ELSE' , 2 );
define( 'FOXWAY_VALUE', 3 );

define( 'FOXWAY_ALLOW_PARENTHES_WITH_VOID_PARAMS', 1 << 0 );
define( 'FOXWAY_EXPECT_CURLY_CLOSE', 1 << 1 );
define( 'FOXWAY_EXPECT_QUOTES_CLOSE', 1 << 2 );
define( 'FOXWAY_ALLOW_LIST_PARAMS', 1 << 3 );
define( 'FOXWAY_EXPECT_PARENTHES_CLOSE', 1 << 4 );
define( 'FOXWAY_EXPECT_BRACKET_CLOSE', 1 << 5 );
define( 'FOXWAY_EXPECT_PARENTHES_WITH_LIST_PARAMS', 1 << 6 );
define( 'FOXWAY_ALLOW_PARAMS_ENDING_BY_COMMA', 1 << 7 );
define( 'FOXWAY_EXPECT_PARENTHES_ENDING_BY_COMMA', 1 << 8 );
define( 'FOXWAY_EXPECT_SEMICOLON', 1 << 9 );
define( 'FOXWAY_EXPECT_FUNCTION_PARENTHESES', 1 << 10 );
define( 'FOXWAY_ALLOW_ASSIGMENT', 1 << 11 );
define( 'FOXWAY_EXPECT_PARENTHES_WITH_DOUBLE_ARROW', 1 << 12 );
define( 'FOXWAY_ALLOW_DOUBLE_ARROW', 1 << 13 );
define( 'FOXWAY_NEED_CONCATENATION_OPERATOR', 1 << 14 );
define( 'FOXWAY_EXPECT_STATIC_VARIABLE', 1 << 15 );
define( 'FOXWAY_EXPECT_GLOBAL_VARIABLE', 1 << 16 );
define( 'FOXWAY_EXPECT_PARENTHES_WITH_VARIABLE_ONLY', 1 << 17 );
define( 'FOXWAY_ALLOW_PARENTHES_WITH_VARIABLE_ONLY', 1 << 18 );

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
		',',
		')',
		T_DOUBLE_ARROW,				// =>
	);

	private static $arrayParams = array(
		T_CONSTANT_ENCAPSED_STRING, // "foo" or 'bar'
		T_ENCAPSED_AND_WHITESPACE, // " $a"
		T_LNUMBER, // 123, 012, 0x1ac
		T_DNUMBER, // 0.12
		T_NUM_STRING, // "$a[0]"
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
		T_UNSET_CAST, // (unset)
		T_ARRAY,	// array()
		T_ISSET,	// isset()
		T_UNSET,	// unset()
		T_EMPTY,	// empty()
	);

	private static $assigmentOperators = array(
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
	);

	public static function run($source, array $args=array(), $scope='', $is_debug=false) {
		$tokens = self::getTokens($source);

		$return = array();
		$debug = $is_debug ? new Debug() : false;
		$blocks = array();
		$expected = false;
		$parentheses = array();
		$parenthesFlags = FOXWAY_EXPECT_SEMICOLON;
		$curlyLever = 0;
		$IfIndex = false;
		$incrementVariable = false;
		$commandResult = null;
		$tokenLine = 1;

		if( $debug ) {
			$runtime = new RuntimeDebug( $args, $scope );
		} else {
			$runtime = new Runtime( $args, $scope );
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
					$parenthesFlags = FOXWAY_EXPECT_SEMICOLON;
					if ( !($parenthesFlags & FOXWAY_EXPECT_SEMICOLON) ) {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					$commandResult = $runtime->getCommandResult();
					break;
				case ',':
					if( $parenthesFlags & (FOXWAY_EXPECT_STATIC_VARIABLE|FOXWAY_EXPECT_GLOBAL_VARIABLE) ) {
						continue;
					}
					if ( !($parenthesFlags & FOXWAY_ALLOW_LIST_PARAMS) ) {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					$runtime->addOperator($id);
					break;
				case T_DOUBLE_ARROW: // =>
					if ( !($parenthesFlags & FOXWAY_ALLOW_DOUBLE_ARROW) ) {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					$runtime->addOperator($id);
					break;
				case '(':
				case '[':
					$parentheses[] = $parenthesFlags;
					$runtime->addOperator( $id );
					break;
				case ']':
					$parenthesFlags = array_pop( $parentheses );
					$runtime->addOperator( $id );
					break;
				case ')':
					if ( !($parenthesFlags & FOXWAY_EXPECT_PARENTHES_CLOSE) ) {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					$parenthesFlags = array_pop($parentheses);
					if( $parenthesFlags & FOXWAY_EXPECT_PARENTHES_WITH_LIST_PARAMS && !($parenthesFlags & FOXWAY_EXPECT_SEMICOLON) ) {
						$commandResult = $runtime->addOperator( ',)' );
					}else{
						$commandResult = $runtime->addOperator( $id );
					}
					if( $parenthesFlags & FOXWAY_EXPECT_FUNCTION_PARENTHESES ) {
						$parenthesFlags = array_pop($parentheses);
					}
					$expected = self::$arrayOperators;
					if( $parenthesFlags & FOXWAY_EXPECT_BRACKET_CLOSE ) {
						$expected[] = ']';
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
						$expected = array('?', ',', ';', ')');
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
						if( $parenthesFlags & FOXWAY_EXPECT_BRACKET_CLOSE ) {
							$expected[] = ']';
						}
					}
					$runtime->addOperator($id);
					break;
				case T_ELSEIF:
					$parentheses[] = $parenthesFlags;
					$parenthesFlags = FOXWAY_EXPECT_FUNCTION_PARENTHESES;
					// break is not necessary here
				case T_ELSE:
					if( isset($blocks[$index]) ) {
						$commandResult = array($id, $blocks[$index][FOXWAY_VALUE]);
					} else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				case T_ARRAY:
					$expected = array('(');
					$runtime->addCommand($id);
					$parentheses[] = $parenthesFlags;
					$parenthesFlags = FOXWAY_EXPECT_PARENTHES_WITH_LIST_PARAMS |
							FOXWAY_EXPECT_PARENTHES_ENDING_BY_COMMA |
							FOXWAY_EXPECT_FUNCTION_PARENTHESES |
							FOXWAY_EXPECT_PARENTHES_WITH_DOUBLE_ARROW;
					break;
				case T_IF:
					$parentheses[] = $parenthesFlags; //TODO check &= ~FOXWAY_EXPECT_SEMICOLON;
					$parenthesFlags = FOXWAY_EXPECT_FUNCTION_PARENTHESES;
					$IfIndex = $index;
					$expected = array('(');
					$runtime->addCommand($id);
					break;
				case T_ECHO:
					$runtime->addCommand($id);
					$parenthesFlags = FOXWAY_ALLOW_LIST_PARAMS | FOXWAY_EXPECT_SEMICOLON;
					break;
				case T_CONSTANT_ENCAPSED_STRING:
					$is_apostrophe = substr($text, 0, 1) == '\'' ? true : false;
					$string = substr($text, 1, -1);
					$runtime->addParamValue( self::process_slashes($string, $is_apostrophe) );
					break;
				case T_NUM_STRING:
				case T_LNUMBER:
					$runtime->addParamValue( self::getIntegerFromString($text) );
					break;
				case T_DNUMBER:
					$runtime->addParamValue( self::getFloatFromString($text) );
					break;
				case T_ENCAPSED_AND_WHITESPACE: // " $a"
					if( $parenthesFlags & FOXWAY_NEED_CONCATENATION_OPERATOR ) {
						$runtime->addOperator('.');
					}elseif( $parenthesFlags & FOXWAY_EXPECT_QUOTES_CLOSE ){
						$parenthesFlags |= FOXWAY_NEED_CONCATENATION_OPERATOR;
					}
					$runtime->addParamValue( self::process_slashes($text, false) );
					$expected = array(T_ENCAPSED_AND_WHITESPACE, T_CURLY_OPEN, T_VARIABLE, '"');
					break;
				case T_VARIABLE:
					if( $expected && in_array(T_VARIABLE, $expected) ) {
						if( $parenthesFlags & FOXWAY_EXPECT_CURLY_CLOSE ) {
							$expected = array( '}' );
						} elseif( $parenthesFlags & FOXWAY_EXPECT_GLOBAL_VARIABLE ) {
							$runtime->addParamVariable($text, T_GLOBAL);
							$expected = array( ',', ';' );
							continue 2;
						} elseif( $parenthesFlags & FOXWAY_EXPECT_STATIC_VARIABLE ) {
							$r = $runtime->addParamVariable($text, T_STATIC);
							if( $r instanceof ErrorMessage ) {
								$r->tokenLine = $tokenLine;
								$return[] = $r;
								break 2;
							}
							if( $r === false ) {
								$count = count($tokens);
								for( ; $index < $count; $index++ ) { // skip already initialized static variable;
									switch ($tokens[$index]) {
									   case ',':
										   $expected = array(T_VARIABLE);
										   continue 3;
									   case ';':
										   $index--;
										   $expected = array(';');
										   continue 3;
								   }
								}
								$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, '$end');
								break 2;
							} else {
								$expected = array( '=', ',', ';' );
							}
							continue;
						} else {
							$expected = array_merge( self::$arrayOperators,	self::$assigmentOperators );
							$parenthesFlags |= FOXWAY_ALLOW_ASSIGMENT;
							if( $incrementVariable ) {
								$incrementVariable = false;
							} else {
								$expected[] = T_INC; // ++
								$expected[] = T_DEC; // --
							}
							if( $parenthesFlags & FOXWAY_EXPECT_BRACKET_CLOSE ) {
								$expected[] = ']';
							}
						}
						if( $parenthesFlags & FOXWAY_NEED_CONCATENATION_OPERATOR ) {
							$runtime->addOperator('.');
						}elseif( $parenthesFlags & FOXWAY_EXPECT_QUOTES_CLOSE ){
							$parenthesFlags |= FOXWAY_NEED_CONCATENATION_OPERATOR;
						}
						if( $parenthesFlags & FOXWAY_EXPECT_QUOTES_CLOSE || $parenthesFlags & FOXWAY_EXPECT_CURLY_CLOSE ) {
							$expected[] = T_VARIABLE; // Allow: echo "$s$s";
							$expected[] = T_CURLY_OPEN; // Allow: echo "$s{$s}";
							$expected[] = '"'; //TODO check it
							//$runtime->addOperator('.');
						}
					} else {
						$parenthesFlags |= FOXWAY_ALLOW_ASSIGMENT;
						$expected = array_merge( self::$assigmentOperators, array(T_INC, T_DEC, ',') );
					}
					$runtime->addParamVariable( $text );
					$expected[] = '[';
					break;
				case T_STRING:
					if( strcasecmp($text, 'true') == 0 ) {
						$runtime->addParamValue( true );
					} elseif( strcasecmp($text, 'false') == 0 ) {
						$runtime->addParamValue( false );
					} elseif( strcasecmp($text, 'null') == 0 ) {
						$runtime->addParamValue( null );
					} elseif( self::stringIsFunction($tokens, $index+1) ) {
						if( self::getClassNameForFunction($text) === false ){
							$return[] = new ErrorMessage(
									__LINE__,
									$tokenLine,
									E_ERROR,
									array( 'foxway-php-fatal-error-undefined-function', $text, isset($args[0])?$args[0]:'n\a' )
								);
							break 2;
						}
						$runtime->addCommand($text);
						$expected = array('(');
						$parentheses[] = $parenthesFlags;
						$parenthesFlags = FOXWAY_EXPECT_FUNCTION_PARENTHESES | FOXWAY_EXPECT_PARENTHES_WITH_LIST_PARAMS | FOXWAY_ALLOW_PARENTHES_WITH_VOID_PARAMS;
						if( $debug ) {
							$debug[] = $token;
						}
						continue 2;
					} elseif( isset(self::$PHPConstants[$text]) ) {
						$runtime->addParamValue( self::$PHPConstants[$text] );
					} else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				case T_ISSET:
					$runtime->addCommand('isset');
					break;
				case T_UNSET:
					$runtime->addCommand('unset');
					break;
				case T_EMPTY:
					$runtime->addCommand('empty');
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
				if( is_array($commandResult) ) {
					list($command, $result) = $commandResult;
					if( $debug && $command != T_ELSEIF && $command != T_ELSE ) {
						$debug->addCommandResult($runtime);
					}
					switch ($command) {
						case T_ECHO:
							$return = array_merge($return, $result);
							break;
						case T_IF:
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
									$debug[] = 'skip';
								}
								continue 2;
							}
							break;
					}
				}elseif( $commandResult instanceof ErrorMessage ) {
					$commandResult->tokenLine = $tokenLine;
					$return[] = $commandResult;
					if( $commandResult->type == E_ERROR ) {
						break;
					}
				}elseif( $commandResult instanceof iRawOutput ) {
					$return[] = $commandResult;
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
					if( $parenthesFlags & FOXWAY_EXPECT_QUOTES_CLOSE ) {
						$parenthesFlags = array_pop($parentheses);
						$runtime->addOperator('")');
					} else {
						$parentheses[] = $parenthesFlags;
						$parenthesFlags = FOXWAY_EXPECT_QUOTES_CLOSE;
						$runtime->addOperator('"(');
						$expected = array(T_ENCAPSED_AND_WHITESPACE, T_CURLY_OPEN, T_VARIABLE, '"');
						break;
					}
					// break is not necessary here
				case T_CONSTANT_ENCAPSED_STRING:
				case T_NUM_STRING:
				case T_LNUMBER:
				case T_DNUMBER:
				case T_STRING:
				case ']':
					$expected = self::$arrayOperators;
					if( $parenthesFlags & FOXWAY_EXPECT_BRACKET_CLOSE ) {
						$expected[] = ']';
					}
					break;
				case '(':
					if( $expected != array('(') ) {
						break;
					}
					// break is not necessary here
				case T_ECHO:
				case ',':
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
				case T_DOUBLE_ARROW:	// =>
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
				case '[':
					$expected = self::$arrayParams;
					break;
				case '=':
					if( $parenthesFlags & FOXWAY_EXPECT_STATIC_VARIABLE ) {
						$expected = array( T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER ); // static and global variables
					}else{
						$expected = self::$arrayParams;
					}
					break;
				case ':':
					$expected = self::$arrayParams;
					$expected[] = '?';
					break;
				case T_CURLY_OPEN:
					if( $parenthesFlags & FOXWAY_EXPECT_QUOTES_CLOSE ) {
						$parentheses[] = $parenthesFlags;
						$parenthesFlags = FOXWAY_EXPECT_CURLY_CLOSE |
								( $parenthesFlags & FOXWAY_NEED_CONCATENATION_OPERATOR ? FOXWAY_NEED_CONCATENATION_OPERATOR : 0 );
						$expected = array( T_VARIABLE );
					} else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				case '}':
					if( $parenthesFlags & FOXWAY_EXPECT_CURLY_CLOSE ) {
						$parenthesFlags = array_pop($parentheses);
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
						$curlyLever--;
					}else {
						$return[] = new ErrorMessage(__LINE__, $tokenLine, E_PARSE, $id);
						break 2;
					}
					break;
				case '{':
					$curlyLever++;
					break;
				case T_STATIC:
					$parenthesFlags |= FOXWAY_EXPECT_STATIC_VARIABLE;
					$expected = array(T_VARIABLE);
					break;
				case T_GLOBAL:
					$parenthesFlags |= FOXWAY_EXPECT_GLOBAL_VARIABLE;
					$expected = array(T_VARIABLE);
					break;
				case T_ISSET:
				case T_UNSET:
				case T_EMPTY:
					$parentheses[] = $parenthesFlags;
					$parenthesFlags = FOXWAY_EXPECT_FUNCTION_PARENTHESES | FOXWAY_EXPECT_PARENTHES_WITH_LIST_PARAMS | FOXWAY_ALLOW_PARENTHES_WITH_VOID_PARAMS;
					$expected = array('(');
					break;
			}

			/*****************   EXPECT  PHASE  TWO  **************************/
			switch ($id) {
				case T_ARRAY:
					$parenthesFlags |= FOXWAY_ALLOW_PARENTHES_WITH_VOID_PARAMS;
					break;
				case '(':
					if( $parenthesFlags & FOXWAY_ALLOW_PARENTHES_WITH_VOID_PARAMS ) {
						$expected[] = ')';
					}
					// @todo OPTIMIZE IT!!!
					$parenthesFlags = FOXWAY_EXPECT_PARENTHES_CLOSE |
							( $parenthesFlags & FOXWAY_EXPECT_PARENTHES_WITH_LIST_PARAMS ? FOXWAY_ALLOW_LIST_PARAMS : 0 ) |
							( $parenthesFlags & FOXWAY_EXPECT_PARENTHES_ENDING_BY_COMMA ? FOXWAY_ALLOW_PARAMS_ENDING_BY_COMMA : 0 ) |
							( $parenthesFlags & FOXWAY_EXPECT_PARENTHES_WITH_DOUBLE_ARROW ? FOXWAY_ALLOW_DOUBLE_ARROW : 0 ) |
							( $parenthesFlags & FOXWAY_EXPECT_PARENTHES_WITH_VARIABLE_ONLY ? FOXWAY_ALLOW_PARENTHES_WITH_VARIABLE_ONLY : 0 );
					if( $parenthesFlags & FOXWAY_ALLOW_PARENTHES_WITH_VARIABLE_ONLY ) {
						$expected = array( T_VARIABLE );
					}
					break;
				case '[':
					$parenthesFlags = FOXWAY_EXPECT_BRACKET_CLOSE;
					$expected[] = ']';
					break;
				case ']':
					$expected[] = '[';
					if( $parenthesFlags & FOXWAY_ALLOW_ASSIGMENT ) {
						$expected = array_merge( $expected, self::$assigmentOperators );
					}
					if( $parenthesFlags & FOXWAY_EXPECT_QUOTES_CLOSE ) {
						$expected[] = '"';
					}
					break;
				case ',':
					if( $parenthesFlags & FOXWAY_ALLOW_PARAMS_ENDING_BY_COMMA ) {
						$expected[] = ')';
					}
					if( $parenthesFlags & FOXWAY_ALLOW_PARENTHES_WITH_VARIABLE_ONLY ) {
						$expected = array( T_VARIABLE );
					}
			}
		}
		if( $debug ) {
			array_unshift($return, $debug);
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

	private static function findTernaryIndexes( &$tokens, &$blocks, $index ) {
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
		$parentheses = 0;
		for( ; $i < $count; $i++ ) { // find end of ternary operator
			switch ( $tokens[$i] ) {
				case '(':
					$parentheses++;
					break;
				case ')':
					if( $parentheses != 0 ) {
						$parentheses--;
						break;
					}
					// break is not necessary here
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

	/**
	 * Checks whether the T_STRING function
	 * @param array $tokens
	 * @param int $index
	 * @return boolean TRUE if T_STRING is function
	 */
	private static function stringIsFunction(&$tokens, $index) {
		$count = count($tokens);
		for( $i = $index; $i < $count; $i++ ) {
			$token = $tokens[$i];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id) = $token;
			}
			switch( $id ) {
				case '(':
					return true;
					break;
				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_WHITESPACE:
					break; // ignore it
				default :
					break 2;
			}
		}
		return false;
	}

	/**
	 * Checks whether the $name is function defined in $wgFoxwayFunctions
	 * @global array $wgFoxwayFunctions
	 * @param string $name
	 * @return boolean
	 */
	public static function getClassNameForFunction($name) {
		global $wgFoxwayFunctions;
		foreach ($wgFoxwayFunctions as $key => &$value) {
			if( array_search($name, $value) !== false ) {
				return $key;
			}
		}
		return false;
	}

	/**
	 * @todo Need to select the most optimal variant
	 * Conversion string to integer the same way as PHP
	 * @see http://www.php.net/manual/ru/language.types.integer.php
	 * @param string $text
	 * @return int
	 */
	private static function getIntegerFromString($string) {
		if( stripos($string, 'x') === false && (strncmp('0', $string, 1) == 0 || strncmp('+0', $string, 2) == 0 || strncmp('-0', $string, 2) == 0) ) {
			return intval( $string, 8 );
		}
		return 0 + $string; // (int)$string fails for 0x1A (InterpreterTest::testRun_echo_intval_10)
		/*
		if( preg_match('/^[+-]?(?:[1-9][0-9]*|0)$/', $string) ) {
			return (int)$string;
		}
		if( preg_match('/^[+-]?0[xX][0-9a-fA-F]+$/', $string) ) {
			return 0 + $string; // (int)$string fails for 0x1A (InterpreterTest::testRun_echo_intval_10)
		}
		$matches = array();
		if( preg_match('/^[+-]?(:?0[0-7]+)/', $string, $matches) ) {
			return intval( $matches[0], 8 );
		}*/
	}

	/**
	 * Conversion string to float the same way as PHP
	 * @see http://www.php.net/manual/ru/language.types.integer.php
	 * @param string $text
	 * @return int
	 */
	private static function getFloatFromString($string) {
		$epos = stripos($string, 'e');
		if( $epos === false ) {
			return (float)$string;
		}
		return (float)( substr($string, 0, $epos) * pow(10, substr($string, $epos+1)) );
	}

	private static $PHPConstants = array(
		'CASE_UPPER' => CASE_UPPER,
		'CASE_LOWER' => CASE_LOWER,
		'SORT_ASC' => SORT_ASC,
		'SORT_DESC' => SORT_DESC,
		'SORT_REGULAR' => SORT_REGULAR,
		'SORT_NUMERIC' => SORT_NUMERIC,
		'SORT_STRING' => SORT_STRING,
		'SORT_LOCALE_STRING' => SORT_LOCALE_STRING,
		//'SORT_NATURAL' => SORT_NATURAL, // @todo PHP >= 5.4.0
		//'SORT_FLAG_CASE' => SORT_FLAG_CASE, // @todo PHP >= 5.4.0
		'COUNT_RECURSIVE' => COUNT_RECURSIVE,
	);

}
