<?php
namespace Foxway;

define( 'FOXWAY_EXPECT_START_COMMAND', 1 << 0 );
define( 'FOXWAY_EXPECT_VARIABLE_REFERENCE', 1 << 1 );
define( 'FOXWAY_EXPECT_PARENTHES_CLOSE', 1 << 2 );
define( 'FOXWAY_NEED_RESTORE_OPERATOR', 1 << 3 ); // it is set if operator exists before parentheses, example: 5 + (
define( 'FOXWAY_NEED_RESTORE_RIGHT_OPERATORS', 1 << 4 ); // it is set if right operator exists before parentheses, example: ~(

define( 'FOXWAY_EXPECT_LIST_PARAMS', 1 << 5 );
define( 'FOXWAY_EXPECT_PARENTHESES_WITH_LIST_PARAMS', 1 << 6 ); // in FOXWAY_CLEAR_FLAG_FOR_SHIFT_BEFORE_PARENTHESES
define( 'FOXWAY_EXPECT_SEMICOLON', 1 << 7 );

define( 'FOXWAY_EXPECT_RESULT_AS_PARAM', 1 << 8 );
define( 'FOXWAY_EXPECT_TERNARY_MIDDLE', 1 << 9 );
define( 'FOXWAY_EXPECT_TERNARY_END', 1 << 10 );
define( 'FOXWAY_EXPECT_DO_TRUE_STACK', 1 << 11 );
define( 'FOXWAY_EXPECT_DO_FALSE_STACK', 1 << 12 );
define( 'FOXWAY_EXPECT_CURLY_CLOSE', 1 << 13 );
define( 'FOXWAY_EXPECT_ELSE', 1 << 14 );
define( 'FOXWAY_KEEP_EXPECT_ELSE', 1 << 15 );
define( 'FOXWAY_ALLOW_COMMA_AT_END_PARENTHES', 1 << 16 );
define( 'FOXWAY_ALLOW_DOUBLE_ARROW', 1 << 17 );
define( 'FOXWAY_THIS_IS_FUNCTION', 1 << 18 );
define( 'FOXWAY_EXPECT_ARRAY_INDEX_CLOSE', 1 << 19 );
define( 'FOXWAY_EXPECT_EQUAL_END', 1 << 20 );
define( 'FOXWAY_EQUAL_HAVE_OPERATOR', 1 << 21 );

