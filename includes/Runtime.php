<?php
namespace PhpTags;

define( 'PHPTAGS_STACK_RESULT', 'r' );
define( 'PHPTAGS_STACK_COMMAND', 'c' );
define( 'PHPTAGS_STACK_PARAM', 'p' );
define( 'PHPTAGS_STACK_PARAM_2', 's' );
define( 'PHPTAGS_STACK_PARAM_3', 'o' );
define( 'PHPTAGS_STACK_INC_AFTER', 'i' );
define( 'PHPTAGS_STACK_TOKEN_LINE', 'l' );
define( 'PHPTAGS_STACK_DO_TRUE', 't' );
define( 'PHPTAGS_STACK_DO_FALSE', 'f' );
define( 'PHPTAGS_STACK_ARRAY_INDEX', 'a' );
define( 'PHPTAGS_STACK_DEBUG', '#' );
define( 'PHPTAGS_STACK_AIM', '*' );
define( 'PHPTAGS_STACK_HOOK_TYPE', 'h' );

define( 'PHPTAGS_HOOK_GET_CONSTANT', '_' );
define( 'PHPTAGS_HOOK_FUNCTION', 'f' );
define( 'PHPTAGS_HOOK_GET_STATIC_PROPERTY', 'c' );
define( 'PHPTAGS_HOOK_GET_OBJECT_PROPERTY', 'p' );
define( 'PHPTAGS_HOOK_SET_STATIC_PROPERTY', 'k' );
define( 'PHPTAGS_HOOK_SET_OBJECT_PROPERTY', 'b' );
define( 'PHPTAGS_HOOK_STATIC_METHOD', 's' );
define( 'PHPTAGS_HOOK_OBJECT_METHOD', 'm' );

define( 'PHPTAGS_OBJECT_DEFINITION', 0 );
define( 'PHPTAGS_METHOD_CONSTRUCTOR', 1 );

define( 'PHPTAGS_T_LOGICAL_OR', 263 );
define( 'PHPTAGS_T_LOGICAL_XOR', 264 );
define( 'PHPTAGS_T_LOGICAL_AND', 265 );
define( 'PHPTAGS_T_PRINT', 266 );
define( 'PHPTAGS_T_SR_EQUAL', 268 );
define( 'PHPTAGS_T_SL_EQUAL', 269 );
define( 'PHPTAGS_T_XOR_EQUAL', 270 );
define( 'PHPTAGS_T_OR_EQUAL', 271 );
define( 'PHPTAGS_T_AND_EQUAL', 272 );
define( 'PHPTAGS_T_MOD_EQUAL', 273 );
define( 'PHPTAGS_T_CONCAT_EQUAL', 274 );
define( 'PHPTAGS_T_DIV_EQUAL', 275 );
define( 'PHPTAGS_T_MUL_EQUAL', 276 );
define( 'PHPTAGS_T_MINUS_EQUAL', 277 );
define( 'PHPTAGS_T_PLUS_EQUAL', 278 );
define( 'PHPTAGS_T_IS_NOT_IDENTICAL', 281 );
define( 'PHPTAGS_T_IS_IDENTICAL', 282 );
define( 'PHPTAGS_T_IS_NOT_EQUAL', 283 );
define( 'PHPTAGS_T_IS_EQUAL', 284 );
define( 'PHPTAGS_T_IS_GREATER_OR_EQUAL', 285 );
define( 'PHPTAGS_T_IS_SMALLER_OR_EQUAL', 286 );
define( 'PHPTAGS_T_SR', 287 );
define( 'PHPTAGS_T_SL', 288 );
define( 'PHPTAGS_T_UNSET_CAST', 290 );
define( 'PHPTAGS_T_BOOL_CAST', 291 );
define( 'PHPTAGS_T_ARRAY_CAST', 293 );
define( 'PHPTAGS_T_STRING_CAST', 294 );
define( 'PHPTAGS_T_DOUBLE_CAST', 295 );
define( 'PHPTAGS_T_INT_CAST', 296 );
define( 'PHPTAGS_T_DEC', 297 );
define( 'PHPTAGS_T_INC', 298 );
define( 'PHPTAGS_T_NEW', 300 );
define( 'PHPTAGS_T_IF', 302 );
define( 'PHPTAGS_T_HOOK', 308 );
define( 'PHPTAGS_T_HOOK_CHECK_PARAM', 309 );
define( 'PHPTAGS_T_VARIABLE', 310 );
define( 'PHPTAGS_T_WHILE', 319 );
define( 'PHPTAGS_T_FOREACH', 323 );
define( 'PHPTAGS_T_AS', 327 );
define( 'PHPTAGS_T_BREAK', 332 );
define( 'PHPTAGS_T_CONTINUE', 333 );
define( 'PHPTAGS_T_RETURN', 337 );
define( 'PHPTAGS_T_GLOBAL', 344 );
define( 'PHPTAGS_T_STATIC', 350 );
define( 'PHPTAGS_T_UNSET', 352 );
define( 'PHPTAGS_T_ISSET', 353 );
define( 'PHPTAGS_T_EMPTY', 354 );
define( 'PHPTAGS_T_LIST', 363 );
define( 'PHPTAGS_T_ARRAY', 364 );
define( 'PHPTAGS_T_COPY', 500 );

