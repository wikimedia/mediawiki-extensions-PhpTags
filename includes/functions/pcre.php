<?php
/**
 * PCRE Functions
 * @see http://www.php.net/manual/en/ref.pcre.php
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

/**
 * add pcre constants in Runtime class
 * @see http://www.php.net/manual/en/pcre.constants.php
 */
Foxway\Runtime::$constants += array(
	'PREG_PATTERN_ORDER' => PREG_PATTERN_ORDER,
	'PREG_SET_ORDER' => PREG_SET_ORDER,
	'PREG_OFFSET_CAPTURE' => PREG_OFFSET_CAPTURE,
	'PREG_SPLIT_NO_EMPTY' => PREG_SPLIT_NO_EMPTY,
	'PREG_SPLIT_DELIM_CAPTURE' => PREG_SPLIT_DELIM_CAPTURE,
	'PREG_SPLIT_OFFSET_CAPTURE' => PREG_SPLIT_OFFSET_CAPTURE,
	'PREG_NO_ERROR' => PREG_NO_ERROR,
	'PREG_INTERNAL_ERROR' => PREG_INTERNAL_ERROR,
	'PREG_BACKTRACK_LIMIT_ERROR' => PREG_BACKTRACK_LIMIT_ERROR,
	'PREG_RECURSION_LIMIT_ERROR' => PREG_RECURSION_LIMIT_ERROR,
	'PREG_BAD_UTF8_ERROR' => PREG_BAD_UTF8_ERROR,
	'PREG_BAD_UTF8_OFFSET_ERROR' => PREG_BAD_UTF8_OFFSET_ERROR,
	'PCRE_VERSION' => PCRE_VERSION,
	'PREG_GREP_INVERT' => PREG_GREP_INVERT,
);

$foxway_check_preg_replace_pattern = function ( $arg ) {
	$getValidPattern = function ( $pattern ) {
		$pattern = str_replace(chr(0), '', $pattern);
		// Set basic statics
		static $regexStarts = '`~!@#$%^&*-_+=.,?"\':;|/<([{';
		static $regexEnds   = '`~!@#$%^&*-_+=.,?"\':;|/>)]}';
		static $regexModifiers = 'imsxADU';

		$delimPos = strpos( $regexStarts, $pattern[0] );
		if ( $delimPos === false ) {
			throw new Foxway\ExceptionFoxway( array(), FOXWAY_PHP_WARNING_WRONG_DELIMITER );
		}

		$end = $regexEnds[$delimPos];
		$pos = 1;
		$endPos = null;
		while ( !isset( $endPos ) ) {
			$pos = strpos( $pattern, $end, $pos );
			if ( $pos === false ) {
				throw new Foxway\ExceptionFoxway( array($end), FOXWAY_PHP_WARNING_NO_ENDING_DELIMITER );
			}
			$backslashes = 0;
			for ( $l = $pos - 1; $l >= 0; $l-- ) {
				if ( $pattern[$l] == '\\' ) $backslashes++;
				else break;
			}
			if ( $backslashes % 2 == 0 ) $endPos = $pos;
			$pos++;
		}
		$startRegex = (string)substr( $pattern, 0, $endPos ) . $end;
		$endRegex = (string)substr( $pattern, $endPos + 1 );
		$len = strlen( $endRegex );
		for ( $c = 0; $c < $len; $c++ ) {
			if ( strpos( $regexModifiers, $endRegex[$c] ) === false ) {
				throw new Foxway\ExceptionFoxway( array($endRegex[$c]), FOXWAY_PHP_WARNING_UNKNOWN_MODIFIER );
			}
		}
		return $startRegex . $endRegex . 'u';
	};

	if( is_array($arg) ) {
		$ret = array();
		foreach ($arg as $key => $value) {
			$ret[$key] = $getValidPattern($value);
		}
		return $ret;
	}else{
		return $getValidPattern($arg);
	}
};

return array(
	'preg_filter' => array(
		3=>function($args) { return preg_filter($args[0], $args[1], $args[2]); },
		4=>function($args) { return preg_filter($args[0], $args[1], $args[2], $args[3]); },
		5=>function($args) { return preg_filter($args[0], $args[1], $args[2], $args[3], $args[4]); },
	),
	'preg_grep' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>0 ),
		3=>function($args) { return preg_grep($args[0], $args[1], $args[2]); },
	),
	'preg_last_error' => array( 0=>function() { return preg_last_error(); } ),
	'preg_match_all' => array(
// @todo PHP 5.4.0:		2=>function($args) { return preg_match_all($args[0], $args[1], $args[2]); },
		3=>function($args) { return preg_match_all($args[0], $args[1], $args[2]); },
		4=>function($args) { return preg_match_all($args[0], $args[1], $args[2], $args[3]); },
		5=>function($args) { return preg_match_all($args[0], $args[1], $args[2], $args[3], $args[4]); },
	),
	'preg_match' => array(
		2=>function($args) { return preg_match($args[0], $args[1]); },
		3=>function($args) { return preg_match($args[0], $args[1], $args[2]); },
		4=>function($args) { return preg_match($args[0], $args[1], $args[2], $args[3]); },
		5=>function($args) { return preg_match($args[0], $args[1], $args[2], $args[3], $args[4]); },
	),
	'preg_quote' => array(
		1=>function($args) { return preg_quote($args[0]); },
		2=>function($args) { return preg_quote($args[0], $args[1]); },
	),
// @todo mixed preg_replace_callback ( mixed $pattern , callable $callback , mixed $subject [, int $limit = -1 [, int &$count ]] )
	'preg_replace' => array(
		FOXWAY_DEFAULT_VALUES=>array( 3=>-1 ),
		4=>function($args) use (&$foxway_check_preg_replace_pattern) { return preg_replace($foxway_check_preg_replace_pattern($args[0]), $args[1], $args[2], $args[3]); },
		5=>function($args) use (&$foxway_check_preg_replace_pattern) { return preg_replace($foxway_check_preg_replace_pattern($args[0]), $args[1], $args[2], $args[3], $args[4]); },
	),
	'preg_split' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>-1, 3=>0 ),
		4=>function($args) { return preg_split($args[0], $args[1], $args[2], $args[3]); },
	),
);
