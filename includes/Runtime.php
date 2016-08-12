<?php
namespace PhpTags;

/**
 * The runtime class of the extension PhpTags.
 *
 * @file Runtime.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Runtime {

	const VERSION = 11;

	##### Bytecode array indexes #####
	const B_COMMAND = 0; // Token ID
	const B_RESULT = 1;
	const B_PARAM_1 = 2;
	const B_PARAM_2 = 3;
	const B_OBJECT = 4; // object or object name
	const B_OBJECT_KEY = 5; // object lower case name
	const B_TOKEN_LINE = 6; // Code line
	const B_DO_TRUE = 7; // Do this code if true
	const B_DO_FALSE = 8; // Do this code if false
	const B_ARRAY_INDEX = 9; // get this array index when read variable, $foo['bar']
	const B_DEBUG = 10; // original token string for debugging and error messages
	const B_AIM = 11; // AIM set variable value to this aim
	const B_HOOK_TYPE = 12; // describes PhpTags hook type, constant, function etc (self::H_...)
	const B_METHOD = 13; // method or function name
	const B_METHOD_KEY = 14; // method or function name in lower case
	const B_FLAGS = 15;

	const F_DONT_CHECK_PARAM1 = 1;

	##### Hook types #####
	const H_GET_CONSTANT = '_';
	const H_FUNCTION = 'f';
	const H_GET_OBJECT_CONSTANT = 'c';
	const H_GET_STATIC_PROPERTY = 'q';
	const H_GET_OBJECT_PROPERTY = 'p';
	const H_SET_STATIC_PROPERTY = 'd';
	const H_SET_OBJECT_PROPERTY = 'b';
	const H_STATIC_METHOD = 's';
	const H_OBJECT_METHOD = 'm';
	const H_NEW_OBJECT = 'n';

	public static $loopsLimit = 0;

	private static $variables = array();
	private static $staticVariables = array();
	private static $globalVariables = array();
	private static $ignoreErrors = false;
	private static $stack = array();

	# self::$stack indexes
	const S_RETURN = 0;
	const S_RUNNING = 1;
	const S_RUN_INDEX = 2;
	const S_COUNT = 3;
	const S_LOOPS_OWNER = 4;
	const S_MEMORY = 5;
	const S_PLACE = 6;
	const S_VARIABLES = 7;

	const R_ARRAY = 'Array';
	const R_DUMP_OBJECT = 'object';

	private static $operators = array(
		self::T_QUOTE => 'doQuote',
		self::T_CONCAT => 'doConcat',
		self::T_PLUS => 'doPlus',
		self::T_MINUS => 'doMinus',
		self::T_MUL => 'doMul',
		self::T_DIV => 'doDiv',
		self::T_MOD => 'doMod',
		self::T_AND => 'doAnd',
		self::T_OR => 'doOr',
		self::T_XOR => 'doXor',
		self::T_SL => 'doShiftLeft',
		self::T_SR => 'doShiftRight',
		self::T_LOGICAL_AND => 'doLogicalAnd',
		self::T_LOGICAL_XOR => 'doLogicalXor',
		self::T_LOGICAL_OR => 'doLogicalOr',
		self::T_IS_SMALLER => 'doIsSmaller',
		self::T_IS_GREATER => 'doIsGreater',
		self::T_IS_SMALLER_OR_EQUAL => 'doIsSmallerOrEqual',
		self::T_IS_GREATER_OR_EQUAL => 'doIsGreaterOrEqual',
		self::T_IS_EQUAL => 'doIsEqual',
		self::T_IS_NOT_EQUAL => 'doIsNotEqual',
		self::T_IS_IDENTICAL => 'doIsIdentical',
		self::T_IS_NOT_IDENTICAL => 'doIsNotIdentical',
		self::T_PRINT => 'doPrint',
		self::T_NOT => 'doNot',
		self::T_IS_NOT => 'doIsNot',
		self::T_INT_CAST => 'doIntCast',
		self::T_DOUBLE_CAST => 'doDoubleCast',
		self::T_STRING_CAST => 'doStringCast',
		self::T_ARRAY_CAST => 'doArrayCast',
		self::T_BOOL_CAST => 'doBoolCast',
		self::T_UNSET_CAST => 'doUnsetCast',
		self::T_VARIABLE => 'doVariable',
		self::T_TERNARY => 'doTernary',
		self::T_IF => 'doIf',
		self::T_FOREACH => 'doForeach',
		self::T_WHILE => 'doWhile',
		self::T_AS => 'doAs',
		self::T_BREAK => 'doBreak',
		self::T_CONTINUE => 'doContinue',
		self::T_ARRAY => 'doArray',
		self::T_STATIC => 'doStatic',
		self::T_GLOBAL => 'doGlobal',
		self::T_HOOK_CHECK_PARAM => 'doCheckingParam',
		self::T_HOOK => 'doCallingHook',
		self::T_NEW => 'doNewObject',
		self::T_UNSET => 'doUnset',
		self::T_ISSET => 'doIsSet',
		self::T_EMPTY => 'doIsEmpty',
		self::T_RETURN => 'doReturn',
		self::T_COPY => 'doCopy',
		self::T_IGNORE_ERROR => 'doIgnoreErrors',
		self::T_LIST => 'doList',
		self::T_INC => 'doIncrease',
		self::T_DEC => 'doDecrease',
		self::T_EQUAL => 'doSetVal',
		self::T_CONCAT_EQUAL => 'doSetConcatVal',
		self::T_PLUS_EQUAL => 'doSetPlusVal',
		self::T_MINUS_EQUAL => 'doSetMinusVal',
		self::T_MUL_EQUAL => 'doSetMulVal',
		self::T_DIV_EQUAL => 'doSetDivVal',
		self::T_MOD_EQUAL => 'doSetModVal',
		self::T_AND_EQUAL => 'doSetAndVal',
		self::T_OR_EQUAL => 'doSetOrVal',
		self::T_XOR_EQUAL => 'doSetXorVal',
		self::T_SL_EQUAL => 'doSetShiftLeftVal',
		self::T_SR_EQUAL => 'doSetShiftRightVal',
	);

	public static function reset() {
		global $wgPhpTagsMaxLoops;

		self::$variables = array();
		self::$staticVariables = array();
		self::$globalVariables = array();
		self::$loopsLimit = $wgPhpTagsMaxLoops;
		self::$ignoreErrors = false;
	}

	public static function runSource( $code, array $args = array(), $scope = '' ) {
		return self::run( Compiler::compile( $code ), $args, $scope );
	}

	private static function pushDown( $newCode, $newLoopsOwner, &$refReturn ) {
		$stack =& self::$stack[0];
		$stack[self::S_MEMORY][] = array( &$refReturn, $stack[self::S_RUNNING], $stack[self::S_RUN_INDEX], $stack[self::S_COUNT], $stack[self::S_LOOPS_OWNER] );
		$stack[self::S_RUNNING] = $newCode;
		$stack[self::S_RUN_INDEX] = -1;
		$stack[self::S_COUNT] = count( $newCode );
		$stack[self::S_LOOPS_OWNER] = $newLoopsOwner;
	}

	private static function popUp() {
		$stack =& self::$stack[0];
		list( $stack[self::S_RUNNING][ $stack[self::S_RUN_INDEX] ][self::B_RESULT], $stack[self::S_RUNNING], $stack[self::S_RUN_INDEX], $stack[self::S_COUNT], $stack[self::S_LOOPS_OWNER] ) = array_pop( $stack[self::S_MEMORY] );
	}

	/**
	 * self::T_QUOTE
	 * @param array $value
	 */
	private static function doQuote ( &$value ) {
		$implode = array();
		foreach ( $value[self::B_PARAM_1] as $v ) {
			if ( $v === null || is_scalar( $v ) ) {
				$implode[] = $v;
			} else if ( is_array( $v ) ) {
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING ) );
				$implode[] = self::R_ARRAY;
			} else if ( $v instanceof GenericObject ) {
				$implode[] = $v->toString();
			} else {
				throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
			}
		}
		$value[self::B_RESULT] = implode( $implode );
	}

	/**
	 * self::T_CONCAT
	 * @param array $value
	 */
	private static function doConcat ( &$value ) {
		$v = self::checkStringParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2], $value[self::B_FLAGS] );
		$value[self::B_RESULT] = $v[0] . $v[1];
	}

	/**
	 * self::T_PLUS
	 * @param array $value
	 */
	private static function doPlus ( &$value ) {
		$v = self::checkArrayParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] + $v[1];
	}

	/**
	 * self::T_MINUS
	 * @param array $value
	 */
	private static function doMinus ( &$value ) {
		$v = self::checkScalarParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] - $v[1];
	}

	/**
	 * self::T_MUL
	 * @param array $value
	 */
	private static function doMul ( &$value ) {
		$v1 = $value[self::B_PARAM_1];
		$v2 = $value[self::B_PARAM_2];
		self::checkScalarParams( $v1, $v2 );
		$value[self::B_RESULT] = $v1 * $v2;
	}

	/**
	 * self::T_DIV
	 * @param array $value
	 */
	private static function doDiv ( &$value ) {
		$v1 = $value[self::B_PARAM_1];
		$v2 = $value[self::B_PARAM_2];
		self::checkScalarParams( $v1, $v2 );
		if ( $v2 == 0 ) {
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO ) );
			$value[self::B_RESULT] = false;
		} else {
			$value[self::B_RESULT] = $v1 / $v2;
		}
	}

	/**
	 * self::T_MOD
	 * @param array $value
	 */
	private static function doMod ( &$value ) {
		$v1 = $value[self::B_PARAM_1];
		$v2 = $value[self::B_PARAM_2];
		self::checkScalarParams( $v1, $v2 );
		if ( $v2 == 0 ) {
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO ) );
			$value[self::B_RESULT] = false;
		} else {
			$value[self::B_RESULT] = $v1 % $v2;
		}
	}

	/**
	 * self::T_AND
	 * @param array $value
	 */
	private static function doAnd ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] & $v[1];
	}

	/**
	 * self::T_OR
	 * @param array $value
	 */
	private static function doOr ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] | $v[1];
	}

	/**
	 * self::T_XOR
	 * @param array $value
	 */
	private static function doXor ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] ^ $v[1];
	}

	/**
	 * self::T_SL
	 * @param array $value
	 */
	private static function doShiftLeft ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] << $v[1];
	}

	/**
	 * self::T_SR
	 * @param array $value
	 */
	private static function doShiftRight ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] >> $v[1];
	}

	/**
	 * self::T_LOGICAL_AND
	 * @param array $value
	 */
	private static function doLogicalAnd ( &$value ) {
		$value[self::B_RESULT] = $value[self::B_PARAM_1] && $value[self::B_PARAM_2];
	}

	/**
	 * self::T_LOGICAL_XOR
	 * @param array $value
	 */
	private static function doLogicalXor ( &$value ) {
		$value[self::B_RESULT] = ($value[self::B_PARAM_1] xor $value[self::B_PARAM_2]);
	}

	/**
	 * self::T_LOGICAL_OR
	 * @param array $value
	 */
	private static function doLogicalOr ( &$value ) {
		$value[self::B_RESULT] = $value[self::B_PARAM_1] || $value[self::B_PARAM_2];
	}

	/**
	 * self::T_IS_SMALLER
	 * @param array $value
	 */
	private static function doIsSmaller ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] < $v[1];
	}

	/**
	 * self::T_IS_GREATER
	 * @param array $value
	 */
	private static function doIsGreater ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] > $v[1];
	}

	/**
	 * self::T_IS_SMALLER_OR_EQUAL
	 * @param array $value
	 */
	private static function doIsSmallerOrEqual ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] <= $v[1];
	}

	/**
	 * self::T_IS_GREATER_OR_EQUAL
	 * @param array $value
	 */
	private static function doIsGreaterOrEqual ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] >= $v[1];
	}

	/**
	 * self::T_IS_EQUAL
	 * @param array $value
	 */
	private static function doIsEqual ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] == $v[1];
	}

	/**
	 * self::T_IS_NOT_EQUAL
	 * @param array $value
	 */
	private static function doIsNotEqual ( &$value ) {
		$v = self::checkObjectParams( $value[self::B_PARAM_1], $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $v[0] != $v[1];
	}

	/**
	 * self::T_IS_IDENTICAL
	 * @param array $value
	 */
	private static function doIsIdentical ( &$value ) {
		$value[self::B_RESULT] = $value[self::B_PARAM_1] === $value[self::B_PARAM_2];
	}

	/**
	 * self::T_IS_NOT_IDENTICAL
	 * @param array $value
	 */
	private static function doIsNotIdentical ( &$value ) {
		$value[self::B_RESULT] = $value[self::B_PARAM_1] !== $value[self::B_PARAM_2];
	}

	/**
	 * self::T_PRINT outputs the value
	 * @param array $value
	 */
	private static function doPrint ( &$value ) {
		$v = $value[self::B_PARAM_1];
		if ( is_scalar( $v ) || $v === null ) {
			self::$stack[0][self::S_RETURN][] = $value[self::B_PARAM_1];
		} else if( $v instanceof GenericObject ) {
			self::$stack[0][self::S_RETURN][] = $value[self::B_PARAM_1]->toString();
		} else if ( is_array( $v ) ) {
			self::pushException( new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING ) );
			self::$stack[0][self::S_RETURN][] = self::R_ARRAY;
		} else { // Should never happen
			throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
		}
	}

	/**
	 * self::T_NOT
	 * @param array $value
	 */
	private static function doNot ( &$value ) {
		$v = $value[self::B_PARAM_2];
		if ( $v === null || is_scalar( $v ) ) {
			$value[self::B_RESULT] = ~$v;
		} else {
			throw new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES );
		}
	}

	/**
	 * self::T_IS_NOT
	 * @param array $value
	 */
	private static function doIsNot ( &$value ) {
		$value[self::B_RESULT] = !$value[self::B_PARAM_2];
	}

	/**
	 * self::T_INT_CAST
	 * @param array $value
	 */
	private static function doIntCast ( &$value ) {
		$tmp = null;
		$v = self::checkObjectParams( $value[self::B_PARAM_2], $tmp );
		$value[self::B_RESULT] = (int)$v[0];
	}

	/**
	 * self::T_DOUBLE_CAST
	 * @param array $value
	 */
	private static function doDoubleCast ( &$value ) {
		$tmp = null;
		$v = self::checkObjectParams( $value[self::B_PARAM_2], $tmp, 'double' );
		$value[self::B_RESULT] = (double)$v[0];
	}

	/**
	 * self::T_STRING_CAST
	 * @param array $value
	 */
	private static function doStringCast ( &$value ) {
		$tmp = null;
		$flags = 0;
		$v = self::checkStringParams( $value[self::B_PARAM_2], $tmp, $flags );
		$value[self::B_RESULT] = (string)$v[0];
	}

	/**
	 * self::T_ARRAY_CAST
	 * @param array $value
	 */
	private static function doArrayCast ( &$value ) {
		$v = $value[self::B_PARAM_2];
		if ( is_object( $v ) ) {
			if ( $v instanceof GenericObject ) {
				$v = $v->getDumpValue();
			} else {
				throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
			}
		}
		$value[self::B_RESULT] = (array)$v;
	}

	/**
	 * self::T_BOOL_CAST
	 * @param array $value
	 */
	private static function doBoolCast ( &$value ) {
		$value[self::B_RESULT] = (bool)$value[self::B_PARAM_2];
	}

	/**
	 * self::T_UNSET_CAST
	 * @param array $value
	 */
	private static function doUnsetCast ( &$value ) {
		$value[self::B_RESULT] = null; //(unset)$value[self::B_PARAM_2];
	}

	/**
	 * self::T_VARIABLE
	 * @param array $value
	 */
	private static function doVariable ( &$value ) {
		$variables =& self::$stack[0][self::S_VARIABLES];
		$aim = $value[self::B_AIM];
		if ( isset( $variables[ $value[self::B_PARAM_1] ] ) || array_key_exists( $value[self::B_PARAM_1], $variables ) ) {
			$value[self::B_PARAM_2][$aim] =& $variables[ $value[self::B_PARAM_1] ];
			if ( isset($value[self::B_ARRAY_INDEX]) ) { // Example: $foo[1]
				foreach ( $value[self::B_ARRAY_INDEX] as $v ) {
					if ( is_array( $value[self::B_PARAM_2][$aim] ) ) { // Variable is array. Examle: $foo = ['string']; echo $foo[0];
						if ( isset($value[self::B_PARAM_2][$aim][$v]) || array_key_exists($v, $value[self::B_PARAM_2][$aim]) ) {
							$value[self::B_PARAM_2][$aim] =& $value[self::B_PARAM_2][$aim][$v];
						} else {
							// PHP Notice:  Undefined offset: $1
							self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_INDEX, $v ) );
							unset( $value[self::B_PARAM_2][$aim] );
							$value[self::B_PARAM_2][$aim] = null;
						}
					} else { // Variable is string. Examle: $foo = 'string'; echo $foo[2];
						if ( isset( $value[self::B_PARAM_2][$aim][$v]) ) {
							$tmp = $value[self::B_PARAM_2][$aim][$v];
							unset( $value[self::B_PARAM_2][$aim] );
							$value[self::B_PARAM_2][$aim] = $tmp;
						} else {
							// PHP Notice:  Uninitialized string offset: $1
							self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNINIT_STRING_OFFSET, (int)$v ) );
							unset( $value[self::B_PARAM_2][$aim] );
							$value[self::B_PARAM_2][$aim] = null;
						}
					}
				}
			}
		} else {
			unset( $value[self::B_PARAM_2][$aim] );
			$value[self::B_PARAM_2][$aim] = null;
			self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $value[self::B_PARAM_1] ) );
		}
	}

	/**
	 * self::T_TERNARY
	 * @param array $value
	 */
	private static function doTernary ( &$value ) {
		if( $value[self::B_PARAM_1] ) { // true ?
			if( $value[self::B_PARAM_2][self::B_DO_TRUE] ) { // true ? 1+2 :
				self::pushDown( $value[self::B_PARAM_2][self::B_DO_TRUE], '?', $value[self::B_PARAM_2][self::B_PARAM_1] );
			}else{ // true ? 1 :
				$value[self::B_RESULT] = $value[self::B_PARAM_2][self::B_PARAM_1];
			}
		}else{ // false ?
			if( $value[self::B_PARAM_2][self::B_DO_FALSE] ) { // false ? ... : 1+2
				self::pushDown( $value[self::B_PARAM_2][self::B_DO_FALSE], '?', $value[self::B_PARAM_2][self::B_PARAM_2] );
			}else{ // false ? ... : 1
				$value[self::B_RESULT] = $value[self::B_PARAM_2][self::B_PARAM_2];
			}
		}
	}

	/**
	 * self::T_IF
	 * @param array $value
	 */
	private static function doIf ( &$value ) {
		$return = null;
		if( $value[self::B_PARAM_1] ) { // Example: if( true )
			if( $value[self::B_DO_TRUE] ) { // Stack not empty: if(true);
				self::pushDown( $value[self::B_DO_TRUE], T_IF, $return );
			}
		}else{ // Example: if( false )
			if( isset($value[self::B_DO_FALSE]) ) { // Stack not empty: if(false) ; else ;
				self::pushDown( $value[self::B_DO_FALSE], T_IF, $return );
			}
		}
	}

	/**
	 * self::T_FOREACH
	 * @param array $value
	 */
	private static function doForeach ( &$value ) {
		$t_as =& $value[self::B_PARAM_1];
		$clone = $t_as[self::B_RESULT];
		if ( !is_array( $clone ) ) {
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_INVALID_ARGUMENT_FOR_FOREACH, null ) );
			return;
		}
		unset( $t_as[self::B_RESULT] );
		reset( $clone );
		$t_as[self::B_RESULT] = $clone;
		$null = null;
		self::pushDown( $value[self::B_DO_TRUE], T_WHILE, $null );
	}

	/**
	 * self::T_WHILE
	 * @param array $value
	 */
	private static function doWhile ( &$value ) {
		$null = null;
		self::pushDown( $value[self::B_DO_TRUE], T_WHILE, $null );
	}

	/**
	 * self::T_AS
	 * @param array $value
	 */
	private static function doAs ( &$value ) {
		// $value[PHPTAGS_STACK_RESULT] is always array, checked in self::doForeach()
		$tmp = each( $value[self::B_RESULT] ); // 'each' can return false and null
		if ( ! $tmp ) { // it is last element
			self::popUp();
		}

		$variables =& self::$stack[0][self::S_VARIABLES];
		$variables[ $value[self::B_PARAM_1] ] = $tmp[1]; // save value
		if ( $value[self::B_PARAM_2] !== false ) { // T_DOUBLE_ARROW Example: while ( $foo as $key=>$value )
			$variables[ $value[self::B_PARAM_2] ] = $tmp[0]; // save key
		}
	}

	/**
	 * self::T_BREAK
	 * @param array $value
	 */
	private static function doBreak ( &$value ) {
		$loopsOwner =& self::$stack[0][self::S_LOOPS_OWNER];
		$memory =& self::$stack[0][self::S_MEMORY];
		$originalBreakLevel = $breakLevel = $value[self::B_RESULT];

		for ( ; $breakLevel > 0; ) {
			if ( $loopsOwner === T_WHILE ) {
				--$breakLevel;
			}
			if ( false === isset( $memory[0] ) ) {
				if ( $breakLevel > 1 ) { // Allows exit from PhpTags
					throw new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, $originalBreakLevel, $value[self::B_TOKEN_LINE] );
				}
				--$breakLevel; // Return
			}
			self::popUp();
		}
	}

	/**
	 * self::T_CONTINUE
	 * @param array $value
	 */
	private static function doContinue ( &$value ) {
		if( --self::$loopsLimit <= 0 ) {
			throw new PhpTagsException( PhpTagsException::FATAL_LOOPS_LIMIT_REACHED, null );
		}
		$stack =& self::$stack[0];
		$loopsOwner =& $stack[self::S_LOOPS_OWNER];
		$memory =& $stack[self::S_MEMORY];
		$originalBreakLevel = $value[self::B_RESULT];
		$breakLevel = $originalBreakLevel - 1;

		for ( ; ; ) {
			if ( $loopsOwner === T_WHILE ) {
				if ( $breakLevel > 0 ) {
					--$breakLevel;
				} else {
					break;
				}
			}
			if ( false === isset( $memory[0] ) ) {
				throw new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, $originalBreakLevel, $value[self::B_TOKEN_LINE] );
			}
			self::popUp();
		}
		$stack[self::S_RUN_INDEX] = -1;
	}

	/**
	 * self::T_ARRAY inits new array
	 * @param array $value
	 */
	private static function doArray ( &$value ) {
		$newArray = $value[self::B_PARAM_1][0];
		$i = 1;
		foreach ( $value[self::B_PARAM_2] as $t ) {
			list ( $k, $v ) = $t;

			if ( is_scalar( $k ) ) {
				$newArray[$k] = $v;
			} else {
				self::pushException( new PhpTagsException( PhpTagsException::WARNING_ILLEGAL_OFFSET_TYPE ) );
			}

			if ( isset($value[self::B_PARAM_1][$i]) ) {
				foreach ( $value[self::B_PARAM_1][$i] as $n ) {
					$newArray[] = $n;
				}
			}
			++$i;
		}
		$value[self::B_RESULT] = $newArray;
	}

	/**
	 * self::T_STATIC inits static variables
	 * @param array $value
	 */
	private static function doStatic ( &$value ) {
		$place = self::$stack[0][self::S_PLACE];
		$name = $value[self::B_PARAM_1]; // variable name
		if ( false === (isset( self::$staticVariables[$place] ) && (isset( self::$staticVariables[$place][$name] ) || array_key_exists( $name, self::$staticVariables[$place] ))) ) {
			// It is not initialised variable, initialise it
			self::$staticVariables[$place][$name] = $value[self::B_RESULT];
		}
		self::$stack[0][self::S_VARIABLES][$name] =& self::$staticVariables[$place][$name];
	}

	/**
	 * self::T_GLOBAL inits global variables
	 * @param array $value
	 */
	private static function doGlobal ( &$value ) {
		$stack =& self::$stack[0];
		$gVars =& self::$globalVariables;
		foreach( $value[self::B_PARAM_1] as $name ) { // variable names
			if( !array_key_exists($name, $gVars) ) {
				$gVars[$name] = null;
			}
			$stack[self::S_VARIABLES][$name] =& $gVars[$name];
		}
	}

	/**
	 * self::T_HOOK_CHECK_PARAM checks param for hooks
	 * @param array $value
	 */
	private static function doCheckingParam ( &$value ) {
		$i = $value[self::B_AIM]; // ordinal number of the argument, zero based
		$reference_info = Hooks::getReferenceInfo( $i, $value );

		if ( $value[self::B_PARAM_2] === true && $reference_info === false ) {
			// Param is variable and it needs to clone
			$clone = $value[self::B_RESULT][$i];
			unset( $value[self::B_RESULT][$i] );
			$value[self::B_RESULT][$i] = $clone;
		} elseif ( $value[self::B_PARAM_2] === false && $reference_info === true ) {
			// Param is not variable and it's need reference
			throw new PhpTagsException( PhpTagsException::FATAL_VALUE_PASSED_BY_REFERENCE, null );
		}
	}

	/**
	 * self::T_HOOK calls hook
	 * @param array $value
	 */
	private static function doCallingHook ( &$value ) {
		$result = Hooks::callHook( $value );

		if ( $result instanceof iRawOutput ) {
			$value[self::B_RESULT] = $result->getReturnValue();
			self::$stack[0][self::S_RETURN][] = $result->placeAsStripItem();
		} else {
			$value[self::B_RESULT] = $result;
		}
		if ( is_object($value[self::B_RESULT]) && !($value[self::B_RESULT] instanceof iRawOutput || $value[self::B_RESULT] instanceof GenericObject) ) {
			// @todo
			$value[self::B_RESULT] = null;
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_RETURNED_INVALID_VALUE, $value[self::B_METHOD] ) );
		}
	}

	/**
	 * self::T_NEW creates new object
	 * @param array $value
	 */
	private static function doNewObject ( &$value ) {
		$result = Hooks::createObject( $value[self::B_PARAM_2], $value[self::B_OBJECT], $value[self::B_OBJECT_KEY] );
		$value[self::B_RESULT] = $result;
	}

	/**
	 * self::T_UNSET unsets variables
	 * @param array $value
	 */
	private static function doUnset ( &$value ) {
		$variables =& self::$stack[0][self::S_VARIABLES];
		foreach ( $value[self::B_PARAM_1] as $val ) {
			$name = $val[self::B_PARAM_1]; // Variable Name
			if ( isset($variables[$name]) || array_key_exists($name, $variables) ) { // defined variable
				if ( isset($val[self::B_ARRAY_INDEX]) ) { // There is array index. Example: unset($foo[0])
					$ref =& $variables[$name];
					$tmp = array_pop( $val[self::B_ARRAY_INDEX] );
					foreach ( $val[self::B_ARRAY_INDEX] as $v ) {
						if ( is_string($ref) ) {
							throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null );
						} elseif ( !isset($ref[$v]) ) { // undefined array index not for string
							continue 2;
						}
						$ref =& $ref[$v];
					}
					if ( is_array($ref) ) {
						unset( $ref[$tmp] );
					} else {
						throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null );
					}
				}else{ // There is no array index. Example: unset($foo)
					unset( $variables[$name] );
				}
			} elseif ( isset($val[self::B_ARRAY_INDEX]) ) { // undefined variable with array index. Example: unset($foo[1])
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $name ) );
			}
		}
	}

	/**
	 * self::T_ISSET returns TRUE when variables are set
	 * @param array $value
	 */
	private static function doIsSet ( &$value ) {
		$variables =& self::$stack[0][self::S_VARIABLES];
		foreach ( $value[self::B_PARAM_1] as $val ) {
			if ( !isset( $variables[ $val[self::B_PARAM_1] ] ) ) { // undefined variable or variable is null
				$value[self::B_RESULT] = false;
				return;
			} // true, variable is defined
			if( isset( $val[self::B_ARRAY_INDEX] ) ) { // Example: isset($foo[1])
				$ref =& $variables[ $val[self::B_PARAM_1] ];
				$tmp = array_pop( $val[self::B_ARRAY_INDEX] );
				foreach( $val[self::B_ARRAY_INDEX] as $v ) {
					if( !isset( $ref[$v] ) ) { // undefined array index
						$value[self::B_RESULT] = false;
						return;
					}
					$ref =& $ref[$v];
				}
				// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
				if( !isset($ref[$tmp]) || (is_string($ref) && is_string($tmp ) && $tmp  != (string)(int)$tmp ) ) {
					$value[self::B_RESULT] = false;
					return;
				}
			} // true, variable is defined and have no array index
		}
		$value[self::B_RESULT] = true;
	}

	/**
	 * self::T_EMPTY returns TRUE when variables are empty
	 * @param array $value
	 */
	private static function doIsEmpty ( &$value ) {
		$variables =& self::$stack[0][self::S_VARIABLES];
		foreach($value[self::B_PARAM_1] as $val) {
			if( !array_key_exists($val[self::B_PARAM_1], $variables) ) { // undefined variable
				continue;
			}
			$ref =& $variables[ $val[self::B_PARAM_1] ];
			if( isset($val[self::B_ARRAY_INDEX]) ) { // Example: empty($foo[1])
				$tmp = array_pop( $val[self::B_ARRAY_INDEX] );
				foreach( $val[self::B_ARRAY_INDEX] as $v ) {
					if( !isset($ref[$v]) ) { // undefined array index
						continue 2;
					}
					$ref = &$ref[$v];
				}
				// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.empty.php Example #2 empty() on String Offsets
				if( !empty($ref[$tmp]) && (is_array($ref) || !is_string( $tmp ) || $tmp  == (string)(int)$tmp ) ) {
					$value[self::B_RESULT] = false;
					return;
				}
			}elseif( !empty($ref) ) { // there is no array index and empty() returns false (PHP 5.5.0 supports expressions)
				$value[self::B_RESULT] = false;
				return;
			}
		}
		$value[self::B_RESULT] = true;
	}

	/**
	 * self::T_RETURN is designed for compiler only!
	 * @param array $value
	 */
	private static function doReturn ( &$value ) {
		self::$stack[0][self::S_RETURN] = self::$stack[0][self::S_RETURN] ? new PhpTagsException() : $value[self::B_PARAM_1];
	}

	/**
	 * self::T_COPY copies value from variable to destination
	 * @param array $value
	 */
	private static function doCopy ( &$value ) {
		$value[self::B_RESULT] = $value[self::B_PARAM_1];
	}

	/**
	 * self::T_IGNORE_ERROR
	 * @param array $value
	 */
	private static function doIgnoreErrors ( &$value ) {
		self::$ignoreErrors = $value[self::B_PARAM_1];
	}

	/**
	 * self::T_LIST
	 * @param array $value
	 */
	private static function doList ( &$value ) {
		self::fillList( $value[self::B_PARAM_2], $value[self::B_PARAM_1] );
		$value[self::B_RESULT] = $value[self::B_PARAM_2];
	}

	/**
	 * self::T_INC
	 * @param array $value
	 */
	private static function doIncrease ( &$value ) {
		$ref =& self::getVariableRef( $value );
		if ( $value[self::B_PARAM_2] ) { // $foo++
			$value[self::B_RESULT] = $ref++;
		}else{ // ++$foo
			$value[self::B_RESULT] = ++$ref;
		}
	}

	/**
	 * self::T_DEC
	 * @param array $value
	 */
	private static function doDecrease ( &$value ) {
		$ref =& self::getVariableRef( $value );
		if ( $value[self::B_PARAM_2] ) { // $foo--
			$value[self::B_RESULT] = $ref--;
		}else{ // --$foo
			$value[self::B_RESULT] = --$ref;
		}
	}

	/**
	 * self::T_EQUAL
	 * @param array $value
	 */
	private static function doSetVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[self::B_RESULT] = $ref = $value[self::B_PARAM_2];
	}

	/**
	 * self::T_CONCAT_EQUAL
	 * @param array $value
	 */
	private static function doSetConcatVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkStringParams( $ref, $value[self::B_PARAM_2], $value[self::B_FLAGS] );
		$value[self::B_RESULT] = $ref = $v[0] . $v[1];
	}

	/**
	 * self::T_PLUS_EQUAL
	 * @param array $value
	 */
	private static function doSetPlusVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkArrayParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] + $v[1];
	}

	/**
	 * self::T_MINUS_EQUAL
	 * @param array $value
	 */
	private static function doSetMinusVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkScalarParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] - $v[1];
	}

	/**
	 * self::T_MUL_EQUAL
	 * @param array $value
	 */
	private static function doSetMulVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkScalarParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] * $v[1];
	}

	/**
	 * self::T_DIV_EQUAL
	 * @param array $value
	 */
	private static function doSetDivVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkScalarParams( $ref, $value[self::B_PARAM_2] );
		if ( $v[1] == 0 ) {
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO ) );
			$value[self::B_RESULT] = $ref = false;
		} else {
			$value[self::B_RESULT] = $ref = $v[0] / $v[1];
		}
	}

	/**
	 * self::T_MOD_EQUAL
	 * @param array $value
	 */
	private static function doSetModVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkScalarParams( $ref, $value[self::B_PARAM_2] );
		if ( $v[1] == 0 ) {
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO ) );
			$value[self::B_RESULT] = $ref = false;
		} else {
			$value[self::B_RESULT] = $ref = $v[0] % $v[1];
		}
	}

	/**
	 * self::T_AND_EQUAL
	 * @param array $value
	 */
	private static function doSetAndVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkObjectParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] & $v[1];
	}

	/**
	 * self::T_OR_EQUAL
	 * @param array $value
	 */
	private static function doSetOrVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkObjectParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] | $v[1];
	}

	/**
	 * self::T_XOR_EQUAL
	 * @param array $value
	 */
	private static function doSetXorVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkObjectParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] ^ $v[1];
	}

	/**
	 * self::T_SL_EQUAL
	 * @param array $value
	 */
	private static function doSetShiftLeftVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkObjectParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] << $v[1];
	}

	/**
	 * self::T_SR_EQUAL
	 * @param array $value
	 */
	private static function doSetShiftRightVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$v = self::checkObjectParams( $ref, $value[self::B_PARAM_2] );
		$value[self::B_RESULT] = $ref = $v[0] >> $v[1];
	}

	private static function checkStringParams( &$v1, &$v2, &$flags ) {
		$return = array( $v1, $v2 );

		if ( $v1 !== null && !is_scalar( $v1 ) ) {
			if ( is_array( $v1 ) ) {
				if ( !($flags & self::F_DONT_CHECK_PARAM1) ) {
					self::pushException( new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING ) );
					$return[0] = self::R_ARRAY;
				}
			} else if ( $v1 instanceof GenericObject ) {
				$return[0] = $v1->toString();
			} else {
				throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
			}
		}
		if ( $v2 !== null && !is_scalar( $v2 ) ) {
			if ( is_array( $v2 ) ) {
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING ) );
				$return[1] = self::R_ARRAY;
			} else if ( $v2 instanceof GenericObject ) {
				$return[1] = $v2->toString();
			} else {
				throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
			}
		}

		return $return;
	}

	private static function checkArrayParams( &$v1, &$v2 ) {
		if ( !($v1 === null || is_scalar( $v1 )) || !($v2 === null || is_scalar( $v2 )) ) {
			$return = self::checkObjectParams( $v1, $v2 );
			if ( is_array( $v1 ) xor is_array( $v2 ) ) { // [1] + 1 or 1 + [1]
				throw new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES );
			}
		} else {
			$return = array( $v1, $v2 );
		}

		return $return;
	}

	private static function checkScalarParams( &$v1, &$v2 ) {
		if ( !($v1 === null || is_scalar( $v1 )) || !($v2 === null || is_scalar( $v2 )) ) {
			$return = self::checkObjectParams( $v1, $v2 );
			if ( is_array( $v1 ) || is_array( $v2 ) ) { // [1] + 1 or 1 + [1]
				throw new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES );
			}
		} else {
			$return = array( $v1, $v2 );
		}

		return $return;
	}

	private static function checkObjectParams( &$v1, &$v2, $to = 'int' ) {
		$return = array( $v1, $v2 );

		if ( is_object( $v1 ) ) {
			if ( $v1 instanceof GenericObject ) {
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($v1->getName(), $to) ) );
				$return[0] = 1;
			} else {
				throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
			}
		}
		if ( is_object( $v2 ) ) {
			if ( $v2 instanceof GenericObject ) {
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, array($v2->getName(), $to) ) );
				$return[1] = 1;
			} else {
				throw new PhpTagsException( PhpTagsException::FATAL_INTERNAL_ERROR, __LINE__ );
			}
		}

		return $return;
	}

	private static function & getVariableRef( $value ) {
		$variables =& self::$stack[0][self::S_VARIABLES];
		$var = $value[self::B_PARAM_1];
		$variableName = $var[self::B_PARAM_1];
		if( !(isset($variables[$variableName]) || array_key_exists($variableName, $variables)) ) { // Use undefined variable
			$variables[$variableName] = null;
			if( $value[self::B_COMMAND] !== self::T_EQUAL ) {
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $variableName ) );
			}
		}
		$ref =& $variables[$variableName];
		if ( isset($var[self::B_ARRAY_INDEX]) ) { // Example: $foo[1]++
			foreach ( $var[self::B_ARRAY_INDEX] as $v ) {
				if ( $ref === true || $ref === false ) {
					if ( $v === INF ) { // Example: $foo[]
						$ref = array();
					}
				} else if ( is_scalar( $ref ) ) {
					self::pushException( new PhpTagsException( PhpTagsException::WARNING_SCALAR_VALUE_AS_ARRAY, null ) );
					unset( $ref );
					$ref = null;
					break;
				} else if ( is_object( $ref ) ) {
					throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_USE_OBJECT_AS_ARRAY, $ref );
				}
				if ( $v === INF ) { // Example: $foo[]
					$t = null;
					$ref[] = &$t;
					$ref = &$t;
					unset( $t );
				} else {
					if ( $ref === null ) {
						if( $value[self::B_COMMAND] !== self::T_EQUAL ) {
							// PHP Notice:  Undefined offset: $1
							self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $v ) );
						}
						$ref[$v] = null;
						$ref =& $ref[$v];
					} else {
						if ( !( isset($ref[$v]) || array_key_exists($v, $ref) ) ) {
							$ref[$v] = null;
							if( $value[self::B_COMMAND] !== self::T_EQUAL ) {
								// PHP Notice:  Undefined offset: $1
								self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $v ) );
							}
						}
						$ref =& $ref[$v];
					}
				}
			}
		}
		return $ref;
	}

	public static function run( $code, array $args, $scope = '' ) {
		try {
			if( false === isset( self::$variables[$scope] ) ) {
				self::$variables[$scope] = array();
			}
			$stack = array(
				self::S_RETURN => array(),
				self::S_RUNNING => $code,
				self::S_RUN_INDEX => -1,
				self::S_COUNT => count( $code ),
				self::S_LOOPS_OWNER => null,
				self::S_MEMORY => array(),
				self::S_PLACE => isset( $args[0] ) ? $args[0] : '', // Page name for static variables and error messages
				self::S_VARIABLES => & self::$variables[$scope],
			);
			$stack[self::S_VARIABLES]['argv'] = $args;
			$stack[self::S_VARIABLES]['argc'] = count( $args );
			$stack[self::S_VARIABLES]['GLOBALS'] =& self::$globalVariables;

			$runCode =& $stack[self::S_RUNNING];
			$runIndex =& $stack[self::S_RUN_INDEX];
			$loopsOwner =& $stack[self::S_LOOPS_OWNER];
			$memory =& $stack[self::S_MEMORY];
			$c =& $stack[self::S_COUNT];
			$operators = self::$operators;

			array_unshift( self::$stack, null );
			self::$stack[0] =& $stack;
doit:
			do {
				for ( ++$runIndex; $runIndex < $c; ++$runIndex ) {
					$value =& $runCode[$runIndex];
					$call = $operators[ $value[self::B_COMMAND] ];
					self::$call( $value );
				}
			} while( list($runCode[$runIndex][self::B_RESULT], $runCode, $runIndex, $c, $loopsOwner) = array_pop($memory) );
		} catch ( PhpTagsException $e ) {
			self::pushException( $e );
			if ( $e->isFatal() !== true && ($call === $operators[self::T_HOOK] || $call === $operators[self::T_NEW]) ) {
				$runCode[$runIndex][self::B_RESULT] = Hooks::getCallInfo( Hooks::INFO_RETURNS_ON_FAILURE );
				goto doit;
			}
			$runCode[$runIndex][self::B_RESULT] = null;
			self::$ignoreErrors = false;
		} catch ( \Exception $e ) {
			Renderer::addRuntimeErrorCategory();
			self::$ignoreErrors = false;
			array_shift( self::$stack );
			throw $e;
		}
		array_shift( self::$stack );
		return $stack[self::S_RETURN];
	}

	private static function fillList( &$values, &$parametrs, $offset = false ) {
		$return = array();

		for ( $pkey = count( $parametrs ) - 1; $pkey >= 0; --$pkey ) {
			$param = $parametrs[$pkey];
			if ( $param === null ) { // skip emty params. Example: list(, $bar) = $array;
				continue;
			}
			if( $param[self::B_COMMAND] == self::T_LIST ) { // T_LIST inside other T_LIST. Example: list($a, list($b, $c)) = array(1, array(2, 3));
				if ( is_array($values) && isset($values[$pkey]) ) {
					$return[$pkey] = self::fillList( $values[$pkey], $param[self::B_PARAM_1] );
				} else { // list() works with array only @todo support strings
					static $emptyArray=array();
					$return[$pkey] = self::fillList( $emptyArray, $param[self::B_PARAM_1], $pkey );
				}
				continue;
			}
			// $param is variable
			$ref =& self::$stack[0][self::S_VARIABLES][ $param[self::B_PARAM_1] ];
			if ( isset($param[self::B_ARRAY_INDEX]) ) { // Example: list($foo[0], $foo[1]) = $array;
				foreach ( $param[self::B_ARRAY_INDEX] as $v ) {
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
				if ( isset($values[$pkey]) || array_key_exists($pkey, $values) ) {
					$ref = $values[$pkey];
				} else {
					$ref = null;
					self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $offset === false ? $pkey : $offset ) );
				}
			} else { // list() works with array only @todo support strings
				$ref = null;
			}
			$return[$pkey] = $ref;
		}
	}

	public static function pushException( PhpTagsException $exc ) {
		if ( self::$ignoreErrors === false ) {
			$stack =& self::$stack[0];
			if ( $exc->tokenLine === null ) {
				$exc->tokenLine = $stack[self::S_RUNNING][ $stack[self::S_RUN_INDEX] ][self::B_TOKEN_LINE];
			}
			$exc->place = $stack[self::S_PLACE];
			$stack[self::S_RETURN][] = (string) $exc;
			Renderer::addRuntimeErrorCategory();
		}
	}

	public static function getCurrentOperator() {
		$stack =& self::$stack[0];
		return $stack[self::S_RUNNING][ $stack[self::S_RUN_INDEX] ];
	}

	public static function getVariables() {
		return self::$stack[0][self::S_VARIABLES];
	}

	##### List of PhpTags runtime Tokens #####
	/**
	 * operator: "
	 */
	const T_QUOTE = 0;

	/**
	 * operator: .
	 */
	const T_CONCAT = 1;

	/**
	 * operator: +
	 */
	const T_PLUS = 2;

	/**
	 * operator: -
	 */
	const T_MINUS = 3;

	/**
	 * operator: *
	 */
	const T_MUL = 4;

	/**
	 * operator: /
	 */
	const T_DIV = 5;

	/**
	 * operator: ==
	 */
	const T_IS_EQUAL = 6;

	/**
	 * get value from variable
	 */
	const T_VARIABLE = 7;

	/**
	 * operator: if
	 */
	const T_IF = 8;

	/**
	 * operator: =
	 */
	const T_EQUAL = 9;

	/**
	 * operator: .=
	 */
	const T_CONCAT_EQUAL = 10;

	/**
	 * operator: /=
	 */
	const T_DIV_EQUAL = 11;

	/**
	 * operator: *=
	 */
	const T_MUL_EQUAL = 12;

	/**
	 * operator: -=
	 */
	const T_MINUS_EQUAL = 13;

	/**
	 * operator: +=
	 */
	const T_PLUS_EQUAL = 14;

	/**
	 * operator: !==
	 */
	const T_IS_NOT_IDENTICAL = 15;

	/**
	 * operator: ===
	 */
	const T_IS_IDENTICAL = 16;

	/**
	 * operator: !=
	 */
	const T_IS_NOT_EQUAL = 17;

	/**
	 * operator: ^=
	 */
	const T_XOR_EQUAL = 18;

	/**
	 * operator: >=
	 */
	const T_IS_GREATER_OR_EQUAL = 19;

	/**
	 * operator: <=
	 */
	const T_IS_SMALLER_OR_EQUAL = 20;

	/**
	 * operator: >>
	 */
	const T_SR = 21;

	/**
	 * operator: <<
	 */
	const T_SL = 22;

	/**
	 * operator: (unset)
	 */
	const T_UNSET_CAST = 23;

	/**
	 * operator: (bool)
	 */
	const T_BOOL_CAST = 24;

	/**
	 * operator: (array)
	 */
	const T_ARRAY_CAST = 25;

	/**
	 * operator: (string)
	 */
	const T_STRING_CAST = 26;

	/**
	 * operator: (double)
	 */
	const T_DOUBLE_CAST = 27;

	/**
	 * operator: (int)
	 */
	const T_INT_CAST = 28;

	/**
	 * operator: --
	 */
	const T_DEC = 29;

	/**
	 * operator: ++
	 */
	const T_INC = 30;

	/**
	 * operator: new
	 */
	const T_NEW = 31;

	/**
	 * operator: &=
	 */
	const T_AND_EQUAL = 32;

	/**
	 * call phptags hook
	 * it can be constant, function, object's constant, property, method
	 */
	const T_HOOK = 33;

	/**
	 * check passed param before call self::T_HOOK
	 */
	const T_HOOK_CHECK_PARAM = 34;

	/**
	 * it copies value from variable to destination
	 */
	const T_COPY = 35;

	/**
	 * It saved current runtime state and starts new loop
	 */
	const T_WHILE = 36;

	/**
	 * It saved current runtime state and starts new foreach loop
	 * next operator is self::T_AS
	 */
	const T_FOREACH = 37;

	/**
	 * It gets each value in started foreach loop, exit from loop if end of array
	 */
	const T_AS = 38;

	/**
	 * It ends execution of the current loop structure.
	 */
	const T_BREAK = 39;

	/**
	 * It starts current loop structure from the beginning
	 * If value > 1, end execution (T_BREAK) of value - 1 loops
	 * It checks loops count limit (self::$loopsLimit)
	 * Every cycle should end with this operator
	 */
	const T_CONTINUE = 40;

	/**
	 * It is designet for \PhpTags\Compiler only!
	 * It returns value of operation and used for calculation values in compiler
	 * if it possible.
	 * For example: compile $a = 5 + 4. Compiler calls Runtime and gets result 9 for
	 * expression 5 + 4, and writes code $a = 9.
	 */
	const T_RETURN = 41;

	/**
	 * It links global variable to local scope
	 */
	const T_GLOBAL = 42;

	/**
	 * It inits static variables from local scope
	 */
	const T_STATIC = 43;

	/**
	 * destroys the specified variables
	 */
	const T_UNSET = 44;

	/**
	 * Determine if a variable is set and is not NULL
	 */
	const T_ISSET = 45;

	/**
	 * Determine whether a variable is empty
	 */
	const T_EMPTY = 46;

	/**
	 * Assign variables as if they were an array
	 */
	const T_LIST = 47;

	/**
	 * Inits new array
	 */
	const T_ARRAY = 48;

	/**
	 * operator: |=
	 */
	const T_OR_EQUAL = 49;

	/**
	 * Output string
	 * Operators: echo, print
	 */
	const T_PRINT = 50;

	/**
	 * operator: ||
	 */
	const T_LOGICAL_OR = 51;

	/**
	 * operator: @
	 */
	const T_IGNORE_ERROR = 52;

	/**
	 * ternary operator: ?
	 */
	const T_TERNARY = 53;

	/**
	 * operator: |
	 */
	const T_OR = 54;

	/**
	 * operator: ^
	 */
	const T_XOR = 55;

	/**
	 * operator: &
	 */
	const T_AND = 56;

	/**
	 * operator: >
	 */
	const T_IS_GREATER = 57;

	/**
	 * operator: <
	 */
	const T_IS_SMALLER = 58;

	/**
	 * operator: xor
	 */
	const T_LOGICAL_XOR = 59;

	/**
	 * operator: %=
	 */
	const T_MOD_EQUAL = 60;

	/**
	 * operator: and
	 */
	const T_LOGICAL_AND = 61;

	/**
	 * operator: %
	 */
	const T_MOD = 62;

	/**
	 * operator: <<=
	 */
	const T_SL_EQUAL = 63;

	/**
	 * operator: >>=
	 */
	const T_SR_EQUAL = 64;

	/**
	 * operator: !
	 */
	const T_IS_NOT = 65;

	/**
	 * operator: ~
	 */
	const T_NOT = 66;

}
