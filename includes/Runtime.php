<?php
namespace PhpTags;

//const PHPTAGS_STACK_RESULT = 'r';
//const PHPTAGS_STACK_COMMAND = 'c';
//const PHPTAGS_STACK_PARAM = 'p';
//const PHPTAGS_STACK_PARAM_2 = 's';
//const PHPTAGS_STACK_PARAM_3 = 'o';
//const PHPTAGS_STACK_INC_AFTER = 'i';
//const PHPTAGS_STACK_TOKEN_LINE = 'l';
//const PHPTAGS_STACK_DO_TRUE = 't';
//const PHPTAGS_STACK_DO_FALSE = 'f';
//const PHPTAGS_STACK_ARRAY_INDEX = 'a';
//const PHPTAGS_STACK_DEBUG = '#';
//const PHPTAGS_STACK_AIM = '*';
//const PHPTAGS_STACK_HOOK_TYPE = 'h';

const PHPTAGS_STACK_COMMAND = 0;
const PHPTAGS_STACK_RESULT = 1;
const PHPTAGS_STACK_PARAM = 2;
const PHPTAGS_STACK_PARAM_2 = 3;
const PHPTAGS_STACK_PARAM_3 = 4;
const PHPTAGS_STACK_INC_AFTER = 5;
const PHPTAGS_STACK_TOKEN_LINE = 6;
const PHPTAGS_STACK_DO_TRUE = 7;
const PHPTAGS_STACK_DO_FALSE = 8;
const PHPTAGS_STACK_ARRAY_INDEX = 9;
const PHPTAGS_STACK_DEBUG = 10;
const PHPTAGS_STACK_AIM = 11;
const PHPTAGS_STACK_HOOK_TYPE = 12;

const PHPTAGS_HOOK_GET_CONSTANT = '_';
const PHPTAGS_HOOK_FUNCTION = 'f';
const PHPTAGS_HOOK_GET_OBJECT_CONSTANT = 'c';
const PHPTAGS_HOOK_GET_STATIC_PROPERTY = 'q';
const PHPTAGS_HOOK_GET_OBJECT_PROPERTY = 'p';
const PHPTAGS_HOOK_SET_STATIC_PROPERTY = 'd';
const PHPTAGS_HOOK_SET_OBJECT_PROPERTY = 'b';
const PHPTAGS_HOOK_STATIC_METHOD = 's';
const PHPTAGS_HOOK_OBJECT_METHOD = 'm';
const PHPTAGS_HOOK_NEW_OBJECT = 'n';

const PHPTAGS_OBJECT_DEFINITION = 0;
const PHPTAGS_METHOD_CONSTRUCTOR = 1;

/**
 * operator: "
 */
const PHPTAGS_T_QUOTE = 0; // "
/**
 * operator: .
 */
const PHPTAGS_T_CONCAT = 1;
/**
 * operator: +
 */
const PHPTAGS_T_PLUS = 2;
/**
 * operator: -
 */
const PHPTAGS_T_MINUS = 3;
/**
 * operator: *
 */
const PHPTAGS_T_MUL = 4;
/**
 * operator: /
 */
const PHPTAGS_T_DIV = 5;
/**
 * operator: ==
 */
const PHPTAGS_T_IS_EQUAL = 6;
/**
 * get value from variable
 */
const PHPTAGS_T_VARIABLE = 7;
/**
 * operator: if
 */
const PHPTAGS_T_IF = 8;
/**
 * operator: =
 */
const PHPTAGS_T_EQUAL = 9;
/**
 * operator: .=
 */
const PHPTAGS_T_CONCAT_EQUAL = 10;
/**
 * operator: /=
 */
const PHPTAGS_T_DIV_EQUAL = 11;
/**
 * operator: *=
 */
const PHPTAGS_T_MUL_EQUAL = 12;
/**
 * operator: -=
 */
const PHPTAGS_T_MINUS_EQUAL = 13;
/**
 * operator: +=
 */
const PHPTAGS_T_PLUS_EQUAL = 14;
/**
 * operator: !==
 */
const PHPTAGS_T_IS_NOT_IDENTICAL = 15;
/**
 * operator: ===
 */
const PHPTAGS_T_IS_IDENTICAL = 16;
/**
 * operator: !=
 */
const PHPTAGS_T_IS_NOT_EQUAL = 17;
/**
 * operator: ^=
 */
const PHPTAGS_T_XOR_EQUAL = 18;
/**
 * operator: >=
 */
const PHPTAGS_T_IS_GREATER_OR_EQUAL = 19;
/**
 * operator: <=
 */
const PHPTAGS_T_IS_SMALLER_OR_EQUAL = 20;
/**
 * operator: >>
 */
const PHPTAGS_T_SR = 21;
/**
 * operator: <<
 */
const PHPTAGS_T_SL = 22;
/**
 * operator: (unset)
 */
const PHPTAGS_T_UNSET_CAST = 23;
/**
 * operator: (bool)
 */
const PHPTAGS_T_BOOL_CAST = 24;
/**
 * operator: (array)
 */
const PHPTAGS_T_ARRAY_CAST = 25;
/**
 * operator: (string)
 */
const PHPTAGS_T_STRING_CAST = 26;
/**
 * operator: (double)
 */
