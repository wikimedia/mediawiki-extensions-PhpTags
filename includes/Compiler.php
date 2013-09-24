<?php
namespace Foxway;

define( 'FOXWAY_STACK_RESULT', 'r' );
define( 'FOXWAY_STACK_COMMAND', 'c' );
define( 'FOXWAY_STACK_PARAM', 'p' );
define( 'FOXWAY_STACK_PARAM_2', 's' );
define( 'FOXWAY_STACK_INC_AFTER', 'i' );
define( 'FOXWAY_STACK_TOKEN_LINE', 'l' );
define( 'FOXWAY_STACK_DO_TRUE', 't' );
define( 'FOXWAY_STACK_DO_FALSE', 'f' );

define( 'FOXWAY_RESULT_NONE', 0);
define( 'FOXWAY_RESULT_SINGLE', 1);
define( 'FOXWAY_RESULT_ARRAY', 3);

define( 'FOXWAY_EXPECT_START_COMMAND', 1 << 0 );
define( 'FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR', 1 << 1 );
define( 'FOXWAY_EXPECT_PARENTHES_CLOSE', 1 << 2 );
define( 'FOXWAY_NEED_RESTORE_OPERATOR', 1 << 3 ); // it is set if operator exists before parentheses, example: 5 + (
define( 'FOXWAY_NEED_RESTORE_RIGHT_OPERATORS', 1 << 4 ); // it is set if right operator exists before parentheses, example: ~(

define( 'FOXWAY_EXPECT_LIST_PARAMS', 1 << 5 );
define( 'FOXWAY_EXPECT_PARENTHESES_WITH_LIST_PARAMS', 1 << 6 ); // in FOXWAY_CLEAR_FLAG_FOR_SHIFT_BEFORE_PARENTHESES
define( 'FOXWAY_EXPECT_SEMICOLON', 1 << 7 );

define( 'FOXWAY_EXPECT_RESULT_FROM_PARENTHESES', 1 << 8 );
define( 'FOXWAY_EXPECT_TERNARY_MIDDLE', 1 << 9 );
define( 'FOXWAY_EXPECT_TERNARY_END', 1 << 10 );
define( 'FOXWAY_EXPECT_DO_TRUE_STACK', 1 << 11 );
define( 'FOXWAY_EXPECT_DO_FALSE_STACK', 1 << 12 );
define( 'FOXWAY_EXPECT_CURLY_CLOSE', 1 << 13 );
define( 'FOXWAY_EXPECT_ELSE', 1 << 14 );
define( 'FOXWAY_KEEP_EXPECT_ELSE', 1 << 15 );

define( 'FOXWAY_CLEAR_FLAG_FOR_SHIFT_BEFORE_PARENTHESES', FOXWAY_EXPECT_PARENTHESES_WITH_LIST_PARAMS );
//define( 'FOXWAY_CLEAR_FLAG_FOR_SHIFT_AFTER_PARENTHESES', FOXWAY_EXPECT_PARENTHESES_WITH_LIST_PARAMS );
define( 'FOXWAY_CLEAR_FLAG_FOR_VALUE', ~(FOXWAY_EXPECT_START_COMMAND|FOXWAY_EXPECT_ELSE) );

define( 'FOXWAY_INDEX_FOR_PARAMS', '0' );

