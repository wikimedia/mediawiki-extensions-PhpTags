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
define( 'FOXWAY_STACK_ARRAY_INDEX', 'a' );

// definitions for Runtame::$functions
define( 'FOXWAY_DEFAULT_VALUES', 'd' );
define( 'FOXWAY_MIN_VALUES', '<' );

/**
 * Runtime class of Foxway extension.
 *
 * @file Runtime.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Runtime {

	static public $functions=array();
	static public $constants=array();
	static public $allowedNamespaces = true;
	static public $time = 0;
	static public $permittedTime = true;
	protected static $startTime = array();

	protected static $variables = array();
	protected static $staticVariables = array();
	protected static $globalVariables = array();

	/*public function startTime($scope) {
		self::$startTime[$scope] = microtime(true);
		if( isset(self::$time[$scope]) ) {
			return $this->checkExceedsTime();
		}else{
			self::$time[$scope] = 0;
		}
	}

	public function stopTime($scope) {
		self::$time[$scope] += microtime(true) - self::$startTime[$scope];
	}

	public function checkExceedsTime() {
		global $wgFoxway_max_execution_time_for_scope;
		if( microtime(true) - self::$startTime[$this->scope] + self::$time[$this->scope] > $wgFoxway_max_execution_time_for_scope ) {
			return new ErrorMessage( __LINE__, null, E_ERROR, array( 'foxway-php-fatal-error-max-execution-time-scope', $wgFoxway_max_execution_time_for_scope, isset($this->args[0])?$this->args[0]:'n\a' ) );
		}
		return null;
	}*/

	public static function runSource($code, array $args = array(), $scope = '', $transit = array() ) {
		return self::run( Compiler::compile($code), $args, $scope, $transit );
	}

	public static function run($code, array $args, $scope = '', $transit = array() ) {
		if( !isset(self::$variables[$scope]) ) {
			self::$variables[$scope] = array();
		}
		$thisVariables = &self::$variables[$scope];
		$thisVariables['argv'] = $args;
		$thisVariables['argc'] = count($args);
		$thisVariables['GLOBALS'] = &self::$globalVariables;
		$memory=array();
		$return = array();
		$break = 0; // used for T_BREAK
		$continue = false; // used for T_CONTINUE
		$loopsOwner = null;
		$place = isset($args[0]) ? $args[0] : ''; // Page name for static variables and error messages

		$c=count($code);
		$i=-1;
		do {
			if( $break ) {
				if( $loopsOwner == T_WHILE ) {
					$break--;
					continue;
				}
				break;
			}elseif( $continue ) {
				if( $loopsOwner == T_WHILE ) {
					$i = -1;
					$continue = false;
				}else{
					continue;
				}
			}
			$i++;
			for(; $i<$c; $i++ ) {
				$value = &$code[$i];
				switch ($value[FOXWAY_STACK_COMMAND]) {
					case T_CONST:
					case T_DOUBLE_ARROW:
						break; // ignore it, @todo need remove it from $code in class Compiler
					case T_ENCAPSED_AND_WHITESPACE:
						$value[FOXWAY_STACK_RESULT] = implode($value[FOXWAY_STACK_PARAM]);
						break;
					case '.':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] . $value[FOXWAY_STACK_PARAM_2];
						break;
					case '+':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] + $value[FOXWAY_STACK_PARAM_2];
						break;
					case '-':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] - $value[FOXWAY_STACK_PARAM_2];
						break;
					case '*':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] * $value[FOXWAY_STACK_PARAM_2];
						break;
					case '/':
						if ( (int)$value[FOXWAY_STACK_PARAM_2] == 0 ) {
							$return[] = (string) new ExceptionFoxway( null, FOXWAY_PHP_WARNING_DIVISION_BY_ZERO, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							$value[FOXWAY_STACK_RESULT] = null;
							break; /**** EXIT ****/
						}
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] / $value[FOXWAY_STACK_PARAM_2];
						break;
					case '%':
						if ( (int)$value[FOXWAY_STACK_PARAM_2] == 0 ) {
							$return[] = (string) new ExceptionFoxway( null, FOXWAY_PHP_WARNING_DIVISION_BY_ZERO, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							$value[FOXWAY_STACK_RESULT] = null;
							break; /**** EXIT ****/
						}
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] % $value[FOXWAY_STACK_PARAM_2];
						break;
					case '&':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] & $value[FOXWAY_STACK_PARAM_2];
						break;
					case '|':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] | $value[FOXWAY_STACK_PARAM_2];
						break;
					case '^':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] ^ $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_SL:			// <<
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] << $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_SR:			// >>
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] >> $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_BOOLEAN_AND:	// &&
					case T_LOGICAL_AND:	// and
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] && $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_LOGICAL_XOR:	// xor
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] xor $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_BOOLEAN_OR:	// ||
					case T_LOGICAL_OR:	// or
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] || $value[FOXWAY_STACK_PARAM_2];
						break;
					case '<':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] < $value[FOXWAY_STACK_PARAM_2];
						break;
					case '>':
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] > $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_IS_SMALLER_OR_EQUAL:	// <=
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] <= $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_IS_GREATER_OR_EQUAL:	// >=
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] >= $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_IS_EQUAL:			// ==
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] == $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_IS_NOT_EQUAL:		// !=
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] != $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_IS_IDENTICAL:		// ===
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] === $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_IS_NOT_IDENTICAL:	// !==
						$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM] !== $value[FOXWAY_STACK_PARAM_2];
						break;
					case T_ECHO:
						foreach( $value[FOXWAY_STACK_PARAM] as $v ) {
							$return[] = $v[FOXWAY_STACK_RESULT];
						}
						break;
					case T_PRINT:
						$return[] = $value[FOXWAY_STACK_PARAM];
						break;
					case '~':
						$value[FOXWAY_STACK_RESULT] = ~$value[FOXWAY_STACK_PARAM_2];
						break;
					case '!':
						$value[FOXWAY_STACK_RESULT] = !$value[FOXWAY_STACK_PARAM_2];
						break;
					case T_INT_CAST:		// (int)
						$value[FOXWAY_STACK_RESULT] = (int)$value[FOXWAY_STACK_PARAM_2];
						break;
					case T_DOUBLE_CAST:		// (double)
						$value[FOXWAY_STACK_RESULT] = (double)$value[FOXWAY_STACK_PARAM_2];
						break;
					case T_STRING_CAST:		// (string)
						$value[FOXWAY_STACK_RESULT] = (string)$value[FOXWAY_STACK_PARAM_2];
						break;
					case T_ARRAY_CAST:		// (array)
						$value[FOXWAY_STACK_RESULT] = (array)$value[FOXWAY_STACK_PARAM_2];
						break;
					case T_BOOL_CAST:		// (bool)
						$value[FOXWAY_STACK_RESULT] = (bool)$value[FOXWAY_STACK_PARAM_2];
						break;
					case T_UNSET_CAST:		// (unset)
						$value[FOXWAY_STACK_RESULT] = (unset)$value[FOXWAY_STACK_PARAM_2];
						break;
					case '?':
						if( $value[FOXWAY_STACK_PARAM] ) { // true ?
							if( $value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_DO_TRUE] ) { // true ? 1+2 :
								$memory[] = array( &$value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_PARAM], $code, $i, $c, $loopsOwner );
								$code = $value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_DO_TRUE];
								$i = -1;
								$c = count($code);
								$loopsOwner = '?';
							}else{ // true ? 1 :
								$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_PARAM];
							}
						}else{ // false ?
							if( $value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_DO_FALSE] ) { // false ? ... : 1+2
								$memory[] = array( &$value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_PARAM_2], $code, $i, $c, $loopsOwner );
								$code = $value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_DO_FALSE];
								$i = -1;
								$c = count($code);
								$loopsOwner = '?';
							}else{ // false ? ... : 1
								$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_PARAM_2][FOXWAY_STACK_PARAM_2];
							}
						}
						break;
					case T_IF:
						if( $value[FOXWAY_STACK_PARAM] ) { // Example: if( true )
							if( $value[FOXWAY_STACK_DO_TRUE] ) { // Stack not empty: if(true);
								$memory[] = array( null, $code, $i, $c, $loopsOwner );
								$code = $value[FOXWAY_STACK_DO_TRUE];
								$i = -1;
								$c = count($code);
								$loopsOwner = T_IF;
							}
						}else{ // Example: if( false )
							if( isset($value[FOXWAY_STACK_DO_FALSE]) ) { // Stack not empty: if(false) ; else ;
								$memory[] = array( null, $code, $i, $c, $loopsOwner );
								$code = $value[FOXWAY_STACK_DO_FALSE];
								$i = -1;
								$c = count($code);
								$loopsOwner = T_IF;
							}
						}
						break;
					case T_FOREACH:
						$vn = $value[FOXWAY_STACK_PARAM]; // Variable name
						if ( !array_key_exists($vn, $thisVariables) ) {
							$return[] = (string) new ExceptionFoxway( $vn, FOXWAY_PHP_NOTICE_UNDEFINED_VARIABLE, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							$return[] = (string) new ExceptionFoxway( null, FOXWAY_PHP_WARNING_INVALID_ARGUMENT_FOR_FOREACH, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							break; // **** EXIT ****
						}
						if ( !is_array($thisVariables[ $value[FOXWAY_STACK_PARAM] ]) ) {
							$return[] = (string) new ExceptionFoxway( null, FOXWAY_PHP_WARNING_INVALID_ARGUMENT_FOR_FOREACH, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							break; // **** EXIT ****
						}
						reset( $thisVariables[ $value[FOXWAY_STACK_PARAM] ] );
						// break is not necessary here
					case T_WHILE: // PHP code "while($foo) { ... }" doing as T_WHILE { T_DO($foo) ... }. If $foo == false, T_DO doing as T_BREAK
						$memory[] = array( null, $code, $i, $c, $loopsOwner );
						$code = $value[FOXWAY_STACK_DO_TRUE];
						$i = -1;
						$c = count($code);
						$loopsOwner = T_WHILE;
						break;
					case T_DO:
						if( $value[FOXWAY_STACK_PARAM] ) {
							continue; // this is "while(true)", just go next
						}// while(false) doing as T_BREAK;
						break 2; // go to one level down
					case T_AS:
						if ( is_string($value[FOXWAY_STACK_PARAM_2]) ) { // T_VARIABLE. Example: while ( $foo as $value )
							if ( !list(,$thisVariables[ $value[FOXWAY_STACK_PARAM_2] ]) = each($thisVariables[ $value[FOXWAY_STACK_PARAM] ]) ) {
								break 2; // go to one level down
							}
						} else { // T_DOUBLE_ARROW Example: while ( $foo as $key=>$value )
							if ( !list($thisVariables[ $value[FOXWAY_STACK_PARAM_2][0] ], $thisVariables[ $value[FOXWAY_STACK_PARAM_2][1] ]) = each($thisVariables[ $value[FOXWAY_STACK_PARAM] ]) ) {
								break 2; // go to one level down
							}
						}
						break;
					case T_BREAK:
						$break = $value[FOXWAY_STACK_RESULT];
						if( $loopsOwner == T_WHILE ) {
							$break--;
						}
						break 2; // go to one level down
					case T_CONTINUE:
						$break = $value[FOXWAY_STACK_RESULT]-1;
						if( $loopsOwner == T_WHILE && $break == 0 ) { // Example: while(true) continue;
							$i = -1;
							break;
						}
						$continue = true;
						break 2; // go to one level down
					case T_ARRAY:			// array
						$value[FOXWAY_STACK_RESULT] = array(); // init array
						foreach ($value[FOXWAY_STACK_PARAM] as $v) {
							if( $v[FOXWAY_STACK_COMMAND] == T_DOUBLE_ARROW ) {
								$value[FOXWAY_STACK_RESULT][ $v[FOXWAY_STACK_RESULT] ] = $v[FOXWAY_STACK_PARAM_2];
							}else{
								$value[FOXWAY_STACK_RESULT][] = $v[FOXWAY_STACK_RESULT];
							}
						}
						break;
					case T_VARIABLE:
						if ( array_key_exists($value[FOXWAY_STACK_PARAM], $thisVariables) ) {
							$value[FOXWAY_STACK_RESULT] = $thisVariables[ $value[FOXWAY_STACK_PARAM] ];
							if ( isset($value[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]
								foreach ( $value[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
									if ( isset($value[FOXWAY_STACK_RESULT][$v]) ) {
										$value[FOXWAY_STACK_RESULT] = $value[FOXWAY_STACK_RESULT][$v];
									} else {
										if ( is_string($value[FOXWAY_STACK_RESULT]) ) {
											$return[] = (string) new ExceptionFoxway( (int)$v, FOXWAY_PHP_NOTICE_UNINIT_STRING_OFFSET, $value[FOXWAY_STACK_TOKEN_LINE], $place );
										}
										$value[FOXWAY_STACK_RESULT] = null;
									}
								}
							}
						}else{
							$value[FOXWAY_STACK_RESULT] = null;
							if( !isset($value[FOXWAY_STACK_PARAM_2]) ) { // skip this E_NOTICE, becouse it is $foo++;  E_NOTICE will be show in ++ operator
								$return[] = (string) new ExceptionFoxway( $value[FOXWAY_STACK_PARAM], FOXWAY_PHP_NOTICE_UNDEFINED_VARIABLE, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							}
						}
						break;
					case T_STATIC:
						$vn = $value[FOXWAY_STACK_PARAM_2]; // variable name
						if( !isset(self::$staticVariables[$place]) || !array_key_exists($vn, self::$staticVariables[$place]) ) {
							self::$staticVariables[$place][$vn] = &$value[FOXWAY_STACK_PARAM];
							if( $value[FOXWAY_STACK_DO_FALSE] ) {
								//self::$staticVariables[$p][$vn] = null;
								$memory[] = array( null, $code, $i, $c, $loopsOwner );
								$code = $value[FOXWAY_STACK_DO_FALSE];
								$i = -1;
								$c = count($code);
								$loopsOwner = T_STATIC;
							}
						}
						$thisVariables[$vn] = &self::$staticVariables[$place][$vn];
						break;
					case T_GLOBAL:
						foreach( $value[FOXWAY_STACK_PARAM] as $vn ) { // variable names
							if( !array_key_exists($vn, self::$globalVariables) ) {
								self::$globalVariables[$vn] = null;
							}
							$thisVariables[$vn] = &self::$globalVariables[$vn];
						}
						break;
					case T_STRING:
						$name = $value[FOXWAY_STACK_PARAM_2];
						if ( isset($value[FOXWAY_STACK_PARAM]) ) { // This is function or object
							if ( is_array($value[FOXWAY_STACK_PARAM]) ) { // This is function
								if ( isset(self::$functions[$name]) ) {
									$function = &self::$functions[$name];
									$param = array();
									foreach ( $value[FOXWAY_STACK_PARAM] as $val ) {
										if ( $val[FOXWAY_STACK_COMMAND] == T_VARIABLE ) { // Example $foo
											$ref = &$thisVariables[ $val[FOXWAY_STACK_PARAM] ];
											if ( isset($val[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]
												foreach ( $val[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
													if ( !isset($ref[$v]) ) {
														$ref[$v]=null;
														// @todo PHP Fatal error:  Only variables can be passed by reference
														if( is_string($ref) ) {
															$return[] = (string) new ExceptionFoxway( (int)$v, FOXWAY_PHP_NOTICE_UNINIT_STRING_OFFSET, $value[FOXWAY_STACK_TOKEN_LINE], $place );
														}
													}
													$ref = &$ref[$v];
												}
											}
											$param[] = &$ref;
										} else {
											// @todo PHP Fatal error:  Only variables can be passed by reference
											$param[] = $val[FOXWAY_STACK_RESULT];
										}
									}
									$count = count( $param );
									do {
										if( isset($function[$count]) ) {
											$function = &$function[$count];
											break;
										} else {
											if ( isset($function[FOXWAY_DEFAULT_VALUES]) ) { // Has default values
												$param += $function[FOXWAY_DEFAULT_VALUES];
												$count = count( $param );
												if( isset($function[$count]) ) {
													$function = &$function[$count];
													break;
												}
											}
											if ( isset($function[FOXWAY_MIN_VALUES]) ) {
												if( $count >= $function[FOXWAY_MIN_VALUES] && isset($function['']) ) {
													$function = &$function[''];
													$count = "''"; // it for error message
													break;
												}
											}
										}
										$return[] = (string) new ExceptionFoxway( $name, FOXWAY_PHP_WARNING_WRONG_PARAMETER_COUNT, $value[FOXWAY_STACK_TOKEN_LINE], $place );
										$value[FOXWAY_STACK_RESULT] = null;
										break 2; /**** EXIT ****/
									} while( false );

									if ( is_callable($function) ) {
										try {
											wfSuppressWarnings();
											$result = $function( $param, $transit );
											if ( $result instanceof outPrint ) {
												$value[FOXWAY_STACK_RESULT] = $result->returnValue;
												$return[] = $result;
											} else {
												$value[FOXWAY_STACK_RESULT] = $result;
											}
											wfRestoreWarnings();
										} catch ( ExceptionFoxway $e ) {
											$e->tokenLine = $value[FOXWAY_STACK_TOKEN_LINE];
											$e->place = $place;
											if ( is_array($e->params) ) {
												array_unshift( $e->params, $name );
											}
											$return[] = $e;
											$value[FOXWAY_STACK_RESULT] = null;
											break; /**** EXIT ****/
										} catch (Exception $e) {
											$return[] = (string) new ExceptionFoxway( $name, FOXWAY_PHP_FATAL_ERROR_CALL_TO_FUNCTION, $value[FOXWAY_STACK_TOKEN_LINE], $place );
											$value[FOXWAY_STACK_RESULT] = null;
											break; /**** EXIT ****/
										}
									} else {
										$return[] = (string) new ExceptionFoxway( array($name, $count), FOXWAY_PHP_FATAL_UNABLE_CALL_TO_FUNCTION, $value[FOXWAY_STACK_TOKEN_LINE], $place );
										$value[FOXWAY_STACK_RESULT] = null;
										break; /**** EXIT ****/
									}
								} else {
									$return[] = (string) new ExceptionFoxway( $name, FOXWAY_PHP_FATAL_CALL_TO_UNDEFINED_FUNCTION, $value[FOXWAY_STACK_TOKEN_LINE], $place );
									$value[FOXWAY_STACK_RESULT] = null;
									break; /**** EXIT ****/
								}
							} else { // This is object
								// @todo
							}
						} else { // This is constant
							if ( isset(self::$constants[$name]) ) {
								$function = &self::$constants[$name];
								$value[FOXWAY_STACK_RESULT] = is_callable($function) ? $function( $transit ) : $function;
							} else {
								$value[FOXWAY_STACK_RESULT] = $name;
								$return[] = (string) new ExceptionFoxway( $name, FOXWAY_PHP_NOTICE_UNDEFINED_CONSTANT, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							}
						}
						break;
					case T_UNSET:
						foreach ( $value[FOXWAY_STACK_PARAM] as $val ) {
							if ( $val[FOXWAY_STACK_COMMAND] != T_VARIABLE ) { // Example: isset($foo);
								throw new Exception; // @todo
							}
							$vn = $val[FOXWAY_STACK_PARAM]; // Variable Name
							if ( array_key_exists($vn, $thisVariables) ) { // defined variable
								if ( isset($val[FOXWAY_STACK_ARRAY_INDEX]) ) { // There is array index. Example: unset($foo[0])
									$ref = &$thisVariables[$vn];
									$tmp = array_pop( $val[FOXWAY_STACK_ARRAY_INDEX] );
									foreach ( $val[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
										if ( is_string($ref) ) {
											throw new ExceptionFoxway( null, FOXWAY_PHP_FATAL_CANNOT_UNSET_STRING_OFFSETS, $value[FOXWAY_STACK_TOKEN_LINE], $place );
										} elseif ( !isset($ref[$v]) ) { // undefined array index not for string
											continue 2;
										}
										$ref = &$ref[$v];
									}
									if ( is_array($ref) ) {
										unset( $ref[$tmp] );
									} else {
										throw new ExceptionFoxway( null, FOXWAY_PHP_FATAL_CANNOT_UNSET_STRING_OFFSETS, $value[FOXWAY_STACK_TOKEN_LINE], $place );
									}
								}else{ // There is no array index. Example: unset($foo)
									unset( $thisVariables[$vn] );
								}
							} elseif ( isset($val[FOXWAY_STACK_ARRAY_INDEX]) ) { // undefined variable with array index. Example: unset($foo[1])
								$return[] = (string) new ExceptionFoxway( $vn, FOXWAY_PHP_NOTICE_UNDEFINED_VARIABLE, $value[FOXWAY_STACK_TOKEN_LINE], $place );
							}
						}
						break;
					case T_ISSET:
						foreach($value[FOXWAY_STACK_PARAM] as $val) {
							if( $val[FOXWAY_STACK_COMMAND] != T_VARIABLE ) { // Example: isset($foo);
								throw new Exception; // @todo
							}
							if( !isset($thisVariables[ $val[FOXWAY_STACK_PARAM] ]) ) { // undefined variable or variable is null
								$value[FOXWAY_STACK_RESULT] = false;
								break 2;
							} // true, variable is defined
							if( isset($val[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: isset($foo[1])
								$ref = &$thisVariables[ $val[FOXWAY_STACK_PARAM] ];
								$tmp = array_pop( $val[FOXWAY_STACK_ARRAY_INDEX] );
								foreach( $val[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
									if( !isset($ref[$v]) ) { // undefined array index
										$value[FOXWAY_STACK_RESULT] = false;
										break 3;
									}
									$ref = &$ref[$v];
								}
								// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
								if( !isset($ref[$tmp]) || (is_string($ref) && is_string($tmp) && $tmp != (string)(int)$tmp) ) {
									$value[FOXWAY_STACK_RESULT] = false;
									break 2;
								}
							} // true, variable is defined and have no array index
						}
						$value[FOXWAY_STACK_RESULT] = true;
						break;
					case T_EMPTY:
						foreach($value[FOXWAY_STACK_PARAM] as $val) {
							if( $val[FOXWAY_STACK_COMMAND] == T_VARIABLE ) { // Example: empty($foo);
								if( !array_key_exists($val[FOXWAY_STACK_PARAM], $thisVariables) ) { // undefined variable
									continue;
								}
								$ref = &$thisVariables[ $val[FOXWAY_STACK_PARAM] ];
							}else{
								$ref = &$val[FOXWAY_STACK_RESULT];
							}
							if( isset($val[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: empty($foo[1])
								$tmp = array_pop( $val[FOXWAY_STACK_ARRAY_INDEX] );
								foreach( $val[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
									if( !isset($ref[$v]) ) { // undefined array index
										continue 2;
									}
									$ref = &$ref[$v];
								}
								// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.empty.php Example #2 empty() on String Offsets
								if( !empty($ref[$tmp]) && (is_array($ref) || !is_string($tmp) || $tmp == (string)(int)$tmp) ) {
									$value[FOXWAY_STACK_RESULT] = false;
									break 2;
								}
							}elseif( !empty($ref) ) { // there is no array index and empty() returns false (PHP 5.5.0 supports expressions)
								$value[FOXWAY_STACK_RESULT] = false;
								break 2;
							}
						}
						$value[FOXWAY_STACK_RESULT] = true;
						break;
					default: // ++, --, =, +=, -=, *=, etc...
						$param = &$value[FOXWAY_STACK_PARAM];
						if ( $param[FOXWAY_STACK_COMMAND] == T_LIST ) { // this is T_LIST. Example: list($foo, $bar) = $array;
							self::fillList( $value[FOXWAY_STACK_PARAM_2], $param, $thisVariables );
							unset( $param );
							break; /**** EXIT ****/
						}
						if( !array_key_exists($param[FOXWAY_STACK_PARAM], $thisVariables) ) { // Use undefined variable
							if( isset($value[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]++
								$thisVariables[ $param[FOXWAY_STACK_PARAM] ] = array();
							}else{
								$thisVariables[ $param[FOXWAY_STACK_PARAM] ] = null;
							}
							if( $value[FOXWAY_STACK_COMMAND] != '=' ) {
								$return[] = (string) new ExceptionFoxway( $param[FOXWAY_STACK_PARAM], FOXWAY_PHP_NOTICE_UNDEFINED_VARIABLE, $param[FOXWAY_STACK_TOKEN_LINE], $place );
							}
						}
						$ref = &$thisVariables[ $param[FOXWAY_STACK_PARAM] ];
						if ( isset($param[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]++
							foreach ( $param[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
								if( $v === null ) { // Example: $foo[]
									$t = null;
									$ref[] = &$t;
									$ref = &$t;
									unset($t);
								}else{
									if( !isset($ref[$v]) ) {
										$ref[$v] = null;
										// @todo E_NOTICE
									}
									$ref = &$ref[$v];
								}
							}
						}
						switch ( $value[FOXWAY_STACK_COMMAND] ) {
							case T_INC:
								$ref++;
								break;
							case T_DEC:
								$ref--;
								break;
							case '=':
								// Save result in T_VARIABLE FOXWAY_STACK_RESULT, Save result in $thisVariables[variable name]
								$param[FOXWAY_STACK_RESULT] = $ref = $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_PLUS_EQUAL:		// +=
								$param[FOXWAY_STACK_RESULT] = $ref += $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_MINUS_EQUAL:		// -=
								$param[FOXWAY_STACK_RESULT] = $ref -= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_MUL_EQUAL:		// *=
								$param[FOXWAY_STACK_RESULT] = $ref *= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_DIV_EQUAL:		// /=
								if ( (int)$value[FOXWAY_STACK_PARAM_2] == 0 ) {
									$return[] = (string) new ExceptionFoxway( null, FOXWAY_PHP_WARNING_DIVISION_BY_ZERO, $value[FOXWAY_STACK_TOKEN_LINE], $place );
									$param[FOXWAY_STACK_RESULT] = $ref = null;
									break; /**** EXIT ****/
								}
								$param[FOXWAY_STACK_RESULT] = $ref /= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_CONCAT_EQUAL:	// .=
								$param[FOXWAY_STACK_RESULT] = $ref .= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_MOD_EQUAL:		// %=
								if ( (int)$value[FOXWAY_STACK_PARAM_2] == 0 ) {
									$return[] = (string) new ExceptionFoxway( null, FOXWAY_PHP_WARNING_DIVISION_BY_ZERO, $value[FOXWAY_STACK_TOKEN_LINE], $place );
									$param[FOXWAY_STACK_RESULT] = $ref = null;
									break; /**** EXIT ****/
								}
								$param[FOXWAY_STACK_RESULT] = $ref %= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_AND_EQUAL:		// &=
								$param[FOXWAY_STACK_RESULT] = $ref &= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_OR_EQUAL:		// |=
								$param[FOXWAY_STACK_RESULT] = $ref |= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_XOR_EQUAL:		// ^=
								$param[FOXWAY_STACK_RESULT] = $ref ^= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_SL_EQUAL:		// <<=
								$param[FOXWAY_STACK_RESULT] = $ref <<= $value[FOXWAY_STACK_PARAM_2];
								break;
							case T_SR_EQUAL:		// >>=
								$param[FOXWAY_STACK_RESULT] = $ref >>= $value[FOXWAY_STACK_PARAM_2];
								break;
						}
						unset($param);
						break;
				}
			}
		} while( list($code[$i][FOXWAY_STACK_RESULT], $code, $i, $c, $loopsOwner) = array_pop($memory) );

		return $return;
	}

	private static function fillList( &$values, &$param, &$thisVariables ) {
		$return = array();
		foreach ( $param[FOXWAY_STACK_PARAM] as $key => $val ) {
			if( $val !== null ) { // skip emty params. Example: list(, $bar) = $array;
				if( $val[FOXWAY_STACK_COMMAND] == T_LIST ) { // T_LIST inside other T_LIST. Example: list($a, list($b, $c)) = array(1, array(2, 3));
					if ( is_array($values) && isset($values[$key]) ) {
						$return[$key] = self::fillList($values[$key], $val, $thisVariables);
					} else {
						static $a=array();
						$return[$key] = self::fillList($a, $val, $thisVariables);
					}
					continue;
				}
				$ref = &$thisVariables[ $val[FOXWAY_STACK_PARAM] ];
				if ( isset($val[FOXWAY_STACK_ARRAY_INDEX]) ) { // Example: list($foo[0], $foo[1]) = $array;
					foreach ( $val[FOXWAY_STACK_ARRAY_INDEX] as $v ) {
						if (  $v === null ) { // Example: $foo[]
							$t = null;
							$ref[] = &$t;
							$ref = &$t;
							unset( $t );
						} else {
							if ( !isset($ref[$v]) ) {
								$ref[$v] = null;
							}
							$ref = &$ref[$v];
						}
					}
				}
				if ( is_array($values) ) {
					if ( isset($values[$key]) ) {
						$ref = $values[$key];
					} else {
						$ref = null;
						// @todo E_NOTICE
					}
				} else { // list() work with array only
					$ref = null;
				}
				$return[$key] = $ref;
			}
		}
	}

}