const PHPTAGS_T_DOUBLE_CAST = 27;
/**
 * operator: (int)
 */
const PHPTAGS_T_INT_CAST = 28;
/**
 * operator: --
 */
const PHPTAGS_T_DEC = 29;
/**
 * operator: ++
 */
const PHPTAGS_T_INC = 30;
/**
 * operator: new
 */
const PHPTAGS_T_NEW = 31;
/**
 * operator: &=
 */
const PHPTAGS_T_AND_EQUAL = 32;
/**
 * call phptags hook
 */
const PHPTAGS_T_HOOK = 33;
/**
 * check passed param to phptags hook
 */
const PHPTAGS_T_HOOK_CHECK_PARAM = 34;
/**
 * it copies value from variable to destination
 */
const PHPTAGS_T_COPY = 35;
const PHPTAGS_T_WHILE = 36;
const PHPTAGS_T_FOREACH = 37;
const PHPTAGS_T_AS = 38;
const PHPTAGS_T_BREAK = 39;
const PHPTAGS_T_CONTINUE = 40;
const PHPTAGS_T_RETURN = 41;
const PHPTAGS_T_GLOBAL = 42;
const PHPTAGS_T_STATIC = 43;
const PHPTAGS_T_UNSET = 44;
const PHPTAGS_T_ISSET = 45;
const PHPTAGS_T_EMPTY = 46;
const PHPTAGS_T_LIST = 47;
const PHPTAGS_T_ARRAY = 48;
const PHPTAGS_T_OR_EQUAL = 49;
/**
 * operators: echo, print
 */
const PHPTAGS_T_PRINT = 50;
/**
 * operator: ||
 */
const PHPTAGS_T_LOGICAL_OR = 51;
/**
 * operator: @
 */
const PHPTAGS_T_IGNORE_ERROR = 52; // @
/**
 * ternary operator: ?
 */
const PHPTAGS_T_TERNARY = 53; // ?
/**
 * operator: |
 */
const PHPTAGS_T_OR = 54; // |
/**
 * operator: ^
 */
const PHPTAGS_T_XOR = 55;
/**
 * operator: &
 */
const PHPTAGS_T_AND = 56;
/**
 * operator: >
 */
const PHPTAGS_T_IS_GREATER = 57;
/**
 * operator: <
 */
const PHPTAGS_T_IS_SMALLER = 58;
/**
 * operator: xor
 */
const PHPTAGS_T_LOGICAL_XOR = 59;
const PHPTAGS_T_MOD_EQUAL = 60;
/**
 * operator: and
 */
const PHPTAGS_T_LOGICAL_AND = 61;
/**
 * operator: %
 */
const PHPTAGS_T_MOD = 62;
const PHPTAGS_T_SL_EQUAL = 63;
const PHPTAGS_T_SR_EQUAL = 64;
/**
 * operator: !
 */
const PHPTAGS_T_IS_NOT = 65;
/**
 * operator: ~
 */