/**
 * Compiler class of Foxway extension.
 * This class converts a php code as data for the class Runtime
 *
 * @file Compiler.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Compiler {

	/**
	 * Operator Precedence
	 * @var array
	 */
	protected static $operatorsPrecedence = array(
		//array('['),
		//		++		--		(int)			(float)		(string)		(array)		(bool)			(unset)
		array(T_INC, T_DEC, '~', T_INT_CAST, T_DOUBLE_CAST, T_STRING_CAST, T_ARRAY_CAST, T_BOOL_CAST, T_UNSET_CAST),
		array('!'),
		array('*', '/', '%'),
		array('+', '-', '.'),
		//		<<	>>
		array(T_SL, T_SR),
		//						<=						>=
		array('<', '>', T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL),
		//		==				!=				===				!==
		array(T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL),
		array('&'),
		array('^'),
		array('|'),
		array(T_BOOLEAN_AND), // &&
		array(T_BOOLEAN_OR), // ||
		array('?', ':'),
		//				+=			-=				*=			/=			.=				%=				&=			|=			^=			<<=			>>=				=>
		array('=', T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL, T_MOD_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL, T_DOUBLE_ARROW),
		array(T_LOGICAL_AND), // and
		array(T_LOGICAL_XOR), // xor
		array(T_LOGICAL_OR), // or
		array(','),
		array(';'),
	);
	/**
	 * Coint of self::$operatorsPrecedence
	 * @var int
	 */
	private static $precedenceEqual;

	/**
	 * Unfurled operator precedence
	 * key - operator
	 * value - precedence
	 * @var array
	 */
	private static $precedencesMatrix=array();

	public static function compile($source, $is_debug=false) {
		if( self::$precedenceEqual === null ) {
			foreach (self::$operatorsPrecedence as $key => &$value) {
				self::$precedencesMatrix += array_fill_keys($value, $key);
			}
			self::$precedenceEqual = self::$precedencesMatrix['='];
		}
		$tokens = self::getTokens($source);
		$bytecode = array();

		$values = array(); // array params of operator
		$operator = false;
		$stackEncapsed = false; // for encapsulated strings
		$parentheses = array();
		$parentLevel = 0;
		$parentFlags = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON;
		$stack = array();
		$memory = array();
		$incompleteOperators = array();
		$needParams = array();
		$lastValue = null;
		$needOperator = false;

		$countTokens = count($tokens);
		for( $index = 0; $index < $countTokens; $index++ ){
			$token = &$tokens[$index];
			if ( is_string($token) ) {
				$id = $token;
			} else {
				list($id, $text, $tokenLine) = $token;
			}
			switch ($id) {
				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_WHITESPACE:
					break; // ignore it
				case T_VARIABLE: // $foo
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$needOperator = true;
					$parentFlags &= FOXWAY_CLEAR_FLAG_FOR_VALUE;

					if( $parentFlags & FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR ) { // right operator was used, example: (int)$foo
						array_unshift($values, array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>substr($text, 1), FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
						$values[1][FOXWAY_STACK_PARAM_2] = &$values[0][FOXWAY_STACK_RESULT];
						$parentFlags &= ~FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR;
					}else{ // right operator was not used
						unset($lastValue);
						$lastValue = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>substr($text, 1), FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
						$values[] = &$lastValue;
					}

					if( $stackEncapsed !== false ) {
						$needOperator = false;
						$stackEncapsed[] = &$lastValue[FOXWAY_STACK_RESULT];
					}
					break;
				case T_LNUMBER: // 123, 012, 0x1ac ...
				case T_DNUMBER: // 0.12 ...
				case T_CONSTANT_ENCAPSED_STRING: // "foo" or 'bar'
				case T_STRING: // true, false, null ...
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$needOperator = true;
					$parentFlags &= FOXWAY_CLEAR_FLAG_FOR_VALUE;

					if( $id == T_LNUMBER ) {
						$tmp = self::getIntegerFromString($text);
					}elseif( $id == T_DNUMBER ) {
						$tmp = self::getFloatFromString($text);
					}elseif( $id == T_CONSTANT_ENCAPSED_STRING ) {
						$tmp = substr($text, 0, 1) == '\'' ? self::process_slashes_apostrophe( substr($text, 1, -1) ) : self::process_slashes( substr($text, 1, -1) );
					}elseif( strcasecmp($text, 'true') == 0 ) { // $id here must be T_STRING
						$tmp = true;
					} elseif( strcasecmp($text, 'false') == 0 ) {
						$tmp = false;
					} elseif( strcasecmp($text, 'null') == 0 ) {
						$tmp = null;
					} /*elseif( self::stringIsFunction($tokens, $index+1) ) {

					}	*/
					if( $parentFlags & FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR ) { // right operator was used, example: (bool)1
						$values[0][FOXWAY_STACK_PARAM_2] = $tmp;
						$parentFlags &= ~FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR;
					}else{ // right operator was not used
						unset($lastValue);
						$lastValue = array( FOXWAY_STACK_COMMAND=>T_CONST, FOXWAY_STACK_RESULT=>$tmp, FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
					}
					break;
				case '"':
					if( $stackEncapsed === false ) { // This is an opening double quote
						$stackEncapsed = array();
						$parentFlags &= FOXWAY_CLEAR_FLAG_FOR_VALUE;
						break;
					}
					// This is a closing double quote
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$needOperator = true;

					if( $parentFlags & FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR ) { // right operator was used, example: (int)"1$foo"
						array_unshift($values, array(FOXWAY_STACK_COMMAND=>T_ENCAPSED_AND_WHITESPACE, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>$stackEncapsed, FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
						$values[1][FOXWAY_STACK_PARAM_2] = &$values[0][FOXWAY_STACK_RESULT];
						$parentFlags &= ~FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR;
					}else{ // right operator was not used
						unset($lastValue);
						$lastValue = array(
							FOXWAY_STACK_COMMAND=> T_ENCAPSED_AND_WHITESPACE,
							FOXWAY_STACK_RESULT => null,
							FOXWAY_STACK_PARAM => $stackEncapsed,
							FOXWAY_STACK_TOKEN_LINE=>$tokenLine,
							);
						$values[] = &$lastValue;
					}
					$stackEncapsed = false;
					break;
				case T_ENCAPSED_AND_WHITESPACE: // " $a"
					$stackEncapsed[] = self::process_slashes($text);
					break;
				case T_INC:
				case T_DEC:
					if( $stackEncapsed !== false /*|| $incrementOperator*/ ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$precedence = self::$precedencesMatrix[$id];
					if( $needOperator ) { // $foo++
						if( $lastValue[FOXWAY_STACK_COMMAND] != T_VARIABLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						// replace the $lastValue by the link
						$lastValue = array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT => &$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_PARAM => $lastValue, FOXWAY_STACK_INC_AFTER=>true, FOXWAY_STACK_TOKEN_LINE=>$tokenLine);
					}else{ // ++$foo
						$needOperator = true;
						$parentFlags &= FOXWAY_CLEAR_FLAG_FOR_VALUE;

						$index++;
						$token = &$tokens[$index];
						if( is_string($token) && $token[0] != T_VARIABLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

						unset($lastValue);
						$lastValue = array(
							FOXWAY_STACK_COMMAND => $id,
							FOXWAY_STACK_RESULT => null,
							FOXWAY_STACK_PARAM => array( FOXWAY_STACK_PARAM=>substr($token[1], 1) ),
							FOXWAY_STACK_INC_AFTER => false,
							FOXWAY_STACK_TOKEN_LINE=>$tokenLine
							);
						$values[] = &$lastValue;
					}
					break;
				case '+':
				case '-':
					if( !$needOperator ) { // This is negative statement of the next value: -$foo, -5, 5 + -5 ...
						if( $id == '-' ) { // ignore '+'
							if( $parentFlags & FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR ) { // more than one right operator
								array_unshift($values, array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>0, FOXWAY_STACK_TOKEN_LINE=>$tokenLine));
								$values[1][FOXWAY_STACK_PARAM_2] = &$values[0][FOXWAY_STACK_RESULT];
							}else{ // first right operator
								$parentFlags = $parentFlags & FOXWAY_CLEAR_FLAG_FOR_VALUE | FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR;
								unset($lastValue);
								$lastValue = array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>0, FOXWAY_STACK_TOKEN_LINE=>$tokenLine);
								$values[] = &$lastValue;
							}
						}
						break;
					}
					// break is not necessary here
				case '.':
				case '*':
				case '/':
				case '%':
				case '&':
				case '|':
				case '^':
				case T_SL:			// <<
				case T_SR:			// >>
				case T_LOGICAL_AND:	// and
				case T_LOGICAL_XOR:	// xor
				case T_LOGICAL_OR:	// or
				case '<':
				case '>':
				case T_IS_SMALLER_OR_EQUAL:	// <=
				case T_IS_GREATER_OR_EQUAL:	// >=
				case T_IS_EQUAL:			// ==
				case T_IS_NOT_EQUAL:		// !=
				case T_IS_IDENTICAL:		// ===
				case T_IS_NOT_IDENTICAL:	// !==
				case '=':
				case T_PLUS_EQUAL:		// +=
				case T_MINUS_EQUAL:		// -=
				case T_MUL_EQUAL:		// *=
				case T_DIV_EQUAL:		// /=
				case T_CONCAT_EQUAL:	// .=
				case T_MOD_EQUAL:		// %=
				case T_AND_EQUAL:		// &=
				case T_OR_EQUAL:		// |=
				case T_XOR_EQUAL:		// ^=
				case T_SL_EQUAL:		// <<=
				case T_SR_EQUAL:		// >>=
				case '?':			// Ternary operator
					if( !$needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					// break is not necessary here
				case ':': // Ternary middle
					$needOperator = false;

					$precedence = self::$precedencesMatrix[$id];
					if( $operator ) { // This is not first operator
						$operPrec = self::$precedencesMatrix[$operator[FOXWAY_STACK_COMMAND]];
						if( $precedence >= $operPrec ) { // 1*2+ or 1+2*3- and 1*2* or 1/2* and 1+2?
							$operator[FOXWAY_STACK_PARAM_2] = &$lastValue[FOXWAY_STACK_RESULT];
							if( $values ) {
								$stack[$parentLevel][$operPrec] = isset($stack[$parentLevel][$operPrec]) ? array_merge( $stack[$parentLevel][$operPrec], $values ) : $values;
								$values = array();
							}
							$stack[$parentLevel][$operPrec][] = &$operator;
							if( $incompleteOperators ) {
								for( $p=$operPrec; $p<=$precedence; $p++ ) {
									if( isset($incompleteOperators[$parentLevel][$p]) ) {
										$tmp = &$incompleteOperators[$parentLevel][$p];
										$tmp[FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
										$operator = &$tmp;
										unset($incompleteOperators[$parentLevel][$p]);
										unset($tmp);
									}
								}
							}
							$tmp = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>&$operator[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
							unset($operator);
							$operator = &$tmp;
							unset($tmp);
						} else { // 1+2*
							$stack[$parentLevel][$operPrec][] = &$operator; // push $operator without PARAM_2
							$incompleteOperators[$parentLevel][$operPrec] = &$operator; // save link to $operator
							unset($operator);
							$operator = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>&$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
							if( $values ) {
								$stack[$parentLevel][$precedence] = isset($stack[$parentLevel][$precedence]) ? array_merge( $stack[$parentLevel][$precedence], $values ) : $values;
								$values = array();
							}
						}
					}else{ // This is first operator
						$operator = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>&$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
						if( $values ) { // add values to stack if need
							$stack[$parentLevel][$precedence] = isset($stack[$parentLevel][$precedence]) ? array_merge($stack[$parentLevel][$precedence], $values) : $values;
							$values = array();
						}
					}

					if( $precedence == self::$precedenceEqual ) {
						if( !$lastValue || $lastValue[FOXWAY_STACK_COMMAND] != T_VARIABLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

						$operator[FOXWAY_STACK_RESULT] = &$operator[FOXWAY_STACK_PARAM]; // Result of this operator is result of previous operator or result of previous variable
						unset($operator[FOXWAY_STACK_PARAM]); // Prepare for ...
						$operator[FOXWAY_STACK_PARAM] = &$lastValue; // ... save variable

						$lastValue[FOXWAY_STACK_PARAM_2] = $lastValue[FOXWAY_STACK_COMMAND]; // Save stack command
						$lastValue[FOXWAY_STACK_COMMAND] = T_CONST; // Mark for delete

						$parentLevel = count($stack);
					}elseif( $id == '?' ) {
						if( $parentFlags & FOXWAY_EXPECT_TERNARY_END ) { // Examples: echo 1?2:3?
							/****** CLOSE previous operator ':' ******/
							// merge stack if need
							if( $stack ) {
								krsort( $stack );
								foreach ($stack as &$value) {
									ksort( $value );
									$value = call_user_func_array( 'array_merge', $value );
								}
								$s = call_user_func_array('array_merge', $stack);
								//$stack=array();
							}else{
								$s = false;
							}
							$needParams[0][FOXWAY_STACK_DO_FALSE] = $s; // Save stack in operator ':'
							$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_PARAM]; // Save result in operator ':'
							$needParams[1][FOXWAY_STACK_PARAM_2] = &$needParams[0]; // link operator ':' to previous operator '?'
							$operator[FOXWAY_STACK_PARAM] = &$needParams[1][FOXWAY_STACK_RESULT]; // link result of previous operator '?' as param of this operator '?'
							$stack = array_pop($memory);
							$stack[$parentLevel][self::$precedencesMatrix['?']][] = &$needParams[1]; // Save previous operator '?' to stack
							unset($needParams[0], $needParams[1]);
						}else{ // it don't need for double ternary operators ( echo 1?2:3? )
							$parentheses[] = $parentFlags;
						}
						array_unshift( $needParams, &$operator ); // Save operator '?'
						unset($operator);
						$operator = false;
						$parentFlags = FOXWAY_EXPECT_TERNARY_MIDDLE;
						ksort($stack);
						$memory[] = $stack; // Save stack
						$stack = array();
					}elseif( $id == ':' ) {
						if( $parentFlags & FOXWAY_EXPECT_TERNARY_END ) { // Examples: echo 1?2?3:4:
							/****** CLOSE previous operator ':' ******/
							// merge stack if need
							if( $stack ) {
								krsort( $stack );
								foreach ($stack as &$value) {
									ksort( $value );
									$value = call_user_func_array( 'array_merge', $value );
								}
								$s = call_user_func_array('array_merge', $stack);
								$stack=array();
							}else{
								$s = false;
							}
							$needParams[0][FOXWAY_STACK_DO_FALSE] = $s; // Save stack in previous operator ':'
							$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_PARAM]; // Save result in previous operator ':'
							$needParams[1][FOXWAY_STACK_PARAM_2] = &$needParams[0]; // link previous operator ':' to its operator '?'
							$operator[FOXWAY_STACK_PARAM] = &$needParams[1][FOXWAY_STACK_RESULT]; // link result of previous operator '?' as result of this operator ':'
							$s = array( &$needParams[1] );
							unset($needParams[0], $needParams[1]);
							$parentFlags = array_pop($parentheses);
						}else{ // Example: echo 1?2: it not echo 1?2?3:4:
							// merge stack if need
							if( $stack ) {
								krsort( $stack );
								foreach ($stack as &$value) {
									ksort( $value );
									$value = call_user_func_array( 'array_merge', $value );
								}
								$s = call_user_func_array('array_merge', $stack);
								$stack=array();
							}else{
								$s = false;
							}
						}
						if( $parentFlags != FOXWAY_EXPECT_TERNARY_MIDDLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						$parentFlags = FOXWAY_EXPECT_TERNARY_END;

						$operator[FOXWAY_STACK_DO_TRUE] = $s; // Save stack in operator
						array_unshift( $needParams, &$operator ); // Save operator ':'
						unset($operator);
						$operator = false;
					}

					break;
				case ')':
				case ',':
				case ';':
					if( !$needOperator || !$parentFlags & FOXWAY_EXPECT_TERNARY_MIDDLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

closeoperator:
					$precedence = $parentFlags == FOXWAY_EXPECT_TERNARY_END ? self::$precedencesMatrix['?'] : self::$precedencesMatrix[';'];
					if( $operator ) { // Operator exists. Examples: echo (1+2) OR echo 1+2, OR echo 1+2;
						$operPrec = self::$precedencesMatrix[$operator[FOXWAY_STACK_COMMAND]];
						//if( $precedence >= $operPrec ) { // 1*2+ or 1+2*3- and 1*2* or 1/2*
						$operator[FOXWAY_STACK_PARAM_2] = &$lastValue[FOXWAY_STACK_RESULT];
						if( $values ) {
							$stack[$parentLevel][$operPrec] = isset($stack[$parentLevel][$operPrec]) ? array_merge( $stack[$parentLevel][$operPrec], $values ) : $values;
							$values = array();
						}
						$stack[$parentLevel][$operPrec][] = &$operator;

						if( $incompleteOperators ) {
							for( $p=$operPrec; $p<=$precedence; $p++ ) {
								if( isset($incompleteOperators[$parentLevel][$p]) ) {
									$tmp = &$incompleteOperators[$parentLevel][$p];
									$tmp[FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
									$operator = &$tmp;
									unset($incompleteOperators[$parentLevel][$p]);
									unset($tmp);
								}
							}
						}
					}else{ // Operator does not exist.
						$operator = &$lastValue;
						if( $values ) { // Value exists
							$stack[$parentLevel][0] = isset($stack[$parentLevel][0]) ? $stack[$parentLevel][0] = array_merge( $stack[$parentLevel][$precedence], $values ) : $values;
							$values = array();
						}/* else { // Value does not exist. Examples: echo () OR echo , OR echo ;
							// @todo ternary without middle
							throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
						}*/
					}

					if( $parentFlags & FOXWAY_EXPECT_TERNARY_END ) { // Examples: echo 1?2:3
						// merge stack if need
						if( $stack ) {
							krsort( $stack );
							foreach ($stack as &$value) {
								ksort( $value );
								$value = call_user_func_array( 'array_merge', $value );
							}
							$s = call_user_func_array('array_merge', $stack);
							$stack=array();
						}else{
							$s = false;
						}
						$needParams[0][FOXWAY_STACK_DO_FALSE] = $s; // Save stack in operator ':'
						$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT]; // Save result in operator ':'
						unset($lastValue);
						$lastValue = array( FOXWAY_STACK_RESULT=>&$needParams[0] ); // restore operator ':' as value
						$operator = &$needParams[1]; // restore operator '?'
						unset($needParams[0]);
						array_shift($needParams);
						$stack = array_pop($memory); // restore stack
						$parentFlags = array_pop($parentheses);
						goto closeoperator;
					}

					if( $id == ')' ) {
						if( $parentFlags & FOXWAY_EXPECT_PARENTHES_CLOSE == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						$parentLevel--;
						// Save result of parentheses to $lastValue
						unset($lastValue);
						if( $parentFlags & FOXWAY_NEED_RESTORE_RIGHT_OPERATORS ) { // Need restore right operators
							$values = array_pop( $stack[$parentLevel] );
							$values[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
							$lk = array_pop( array_keys($values) ); // Last key
							$lastValue = &$values[$lk];
						}else{
							$lastValue = &$operator;
						}
						unset($operator);

						// Restore $operator if necessary
						if( $parentFlags & FOXWAY_NEED_RESTORE_OPERATOR ) {
							$operator = array_pop( $stack[$parentLevel] );
						}else{
							$operator = false;
						}
						// Restore flags
						$parentFlags = array_pop($parentheses);
						if( $parentFlags & FOXWAY_EXPECT_RESULT_FROM_PARENTHESES ) {
							$needParams[0][FOXWAY_STACK_PARAM] = &$lastValue[FOXWAY_STACK_RESULT]; // Save result of parentheses, exsample: if(true)
							$parentFlags = array_pop($parentheses);
							if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) {
								ksort($stack);
								$memory[] = $stack;
								$stack = array();
							}
							$needOperator = false;
						}
						$parentFlags &= ~FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR;
						break;
					}
					// $id == ',' or ';'
					$needOperator = false;

					if( $id == ',' ) {
						if( $parentFlags & FOXWAY_EXPECT_LIST_PARAMS == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						$needParams[0][FOXWAY_STACK_PARAM][] = &$operator[FOXWAY_STACK_RESULT];
						if( $stack ) {
							ksort($stack);
							$memory[0] = array_merge($stack, $memory[0]);
							$stack = array();
						}
					}else{ // $id == ';'
						if( $parentFlags & FOXWAY_EXPECT_SEMICOLON == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						if( $parentFlags & FOXWAY_EXPECT_LIST_PARAMS ) {
							$needParams[0][FOXWAY_STACK_PARAM][] = &$operator[FOXWAY_STACK_RESULT];
							ksort($stack);
							$stack = array_merge( array(array(array(array_shift($needParams)))), $stack, array_shift($memory) );
							$parentFlags = array_pop($parentheses);
						}

						while(true) {
							krsort( $stack );
							foreach ($stack as &$value) {
								ksort( $value );
								$value = call_user_func_array( 'array_merge', $value );
							}
							$s = call_user_func_array( 'array_merge', $stack );
							if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) { // Exsample: if(1) echo 2;
								$lastValue = &$needParams[0]; // Save link for operator 'else'
								if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // if(1) { echo 2; }
									$lastValue[FOXWAY_STACK_DO_TRUE] = array_merge( $lastValue[FOXWAY_STACK_DO_TRUE], $s );
									break; /********** EXIT **********/
								}else{ // if(1) echo 2;
									$lastValue[FOXWAY_STACK_DO_TRUE] = $s;
									$stack = array_merge( array(array(array(&$lastValue))), array_shift($memory) ); // Restore stack and add operator
									array_shift($needParams);
									$parentFlags = array_pop($parentheses);
								}
							} elseif ( $parentFlags & FOXWAY_EXPECT_DO_FALSE_STACK ) { // Exsample: if(1) echo 2; else echo 3;
								if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // if(1) { echo 2; } else { echo 3;
									$needParams[0][FOXWAY_STACK_DO_FALSE] = array_merge( $needParams[0][FOXWAY_STACK_DO_FALSE], $s );
									break; /********** EXIT **********/
								}else{ // if(1) echo 2; else echo 3;
									$needParams[0][FOXWAY_STACK_DO_FALSE] = $s;
									array_shift($needParams);
									$parentFlags = array_pop($parentheses);
									if( $parentFlags & FOXWAY_KEEP_EXPECT_ELSE ) { // Exsample: if(1) if(2) echo 3; else echo 4;
										$lastValue = &$needParams[0]; // Save link for operator 'else' of 'if(1)'
									}else{ // Exsample: if(1) echo 2; else echo 3;
										$parentFlags &= ~FOXWAY_EXPECT_ELSE;
									}
									break; /********** EXIT **********/
								}
							} else { // Example: echo 1;
								$bytecode[] = $s;
								break; /********** EXIT **********/
							}
						}
						$stack = array();
						$parentLevel = 0;
					}
					unset($operator);
					$operator = false;
					break;
				case '(':
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE | ($parentFlags & FOXWAY_CLEAR_FLAG_FOR_SHIFT_BEFORE_PARENTHESES) >> 1;

					if( $operator ) { // Operator exists. Examples: echo 1+(
						$stack[$parentLevel][] = &$operator; // push $operator temporarily in $parentLevel without PARAM_2
						unset($operator);
						$operator = false;
						$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
					}
					if( $values ) { // right operator was used, example: echo -(
						$stack[$parentLevel][] = $values; // push $values (contains right operators) temporarily in $parentLevel
						$values = array();
						$parentFlags |= FOXWAY_NEED_RESTORE_RIGHT_OPERATORS;
					}
					$parentLevel++;
					break;
				case T_ECHO:		// echo
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					// for compatible with functions that use flag FOXWAY_EXPECT_LIST_PARAMS
					ksort($stack);
					array_unshift($memory, $stack);
					$stack = array();

					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>array(), FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );

					//$parentLevel = 0;
					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_LIST_PARAMS;
					break;
				case T_IF:			// if
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 || $stack || $operator || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );
					//$parentheses[] = $parentFlags;
					if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) { // Example: if(1) if
						$parentFlags |= FOXWAY_KEEP_EXPECT_ELSE;
					}
					$parentheses[] = $parentFlags | FOXWAY_EXPECT_ELSE;
					$parentheses[] = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_TRUE_STACK;
					$parentFlags = FOXWAY_EXPECT_RESULT_FROM_PARENTHESES;
					break;
				case T_ELSE:		// else
					if( $parentFlags & FOXWAY_EXPECT_ELSE == 0 || $stack || $operator || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					array_unshift( $needParams, &$lastValue ); // $lastValue is link to operator 'if'
					if( $parentFlags & FOXWAY_KEEP_EXPECT_ELSE == 0 ) { // Example: if (1) echo 2; else
						$parentFlags &= ~FOXWAY_EXPECT_ELSE; // Skip for: if(1) if (2) echo 3; else
					}
					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_FALSE_STACK;
					break;
				case T_ELSEIF:		// elseif
					if( $parentFlags & FOXWAY_EXPECT_ELSE == 0 || $stack || $operator || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					array_unshift( $needParams, &$lastValue ); // $lastValue is link to operator 'if'
					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>T_IF, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );
					$parentheses[] = $parentFlags|FOXWAY_EXPECT_DO_FALSE_STACK;
					$parentheses[] = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_TRUE_STACK;
					$parentFlags = FOXWAY_EXPECT_RESULT_FROM_PARENTHESES;
					break;
				case '~':
				case '!':
				case T_ARRAY_CAST:	// (array)
				case T_INT_CAST:	// (int)
				case T_DOUBLE_CAST:	// (double)
				case T_STRING_CAST:	// (string)
				case T_BOOL_CAST:	// (bool)
				case T_UNSET_CAST:	// (unset)
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					if( $parentFlags & FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR ) { // more than one right operator
						array_unshift( $values, array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
						$values[1][FOXWAY_STACK_PARAM_2] = &$values[0][FOXWAY_STACK_RESULT];
					}else{ // first right operator
						$parentFlags = $parentFlags & FOXWAY_CLEAR_FLAG_FOR_VALUE | FOXWAY_EXPECT_VALUE_FOR_RIGHT_OPERATOR;
						unset($lastValue);
						$lastValue = array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine);
						$values[] = &$lastValue;
					}
					break;
				case '{':
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 || $stack || $operator || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) {
						$needParams[0][FOXWAY_STACK_DO_TRUE] = array();
					}elseif( $parentFlags & FOXWAY_EXPECT_DO_FALSE_STACK ) {
						$needParams[0][FOXWAY_STACK_DO_FALSE] = array();
					}else{
						throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
					}
					// break is not necessary here
				case T_CURLY_OPEN: // Example: echo "hello {
					$parentheses[] = $parentFlags;
					$parentFlags |= FOXWAY_EXPECT_CURLY_CLOSE;
					break;
				case '}':
					if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					if( $stackEncapsed ) { // Example: echo "hello {$foo}
						$parentFlags = array_pop($parentheses);
						break;
					}

					// Example: if(1) { echo "hello"; }
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 || $stack || $operator || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$lastValue = &$needParams[0];
					array_shift($needParams);
					array_pop($parentheses);
					$parentFlags = array_pop($parentheses);
					if( !isset($lastValue[FOXWAY_STACK_DO_FALSE]) ) { // operator 'else' not used
						$tmp = array_merge( array(array(array(&$lastValue))), array_shift($memory) ); // Restore stack and add operator
						while(true) {
							krsort( $tmp );
							foreach ($tmp as &$value) {
								ksort( $value );
								$value = call_user_func_array( 'array_merge', $value );
							}
							$s = call_user_func_array( 'array_merge', $tmp );
							if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) { // Exsample: if(1) if(2) echo 3;
								if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // Exsample: if(1) { if(2) echo 3; }
									$needParams[0][FOXWAY_STACK_DO_TRUE] = array_merge( $needParams[0][FOXWAY_STACK_DO_TRUE], $s );
									break; /********** EXIT **********/
								}else{ // Exsample: if(1) if(2) { echo 3; }
									$needParams[0][FOXWAY_STACK_DO_TRUE] = $s;
									$tmp = array_merge( array(array(array(&$needParams[0]))), array_shift($memory) ); // Restore stack and add operator
									$parentFlags = array_pop($parentheses) | $parentFlags & FOXWAY_KEEP_EXPECT_ELSE;
								}
							} elseif ( $parentFlags & FOXWAY_EXPECT_DO_FALSE_STACK ) { // Exsample: if(1) echo 2; else if(3) echo 4;
								if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // Exsample: if(1) echo 2; else { if(3) echo 3; }
									$needParams[0][FOXWAY_STACK_DO_FALSE] = array_merge( $needParams[0][FOXWAY_STACK_DO_FALSE], $s );
									break; /********** EXIT **********/
								}else{ // Exsample: if(1) echo 2; else if(3) echo 4;
									$needParams[0][FOXWAY_STACK_DO_FALSE] = $s;
									$parentFlags = array_pop($parentheses);
								}
							} else { // Example: if(1) { echo 2; }
								$bytecode[] = $s;
								break; /********** EXIT **********/
							}
						}
						// $stack = array();
						$parentLevel = 0;
					}else{
						$lastValue = &$needParams[0]; // Save link for operator 'else'
					}
					break;
				default :
					//throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
					break;
			}

		}

		return call_user_func_array( 'array_merge', $bytecode );
		throw new ExceptionFoxway('$end', FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
		//return $stackOperation; //array_merge(array_reverse($defStak), $stackValues);
	}

	private static function getTokens($source) {
		$tokens = token_get_all("<?php $source ?>");

		// remove open tag < ? php
		array_shift($tokens);

		// remove first T_WHITESPACE
		if( is_array($tokens[0]) && $tokens[0][0] == T_WHITESPACE ) {
			array_shift($tokens);
		}

		// remove close tag ? >
		array_pop($tokens);

		return $tokens;
	}

	private static function process_slashes_apostrophe($string) {
		static $pattern = array(
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\\'/', # (\\)*\'
			'/\\\\\\\\/', #							\\
		);
		static $replacement = array('$1\'', '\\');
		return preg_replace($pattern, $replacement, $string);
	}

	private static function process_slashes($string) {
		static $pattern = array(
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\"/', # (\\)*\"
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\n/', # (\\)*\n
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\r/', # (\\)*\r
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\t/', # (\\)*\t
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\v/', # (\\)*\v
			'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\\$/', # (\\)*\$
			'/\\\\\\\\/', #						  \\
		);
		static $replacement = array('$1"', "$1\n", "$1\r", "$1\t", "$1\v", '$1$', '\\');
		return preg_replace($pattern, $replacement, $string);
	}

	/**
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

}