define( 'FOXWAY_CLEAR_FLAG_FOR_SHIFT_BEFORE_PARENTHESES', FOXWAY_EXPECT_PARENTHESES_WITH_LIST_PARAMS );
//define( 'FOXWAY_CLEAR_FLAG_FOR_SHIFT_AFTER_PARENTHESES', FOXWAY_EXPECT_PARENTHESES_WITH_LIST_PARAMS );
define( 'FOXWAY_CLEAR_FLAG_FOR_VALUE', ~(FOXWAY_EXPECT_START_COMMAND|FOXWAY_EXPECT_ELSE) );

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
		//$operator = false;
		$stackEncapsed = false; // for encapsulated strings
		$parentheses = array();
		$parentLevel = 0;
		$parentFlags = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON;
		$stack = array();
		$math = array();
		$memory = array();
		$memOperators = array();
		$memEncapsed = array();
		$incompleteOperators = array();
		$needParams = array();
		//$lastValue = null;
		$needOperator = false;
		$incrementOperator = false;
		$rightOperators = array();
		$ifOperators = array();

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

					$tmp = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>substr($text, 1), FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
					if( $rightOperators ) { // right operator was used, example: (int)$foo
						if( $incrementOperator ) { // increment operator was used. Example: echo (int)++$foo
							$incrementOperator[FOXWAY_STACK_PARAM] = &$tmp;
							unset( $incrementOperator );
							$incrementOperator = false;
						}
						$stack[] = &$tmp;
						$rightOperators[0][FOXWAY_STACK_PARAM_2] = &$tmp[FOXWAY_STACK_RESULT];
						$stack = array_merge( $stack, $rightOperators );
						$rightOperators = array();
					}else{ // right operator was not used
						$lastValue = &$tmp;
						if( $incrementOperator ) { // increment operator was used. Example: echo ++$foo
							$incrementOperator[FOXWAY_STACK_PARAM] = &$tmp;
							unset( $incrementOperator );
							$incrementOperator = false;
							$stack[] = &$lastValue;
						}else{ // increment operator was not used. Example: echo $foo
							$values[] = &$lastValue; // in $values there is T_VARIABLE only if right and increment operators was not used
						}
					}
					unset($tmp);

					if( $stackEncapsed !== false ) {
						$needOperator = false;
						$stackEncapsed[] = &$lastValue[FOXWAY_STACK_RESULT];
						array_pop($values); // Move T_VARIABLE from $values ...
						$stack[] = &$lastValue; // to $stack
					}
					break;
				case T_LNUMBER: // 123, 012, 0x1ac ...
				case T_DNUMBER: // 0.12 ...
				case T_CONSTANT_ENCAPSED_STRING: // "foo" or 'bar'
				case T_STRING: // true, false, null ...
				case T_NUM_STRING: // echo "$foo[1]"; 1 is num string
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$needOperator = true;
					$parentFlags &= FOXWAY_CLEAR_FLAG_FOR_VALUE;

					switch ( $id ) {
						case T_LNUMBER:
						case T_NUM_STRING:
							$tmp = self::getIntegerFromString($text);
							break;
						case T_DNUMBER:
							$tmp = self::getFloatFromString($text);
							break;
						case T_CONSTANT_ENCAPSED_STRING:
							$tmp = substr($text, 0, 1) == '\'' ? self::process_slashes_apostrophe( substr($text, 1, -1) ) : self::process_slashes( substr($text, 1, -1) );
							break;
						default: // T_STRING
							if( strcasecmp($text, 'true') == 0 ) { // $id here must be T_STRING
								$tmp = true;
							} elseif( strcasecmp($text, 'false') == 0 ) {
								$tmp = false;
							} elseif( strcasecmp($text, 'null') == 0 ) {
								$tmp = null;
							} else { // constant, function, etc...
								$tmp = array( FOXWAY_STACK_COMMAND=>T_STRING, FOXWAY_STACK_PARAM_2=>$text, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine, );

								for( $index++; $index < $countTokens; $index++ ){ // find '('
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
										case '(': // T_STRING is function
											$parentLevel++;
											$parentheses[] = $parentFlags;
											$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE | FOXWAY_THIS_IS_FUNCTION | FOXWAY_EXPECT_LIST_PARAMS | FOXWAY_EXPECT_VARIABLE_REFERENCE;
											$tmp[FOXWAY_STACK_PARAM] = array();
											$needParams = array_merge( array(&$tmp), $needParams );
											//$stack[] = &$tmp;
											unset( $tmp );
											if( isset($operator) ) { // Operator exists. Examples: echo 1+function
												$memOperators[] = &$operator; // push $operator temporarily without PARAM_2
												unset($operator);
												$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
											}
											if( $rightOperators ) { // right operator was used, example: echo -function
												$memOperators[] = $rightOperators; // push $rightOperators for restore later
												$rightOperators = array();
												$parentFlags |= FOXWAY_NEED_RESTORE_RIGHT_OPERATORS;
												unset($lastValue);
											}
											ksort( $math );
											$memory[] = array($stack, $math); // push stack for restore late. Example: echo 1 + function
											$stack = array();
											$math = array();
											$needOperator = false;
											break 4; /******* EXIT *******/
										default: // T_STRING is constant
											if( $rightOperators ) { // right operator was used
												$stack[] = &$tmp;
												$rightOperators[0][FOXWAY_STACK_PARAM_2] = &$tmp[FOXWAY_STACK_RESULT];
												$stack = array_merge( $stack, $rightOperators );
												$rightOperators = array();
											}else{
												$values[] = &$tmp;
												$lastValue = &$tmp;
											}
											unset( $tmp );
											$index--;
											break 4; /******* EXIT *******/
									}
								}
								throw new ExceptionFoxway('$end', FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
								break 2; /******* EXIT *******/
							}
							break;
					}

					if( $rightOperators ) { // right operator was used, example: (bool)1
						$rightOperators[0][FOXWAY_STACK_PARAM_2] = $tmp;
						$stack = array_merge( $stack, $rightOperators );
						$rightOperators = array();
					}else{ // right operator was not used
						unset($lastValue); // @todo should be already unspecified
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

					$tmp = array(
						FOXWAY_STACK_COMMAND=> T_ENCAPSED_AND_WHITESPACE,
						FOXWAY_STACK_RESULT => null,
						FOXWAY_STACK_PARAM => $stackEncapsed,
						FOXWAY_STACK_TOKEN_LINE=>$tokenLine,
						);
					$stack[] = &$tmp;
					if( $rightOperators ) { // right operator was used, example: (int)"1$foo"
						$rightOperators[0][FOXWAY_STACK_PARAM_2] = &$tmp[FOXWAY_STACK_RESULT];
					}else{ // right operator was not used
						$lastValue = &$tmp;
					}
					unset($tmp);
					$stackEncapsed = false;
					break;
				case T_ENCAPSED_AND_WHITESPACE: // " $a"
					if( $stackEncapsed === false ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$stackEncapsed[] = self::process_slashes($text);
					break;
				case T_INC:
				case T_DEC:
					if( $stackEncapsed !== false || $incrementOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$precedence = self::$precedencesMatrix[$id];
					if( $needOperator ) { // $foo++
						if( $lastValue[FOXWAY_STACK_COMMAND] != T_VARIABLE ) {
							throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
						}
						array_pop($values); // Move last T_VARIABLE ...
						$stack[] = &$lastValue; // ... to stack ...
						$stack[] = array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_PARAM=>&$lastValue, FOXWAY_STACK_TOKEN_LINE=>$tokenLine); // ... and add ++ after it
					}else{ // ++$foo
						if( is_string($tokens[$index+1]) && $tokens[$index+1][0] != T_VARIABLE ) {
							throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
						}
						$incrementOperator = array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_TOKEN_LINE=>$tokenLine);
						$stack[] = &$incrementOperator;
					}
					break;
				case '+':
				case '-':
					if( !$needOperator ) { // This is negative statement of the next value: -$foo, -5, 5 + -5 ...
						if( $id == '-' ) { // ignore '+'
							array_unshift( $rightOperators, array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>0, FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
							if( isset($rightOperators[1]) ) { // this is not first right operator. Example: echo (int)-
								$rightOperators[1][FOXWAY_STACK_PARAM_2] = &$rightOperators[0][FOXWAY_STACK_RESULT];
							}else{ // this is first right operator. Example: echo -
								$parentFlags = $parentFlags & FOXWAY_CLEAR_FLAG_FOR_VALUE;
								$lastValue = &$rightOperators[0];
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
				case T_BOOLEAN_AND:	// &&
				case T_BOOLEAN_OR:	// ||
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
				case '?':			// Ternary operator
					if( !$needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					// break is not necessary here
				case ':': // Ternary middle
					if( $rightOperators ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$needOperator = false;

					$precedence = self::$precedencesMatrix[$id];
					if( isset($operator) ) { // This is not first operator
						$operPrec = self::$precedencesMatrix[$operator[FOXWAY_STACK_COMMAND]];
						if( $precedence >= $operPrec ) { // 1*2+ or 1+2*3- and 1*2* or 1/2* and 1+2?
							$operator[FOXWAY_STACK_PARAM_2] = &$lastValue[FOXWAY_STACK_RESULT];
							if( $values ) {
								$stack = array_merge($stack, $values);
								$values = array();
							}
							$stack[] = &$operator;
							if( isset($incompleteOperators[$parentLevel]) ) {
								foreach ( $incompleteOperators[$parentLevel] as $incomplPrec=>&$incomplOper ) {
									if( $incomplPrec > $precedence ) {
										break;
									}
									$incomplOper[FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
									$operator = &$incomplOper;
									$stack = array_merge($stack, $math[$parentLevel][$incomplPrec]);
									unset( $incompleteOperators[$parentLevel][$incomplPrec], $math[$parentLevel][$incomplPrec] );
								}
							}
							$tmp = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>&$operator[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
							unset($operator);
							$operator = &$tmp;
							unset($tmp);
						} else { // 1+2*
							$math[$parentLevel][$operPrec][] = &$operator; // push $operator without PARAM_2
							$incompleteOperators[$parentLevel][$operPrec] = &$operator; // save link to $operator
							ksort($incompleteOperators[$parentLevel]);
							unset($operator);
							$operator = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>&$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
						}
					}else{ // This is first operator
						$operator = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>&$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
					}

					if( $id == '?' ) {
						if( $values ) { // There is values without operator. Example: echo $foo?
							$stack = array_merge($stack, $values); // push it to stack
							$values = array();
						}
						if( $parentFlags & FOXWAY_EXPECT_TERNARY_END ) { // use ternary in end previous ternary. Examples: echo 1?2:3?
							/****** CLOSE previous operator ':' ******/
							// prepare $math
							$needParams[0][FOXWAY_STACK_DO_FALSE] = $stack?:false; // Save stack in operator ':'
							$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_PARAM]; // Save result in operator ':'
							$needParams[1][FOXWAY_STACK_PARAM_2] = &$needParams[0]; // link operator ':' to previous operator '?'
							$operator[FOXWAY_STACK_PARAM] = &$needParams[1][FOXWAY_STACK_RESULT]; // link result of previous operator '?' as param of this operator '?'
							list( $stack, $math ) = array_pop($memory); // restore $stack, $math
							$stack[] = &$needParams[1]; // Save previous operator '?' to stack
							unset($needParams[0], $needParams[1]);
						}else{
							// it don't need for double ternary operators. Example: echo 1?2:3?
							$parentheses[] = $parentFlags; // only for first ternery operator. Example: echo 1?
						}
						ksort($math);
						$memory[] = array($stack, $math); // Save $stack, $math for restore late
						$math = array();
						$stack = array();
						$needParams = array_merge( array(&$operator), $needParams ); // Save operator '?'
						//array_unshift( $needParams, &$operator ); // Save operator '?'
						unset($operator);
						//$operator = false;
						$parentFlags = FOXWAY_EXPECT_TERNARY_MIDDLE;
					}elseif( $id == ':' ) {
						if( $values ) { // There is values without operator. Example: echo 1?$foo:
							$stack = array_merge($stack, $values);
							$values = array();
						}
						if( $parentFlags & FOXWAY_EXPECT_TERNARY_END ) { // Examples: echo 1?2?3:4:
							/****** CLOSE previous operator ':' ******/
							//$s = self::mergeStackAndMath($stack, $math);
							$needParams[0][FOXWAY_STACK_DO_FALSE] = $stack?:false; // Save stack in previous operator ':'
							$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_PARAM]; // Save result in previous operator ':'
							$needParams[1][FOXWAY_STACK_PARAM_2] = &$needParams[0]; // link previous operator ':' to its operator '?'
							$operator[FOXWAY_STACK_PARAM] = &$needParams[1][FOXWAY_STACK_RESULT]; // link result of previous operator '?' as result of this operator ':'
							$stack = array( &$needParams[1] );
							unset($needParams[0], $needParams[1]);
							$parentFlags = array_pop($parentheses);
						}
						if( $parentFlags != FOXWAY_EXPECT_TERNARY_MIDDLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						$parentFlags = FOXWAY_EXPECT_TERNARY_END;

						$operator[FOXWAY_STACK_DO_TRUE] = $stack?:false; // Save stack in operator
						$stack = array();
						$needParams = array_merge( array(&$operator), $needParams );
						//array_unshift( $needParams, &$operator ); // Save operator ':'
						unset($operator);
					}
					unset($lastValue);
					break;
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
					if( $lastValue[FOXWAY_STACK_COMMAND] != T_VARIABLE ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					array_pop( $values ); // remove T_VARIABLE from $values
					// break is not necessary here
				case T_DOUBLE_ARROW:	// =>
					if( !$needOperator || !$lastValue || $rightOperators ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$needOperator = false;

					array_unshift( $needParams, array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>&$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_PARAM=>&$lastValue, FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
					if( $id == T_DOUBLE_ARROW ) {
						if( $parentFlags & FOXWAY_ALLOW_DOUBLE_ARROW == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					}else{
						$stack[] = &$needParams[0];
					}
					$parentheses[] = $parentFlags;
					$parentFlags |= FOXWAY_EXPECT_EQUAL_END;

					if( $values ) {
						$stack = array_merge($stack, $values);
						$values = array();
					}

					if( isset($operator) ) { // This is not first operator
						$operator[FOXWAY_STACK_PARAM_2] = &$needParams[0][FOXWAY_STACK_RESULT];
						$memOperators = array_merge( array(&$operator), $memOperators ); //array_unshift($memOperators, &$operator); // push $operator temporarily for restore late
						$parentFlags |= FOXWAY_EQUAL_HAVE_OPERATOR;
						$operPrec = self::$precedencesMatrix[$operator[FOXWAY_STACK_COMMAND]];
						if( $values ) {
							$stack = array_merge($stack, $values);
							$values = array();
						}
						$stack[] = &$operator;
						if( isset($incompleteOperators[$parentLevel]) ) {
							foreach ( $incompleteOperators[$parentLevel] as $incomplPrec=>&$incomplOper ) {
								$incomplOper[FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
								$operator = &$incomplOper;
								$stack = array_merge($stack, $math[$parentLevel][$incomplPrec]);
							}
							unset( $incompleteOperators[$parentLevel], $math[$parentLevel] );
						}
						unset($operator);
					}
					unset($lastValue);

					$memory[] = $stack; // Save $stack for restore late
					$math = array(); // @todo $math must be empty array
					$stack = array();
					break;
				case ']':
					if( $parentFlags & FOXWAY_EXPECT_ARRAY_INDEX_CLOSE == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					if( !isset($operator) ) { $needOperator = true; } // Example: $foo[]
					// break is not necessary here
				case ')':
					if( !$needOperator && !isset($operator) && $parentFlags & FOXWAY_THIS_IS_FUNCTION ) { $needOperator = true; }
					// break is not necessary here
				case ',':
				case ';':
					if( !$needOperator || !$parentFlags & FOXWAY_EXPECT_TERNARY_MIDDLE || $rightOperators ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					if( $values ) {
						$stack = array_merge($stack, $values);
						$values = array();
					}
closeoperator:
					$precedence = $parentFlags == FOXWAY_EXPECT_TERNARY_END ? self::$precedencesMatrix['?'] : self::$precedencesMatrix[';'];
					if( isset($operator) ) { // Operator exists. Examples: echo (1+2) OR echo 1+2, OR echo 1+2;
						$operPrec = self::$precedencesMatrix[$operator[FOXWAY_STACK_COMMAND]];
						//if( $precedence >= $operPrec ) { // 1*2+ or 1+2*3- and 1*2* or 1/2*
						$operator[FOXWAY_STACK_PARAM_2] = &$lastValue[FOXWAY_STACK_RESULT];
						$stack[] = &$operator;

						if( isset($incompleteOperators[$parentLevel]) ) {
							foreach( $incompleteOperators[$parentLevel] as $incomplPrec=>&$incomplOper ) {
								$incomplOper[FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
								$operator = &$incomplOper;
								$stack = array_merge( $stack, $math[$parentLevel][$incomplPrec] );
							}
							unset( $incompleteOperators[$parentLevel], $math[$parentLevel] );
						}
					}elseif( isset($lastValue) ) { // Operator does not exists, but there is value. Operator and value not exists for: array() or array(1,)
						if( isset($lastValue) ) {
							$operator = &$lastValue;
						}else{ // Value does not exist. Examples: echo () OR echo , OR echo ;
							// @todo ternary without middle
							throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
						}
					}

					if( $parentFlags & FOXWAY_EXPECT_TERNARY_END ) { // Examples: echo 1?2:3,
						// prepare $math
						$needParams[0][FOXWAY_STACK_DO_FALSE] = $stack; // Save stack in operator ':'
						$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT]; // Save result in operator ':'
						unset($lastValue);
						$lastValue = array( FOXWAY_STACK_RESULT=>&$needParams[0] ); // restore operator ':' as value
						$operator = &$needParams[1]; // restore operator '?'
						unset($needParams[0]);
						array_shift($needParams);
						list( $stack, $math ) = array_pop($memory); // restore $stack, $math
						$parentFlags = array_pop($parentheses);
						goto closeoperator;
					}

					while ( $parentFlags & FOXWAY_EXPECT_EQUAL_END ) { // Examples: echo ($foo=1+2) OR echo $foo=1, OR echo $foo=1;
						$needParams[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT];
						if( $parentFlags & FOXWAY_EQUAL_HAVE_OPERATOR ) { // Example: echo 1+$foo=2;
							unset($operator);
							$operator = &$memOperators[0];
							array_shift($memOperators);
						}else{ // Example: echo $foo=1;
							$operator = &$needParams[0];
						}
						array_shift($needParams);
						$parentFlags = array_pop( $parentheses );
						$s = array_pop($memory); // restore $stack
						if( $s ) {
							$stack = array_merge($stack, $s);
						}
					}

					switch ($id) {
						case ']':
							$stackEncapsed = array_pop($memEncapsed);
							if( $stackEncapsed !== false ) {
								$needOperator = false;
							}
							unset($lastValue);
							$lastValue = &$needParams[0];
							array_shift($needParams);
							$lastValue[FOXWAY_STACK_ARRAY_INDEX][] = &$operator[FOXWAY_STACK_RESULT]; // $lastValue must be T_VARIABLE only
							$stack[] = &$lastValue;
							unset($operator);
							if( $parentFlags & FOXWAY_NEED_RESTORE_OPERATOR ) {
								$operator = array_pop( $memOperators );
							}
							$parentFlags = array_pop($parentheses);

							list( $tmp, $math ) = array_pop($memory); // restore $stack, $math
							$s = self::mergeStackAndMath($tmp, $math);
							if( $s ) {
								$stack = array_merge( $s, $stack );
							}
							break 2;
						case ')':
							$parentLevel--;
							if( $parentFlags & FOXWAY_EXPECT_PARENTHES_CLOSE == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
							if( $parentFlags & FOXWAY_THIS_IS_FUNCTION ) {
								if( isset($operator) ) {
									if( $parentFlags & FOXWAY_EXPECT_LIST_PARAMS ) {
										$needParams[0][FOXWAY_STACK_PARAM][] = &$operator;
										if( $parentFlags & FOXWAY_EXPECT_VARIABLE_REFERENCE && $operator[FOXWAY_STACK_COMMAND] == T_VARIABLE ) {
											array_pop( $stack );
										}
									}else{
										throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
									}
									unset($operator);
								}
								$operator = &$needParams[0]; // restore result of function as value, this will be set as $lastValue
								$stack[] = &$operator;
								array_shift($needParams);
								list( $s, $math ) = array_pop($memory); // restore $stack, $math
								if( $s ) {
									$stack = array_merge( $s, $stack );
								}
							}

							// Save result of parentheses to $lastValue
							unset($lastValue);
							if( $parentFlags & FOXWAY_NEED_RESTORE_RIGHT_OPERATORS ) { // Need restore right operators
								$tmp = array_pop( $memOperators ); // restore right operators to $tmp
								$tmp[0][FOXWAY_STACK_PARAM_2] = &$operator[FOXWAY_STACK_RESULT]; // Set parents result as param to right operators
								$k = array_keys($tmp);
								$lk = array_pop( $k ); // Get key of last right operator
								$lastValue = &$tmp[$lk]; // Set $lastValue as link to last right operator
								$stack = array_merge($stack, $tmp); // Push right operators to stack
								unset($tmp);
							}else{
								$lastValue = &$operator;
							}
							unset($operator);
							// Restore $operator if necessary
							if( $parentFlags & FOXWAY_NEED_RESTORE_OPERATOR ) {
								$operator = array_pop( $memOperators );
							}
							// Restore flags
							$parentFlags = array_pop($parentheses);
							if( $parentFlags & FOXWAY_EXPECT_RESULT_AS_PARAM ) {
								$parentFlags = array_pop($parentheses);
								if( $needParams[0][FOXWAY_STACK_COMMAND] == T_WHILE ) {
									$stack[] = array( FOXWAY_STACK_COMMAND=>T_DO, FOXWAY_STACK_PARAM=>&$lastValue[FOXWAY_STACK_RESULT], FOXWAY_STACK_TOKEN_LINE=>$tokenLine ); // Save result of parentheses, Example: while(true)
									$needParams[0][FOXWAY_STACK_DO_TRUE] = $stack;
									$stack = array();
								}elseif( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) {
									$memory[] = $stack;
									$stack = array();
									$needParams[0][FOXWAY_STACK_PARAM] = &$lastValue[FOXWAY_STACK_RESULT]; // Save result of parentheses, exsample: if(true)
								}
								$needOperator = false;
							}
							break 2;
						case ',':
							if( $parentFlags & FOXWAY_EXPECT_LIST_PARAMS ) {
								$needParams[0][FOXWAY_STACK_PARAM][] = &$operator;
								if( $parentFlags & FOXWAY_EXPECT_VARIABLE_REFERENCE && $operator[FOXWAY_STACK_COMMAND] == T_VARIABLE ) {
									array_pop( $stack );
								}
							}else{
								throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
							}
							if( $stack ) {
								$k = array_keys($memory);
								$lk = array_pop( $k );
								$memory[$lk][0] = array_merge( $memory[$lk][0], $stack );
								$stack = array();
							}
							unset($lastValue);
							$needOperator = false;
							break;
						default: // ';'
							$needOperator = false;

							if( $parentFlags & FOXWAY_EXPECT_SEMICOLON == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
							if( $parentFlags & FOXWAY_EXPECT_LIST_PARAMS ) { // for operator T_ECHO only
								$needParams[0][FOXWAY_STACK_PARAM][] = &$operator;
								$parentFlags = array_pop($parentheses);

								list( $s ) = array_pop( $memory ); // restore $stack
								if( $s ) {
									$stack = array_merge( $s, $stack );
								}
								$stack[] = array_shift($needParams);
							}elseif( $parentFlags & FOXWAY_EXPECT_RESULT_AS_PARAM ) { // for operator T_CONTINUE
								$needParams[0][FOXWAY_STACK_PARAM] = &$operator[FOXWAY_STACK_RESULT];
							}elseif( $parentFlags & FOXWAY_THIS_IS_FUNCTION ) { // for operator T_PRINT
								$needParams[0][FOXWAY_STACK_PARAM] = &$operator[FOXWAY_STACK_RESULT];
								unset($operator);
								// Save result of T_PRINT to $lastValue
								if( $parentFlags & FOXWAY_NEED_RESTORE_RIGHT_OPERATORS ) { // Need restore right operators
									$tmp = array_pop( $memOperators ); // restore right operators to $tmp
									$tmp[0][FOXWAY_STACK_PARAM_2] = &$needParams[0][FOXWAY_STACK_RESULT]; // Set parents result as param to right operators
									$k = array_keys($tmp);
									$lk = array_pop( $k ); // Get key of last right operator
									$lastValue = &$tmp[$lk]; // Set $lastValue as link to last right operator
									$stack = array_merge($stack, $tmp); // Push right operators to stack
									unset($tmp);
								}else{
									$lastValue = &$needParams[0]; // restore T_PRINT as value
								}
								array_shift($needParams);
								if( $parentFlags & FOXWAY_NEED_RESTORE_OPERATOR ) { // Restore $operator if necessary
									$operator = array_pop( $memOperators );
								}
								list( $s, $math ) = array_pop($memory); // restore $stack, $math
								if( $s ) {
									$stack = array_merge( $stack, $s );
								}
								$parentFlags = array_pop($parentheses);
								goto closeoperator;
							}

							//$ifOperators = array();
							while(true) {
								if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) { // Exsample: if(1) echo 2;
									if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // if(1) { echo 2;
										$needParams[0][FOXWAY_STACK_DO_TRUE] = array_merge( $needParams[0][FOXWAY_STACK_DO_TRUE], $stack );
										break; /********** EXIT **********/
									}else{ // if(1) echo 2;
										$link = &$needParams[0];
										if( $link[FOXWAY_STACK_COMMAND] == T_WHILE ) { // T_WHILE
											$stack[] = array( FOXWAY_STACK_COMMAND=>T_CONTINUE, FOXWAY_STACK_RESULT=>1 ); // Add operator T_CONTINUE to the end of the cycle
											$link[FOXWAY_STACK_DO_TRUE] = array_merge( $link[FOXWAY_STACK_DO_TRUE], $stack );
											$stack = array( &$link );
										}else{ // T_IF
											$link[FOXWAY_STACK_DO_TRUE] = $stack;
											$stack = array_pop($memory); // Restore stack and ...
											$stack[] = &$link; // ... add operator
											$ifOperators[] = &$link; // Save operator T_IF for restore if will be used operator T_ELSE
										}
										array_shift($needParams);
										$parentFlags = array_pop($parentheses);
									}
								} elseif ( $parentFlags & FOXWAY_EXPECT_DO_FALSE_STACK ) { // Exsample: if(1) echo 2; else echo 3;
									if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // if(1) { echo 2; } else { echo 3;
										$needParams[0][FOXWAY_STACK_DO_FALSE] = array_merge( $needParams[0][FOXWAY_STACK_DO_FALSE], $stack );
										break; /********** EXIT **********/
									}else{ // if(1) echo 2; else echo 3;
										$needParams[0][FOXWAY_STACK_DO_FALSE] = $stack;
										array_shift($needParams);
										$parentFlags = array_pop($parentheses);
										if( $parentFlags & FOXWAY_KEEP_EXPECT_ELSE == 0 ) { // Exsample: if(1) echo 2; else echo 3;
											$parentFlags &= ~FOXWAY_EXPECT_ELSE;
										}
										break; /********** EXIT **********/
									}
								} else { // Example: echo 1;
									$bytecode[] = $stack;
									break; /********** EXIT **********/
								}
							}
							$stack = array();
							$parentLevel = 0;
							break;
					}
					unset($operator);
					//$operator = false;
					break;
				case '(':
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE | ($parentFlags & FOXWAY_CLEAR_FLAG_FOR_SHIFT_BEFORE_PARENTHESES) >> 1;

					if( isset($operator) ) { // Operator exists. Examples: echo 1+(
						$memOperators[] = &$operator; // push $operator temporarily without PARAM_2
						unset($operator);
						$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
					}
					if( $rightOperators ) { // right operator was used, example: echo -(
						$memOperators[] = $rightOperators; // push $rightOperators for restore later
						$rightOperators = array();
						$parentFlags |= FOXWAY_NEED_RESTORE_RIGHT_OPERATORS;
						unset($lastValue);
					}
					$parentLevel++;
					break;
				case T_ECHO:		// echo
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					// for compatible with functions that use flag FOXWAY_EXPECT_LIST_PARAMS
					$memory[] = array(array(), array());
					/*ksort($math);
					array_unshift( $memory, array($stack, $math) );
					$stack = array();
					$math = array();*/

					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>array(), FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );

					//$parentLevel = 0;
					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_LIST_PARAMS;
					break;
				case T_IF:			// if
					if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) { // Example: if(1) if
						$parentFlags |= FOXWAY_KEEP_EXPECT_ELSE;
					}
					$parentFlags |= FOXWAY_EXPECT_ELSE;
					// break is not necessary here
				case T_WHILE:		// while
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 || $stack || isset($operator) || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );
					$parentheses[] = $parentFlags;
					$parentheses[] = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_TRUE_STACK;
					$parentheses[] = FOXWAY_EXPECT_RESULT_AS_PARAM;
					$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE;

					self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array('(') );
					break;
				case T_ELSE:		// else
					if( $parentFlags & FOXWAY_EXPECT_ELSE == 0 || $stack || isset($operator) || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$needParams = array_merge( $ifOperators, $needParams  );
					$ifOperators = array();
					if( $parentFlags & FOXWAY_KEEP_EXPECT_ELSE == 0 ) { // Example: if (1) echo 2; else
						$parentFlags &= ~FOXWAY_EXPECT_ELSE; // Skip for: if(1) if (2) echo 3; else
					}
					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_FALSE_STACK;
					break;
				case T_ELSEIF:		// elseif
					if( $parentFlags & FOXWAY_EXPECT_ELSE == 0 || $stack || isset($operator) || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$needParams = array_merge( $ifOperators, $needParams );
					$ifOperators = array();
					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>T_IF, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );
					$parentheses[] = $parentFlags|FOXWAY_EXPECT_DO_FALSE_STACK;
					$parentheses[] = FOXWAY_EXPECT_START_COMMAND | FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_TRUE_STACK;
					$parentheses[] = FOXWAY_EXPECT_RESULT_AS_PARAM;
					$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE;

					self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array('(') );
					break;
				case T_ARRAY:		// array
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE|FOXWAY_ALLOW_COMMA_AT_END_PARENTHES|FOXWAY_EXPECT_LIST_PARAMS|FOXWAY_THIS_IS_FUNCTION|FOXWAY_ALLOW_DOUBLE_ARROW;

					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>array(), FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );

					if( isset($operator) ) { // Operator exists. Example: $foo = array
						$memOperators[] = &$operator; // push $operator temporarily without PARAM_2
						unset($operator);
						$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
					}
					$parentLevel++;

					ksort($math);
					$memory[] = array($stack, $math); // push stack for restore late. Example: echo $a + array
					$stack = array();
					$math = array();

					self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array('(') );
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

					array_unshift( $rightOperators, array(FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
					if( isset($rightOperators[1]) ) { // this is not first right operator. Example: echo (int)-
						$rightOperators[1][FOXWAY_STACK_PARAM_2] = &$rightOperators[0][FOXWAY_STACK_RESULT];
					}else{ // this is first right operator. Example: echo -
						$parentFlags = $parentFlags & FOXWAY_CLEAR_FLAG_FOR_VALUE;
						$lastValue = &$rightOperators[0];
					}
					break;
				case '[':
					$memEncapsed[] = $stackEncapsed;
					if( $stackEncapsed === false ) {
						if( !$needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
						$needOperator = false;
					}else{
						$stackEncapsed = false;
					}

					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_ARRAY_INDEX_CLOSE;

					if( isset($operator) ) { // Operator exists. Example: 1+$foo[
						$memOperators[] = &$operator; // push $operator temporarily without PARAM_2
						unset($operator);
						$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
					}

					ksort($math);
					$memory[] = array( $stack, $math ); // save $stack, $math for restore late
					$math = array();
					$stack = array();

					if( isset($lastValue) ) {
						if( $lastValue[FOXWAY_STACK_COMMAND] != T_VARIABLE ) {
							throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
						}
						$needParams = array_merge( array(&$lastValue), $needParams ); //array_unshift( $needParams, &$lastValue );
						$values = array();
						unset($lastValue);
					}else{ // $foo = [
						// @todo
						throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
					}
					break;
				case '{':
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 || $stack || isset($operator) || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					if( $needParams[0][FOXWAY_STACK_COMMAND] == T_IF ) {
						if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) {
							$needParams[0][FOXWAY_STACK_DO_TRUE] = array();
						}elseif( $parentFlags & FOXWAY_EXPECT_DO_FALSE_STACK ) {
							$needParams[0][FOXWAY_STACK_DO_FALSE] = array();
						}else{
							throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
						}
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
					} // Example: if(1) { echo "hello"; }

					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 || $stack || isset($operator) || $values ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$link = &$needParams[0];
					array_pop($parentheses);
					$parentFlags = array_pop($parentheses);
					if( !isset($link[FOXWAY_STACK_DO_FALSE]) ) { // operator 'else' not used
						if( $link[FOXWAY_STACK_COMMAND] == T_WHILE ) {
							$link[FOXWAY_STACK_DO_TRUE][] = array( FOXWAY_STACK_COMMAND=>T_CONTINUE, FOXWAY_STACK_RESULT=>1 );  // Add operator T_CONTINUE to the end of the cycle
							$tmp = array( &$link );
							$ifOperators = array();
						}else{ // T_IF
							$tmp = array_pop($memory); // Restore stack and ...
							$tmp[] = &$link; // ... add operator
							$ifOperators = array( &$needParams[0] );
						}
						array_shift($needParams);
						while(true) {
							if( $parentFlags & FOXWAY_EXPECT_DO_TRUE_STACK ) { // Exsample: if(1) if(2) { echo 3; }
								if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // Exsample: if(1) { if(2) { echo 3; }
									$needParams[0][FOXWAY_STACK_DO_TRUE] = array_merge( $needParams[0][FOXWAY_STACK_DO_TRUE], $tmp );
									break; /********** EXIT **********/
								}else{ // Exsample: if(1) if(2) { echo 3; }
									$link = &$needParams[0];
									if( $link[FOXWAY_STACK_COMMAND] == T_WHILE ) { // T_WHILE
										$link[FOXWAY_STACK_DO_TRUE] = array_merge( $link[FOXWAY_STACK_DO_TRUE], $tmp );
										$tmp = array( &$link );
									}else{ // T_IF
										$link[FOXWAY_STACK_DO_TRUE] = $tmp;
										$tmp = array_pop($memory); // Restore stack and ...
										$tmp[] = &$link; // ... add operator
										$ifOperators[] = &$link;
									}
									array_shift($needParams);
									$parentFlags = array_pop($parentheses) | $parentFlags & FOXWAY_KEEP_EXPECT_ELSE;
								}
							} elseif ( $parentFlags & FOXWAY_EXPECT_DO_FALSE_STACK ) { // Exsample: if(1) echo 2; else if(3) echo 4;
								if( $parentFlags & FOXWAY_EXPECT_CURLY_CLOSE ) { // Exsample: if(1) echo 2; else { if(3) echo 3; }
									$needParams[0][FOXWAY_STACK_DO_FALSE] = array_merge( $needParams[0][FOXWAY_STACK_DO_FALSE], $tmp );
								}else{ // Exsample: if(1) echo 2; else if(3) echo 4;
									$needParams[0][FOXWAY_STACK_DO_FALSE] = $tmp;
									$parentFlags = array_pop($parentheses);
									$ifOperators[] = &$needParams[0];
								}
								array_shift($needParams);
								break; /********** EXIT **********/
							} else { // Example: if(1) { echo 2; }
								$bytecode[] = $tmp;
								break; /********** EXIT **********/
							}
						}
						//$stack = array();
						$parentLevel = 0;
					}else{ // operator 'else' was used. Example: if(1) { if(2) { echo 3; } else { echo 4; }
						array_shift($needParams);
					}
					break;
				case T_CONTINUE:
				case T_BREAK:
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$tmp = self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array(T_LNUMBER, ';') );
					if( $tmp == ';' ) { // Example: break;
						$text = 1;
					}else{ // Example: break 1;
						$text = self::getIntegerFromString($tmp);
						if( $text == 0 ) {
							$text = 1; // todo throw new ExceptionFoxway, as in PHP 5.4
						}
						self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array(';') );
					}
					$stack[] = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>$text, FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
					//unset($lastValue);
					//$lastValue = null;
					$needOperator = true;
					$index--;
					break;
				case T_STATIC:
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$text = self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array(T_VARIABLE) ); // Get variable name;
					$tmp = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_PARAM_2=>substr($text, 1), FOXWAY_STACK_PARAM=>null, FOXWAY_STACK_DO_FALSE=>false, FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
					$bytecode[][] = &$tmp;
					if( '=' == self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array(';', '=') ) ) { // Example: static $foo=
						$needParams = array( &$tmp );
						$parentheses[] = $parentFlags;
						$parentFlags = FOXWAY_EXPECT_SEMICOLON | FOXWAY_EXPECT_DO_FALSE_STACK | FOXWAY_EXPECT_RESULT_AS_PARAM;
					} // Example: static $foo;
					unset( $tmp );
					break;
				case T_GLOBAL:
					if( $parentFlags & FOXWAY_EXPECT_START_COMMAND == 0 ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$tmp = array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_PARAM=>array(), FOXWAY_STACK_TOKEN_LINE=>$tokenLine );
					do {
						$text = self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array(T_VARIABLE) ); // Get variable name;
						$tmp[FOXWAY_STACK_PARAM][] = substr($text, 1);
					}while( ',' == self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array(',', ';') ) );
					$bytecode[][] = $tmp;
					break;
				case T_PRINT: // print
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }
					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_SEMICOLON | FOXWAY_THIS_IS_FUNCTION;

					array_unshift( $needParams, array(FOXWAY_STACK_COMMAND=>T_PRINT, FOXWAY_STACK_RESULT=>1, FOXWAY_STACK_TOKEN_LINE=>$tokenLine) );
					$stack[] = &$needParams[0];

					if( isset($operator) ) { // Operator exists. Examples: echo 1+print
						$memOperators[] = &$operator; // push $operator temporarily without PARAM_2
						unset($operator);
						$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
					}
					if( $rightOperators ) { // right operator was used, example: echo -print
						$memOperators[] = $rightOperators; // push $rightOperators for restore later
						$rightOperators = array();
						$parentFlags |= FOXWAY_NEED_RESTORE_RIGHT_OPERATORS;
						unset($lastValue);
					}

					ksort($math);
					$memory[] = array($stack, $math); // push stack for restore late. Example: echo $a + array
					$stack = array();
					$math = array();
					break;
				case T_ISSET:
				case T_UNSET:
				case T_EMPTY:
					if( $needOperator ) { throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine); }

					$parentheses[] = $parentFlags;
					$parentFlags = FOXWAY_EXPECT_PARENTHES_CLOSE|FOXWAY_EXPECT_LIST_PARAMS|FOXWAY_THIS_IS_FUNCTION;

					array_unshift( $needParams, array( FOXWAY_STACK_COMMAND=>$id, FOXWAY_STACK_RESULT=>null, FOXWAY_STACK_PARAM=>array(), FOXWAY_STACK_TOKEN_LINE=>$tokenLine ) );

					if( isset($operator) ) { // Operator exists. Example: $foo = isset
						$memOperators[] = &$operator; // push $operator temporarily without PARAM_2
						unset($operator);
						$parentFlags |= FOXWAY_NEED_RESTORE_OPERATOR;
					}
					if( $rightOperators ) { // right operator was used, example: echo -isset
						$memOperators[] = $rightOperators; // push $rightOperators for restore later
						$rightOperators = array();
						$parentFlags |= FOXWAY_NEED_RESTORE_RIGHT_OPERATORS;
						unset($lastValue);
					}
					$parentLevel++;

					ksort($math);
					$memory[] = array($stack, $math); // push stack for restore late. Example: echo $a + array
					$stack = array();
					$math = array();

					self::getNextToken( $tokens, $index, $countTokens, $tokenLine, array('(') );
					break;
				case T_LIST:

					// @todo
				default :
					throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
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

	/**
	 * Merge the stack and the math in return value and emptying them
	 * @param array $stack
	 * @param array $math
	 * @return array $stack + $match
	 */
	private static function mergeStackAndMath( &$stack, &$math ) {
		if( $math ) { // $math is not empty array. Example: echo 1+2
			$c = -1;
			foreach ($math as &$value) {
				ksort( $value );
				$value = call_user_func_array( 'array_merge', $value );
				$c++;
			}
			if( $c ) { // there is more one. Example: echo 1*(2+3)
				krsort( $math );
				$s = call_user_func_array('array_merge', $math);
			} else { // there is one. Example: echo 1+2
				$s = $value;
				unset($value);
			}
			$math = array();
			if( $stack ) { // Example: echo 1+"$foo"
				$s = array_merge( $stack, $s );
				$stack = array();
			}
		}elseif( $stack ) { // $math is empty, $stack is not empty. Example: echo "$foo"
			$s = $stack;
			$stack = array();
		}else{ // $math and $stack is empty. Example: echo 1
			$s = array();
		}
		return $s;
	}

	private static function getNextToken( &$tokens, &$index, $countTokens, $tokenLine, $found ) {
		static $ignore = array( T_COMMENT, T_DOC_COMMENT, T_WHITESPACE );

		for( $index++; $index < $countTokens; $index++ ){ // go to '('
			$token = &$tokens[$index];
			if ( is_string($token) ) {
				$id = $token;
				$text = $token;
			} else {
				list($id, $text, $tokenLine) = $token;
			}
			if( in_array($id, $found) ) {
				return $text;
			}
			if( !in_array($id, $ignore) ) {
				throw new ExceptionFoxway($id, FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
			}
		}
		throw new ExceptionFoxway('$end', FOXWAY_PHP_SYNTAX_ERROR_UNEXPECTED, $tokenLine);
	}

}