const PHPTAGS_T_NOT = 66;

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

	/**
	 * PPFrame
	 * @var \PPFrame
	 */
	static $frame;

	/**
	 * Parser
	 * @var \Parser
	 */
	static $parser;

	private static $variables = array();
	private static $staticVariables = array();
	private static $globalVariables = array();
	private static $ignoreErrors = false;
	private static $scope;
	private static $parserDisabled = false;
	private static $errorCategoryAdded = false;

	private static $operators = array(
		PHPTAGS_T_QUOTE => 'doQuote',
		PHPTAGS_T_CONCAT => 'doConcat',
		PHPTAGS_T_PLUS => 'doPlus',
		PHPTAGS_T_MINUS => 'doMinus',
		PHPTAGS_T_MUL => 'doMul',
		PHPTAGS_T_DIV => 'doDiv',
		PHPTAGS_T_MOD => 'doMod',
		PHPTAGS_T_AND => 'doAnd',
		PHPTAGS_T_OR => 'doOr',
		PHPTAGS_T_XOR => 'doXor',
		PHPTAGS_T_SL => 'doShiftLeft',
		PHPTAGS_T_SR => 'doShiftRight',
		PHPTAGS_T_LOGICAL_AND => 'doLogicalAnd',
		PHPTAGS_T_LOGICAL_XOR => 'doLogicalXor',
		PHPTAGS_T_LOGICAL_OR => 'doLogicalOr',
		PHPTAGS_T_IS_SMALLER => 'doIsSmaller',
		PHPTAGS_T_IS_GREATER => 'doIsGreater',
		PHPTAGS_T_IS_SMALLER_OR_EQUAL => 'doIsSmallerOrEqual',
		PHPTAGS_T_IS_GREATER_OR_EQUAL => 'doIsGreaterOrEqual',
		PHPTAGS_T_IS_EQUAL => 'doIsEqual',
		PHPTAGS_T_IS_NOT_EQUAL => 'doIsNotEqual',
		PHPTAGS_T_IS_IDENTICAL => 'doIsIdentical',
		PHPTAGS_T_IS_NOT_IDENTICAL => 'doIsNotIdentical',
		PHPTAGS_T_PRINT => 'doPrint',
		PHPTAGS_T_NOT => 'doNot',
		PHPTAGS_T_IS_NOT => 'doIsNot',
		PHPTAGS_T_INT_CAST => 'doIntCast',
		PHPTAGS_T_DOUBLE_CAST => 'doDoubleCast',
		PHPTAGS_T_STRING_CAST => 'doStringCast',
		PHPTAGS_T_ARRAY_CAST => 'doArrayCast',
		PHPTAGS_T_BOOL_CAST => 'doBoolCast',
		PHPTAGS_T_UNSET_CAST => 'doUnsetCast',
		PHPTAGS_T_VARIABLE => 'doVariable',
		PHPTAGS_T_TERNARY => 'doTernary',
		PHPTAGS_T_IF => 'doIf',
		PHPTAGS_T_FOREACH => 'doForeach',
		PHPTAGS_T_WHILE => 'doWhile',
		PHPTAGS_T_AS => 'doAs',
		PHPTAGS_T_BREAK => 'doBreak',
		PHPTAGS_T_CONTINUE => 'doContinue',
		PHPTAGS_T_ARRAY => 'doArray',
		PHPTAGS_T_STATIC => 'doStatic',
		PHPTAGS_T_GLOBAL => 'doGlobal',
		PHPTAGS_T_HOOK_CHECK_PARAM => 'doCheckingParam',
		PHPTAGS_T_HOOK => 'doCallingHook',
		PHPTAGS_T_NEW => 'doNewObject',
		PHPTAGS_T_UNSET => 'doUnset',
		PHPTAGS_T_ISSET => 'doIsSet',
		PHPTAGS_T_EMPTY => 'doIsEmpty',
		PHPTAGS_T_RETURN => 'doReturn',
		PHPTAGS_T_COPY => 'doCopy',
		PHPTAGS_T_IGNORE_ERROR => 'doIgnoreErrors',
		PHPTAGS_T_LIST => 'doList',
		PHPTAGS_T_INC => 'doIncrease',
		PHPTAGS_T_DEC => 'doDecrease',
		PHPTAGS_T_EQUAL => 'doSetVal',
		PHPTAGS_T_CONCAT_EQUAL => 'doSetConcatVal',
		PHPTAGS_T_PLUS_EQUAL => 'doSetPlusVal',
		PHPTAGS_T_MINUS_EQUAL => 'doSetMinusVal',
		PHPTAGS_T_MUL_EQUAL => 'doSetMulVal',
		PHPTAGS_T_DIV_EQUAL => 'doSetDivVal',
		PHPTAGS_T_MOD_EQUAL => 'doSetModVal',
		PHPTAGS_T_AND_EQUAL => 'doSetAndVal',
		PHPTAGS_T_OR_EQUAL => 'doSetOrVal',
		PHPTAGS_T_XOR_EQUAL => 'doSetXorVal',
		PHPTAGS_T_SL_EQUAL => 'doSetShiftLeftVal',
		PHPTAGS_T_SR_EQUAL => 'doSetShiftRightVal',
	);

	public static function reset() {
		global $wgPhpTagsMaxLoops;

		self::$variables = array();
		self::$staticVariables = array();
		self::$globalVariables = array();
		self::$loopsLimit = $wgPhpTagsMaxLoops;
		self::$parserDisabled = false;
		self::$errorCategoryAdded = false;
	}

	public static function runSource( $code, array $args = array(), $scope = '' ) {
		return self::run( Compiler::compile($code), $args, $scope );
	}

	/**
	 * The output cache
	 * @var array
	 */
	private static $return;
	private static $running;
	private static $runIndex;
	private static $c;
	private static $loopsOwner;
	private static $memory;
	private static $place;
	private static $thisVariables;

	private static function pushDown( $newCode, $newLoopsOwner, &$refReturn ) {
		self::$memory[] = array( &$refReturn, self::$running, self::$runIndex, self::$c, self::$loopsOwner );
		self::$running = $newCode;
		self::$runIndex = -1;
		self::$c = count( $newCode );
		self::$loopsOwner = $newLoopsOwner;
	}

	private static function popUp() {
		list( self::$running[self::$runIndex][PHPTAGS_STACK_RESULT], self::$running, self::$runIndex, self::$c, self::$loopsOwner ) = array_pop( self::$memory );
	}

	/**
	 * PHPTAGS_T_QUOTE
	 * @param array $value
	 */
	private static function doQuote ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = implode( $value[PHPTAGS_STACK_PARAM] );
	}

	/**
	 * PHPTAGS_T_CONCAT
	 * @param array $value
	 */
	private static function doConcat ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] . $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_PLUS
	 * @param array $value
	 */
	private static function doPlus ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] + $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_MINUS
	 * @param array $value
	 */
	private static function doMinus ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] - $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_MUL
	 * @param array $value
	 */
	private static function doMul ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] * $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_DIV
	 * @param array $value
	 */
	private static function doDiv ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] / $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_MOD
	 * @param array $value
	 */
	private static function doMod ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] % $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_AND
	 * @param array $value
	 */
	private static function doAnd ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] & $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_OR
	 * @param array $value
	 */
	private static function doOr ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] | $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_XOR
	 * @param array $value
	 */
	private static function doXor ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] ^ $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_SL
	 * @param array $value
	 */
	private static function doShiftLeft ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] << $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_SR
	 * @param array $value
	 */
	private static function doShiftRight ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] >> $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_LOGICAL_AND
	 * @param array $value
	 */
	private static function doLogicalAnd ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] && $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_LOGICAL_XOR
	 * @param array $value
	 */
	private static function doLogicalXor ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = ($value[PHPTAGS_STACK_PARAM] xor $value[PHPTAGS_STACK_PARAM_2]);
	}

	/**
	 * PHPTAGS_T_LOGICAL_OR
	 * @param array $value
	 */
	private static function doLogicalOr ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] || $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_SMALLER
	 * @param array $value
	 */
	private static function doIsSmaller ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] < $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_GREATER
	 * @param array $value
	 */
	private static function doIsGreater ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] > $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_SMALLER_OR_EQUAL
	 * @param array $value
	 */
	private static function doIsSmallerOrEqual ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] <= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_GREATER_OR_EQUAL
	 * @param array $value
	 */
	private static function doIsGreaterOrEqual ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] >= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_EQUAL
	 * @param array $value
	 */
	private static function doIsEqual ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] == $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_NOT_EQUAL
	 * @param array $value
	 */
	private static function doIsNotEqual ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] != $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_IDENTICAL
	 * @param array $value
	 */
	private static function doIsIdentical ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] === $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_NOT_IDENTICAL
	 * @param array $value
	 */
	private static function doIsNotIdentical ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM] !== $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_PRINT outputs the value
	 * @param array $value
	 */
	private static function doPrint ( &$value ) {
		if( $value[PHPTAGS_STACK_PARAM] instanceof GenericObject ) {
			self::$return[] = $value[PHPTAGS_STACK_PARAM]->toString();
		} else {
			self::$return[] = $value[PHPTAGS_STACK_PARAM];
		}
	}

	/**
	 * PHPTAGS_T_NOT
	 * @param array $value
	 */
	private static function doNot ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = ~$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_IS_NOT
	 * @param array $value
	 */
	private static function doIsNot ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = !$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_INT_CAST
	 * @param array $value
	 */
	private static function doIntCast ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = (int)$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_DOUBLE_CAST
	 * @param array $value
	 */
	private static function doDoubleCast ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = (double)$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_STRING_CAST
	 * @param array $value
	 */
	private static function doStringCast ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = (string)$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_ARRAY_CAST
	 * @param array $value
	 */
	private static function doArrayCast ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = (array)$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_BOOL_CAST
	 * @param array $value
	 */
	private static function doBoolCast ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = (bool)$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_UNSET_CAST
	 * @param array $value
	 */
	private static function doUnsetCast ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = (unset)$value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_VARIABLE
	 * @param array $value
	 */
	private static function doVariable ( &$value ) {
		$aim = $value[PHPTAGS_STACK_AIM];
		if ( isset(self::$thisVariables[ $value[PHPTAGS_STACK_PARAM] ]) || array_key_exists($value[PHPTAGS_STACK_PARAM], self::$thisVariables) ) {
			$value[PHPTAGS_STACK_PARAM_2][$aim] =& self::$thisVariables[ $value[PHPTAGS_STACK_PARAM] ];
			if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]
				foreach ( $value[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
					if ( is_array( $value[PHPTAGS_STACK_PARAM_2][$aim] ) ) { // Variable is array. Examle: $foo = ['string']; echo $foo[0];
						if ( isset($value[PHPTAGS_STACK_PARAM_2][$aim][$v]) || array_key_exists($v, $value[PHPTAGS_STACK_PARAM_2][$aim]) ) {
							$value[PHPTAGS_STACK_PARAM_2][$aim] =& $value[PHPTAGS_STACK_PARAM_2][$aim][$v];
						} else {
							// PHP Notice:  Undefined offset: $1
							self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_INDEX, $v ) );
							unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
							$value[PHPTAGS_STACK_PARAM_2][$aim] = null;
						}
					} else { // Variable is string. Examle: $foo = 'string'; echo $foo[2];
						if ( isset( $value[PHPTAGS_STACK_PARAM_2][$aim][$v]) ) {
							$tmp = $value[PHPTAGS_STACK_PARAM_2][$aim][$v];
							unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
							$value[PHPTAGS_STACK_PARAM_2][$aim] = $tmp;
						} else {
							// PHP Notice:  Uninitialized string offset: $1
							self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNINIT_STRING_OFFSET, (int)$v ) );
							unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
							$value[PHPTAGS_STACK_PARAM_2][$aim] = null;
						}
					}
				}
			}
		} else {
			unset( $value[PHPTAGS_STACK_PARAM_2][$aim] );
			$value[PHPTAGS_STACK_PARAM_2][$aim] = null;
			self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $value[PHPTAGS_STACK_PARAM] ) );
		}
	}

	/**
	 * PHPTAGS_T_TERNARY
	 * @param array $value
	 */
	private static function doTernary ( &$value ) {
		if( $value[PHPTAGS_STACK_PARAM] ) { // true ?
			if( $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_TRUE] ) { // true ? 1+2 :
				self::pushDown( $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_TRUE], '?', $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM] );
			}else{ // true ? 1 :
				$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM];
			}
		}else{ // false ?
			if( $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_FALSE] ) { // false ? ... : 1+2
				self::pushDown( $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_FALSE], '?', $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM_2] );
			}else{ // false ? ... : 1
				$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM_2];
			}
		}
	}

	/**
	 * PHPTAGS_T_IF
	 * @param array $value
	 */
	private static function doIf ( &$value ) {
		$return = null;
		if( $value[PHPTAGS_STACK_PARAM] ) { // Example: if( true )
			if( $value[PHPTAGS_STACK_DO_TRUE] ) { // Stack not empty: if(true);
				self::pushDown( $value[PHPTAGS_STACK_DO_TRUE], T_IF, $return );
			}
		}else{ // Example: if( false )
			if( isset($value[PHPTAGS_STACK_DO_FALSE]) ) { // Stack not empty: if(false) ; else ;
				self::pushDown( $value[PHPTAGS_STACK_DO_FALSE], T_IF, $return );
			}
		}
	}

	/**
	 * PHPTAGS_T_FOREACH
	 * @param array $value
	 */
	private static function doForeach ( &$value ) {
		if ( !is_array($value[PHPTAGS_STACK_PARAM]) ) {
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_INVALID_ARGUMENT_FOR_FOREACH, null ) );
			return;
		}
		reset( $value[PHPTAGS_STACK_PARAM] );
		$null = null;
		self::pushDown( $value[PHPTAGS_STACK_DO_TRUE], T_WHILE, $null );
	}

	/**
	 * PHPTAGS_T_WHILE
	 * @param array $value
	 */
	private static function doWhile ( &$value ) {
		$null = null;
		self::pushDown( $value[PHPTAGS_STACK_DO_TRUE], T_WHILE, $null );
	}

	/**
	 * PHPTAGS_T_AS
	 * @param array $value
	 */
	private static function doAs ( &$value ) {
		// $value[PHPTAGS_STACK_RESULT] is always array, checked in self::doForeach()
		$tmp = each( $value[PHPTAGS_STACK_RESULT] );
		if ( $tmp === false ) { // it is last element
			self::popUp();
		}

		self::$thisVariables[ $value[PHPTAGS_STACK_PARAM] ] = $tmp[1]; // save value
		if ( $value[PHPTAGS_STACK_PARAM_2] !== false ) { // T_DOUBLE_ARROW Example: while ( $foo as $key=>$value )
			self::$thisVariables[ $value[PHPTAGS_STACK_PARAM_2] ] = $tmp[0]; // save key
		}
	}

	/**
	 * PHPTAGS_T_BREAK
	 * @param array $value
	 */
	private static function doBreak ( &$value ) {
		$loopsOwner =& self::$loopsOwner;
		$memory =& self::$memory;
		$originalBreakLevel = $breakLevel = $value[PHPTAGS_STACK_RESULT];

		for ( ; $breakLevel > 0; ) {
			if ( $loopsOwner === T_WHILE ) {
				--$breakLevel;
			}
			if ( false === isset( $memory[0] ) ) {
				if ( $breakLevel > 1 ) { // Allows exit from PhpTags
					throw new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, $originalBreakLevel, $value[PHPTAGS_STACK_TOKEN_LINE] );
				}
				--$breakLevel; // Return
			}
			self::popUp();
		}
	}

	/**
	 * PHPTAGS_T_CONTINUE
	 * @param array $value
	 */
	private static function doContinue ( &$value ) {
		if( --self::$loopsLimit <= 0 ) {
			throw new PhpTagsException( PhpTagsException::FATAL_LOOPS_LIMIT_REACHED, null );
		}
		$loopsOwner =& self::$loopsOwner;
		$memory =& self::$memory;
		$originalBreakLevel = $value[PHPTAGS_STACK_RESULT];
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
				throw new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, $originalBreakLevel, $value[PHPTAGS_STACK_TOKEN_LINE] );
			}
			self::popUp();
		}
		self::$runIndex = -1;
	}

	/**
	 * PHPTAGS_T_ARRAY inits new array
	 * @param array $value
	 */
	private static function doArray ( &$value ) {
		$newArray = $value[PHPTAGS_STACK_PARAM][0];
		$i = 1;
		foreach ( $value[PHPTAGS_STACK_PARAM_2] as $t ) {
			list ( $k, $v ) = $t;
			$newArray[$k] = $v;
			if ( isset($value[PHPTAGS_STACK_PARAM][$i]) ) {
				foreach ( $value[PHPTAGS_STACK_PARAM][$i] as $n ) {
					$newArray[] = $n;
				}
			}
			++$i;
		}
		$value[PHPTAGS_STACK_RESULT] = $newArray;
	}

	/**
	 * PHPTAGS_T_STATIC inits static variables
	 * @param array $value
	 */
	private static function doStatic ( &$value ) {
		$name = $value[PHPTAGS_STACK_PARAM]; // variable name
		if ( false === (isset( self::$staticVariables[self::$place] ) && (isset( self::$staticVariables[self::$place][$name] ) || array_key_exists( $name, self::$staticVariables[self::$place] ))) ) {
			// It is not initialised variable, initialise it
			self::$staticVariables[self::$place][$name] = $value[PHPTAGS_STACK_RESULT];
		}
		self::$thisVariables[$name] =& self::$staticVariables[self::$place][$name];
	}

	/**
	 * PHPTAGS_T_GLOBAL inits global variables
	 * @param array $value
	 */
	private static function doGlobal ( &$value ) {
		$gVars =& self::$globalVariables;
		foreach( $value[PHPTAGS_STACK_PARAM] as $name ) { // variable names
			if( !array_key_exists($name, $gVars) ) {
				$gVars[$name] = null;
			}
			self::$thisVariables[$name] =& $gVars[$name];
		}
	}

	/**
	 * PHPTAGS_T_HOOK_CHECK_PARAM checks param for hooks
	 * @param array $value
	 */
	private static function doCheckingParam ( &$value ) {
		$i = $value[PHPTAGS_STACK_AIM]; // ordinal number of the argument, zero based
		$reference_info = Hooks::getReferenceInfo( $i, $value );

		if ( $value[PHPTAGS_STACK_PARAM_2] === true && $reference_info === false ) {
			// Param is variable and it needs to clone
			$t = $value[PHPTAGS_STACK_RESULT][$i];
			unset( $value[PHPTAGS_STACK_RESULT][$i] );
			$value[PHPTAGS_STACK_RESULT][$i] = $t;
		} elseif ( $value[PHPTAGS_STACK_PARAM_2] === false && $reference_info === true ) {
			// Param is not variable and it's need reference
			throw new PhpTagsException( PhpTagsException::FATAL_VALUE_PASSED_BY_REFERENCE, null );
		}
	}

	/**
	 * PHPTAGS_T_HOOK calls hook
	 * @param array $value
	 */
	private static function doCallingHook ( &$value ) {
		try {
			$result = Hooks::callHook( $value );
		} catch ( \PhpTags\HookException $exc ) {
			if ( $exc->isFatal() ) {
				throw $exc;
			}
			self::pushException( $exc );
			$result = Hooks::getCallInfo( Hooks::INFO_RETURNS_ON_FAILURE );
		}

		if ( $result instanceof outPrint ) {
			$value[PHPTAGS_STACK_RESULT] = $result->returnValue;
			self::$return[] = $result;
		} else {
			$value[PHPTAGS_STACK_RESULT] = $result;
		}
		if ( is_object($value[PHPTAGS_STACK_RESULT]) && !($value[PHPTAGS_STACK_RESULT] instanceof iRawOutput || $value[PHPTAGS_STACK_RESULT] instanceof GenericObject) ) {
			// @todo
			$value[PHPTAGS_STACK_RESULT] = null;
			self::pushException( new PhpTagsException( PhpTagsException::WARNING_RETURNED_INVALID_VALUE, $value[PHPTAGS_STACK_PARAM] ) );
		}
	}

	/**
	 * PHPTAGS_T_NEW creates new object
	 * @param array $value
	 */
	private static function doNewObject ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = Hooks::createObject( $value[PHPTAGS_STACK_PARAM_2], $value[PHPTAGS_STACK_PARAM_3] );
	}

	/**
	 * PHPTAGS_T_UNSET unsets variables
	 * @param array $value
	 */
	private static function doUnset ( &$value ) {
		$thisVariables =& self::$thisVariables;
		foreach ( $value[PHPTAGS_STACK_PARAM] as $val ) {
			$name = $val[PHPTAGS_STACK_PARAM]; // Variable Name
			if ( isset($thisVariables[$name]) || array_key_exists($name, $thisVariables) ) { // defined variable
				if ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // There is array index. Example: unset($foo[0])
					$ref =& $thisVariables[$name];
					$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
					foreach ( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
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
					unset( $thisVariables[$name] );
				}
			} elseif ( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // undefined variable with array index. Example: unset($foo[1])
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $name ) );
			}
		}
	}

	/**
	 * PHPTAGS_T_ISSET returns TRUE when variables are set
	 * @param array $value
	 */
	private static function doIsSet ( &$value ) {
		$thisVariables =& self::$thisVariables;
		foreach($value[PHPTAGS_STACK_PARAM] as $val) {
			if( !isset($thisVariables[ $val[PHPTAGS_STACK_PARAM] ]) ) { // undefined variable or variable is null
				$value[PHPTAGS_STACK_RESULT] = false;
				return;
			} // true, variable is defined
			if( isset($val[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: isset($foo[1])
				$ref =& $thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
				$tmp = array_pop( $val[PHPTAGS_STACK_ARRAY_INDEX] );
				foreach( $val[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
					if( !isset($ref[$v]) ) { // undefined array index
						$value[PHPTAGS_STACK_RESULT] = false;
						return;
					}
					$ref =& $ref[$v];
				}
				// @todo ->>>>>>>>>>>> | ************************************************************* | <<<<< it only for compatible with PHP 5.4 if used PHP 5.3 @see http://www.php.net/manual/en/function.isset.php Example #2 isset() on String Offsets
				if( !isset($ref[$tmp]) || (is_string($ref) && is_string($tmp ) && $tmp  != (string)(int)$tmp ) ) {
					$value[PHPTAGS_STACK_RESULT] = false;
					return;
				}
			} // true, variable is defined and have no array index
		}
		$value[PHPTAGS_STACK_RESULT] = true;
	}

	/**
	 * PHPTAGS_T_EMPTY returns TRUE when variables are empty
	 * @param array $value
	 */
	private static function doIsEmpty ( &$value ) {
		$thisVariables =& self::$thisVariables;
		foreach($value[PHPTAGS_STACK_PARAM] as $val) {
			if( !array_key_exists($val[PHPTAGS_STACK_PARAM], $thisVariables) ) { // undefined variable
				continue;
			}
			$ref =& $thisVariables[ $val[PHPTAGS_STACK_PARAM] ];
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
					return;
				}
			}elseif( !empty($ref) ) { // there is no array index and empty() returns false (PHP 5.5.0 supports expressions)
				$value[PHPTAGS_STACK_RESULT] = false;
				return;
			}
		}
		$value[PHPTAGS_STACK_RESULT] = true;
	}

	/**
	 * PHPTAGS_T_RETURN is designed for compiler only!
	 * @param array $value
	 */
	private static function doReturn ( &$value ) {
		self::$return = self::$return ? new PhpTagsException() : $value[PHPTAGS_STACK_PARAM];
	}

	/**
	 * PHPTAGS_T_COPY copies value from variable to destination
	 * @param array $value
	 */
	private static function doCopy ( &$value ) {
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM];
	}

	/**
	 * PHPTAGS_T_IGNORE_ERROR
	 * @param array $value
	 */
	private static function doIgnoreErrors ( &$value ) {
		self::$ignoreErrors = $value[PHPTAGS_STACK_PARAM];
	}

	/**
	 * PHPTAGS_T_LIST
	 * @param array $value
	 */
	private static function doList ( &$value ) {
		self::fillList( $value[PHPTAGS_STACK_PARAM_2], $value[PHPTAGS_STACK_PARAM] );
		$value[PHPTAGS_STACK_RESULT] = $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_INC
	 * @param array $value
	 */
	private static function doIncrease ( &$value ) {
		$ref =& self::getVariableRef( $value );
		if ( $value[PHPTAGS_STACK_PARAM_2] ) { // $foo++
			$value[PHPTAGS_STACK_RESULT] = $ref++;
		}else{ // ++$foo
			$value[PHPTAGS_STACK_RESULT] = ++$ref;
		}
	}

	/**
	 * PHPTAGS_T_DEC
	 * @param array $value
	 */
	private static function doDecrease ( &$value ) {
		$ref =& self::getVariableRef( $value );
		if ( $value[PHPTAGS_STACK_PARAM_2] ) { // $foo--
			$value[PHPTAGS_STACK_RESULT] = $ref--;
		}else{ // --$foo
			$value[PHPTAGS_STACK_RESULT] = --$ref;
		}
	}

	/**
	 * PHPTAGS_T_EQUAL
	 * @param array $value
	 */
	private static function doSetVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref = $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_CONCAT_EQUAL
	 * @param array $value
	 */
	private static function doSetConcatVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref .= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_PLUS_EQUAL
	 * @param array $value
	 */
	private static function doSetPlusVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref += $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_MINUS_EQUAL
	 * @param array $value
	 */
	private static function doSetMinusVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref -= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_MUL_EQUAL
	 * @param array $value
	 */
	private static function doSetMulVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref *= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_DIV_EQUAL
	 * @param array $value
	 */
	private static function doSetDivVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref /= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_MOD_EQUAL
	 * @param array $value
	 */
	private static function doSetModVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref %= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_AND_EQUAL
	 * @param array $value
	 */
	private static function doSetAndVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref &= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_OR_EQUAL
	 * @param array $value
	 */
	private static function doSetOrVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref |= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_XOR_EQUAL
	 * @param array $value
	 */
	private static function doSetXorVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref ^= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_SL_EQUAL
	 * @param array $value
	 */
	private static function doSetShiftLeftVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref <<= $value[PHPTAGS_STACK_PARAM_2];
	}

	/**
	 * PHPTAGS_T_SR_EQUAL
	 * @param array $value
	 */
	private static function doSetShiftRightVal ( &$value ) {
		$ref =& self::getVariableRef( $value );
		$value[PHPTAGS_STACK_RESULT] = $ref <<= $value[PHPTAGS_STACK_PARAM_2];
	}

	private static function & getVariableRef( $value ) {
		$thisVariables =& self::$thisVariables;
		$variable = $value[PHPTAGS_STACK_PARAM];
		$variableName = $variable[PHPTAGS_STACK_PARAM];
		if( !(isset($thisVariables[$variableName]) || array_key_exists($variableName, $thisVariables)) ) { // Use undefined variable
			$thisVariables[$variableName] = null;
			if( $value[PHPTAGS_STACK_COMMAND] !== PHPTAGS_T_EQUAL ) {
				self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, $variableName ) );
			}
		}
		$ref =& $thisVariables[$variableName];
		if ( isset($variable[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: $foo[1]++
			foreach ( $variable[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
				if ( $v === INF ) { // Example: $foo[]
					$t = null;
					$ref[] = &$t;
					$ref = &$t;
					unset( $t );
				} else {
					if ( $ref === null ) {
						if( $value[PHPTAGS_STACK_COMMAND] !== PHPTAGS_T_EQUAL ) {
							// PHP Notice:  Undefined offset: $1
							self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $v ) );
						}
						$ref[$v] = null;
						$ref =& $ref[$v];
					} elseif ( is_array($ref) ) {
						if ( !( isset($ref[$v]) || array_key_exists($v, $ref) ) ) {
							$ref[$v] = null;
							if( $value[PHPTAGS_STACK_COMMAND] !== PHPTAGS_T_EQUAL ) {
								// PHP Notice:  Undefined offset: $1
								self::pushException( new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, $v ) );
							}
						}
						$ref =& $ref[$v];
					} else { // scalar
						// PHP Warning:  Cannot use a scalar value as an array
						self::pushException( new PhpTagsException( PhpTagsException::WARNING_SCALAR_VALUE_AS_ARRAY, null ) );
						unset( $ref );
						$ref = null;
						break;
					}
				}
			}
		}
		return $ref;
	}

	public static function run( $code, array $args, $scope = '' ) {
		set_error_handler( '\\PhpTags\\ErrorHandler::onError' );
		try {
			self::$scope = $scope;
			self::$running = $code;
			self::$memory = array();
			self::$return = array();
			self::$loopsOwner = null;
			self::$place = isset($args[0]) ? $args[0] : ''; // Page name for static variables and error messages
			self::$c = count( $code );
			if( false === isset(self::$variables[$scope]) ) {
				self::$variables[$scope] = array();
			}
			self::$thisVariables =& self::$variables[$scope];
			self::$thisVariables['argv'] = $args;
			self::$thisVariables['argc'] = count($args);
			self::$thisVariables['GLOBALS'] =& self::$globalVariables;

			$runCode =& self::$running;
			$runIndex =& self::$runIndex;
			$loopsOwner =& self::$loopsOwner;
			$memory =& self::$memory;
			$c =& self::$c;
			$operators = self::$operators;

			$runIndex = -1;
			do {
				for ( ++$runIndex; $runIndex < $c; ++$runIndex ) {
					$value =& $runCode[$runIndex];
					$call = $operators[ $value[PHPTAGS_STACK_COMMAND] ];
					self::$call( $value );
				}
			} while( list($runCode[$runIndex][PHPTAGS_STACK_RESULT], $runCode, $runIndex, $c, $loopsOwner) = array_pop($memory) );
		} catch ( PhpTagsException $e ) {
			if ( self::$ignoreErrors ) {
				self::$ignoreErrors = false;
			} else {
				self::pushException( $e );
			}
			self::$running[self::$runIndex][PHPTAGS_STACK_RESULT] = null;
		} catch ( \Exception $e ) {
			self::addRuntimeErrorCategory();
			restore_error_handler();
			throw $e;
		}
		restore_error_handler();
		return self::$return;
	}

	static function fillList( &$values, &$parametrs, $offset = false ) {
		$return = array();

		for ( $pkey = count( $parametrs ) - 1; $pkey >= 0; --$pkey ) {
			$param = $parametrs[$pkey];
			if ( $param === null ) { // skip emty params. Example: list(, $bar) = $array;
				continue;
			}
			if( $param[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_LIST ) { // T_LIST inside other T_LIST. Example: list($a, list($b, $c)) = array(1, array(2, 3));
				if ( is_array($values) && isset($values[$pkey]) ) {
					$return[$pkey] = self::fillList( $values[$pkey], $param[PHPTAGS_STACK_PARAM] );
				} else { // list() works with array only @todo support strings
					static $emptyArray=array();
					$return[$pkey] = self::fillList( $emptyArray, $param[PHPTAGS_STACK_PARAM], $pkey );
				}
				continue;
			}
			// $param is variable
			$ref = &self::$thisVariables[ $param[PHPTAGS_STACK_PARAM] ];
			if ( isset($param[PHPTAGS_STACK_ARRAY_INDEX]) ) { // Example: list($foo[0], $foo[1]) = $array;
				foreach ( $param[PHPTAGS_STACK_ARRAY_INDEX] as $v ) {
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

	/**
	 * Set a flag in the output object indicating that the content is dynamic and
	 * shouldn't be cached.
	 * @global \OutputPage $wgOut
	 * @staticvar boolean $done
	 * @return null
	 */
	public static function disableParserCache() {
		if ( self::$parserDisabled === true ) {
			return;
		}

		global $wgOut;
		self::$parser->disableCache();
		$wgOut->enableClientCache( false );
		self::$parserDisabled = true;
	}

	private static function addRuntimeErrorCategory() {
		if ( self::$errorCategoryAdded === true || self::$parser === null ) {
			return;
		}

		self::$parser->addTrackingCategory( 'phptags-runtime-error-category' );
		self::$errorCategoryAdded = true;
	}

	/**
	 * Increment the expensive function count
	 * @param string $functionName
	 * @return null
	 * @throws PhpTagsException
	 */
	public static function incrementExpensiveFunctionCount( $functionName ) {
		if ( false === self::$parser->incrementExpensiveFunctionCount() ) {
			throw new PhpTagsException( PhpTagsException::FATAL_CALLED_MANY_EXPENSIVE_FUNCTION );
		}
		return null;
	}

	public static function pushException( PhpTagsException $exc ) {
		if ( self::$ignoreErrors === false ) {
			if ( $exc->tokenLine === null ) {
				$exc->tokenLine = self::$running[self::$runIndex][PHPTAGS_STACK_TOKEN_LINE];
			}
			$exc->place = self::$place;
			self::$return[] = (string) $exc;
			self::addRuntimeErrorCategory();
		}
	}

	public static function getCurrentOperator() {
		return self::$running[self::$runIndex];
	}

	public static function getVariables() {
		return self::$variables[ self::$scope ];
	}

}
