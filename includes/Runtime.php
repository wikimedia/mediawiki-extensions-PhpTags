<?php
namespace PhpTags;

define( 'PHPTAGS_STACK_RESULT', 'r' );
define( 'PHPTAGS_STACK_COMMAND', 'c' );
define( 'PHPTAGS_STACK_PARAM', 'p' );
define( 'PHPTAGS_STACK_PARAM_2', 's' );
define( 'PHPTAGS_STACK_INC_AFTER', 'i' );
define( 'PHPTAGS_STACK_TOKEN_LINE', 'l' );
define( 'PHPTAGS_STACK_DO_TRUE', 't' );
define( 'PHPTAGS_STACK_DO_FALSE', 'f' );
define( 'PHPTAGS_STACK_ARRAY_INDEX', 'a' );
define( 'PHPTAGS_STACK_DEBUG', '#' );

/**
 * The runtime class of the extension PhpTags.
 *
 * @file Runtime.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Runtime {

	private static $constantsValue = array();
	private static $constantsHook = array();
	private static $functionsHook = array();
	private static $objectsHook = array();

	static public $time = 0;
	static public $permittedTime = true;
	protected static $startTime = array();

	private static $variables = array();
	private static $staticVariables = array();
	private static $globalVariables = array();

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
		$compiler = new Compiler();
		return self::run( $compiler->compile($code), $args, $scope, $transit );
	}

	public static function run( $code, array $args, $scope = '', $transit = array() ) {
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
		$codeIndex=-1;
		do {
			if( $break ) {
				if( $loopsOwner == T_WHILE ) {
					$break--;
					continue;
				}
				break;
			}elseif( $continue ) {
				if( $loopsOwner == T_WHILE ) {
					$codeIndex = -1;
					$continue = false;
				}else{
					continue;
				}
			}
			$codeIndex++;
			for(; $codeIndex<$c; $codeIndex++ ) {
				$value = &$code[$codeIndex];
				switch ($value[PHPTAGS_STACK_COMMAND]) {
					case '"': // Example: echo "abc$foo";
						$value[PHPTAGS_STACK_RESULT] = implode( $value[PHPTAGS_STACK_PARAM] );
						break;
					case '.':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] . $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '+':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] + $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '-':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] - $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '*':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] * $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '/':
						if ( (int)$value[PHPTAGS_STACK_PARAM_2] == 0 ) {
							$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							$value[PHPTAGS_STACK_RESULT] = null;
							break; /**** EXIT ****/
						}
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] / $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '%':
						if ( (int)$value[PHPTAGS_STACK_PARAM_2] == 0 ) {
							$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							$value[PHPTAGS_STACK_RESULT] = null;
							break; /**** EXIT ****/
						}
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] % $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '&':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] & $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '|':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] | $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '^':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] ^ $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_SL:			// <<
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] << $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_SR:			// >>
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] >> $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_LOGICAL_AND:	// and
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] && $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_LOGICAL_XOR:	// xor
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] xor $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_LOGICAL_OR:	// or
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] || $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '<':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] < $value[PHPTAGS_STACK_PARAM_2];
						break;
					case '>':
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] > $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_IS_SMALLER_OR_EQUAL:	// <=
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] <= $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_IS_GREATER_OR_EQUAL:	// >=
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] >= $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_IS_EQUAL:			// ==
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] == $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_IS_NOT_EQUAL:		// !=
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] != $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_IS_IDENTICAL:		// ===
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] === $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_IS_NOT_IDENTICAL:	// !==
						$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] !== $value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_ECHO:
						foreach( $value[PHPTAGS_STACK_PARAM] as $v ) {
							$return[] = $v;
						}
						break;
					case '~':
						$value[PHPTAGS_STACK_RESULT] = ~$value[PHPTAGS_STACK_PARAM_2];
						break;
					case '!':
						$value[PHPTAGS_STACK_RESULT] = !$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_INT_CAST:		// (int)
						$value[PHPTAGS_STACK_RESULT] = (int)$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_DOUBLE_CAST:		// (double)
						$value[PHPTAGS_STACK_RESULT] = (double)$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_STRING_CAST:		// (string)
						$value[PHPTAGS_STACK_RESULT] = (string)$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_ARRAY_CAST:		// (array)
						$value[PHPTAGS_STACK_RESULT] = (array)$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_BOOL_CAST:		// (bool)
						$value[PHPTAGS_STACK_RESULT] = (bool)$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_UNSET_CAST:		// (unset)
						$value[PHPTAGS_STACK_RESULT] = (unset)$value[PHPTAGS_STACK_PARAM_2];
						break;
					case T_VARIABLE:
						if ( array_key_exists($value[PHPTAGS_STACK_PARAM], $thisVariables) ) {
							$value[PHPTAGS_STACK_RESULT] = $thisVariables[ $value[PHPTAGS_STACK_PARAM] ];
							if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]
								foreach ( $value[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
									if ( is_array($value[PHPTAGS_STACK_RESULT]) ) {
										if ( array_key_exists($v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_RESULT]) ) {
											$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_RESULT][ $v[PHPTAGS_STACK_RESULT] ];
										} else {
											// PHP Notice:  Undefined offset: $1
											$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET, $v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
											$value[PHPTAGS_STACK_RESULT] = null;
										}
									} else {
										if ( isset( $value[PHPTAGS_STACK_RESULT][ $v[PHPTAGS_STACK_RESULT] ]) ) {
											$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_RESULT][ $v[PHPTAGS_STACK_RESULT] ];
										} else {
											// PHP Notice:  Uninitialized string offset: $1
											$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNINIT_STRING_OFFSET, (int)$v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
											$value[PHPTAGS_STACK_RESULT] = null;
										}
									}
								}
							}
						}else{
							$value[PHPTAGS_STACK_RESULT] = null;
							$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE, $value[PHPTAGS_STACK_PARAM], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
						}
						break;
					case '?':
						if( $value[PHPTAGS_STACK_PARAM] ) { // true ?
							if( $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_TRUE] ) { // true ? 1+2 :
								$memory[] = array( &$value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM], $code, $codeIndex, $c, $loopsOwner );
								$code = $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_TRUE];
								$codeIndex = -1;
								$c = count($code);
								$loopsOwner = '?';
							}else{ // true ? 1 :
								$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM];
							}
						}else{ // false ?
							if( $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_FALSE] ) { // false ? ... : 1+2
								$memory[] = array( &$value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM_2], $code, $codeIndex, $c, $loopsOwner );
								$code = $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_FALSE];
								$codeIndex = -1;
								$c = count($code);
								$loopsOwner = '?';
							}else{ // false ? ... : 1
								$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM_2];
							}
						}
						break;
					case T_IF:
						if( $value[PHPTAGS_STACK_PARAM] ) { // Example: if( true )
							if( $value[PHPTAGS_STACK_DO_TRUE] ) { // Stack not empty: if(true);
								$memory[] = array( null, $code, $codeIndex, $c, $loopsOwner );
								$code = $value[PHPTAGS_STACK_DO_TRUE];
								$codeIndex = -1;
								$c = count($code);
								$loopsOwner = T_IF;
							}
						}else{ // Example: if( false )
							if( isset($value[PHPTAGS_STACK_DO_FALSE]) ) { // Stack not empty: if(false) ; else ;
								$memory[] = array( null, $code, $codeIndex, $c, $loopsOwner );
								$code = $value[PHPTAGS_STACK_DO_FALSE];
								$codeIndex = -1;
								$c = count($code);
								$loopsOwner = T_IF;
							}
						}
						break;
					case T_FOREACH:
						if ( !is_array($value[PHPTAGS_STACK_PARAM]) ) {
							$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_INVALID_ARGUMENT_FOR_FOREACH, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							break; // **** EXIT ****
						}
						reset( $value[PHPTAGS_STACK_PARAM] );
						// break is not necessary here
					case T_WHILE: // PHP code "while($foo) { ... }" doing as T_WHILE { T_DO($foo) ... }. If $foo == false, T_DO doing as T_BREAK
						$memory[] = array( null, $code, $codeIndex, $c, $loopsOwner );
						$code = $value[PHPTAGS_STACK_DO_TRUE];
						$codeIndex = -1;
						$c = count($code);
						$loopsOwner = T_WHILE;
						break;
					case T_AS:
						if ( !is_array($value[PHPTAGS_STACK_RESULT]) ) {
							$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_INVALID_ARGUMENT_FOR_FOREACH, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							break; // **** EXIT ****
						}
						if ( isset($value[PHPTAGS_STACK_PARAM_2]) ) { // T_DOUBLE_ARROW Example: while ( $foo as $key=>$value )
							if ( !list($thisVariables[ $value[PHPTAGS_STACK_PARAM] ], $thisVariables[ $value[PHPTAGS_STACK_PARAM_2] ]) = each($value[PHPTAGS_STACK_RESULT]) ) {
								break 2; // go to one level down
							}
						} else { // Example: while ( $foo as $value )
							if ( !list(,$thisVariables[ $value[PHPTAGS_STACK_PARAM] ]) = each($value[PHPTAGS_STACK_RESULT]) ) {
								break 2; // go to one level down
							}
						}
						break;
					case T_BREAK:
						$break = $value[PHPTAGS_STACK_RESULT];
						if( $loopsOwner == T_WHILE ) {
							$break--;
						}
						break 2; // go to one level down
					case T_CONTINUE:
						$break = $value[PHPTAGS_STACK_RESULT]-1;
						if( $loopsOwner == T_WHILE && $break == 0 ) { // Example: while(true) continue;
							$codeIndex = -1;
							break;
						}
						$continue = true;
						break 2; // go to one level down
					case T_ARRAY:			// array
						$tmp = $value[PHPTAGS_STACK_PARAM][0];
						$i = 1;
						foreach ( $value[PHPTAGS_STACK_PARAM_2] as $t ) {
							list ( $k, $v ) = $t;
							$tmp[$k] = $v;
							if ( isset($value[PHPTAGS_STACK_PARAM][$i]) ) {
								foreach ( $value[PHPTAGS_STACK_PARAM][$i] as $n ) {
									$tmp[] = $n;
								}
							}
							$i++;
						}
						$value[PHPTAGS_STACK_RESULT] = $tmp;
						break;
					case T_STATIC:
						$vn = $value[PHPTAGS_STACK_PARAM]; // variable name
						if( !isset(self::$staticVariables[$place]) || !array_key_exists($vn, self::$staticVariables[$place]) ) {
							self::$staticVariables[$place][$vn] = &$value[PHPTAGS_STACK_RESULT];
							if( $value[PHPTAGS_STACK_DO_TRUE] ) {
								//self::$staticVariables[$p][$vn] = null;
								$memory[] = array( null, $code, $codeIndex, $c, $loopsOwner );
								$code = $value[PHPTAGS_STACK_DO_TRUE];
								$codeIndex = -1;
								$c = count($code);
								$loopsOwner = T_STATIC;
							}
						}
						$thisVariables[$vn] = &self::$staticVariables[$place][$vn];
						break;
					case T_GLOBAL:
						foreach( $value[PHPTAGS_STACK_PARAM] as $vn ) { // variable names
							if( !array_key_exists($vn, self::$globalVariables) ) {
								self::$globalVariables[$vn] = null;
							}
							$thisVariables[$vn] = &self::$globalVariables[$vn];
						}
						break;
					case T_STRING:
						$name = $value[PHPTAGS_STACK_PARAM];
						$transit[PHPTAGS_TRANSIT_VARIABLES] = &$thisVariables;
						$transit[PHPTAGS_TRANSIT_EXCEPTION] = array();
						if ( isset($value[PHPTAGS_STACK_PARAM_2]) ) { // This is function or object
							if ( is_array($value[PHPTAGS_STACK_PARAM_2]) ) { // This is function
								if ( !isset(self::$functionsHook[$name]) ) {
									$return[] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_CALL_TO_UNDEFINED_FUNCTION, $name, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									return $return;
								}
								$hookClassName = self::$functionsHook[$name];
								if( !class_exists($hookClassName) ) {
									$return[] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_NONEXISTENT_HOOK_CLASS, array($name, $hookClassName), $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									return $return;
								}
								$classParens = class_parents( $hookClassName );
								if ( !isset($classParens['PhpTags\\BaseHooks']) ) {
									$return[] = new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_INVALID_HOOK_CLASS, array($name, $hookClassName), $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									return $return;
								}

								try {
									wfSuppressWarnings();

									$result = $hookClassName::onFunctionHook( $name, $value[PHPTAGS_STACK_PARAM_2], $transit );
									if ( $result instanceof outPrint ) {
										$value[PHPTAGS_STACK_RESULT] = $result->returnValue;
										$return[] = $result;
									} else {
										$value[PHPTAGS_STACK_RESULT] = $result;
									}

									wfRestoreWarnings();
								} catch ( ExceptionPhpTags $e ) {
									$e->tokenLine = $value[PHPTAGS_STACK_TOKEN_LINE];
									$e->place = $place;
									if ( is_array($e->params) ) {
										array_unshift( $e->params, $name );
									}
									$return[] = $e;
									$value[PHPTAGS_STACK_RESULT] = null;
									break; /**** EXIT ****/
								} catch (Exception $e) {
									$return[] = (string) new ExceptionPhpTags( $name, PHPTAGS_FATAL_ERROR_CALL_TO_FUNCTION, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									$value[PHPTAGS_STACK_RESULT] = null;
									break; /**** EXIT ****/
								}
							} else { // This is object
								// @todo
							}
						} else { // This is constant
							if ( isset(self::$constantsValue[$name]) ) {
								$value[PHPTAGS_STACK_RESULT] = self::$constantsValue[$name];
							} elseif ( isset(self::$constantsHook[$name]) ) {
								$function = &self::$constantsHook[$name];
								$value[PHPTAGS_STACK_RESULT] = is_callable($function) ? $function( $transit ) : $function;
							} elseif ( array_key_exists($name, self::$constantsValue) ) {
								$value[PHPTAGS_STACK_RESULT] = self::$constantsValue[$name];
							} else {
								$value[PHPTAGS_STACK_RESULT] = $name;
								$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_CONSTANT, $name, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							}
						}
						foreach ( $transit[PHPTAGS_TRANSIT_EXCEPTION] as $exc ) {
							if ( $exc instanceof ExceptionPhpTags ) {
								$return[] = (string) $exc;
							}
						}
						if ( is_object($value[PHPTAGS_STACK_RESULT]) && !($value[PHPTAGS_STACK_RESULT] instanceof iRawOutput) ) {
							// @todo
							$value[PHPTAGS_STACK_RESULT] = null;
							$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_RETURNED_INVALID_VALUE, $name, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
						}
						break;
					case T_UNSET:
						foreach ( $value[PHPTAGS_STACK_PARAM] as $val ) {
							$vn = $val[PHPTAGS_STACK_PARAM]; // Variable Name
							if ( array_key_exists($vn, $thisVariables) ) { // defined variable
								if ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // There is array index. Example: unset($foo[0])
									$ref = &$thisVariables[$vn];
									$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
									foreach ( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
										if ( is_string($ref) ) {
											throw new ExceptionPhpTags( null, PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
										} elseif ( !isset($ref[ $v[PHPTAGS_STACK_RESULT] ]) ) { // undefined array index not for string
											continue 2;
										}
										$ref = &$ref[ $v[PHPTAGS_STACK_RESULT] ];
									}
									if ( is_array($ref) ) {
										unset( $ref[ $tmp[PHPTAGS_STACK_RESULT] ] );
									} else {
										throw new ExceptionPhpTags( null, PHPTAGS_FATAL_CANNOT_UNSET_STRING_OFFSETS, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									}
								}else{ // There is no array index. Example: unset($foo)
									unset( $thisVariables[$vn] );
								}
							} elseif ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // undefined variable with array index. Example: unset($foo[1])
								$return[] = (string) new ExceptionPhpTags( $vn, PHPTAGS_NOTICE_UNDEFINED_VARIABLE, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							}
						}
						break;
					case T_ISSET:
						foreach($value[PHPTAGS_STACK_PARAM] as $val) {
							if( !isset($thisVariables[ $val[PHPTAGS_STACK_PARAM] ]) ) { // undefined variable or variable is null
								$value[PHPTAGS_STACK_RESULT] = false;
								break 2;
							} // true, variable is defined
							if( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: isset($foo[1])
								$ref = &$thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
								$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
								foreach( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
									if( !isset($ref[ $v[PHPTAGS_STACK_RESULT] ]) ) { // undefined array index
										$value[PHPTAGS_STACK_RESULT] = false;
										break 3;
									}
									$ref = &$ref[ $v[PHPTAGS_STACK_RESULT] ];
								}
								// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
								if( !isset($ref[ $tmp[PHPTAGS_STACK_RESULT] ]) || (is_string($ref) && is_string($tmp[PHPTAGS_STACK_RESULT] ) && $tmp[PHPTAGS_STACK_RESULT]  != (string)(int)$tmp[PHPTAGS_STACK_RESULT] ) ) {
									$value[PHPTAGS_STACK_RESULT] = false;
									break 2;
								}
							} // true, variable is defined and have no array index
						}
						$value[PHPTAGS_STACK_RESULT] = true;
						break;
					case T_EMPTY:
						foreach($value[PHPTAGS_STACK_PARAM] as $val) {
							if( !array_key_exists($val[PHPTAGS_STACK_PARAM], $thisVariables) ) { // undefined variable
								continue;
							}
							$ref = &$thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
							if( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: empty($foo[1])
								$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
								foreach( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
									if( !isset($ref[ $v[PHPTAGS_STACK_RESULT] ]) ) { // undefined array index
										continue 2;
									}
									$ref = &$ref[ $v[PHPTAGS_STACK_RESULT] ];
								}
								// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.empty.php Example #2 empty() on String Offsets
								if( !empty($ref[ $tmp[PHPTAGS_STACK_RESULT] ]) && (is_array($ref) || !is_string( $tmp[PHPTAGS_STACK_RESULT] ) || $tmp[PHPTAGS_STACK_RESULT]  == (string)(int)$tmp[PHPTAGS_STACK_RESULT] ) ) {
									$value[PHPTAGS_STACK_RESULT] = false;
									break 2;
								}
							}elseif( !empty($ref) ) { // there is no array index and empty() returns false (PHP 5.5.0 supports expressions)
								$value[PHPTAGS_STACK_RESULT] = false;
								break 2;
							}
						}
						$value[PHPTAGS_STACK_RESULT] = true;
						break;
					case T_RETURN:
						return $value[PHPTAGS_STACK_PARAM];
						break;
					default: // ++, --, =, +=, -=, *=, etc...
						$variable = &$value[PHPTAGS_STACK_PARAM];
						if ( $variable[PHPTAGS_STACK_COMMAND] == T_LIST ) { // this is T_LIST. Example: list($foo, $bar) = $array;
							self::fillList( $value[PHPTAGS_STACK_PARAM_2], $variable, $thisVariables );
							unset( $variable );
							break; /**** EXIT ****/
						}
						if( !array_key_exists($variable[PHPTAGS_STACK_PARAM], $thisVariables) ) { // Use undefined variable
							if( array_key_exists(PHPTAGS_STACK_ARRAY_INDEX, $value) ) { // Example: $foo[1]++
								$thisVariables[ $variable[PHPTAGS_STACK_PARAM] ] = array();
							}else{
								$thisVariables[ $variable[PHPTAGS_STACK_PARAM] ] = null;
							}
							if( $value[PHPTAGS_STACK_COMMAND] != '=' ) {
								$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_VARIABLE, $variable[PHPTAGS_STACK_PARAM], $variable[PHPTAGS_STACK_TOKEN_LINE], $place );
							}
						}
						$ref = &$thisVariables[ $variable[PHPTAGS_STACK_PARAM] ];
						if ( array_key_exists(PHPTAGS_STACK_ARRAY_INDEX, $variable) ) { // Example: $foo[1]++
							foreach ( $variable[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
								if ( $v === null ) { // Example: $foo[]
									$t = null;
									$ref[] = &$t;
									$ref = &$t;
									unset( $t );
								} else {
									if ( $ref === null ) {
										if( $value[PHPTAGS_STACK_COMMAND] != '=' ) {
											// PHP Notice:  Undefined offset: $1
											$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET, $v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
										}
										$ref[ $v[PHPTAGS_STACK_RESULT] ] = null;
										$ref = &$ref[ $v[PHPTAGS_STACK_RESULT] ];
									} elseif ( is_array($ref) ) {
										if ( !array_key_exists($v[PHPTAGS_STACK_RESULT], $ref) ) {
											$ref[ $v[PHPTAGS_STACK_RESULT] ] = null;
											if( $value[PHPTAGS_STACK_COMMAND] != '=' ) {
												// PHP Notice:  Undefined offset: $1
												$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET, $v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
											}
										}
										$ref = &$ref[ $v[PHPTAGS_STACK_RESULT] ];
									} elseif ( is_string($ref) ) {
										// PHP Fatal error:  Cannot use string offset as an array
										throw new ExceptionPhpTags( PHPTAGS_EXCEPTION_NOTICE_UNDEFINED_OFFSET, $v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									} else {
										$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_FATAL_STRING_OFFSET_AS_ARRAY, $v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
										unset( $variable );
										break 2;
										// PHP Warning:  Cannot use a scalar value as an array
									}
								}
							}
						}
						switch ( $value[PHPTAGS_STACK_COMMAND] ) {
							case T_INC:
								if ( $value[PHPTAGS_STACK_PARAM_2] ) { // $foo++
									$value[PHPTAGS_STACK_RESULT] = $ref++;
								}else{ // ++$foo
									$value[PHPTAGS_STACK_RESULT] = ++$ref;
								}
								break;
							case T_DEC:
								if ( $value[PHPTAGS_STACK_PARAM_2] ) { // $foo--
									$value[PHPTAGS_STACK_RESULT] = $ref--;
								}else{ // --$foo
									$value[PHPTAGS_STACK_RESULT] = --$ref;
								}
								break;
							case '=':
								$value[PHPTAGS_STACK_RESULT] = $ref = $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_CONCAT_EQUAL:	// .=
								$value[PHPTAGS_STACK_RESULT] = $ref .= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_PLUS_EQUAL:		// +=
								$value[PHPTAGS_STACK_RESULT] = $ref += $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_MINUS_EQUAL:		// -=
								$value[PHPTAGS_STACK_RESULT] = $ref -= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_MUL_EQUAL:		// *=
								$value[PHPTAGS_STACK_RESULT] = $ref *= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_DIV_EQUAL:		// /=
								if ( (int)$value[PHPTAGS_STACK_PARAM_2] == 0 ) {
									$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									$value[PHPTAGS_STACK_RESULT] = $ref = null;
									break; // **** EXIT ****
								}
								$value[PHPTAGS_STACK_RESULT] = $ref /= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_MOD_EQUAL:		// %=
								if ( (int)$value[PHPTAGS_STACK_PARAM_2] == 0 ) {
									$return[] = (string) new ExceptionPhpTags( PHPTAGS_EXCEPTION_WARNING_DIVISION_BY_ZERO, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
									$value[PHPTAGS_STACK_RESULT] = $ref = null;
									break; // **** EXIT ****
								}
								$value[PHPTAGS_STACK_RESULT] = $ref %= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_AND_EQUAL:		// &=
								$value[PHPTAGS_STACK_RESULT] = $ref &= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_OR_EQUAL:		// |=
								$value[PHPTAGS_STACK_RESULT] = $ref |= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_XOR_EQUAL:		// ^=
								$value[PHPTAGS_STACK_RESULT] = $ref ^= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_SL_EQUAL:		// <<=
								$value[PHPTAGS_STACK_RESULT] = $ref <<= $value[PHPTAGS_STACK_PARAM_2];
								break;
							case T_SR_EQUAL:		// >>=
								$value[PHPTAGS_STACK_RESULT] = $ref >>= $value[PHPTAGS_STACK_PARAM_2];
								break;
						}
						unset( $value );
						break;
				}
			}
		} while( list($code[$codeIndex][PHPTAGS_STACK_RESULT], $code, $codeIndex, $c, $loopsOwner) = array_pop($memory) );

		return $return;
	}

	private static function fillList( &$values, &$param, &$thisVariables ) {
		$return = array();
		foreach ( $param[PHPTAGS_STACK_PARAM] as $key => $val ) {
			if( $val !== null ) { // skip emty params. Example: list(, $bar) = $array;
				if( $val[PHPTAGS_STACK_COMMAND] == T_LIST ) { // T_LIST inside other T_LIST. Example: list($a, list($b, $c)) = array(1, array(2, 3));
					if ( is_array($values) && isset($values[$key]) ) {
						$return[$key] = self::fillList($values[$key], $val, $thisVariables);
					} else {
						static $a=array();
						$return[$key] = self::fillList($a, $val, $thisVariables);
					}
					continue;
				}
				$ref = &$thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
				if ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: list($foo[0], $foo[1]) = $array;
					foreach ( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
						if (  $v === null ) { // Example: $foo[]
							$t = null;
							$ref[] = &$t;
							$ref = &$t;
							unset( $t );
						} else {
							if ( !isset($ref[ $v[PHPTAGS_STACK_RESULT] ]) ) {
								$ref[ $v[PHPTAGS_STACK_RESULT] ] = null;
							}
							$ref = &$ref[ $v[PHPTAGS_STACK_RESULT] ];
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

	public static function setConstantsValue( array $constantsValue ) {
		self::$constantsValue += $constantsValue;
	}
	public static function setConstantsHook( $className, array $constantsName ) {
		self::$constantsHook += array_fill_keys( $constantsName, $className );
	}
	public static function setFunctionsHook( $className, array $functionsName ) {
		self::$functionsHook += array_fill_keys( $functionsName, $className );
	}
	public static function setObjectsHook( $className, array $objectsName ) {
		self::$objectsHook += array_fill_keys( $objectsName, $className );
	}

}