/**
 * The runtime class of the extension PhpTags.
 *
 * @file Runtime.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Runtime {

	public static $loopsLimit = 0;
	public static $transit = array();
//	static public $time = 0;
//	static public $permittedTime = true;
//	protected static $startTime = array();

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

	public static function runSource($code, array $args = array(), $scope = '' ) {
		$compiler = new Compiler();
		return self::run( $compiler->compile($code), $args, $scope );
	}

	public static function run( $code, array $args, $scope = '' ) {
		set_error_handler( '\\PhpTags\\ErrorHandler::onError' );
		try {
			if( !isset(self::$variables[$scope]) ) {
				self::$variables[$scope] = array();
			}
			$thisVariables = &self::$variables[$scope];
			$thisVariables['argv'] = $args;
			$thisVariables['argc'] = count($args);
			$thisVariables['GLOBALS'] = &self::$globalVariables;
			self::$transit[PHPTAGS_TRANSIT_VARIABLES] = &$thisVariables;
			self::$transit[PHPTAGS_TRANSIT_EXCEPTION] = array();
			$exceptions =& self::$transit[PHPTAGS_TRANSIT_EXCEPTION];
			$memory=array();
			$return = array();
			$break = 0; // used for T_BREAK
			$continue = false; // used for T_CONTINUE
			$loopsOwner = null;
			$place = isset($args[0]) ? $args[0] : ''; // Page name for static variables and error messages

			$c=count($code);
			$codeIndex=-1;

			do {
				if ( $break ) {
					if ( $loopsOwner == T_WHILE ) {
						$break--;
						continue;
					}
					break;
				} elseif ( $continue ) {
					if ( $loopsOwner == T_WHILE ) {
						$codeIndex = -1;
						$continue = false;
					} else {
						continue;
					}
				}
				$codeIndex++;
				for ( ; $codeIndex < $c; $codeIndex++ ) {
					$value = &$code[$codeIndex];
					switch ( $value[PHPTAGS_STACK_COMMAND] ) {
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
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] / $value[PHPTAGS_STACK_PARAM_2];
							break;
						case '%':
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
						case PHPTAGS_T_SL:			// <<
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] << $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_SR:			// >>
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] >> $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_LOGICAL_AND:	// and
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] && $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_LOGICAL_XOR:	// xor
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] xor $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_LOGICAL_OR:	// or
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] || $value[PHPTAGS_STACK_PARAM_2];
							break;
						case '<':
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] < $value[PHPTAGS_STACK_PARAM_2];
							break;
						case '>':
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] > $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_IS_SMALLER_OR_EQUAL:	// <=
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] <= $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_IS_GREATER_OR_EQUAL:	// >=
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] >= $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_IS_EQUAL:			// ==
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] == $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_IS_NOT_EQUAL:		// !=
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] != $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_IS_IDENTICAL:		// ===
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] === $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_IS_NOT_IDENTICAL:	// !==
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] !== $value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_PRINT:
							if( $value[PHPTAGS_STACK_PARAM] instanceof GenericObject ) {
								$return[] = $value[PHPTAGS_STACK_PARAM]->toString();
							} else {
								$return[] = $value[PHPTAGS_STACK_PARAM];
							}
							break;
						case '~':
							$value[PHPTAGS_STACK_RESULT] = ~$value[PHPTAGS_STACK_PARAM_2];
							break;
						case '!':
							$value[PHPTAGS_STACK_RESULT] = !$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_INT_CAST:		// (int)
							$value[PHPTAGS_STACK_RESULT] = (int)$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_DOUBLE_CAST:		// (double)
							$value[PHPTAGS_STACK_RESULT] = (double)$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_STRING_CAST:		// (string)
							$value[PHPTAGS_STACK_RESULT] = (string)$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_ARRAY_CAST:		// (array)
							$value[PHPTAGS_STACK_RESULT] = (array)$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_BOOL_CAST:		// (bool)
							$value[PHPTAGS_STACK_RESULT] = (bool)$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_UNSET_CAST:		// (unset)
							$value[PHPTAGS_STACK_RESULT] = (unset)$value[PHPTAGS_STACK_PARAM_2];
							break;
						case PHPTAGS_T_VARIABLE:
							$aim = $value[PHPTAGS_STACK_AIM];
							if ( isset($thisVariables[ $value[PHPTAGS_STACK_PARAM] ]) || array_key_exists($value[PHPTAGS_STACK_PARAM], $thisVariables) ) {
								$value[PHPTAGS_STACK_PARAM_2][$aim] =& $thisVariables[ $value[PHPTAGS_STACK_PARAM] ];
								if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]
									foreach ( $value[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
										if ( is_array( $value[PHPTAGS_STACK_PARAM_2][$aim] ) ) {
											if ( isset($value[PHPTAGS_STACK_PARAM_2][$aim][$v]) || array_key_exists($v, $value[PHPTAGS_STACK_PARAM_2][$aim]) ) {
												$value[PHPTAGS_STACK_PARAM_2][$aim] =& $value[PHPTAGS_STACK_PARAM_2][$aim][$v];
											} else {
												// PHP Notice:  Undefined offset: $1
												$return[] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_INDEX, $v, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
												unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
												$value[PHPTAGS_STACK_PARAM_2][$aim] = null;
											}
										} else {
											if ( isset( $value[PHPTAGS_STACK_PARAM_2][$aim][$v]) ) {
												unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
												$value[PHPTAGS_STACK_PARAM_2][$aim] = $value[PHPTAGS_STACK_PARAM_2][$aim][$v];
											} else {
												// PHP Notice:  Uninitialized string offset: $1
												$return[] = new PhpTagsException( PhpTagsException::NOTICE_UNINIT_STRING_OFFSET, (int)$v, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
												unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
												$value[PHPTAGS_STACK_PARAM_2][$aim] = null;
											}
										}
									}
								}
							} else {
								unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
								$value[PHPTAGS_STACK_PARAM_2][$aim] = null;
								$return[] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $value[PHPTAGS_STACK_PARAM], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
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
						case PHPTAGS_T_IF:
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
						case PHPTAGS_T_FOREACH:
							if ( !is_array($value[PHPTAGS_STACK_PARAM]) ) {
								$return[] = new PhpTagsException( PhpTagsException::WARNING_INVALID_ARGUMENT_FOR_FOREACH, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
								break; // **** EXIT ****
							}
							reset( $value[PHPTAGS_STACK_PARAM] );
							// break is not necessary here
						case PHPTAGS_T_WHILE: // PHP code "while($foo) { ... }" doing as T_WHILE { T_DO($foo) ... }. If $foo == false, T_DO doing as T_BREAK
							$memory[] = array( null, $code, $codeIndex, $c, $loopsOwner );
							$code = $value[PHPTAGS_STACK_DO_TRUE];
							$codeIndex = -1;
							$c = count($code);
							$loopsOwner = T_WHILE;
							break;
						case PHPTAGS_T_AS:
							if ( !is_array($value[PHPTAGS_STACK_RESULT]) ) {
								$return[] = new PhpTagsException( PhpTagsException::WARNING_INVALID_ARGUMENT_FOR_FOREACH, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
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
						case PHPTAGS_T_BREAK:
							$break = $value[PHPTAGS_STACK_RESULT];
							if( $loopsOwner == T_WHILE ) {
								$break--;
							}
							break 2; // go to one level down
						case PHPTAGS_T_CONTINUE:
							if( self::$loopsLimit-- <= 0 ) {
								$return[] = new PhpTagsException( PhpTagsException::FATAL_LOOPS_LIMIT_REACHED, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
								return $return;
							}
							$break = $value[PHPTAGS_STACK_RESULT]-1;
							if( $loopsOwner == T_WHILE && $break == 0 ) { // Example: while(true) continue;
								$codeIndex = -1;
								break;
							}
							$continue = true;
							break 2; // go to one level down
						case PHPTAGS_T_ARRAY:			// array
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
						case PHPTAGS_T_STATIC:
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
						case PHPTAGS_T_GLOBAL:
							foreach( $value[PHPTAGS_STACK_PARAM] as $vn ) { // variable names
								if( !array_key_exists($vn, self::$globalVariables) ) {
									self::$globalVariables[$vn] = null;
								}
								$thisVariables[$vn] = &self::$globalVariables[$vn];
							}
							break;
						case PHPTAGS_T_HOOK_CHECK_PARAM:
							$i = $value[PHPTAGS_STACK_AIM];
							$reference_info = Hooks::getReferenceInfo(
									$i + 1, // ordinal number of the argument
									$value[PHPTAGS_STACK_PARAM], // name of function or method
									$value[PHPTAGS_STACK_PARAM_3] === false ? false : $value[PHPTAGS_STACK_PARAM_3][PHPTAGS_STACK_PARAM_3] // $object or false
								);

							if ( $value[PHPTAGS_STACK_PARAM_2] === true && $reference_info === false ) {
								// Param is variable and it's need to clone
								$t = $value[PHPTAGS_STACK_RESULT][$i];
								unset( $value[PHPTAGS_STACK_RESULT][$i] );
								$value[PHPTAGS_STACK_RESULT][$i] = $t;
							} elseif ( $value[PHPTAGS_STACK_PARAM_2] === false && $reference_info === true ) {
								// Param is not variable and it's need reference
								$return[] = new PhpTagsException( PhpTagsException::FATAL_VALUE_PASSED_BY_REFERENCE, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
								return $return;
							}
							break;
						case PHPTAGS_T_HOOK:
							$result = Hooks::callHook(
									$value[PHPTAGS_STACK_HOOK_TYPE],
									$value[PHPTAGS_STACK_PARAM_2], // arguments
									$value[PHPTAGS_STACK_PARAM], // name of function or method
									$value[PHPTAGS_STACK_PARAM_3] // $object or false
								);

							if ( $result instanceof outPrint ) {
								$value[PHPTAGS_STACK_RESULT] = $result->returnValue;
								$return[] = $result;
							} else {
								$value[PHPTAGS_STACK_RESULT] = $result;
							}
							if ( is_object($value[PHPTAGS_STACK_RESULT]) && !($value[PHPTAGS_STACK_RESULT] instanceof iRawOutput || $value[PHPTAGS_STACK_RESULT] instanceof GenericObject) ) {
								// @todo
								$value[PHPTAGS_STACK_RESULT] = null;
								$return[] = new PhpTagsException( PhpTagsException::WARNING_RETURNED_INVALID_VALUE, $value[PHPTAGS_STACK_PARAM], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
							}
							break;
						case PHPTAGS_T_NEW:
							$value[PHPTAGS_STACK_RESULT] = Hooks::createObject( $value[PHPTAGS_STACK_PARAM_2], $value[PHPTAGS_STACK_PARAM_3] );
							break;
						case PHPTAGS_T_UNSET:
							foreach ( $value[PHPTAGS_STACK_PARAM] as $val ) {
								$vn = $val[PHPTAGS_STACK_PARAM]; // Variable Name
								if ( isset($thisVariables[$vn]) || array_key_exists($vn, $thisVariables) ) { // defined variable
									if ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // There is array index. Example: unset($foo[0])
										$ref = &$thisVariables[$vn];
										$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
										foreach ( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
											if ( is_string($ref) ) {
												throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
											} elseif ( !isset($ref[$v]) ) { // undefined array index not for string
												continue 2;
											}
											$ref = &$ref[$v];
										}
										if ( is_array($ref) ) {
											unset( $ref[$tmp] );
										} else {
											throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
										}
									}else{ // There is no array index. Example: unset($foo)
										unset( $thisVariables[$vn] );
									}
								} elseif ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // undefined variable with array index. Example: unset($foo[1])
									$return[] = new PhpTagsException( PHPTAGS_NOTICE_UNDEFINED_VARIABLE, $vn, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
								}
							}
							break;
						case PHPTAGS_T_ISSET:
							foreach($value[PHPTAGS_STACK_PARAM] as $val) {
								if( !isset($thisVariables[ $val[PHPTAGS_STACK_PARAM] ]) ) { // undefined variable or variable is null
									$value[PHPTAGS_STACK_RESULT] = false;
									break 2;
								} // true, variable is defined
								if( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: isset($foo[1])
									$ref = &$thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
									$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
									foreach( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
										if( !isset($ref[$v]) ) { // undefined array index
											$value[PHPTAGS_STACK_RESULT] = false;
											break 3;
										}
										$ref = &$ref[$v];
									}
									// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
									if( !isset($ref[$tmp]) || (is_string($ref) && is_string($tmp ) && $tmp  != (string)(int)$tmp ) ) {
										$value[PHPTAGS_STACK_RESULT] = false;
										break 2;
									}
								} // true, variable is defined and have no array index
							}
							$value[PHPTAGS_STACK_RESULT] = true;
							break;
						case PHPTAGS_T_EMPTY:
							foreach($value[PHPTAGS_STACK_PARAM] as $val) {
								if( !array_key_exists($val[PHPTAGS_STACK_PARAM], $thisVariables) ) { // undefined variable
									continue;
								}
								$ref = &$thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
								if( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: empty($foo[1])
									$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
									foreach( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
										if( !isset($ref[$v]) ) { // undefined array index
											continue 2;
										}
										$ref = &$ref[$v];
									}
									// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.empty.php Example #2 empty() on String Offsets
									if( !empty($ref[$tmp]) && (is_array($ref) || !is_string( $tmp ) || $tmp  == (string)(int)$tmp ) ) {
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
						case PHPTAGS_T_RETURN:
							if ( $return ) {
								return new PhpTagsException();
							}
							return $value[PHPTAGS_STACK_PARAM];
						case PHPTAGS_T_COPY:
							$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM];
							break;
						default: // ++, --, =, +=, -=, *=, etc...
							$variable = &$value[PHPTAGS_STACK_PARAM];
							$variableName = $variable[PHPTAGS_STACK_PARAM];
							if ( $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_LIST ) { // this is T_LIST. Example: list($foo, $bar) = $array;
								self::fillList( $value[PHPTAGS_STACK_PARAM_2], $variable, $thisVariables );
								$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM_2];
								unset( $variable );
								break; /**** EXIT ****/
							}
							if( !(isset($thisVariables[$variableName]) || array_key_exists($variableName, $thisVariables)) ) { // Use undefined variable
								if( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]++
									$thisVariables[$variableName] = array();
								}else{
									$thisVariables[$variableName] = null;
								}
								if( $value[PHPTAGS_STACK_COMMAND] != '=' ) {
									$return[] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $variableName, $variable[PHPTAGS_STACK_TOKEN_LINE], $place );
								}
							}
							$ref = &$thisVariables[$variableName];
							if ( isset($variable[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]++
								foreach ( $variable[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
									if ( $v === INF ) { // Example: $foo[]
										$t = null;
										$ref[] = &$t;
										$ref = &$t;
										unset( $t );
									} else {
										if ( $ref === null ) {
											if( $value[PHPTAGS_STACK_COMMAND] != '=' ) {
												// PHP Notice:  Undefined offset: $1
												$return[] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $v[PHPTAGS_STACK_RESULT], $value[PHPTAGS_STACK_TOKEN_LINE], $place );
											}
											$ref[$v] = null;
											$ref = &$ref[$v];
										} elseif ( is_array($ref) ) {
											if ( !( isset($ref[$v]) || array_key_exists($v, $ref) ) ) {
												$ref[$v] = null;
												if( $value[PHPTAGS_STACK_COMMAND] != '=' ) {
													// PHP Notice:  Undefined offset: $1
													$return[] = new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_INDEX, $v, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
												}
											}
											$ref = &$ref[$v];
										} elseif ( is_string($ref) ) {
											// PHP Fatal error:  Cannot use string offset as an array
											throw new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $v, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
										} else {
											$return[] = new PhpTagsException( PhpTagsException::FATAL_STRING_OFFSET_AS_ARRAY, $v, $value[PHPTAGS_STACK_TOKEN_LINE], $place );
											unset( $variable );
											break 2;
											// PHP Warning:  Cannot use a scalar value as an array
										}
									}
								}
							}
							switch ( $value[PHPTAGS_STACK_COMMAND] ) {
								case PHPTAGS_T_INC:
									if ( $value[PHPTAGS_STACK_PARAM_2] ) { // $foo++
										$value[PHPTAGS_STACK_RESULT] = $ref++;
									}else{ // ++$foo
										$value[PHPTAGS_STACK_RESULT] = ++$ref;
									}
									break;
								case PHPTAGS_T_DEC:
									if ( $value[PHPTAGS_STACK_PARAM_2] ) { // $foo--
										$value[PHPTAGS_STACK_RESULT] = $ref--;
									}else{ // --$foo
										$value[PHPTAGS_STACK_RESULT] = --$ref;
									}
									break;
								case '=':
									$value[PHPTAGS_STACK_RESULT] = $ref = $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_CONCAT_EQUAL:	// .=
									$value[PHPTAGS_STACK_RESULT] = $ref .= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_PLUS_EQUAL:		// +=
									$value[PHPTAGS_STACK_RESULT] = $ref += $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_MINUS_EQUAL:		// -=
									$value[PHPTAGS_STACK_RESULT] = $ref -= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_MUL_EQUAL:		// *=
									$value[PHPTAGS_STACK_RESULT] = $ref *= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_DIV_EQUAL:		// /=
									$value[PHPTAGS_STACK_RESULT] = $ref /= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_MOD_EQUAL:		// %=
									$value[PHPTAGS_STACK_RESULT] = $ref %= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_AND_EQUAL:		// &=
									$value[PHPTAGS_STACK_RESULT] = $ref &= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_OR_EQUAL:		// |=
									$value[PHPTAGS_STACK_RESULT] = $ref |= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_XOR_EQUAL:		// ^=
									$value[PHPTAGS_STACK_RESULT] = $ref ^= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_SL_EQUAL:		// <<=
									$value[PHPTAGS_STACK_RESULT] = $ref <<= $value[PHPTAGS_STACK_PARAM_2];
									break;
								case PHPTAGS_T_SR_EQUAL:		// >>=
									$value[PHPTAGS_STACK_RESULT] = $ref >>= $value[PHPTAGS_STACK_PARAM_2];
									break;
							}
							unset( $value );
							break;
					}
					if ( $exceptions ) {
						foreach ( $exceptions as $exc ) {
							if ( $exc instanceof PhpTagsException ) {
								$exc->tokenLine = $value[PHPTAGS_STACK_TOKEN_LINE];
								$exc->place = $place;
								$return[] = $exc;
							}
						}
						self::$transit[PHPTAGS_TRANSIT_EXCEPTION] = array();
					}
				}
			} while( list($code[$codeIndex][PHPTAGS_STACK_RESULT], $code, $codeIndex, $c, $loopsOwner) = array_pop($memory) );
		}  catch ( PhpTagsException $e ) {
			$e->tokenLine = $value[PHPTAGS_STACK_TOKEN_LINE];
			$e->place = $place;
			foreach ( self::$transit[PHPTAGS_TRANSIT_EXCEPTION] as $exc ) {
				if ( $exc instanceof PhpTagsException ) {
					$exc->tokenLine = $value[PHPTAGS_STACK_TOKEN_LINE];
					$exc->place = $place;
					$return[] = $exc;
				}
			}
			self::$transit[PHPTAGS_TRANSIT_EXCEPTION] = array();
			$return[] = $e;
			$value[PHPTAGS_STACK_RESULT] = null;
		}
		restore_error_handler();
		return $return;
	}

	private static function fillList( &$values, &$param, &$thisVariables ) {
		$return = array();
		foreach ( $param[PHPTAGS_STACK_PARAM] as $key => $val ) {
			if( $val !== null ) { // skip emty params. Example: list(, $bar) = $array;
				if( $val[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_LIST ) { // T_LIST inside other T_LIST. Example: list($a, list($b, $c)) = array(1, array(2, 3));
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
						if (  $v === INF ) { // Example: $foo[]
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

	/**
	 * Get Parser
	 * @return \Parser
	 */
	public static function getParser() {
		return self::$transit[PHPTAGS_TRANSIT_PARSER];
	}

	/**
	 * Increment the expensive function count
	 * @param string $functionName
	 * @return null
	 * @throws PhpTagsException
	 */
	public static function incrementExpensiveFunctionCount( $functionName ) {
		if ( false === self::getParser()->incrementExpensiveFunctionCount() ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALLED_MANY_EXPENSIVE_FUNCTION, $functionName );
		}
		return null;
	}

}
