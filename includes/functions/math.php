<?php
/**
 * Math Functions
 * @see http://www.php.net/manual/en/ref.math.php
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

return array(
	'abs' => array( 1=>function($args) { return abs($args[0]); } ),
	'acos' => array( 1=>function($args) { return acos($args[0]); } ),
	'acosh' => array( 1=>function($args) { return acosh($args[0]); } ),
	'asin' => array( 1=>function($args) { return asin($args[0]); } ),
	'asinh' => array( 1=>function($args) { return asinh($args[0]); } ),
	'atan2' => array( 2=>function($args) { return atan2($args[0], $args[1]); } ),
	'atan' => array( 1=>function($args) { return atan($args[0]); } ),
	'atanh' => array( 1=>function($args) { return atanh($args[0]); } ),
	'base_convert' => array( 3=>function($args) { return base_convert($args[0], $args[1], $args[2]); } ),
	'bindec' => array( 1=>function($args) { return bindec($args[0]); } ),
	'ceil' => array( 1=>function($args) { return ceil($args[0]); } ),
	'cos' => array( 1=>function($args) { return cos($args[0]); } ),
	'cosh' => array( 1=>function($args) { return cosh($args[0]); } ),
	'decbin' => array( 1=>function($args) { return decbin($args[0]); } ),
	'dechex' => array( 1=>function($args) { return dechex($args[0]); } ),
	'decoct' => array( 1=>function($args) { return decoct($args[0]); } ),
	'deg2rad' => array( 1=>function($args) { return deg2rad($args[0]); } ),
	'exp' => array( 1=>function($args) { return exp($args[0]); } ),
	'expm1' => array( 1=>function($args) { return expm1($args[0]); } ),
	'floor' => array( 1=>function($args) { return floor($args[0]); } ),
	'fmod' => array( 2=>function($args) { return fmod($args[0], $args[1]); } ),
	'getrandmax' => array( 0=>function() { return getrandmax(); } ),
	'hexdec' => array( 1=>function($args) { return hexdec($args[0]); } ),
	'hypot' => array( 2=>function($args) { return hypot($args[0], $args[1]); } ),
	'is_finite' => array( 1=>function($args) { return is_finite($args[0]); } ),
	'is_infinite' => array( 1=>function($args) { return is_infinite($args[0]); } ),
	'is_nan' => array( 1=>function($args) { return is_nan($args[0]); } ),
	'lcg_value' => array( 0=>function() { return lcg_value(); } ),
	'log10' => array( 1=>function($args) { return log10($args[0]); } ),
	'log1p' => array( 1=>function($args) { return log1p($args[0]); } ),
	'log' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>M_E ),
		2=>function($args) { return log($args[0], $args[1]); },
	),
	'max' => array(
		PHPTAGS_MIN_VALUES=>1,
		''=>function($args) { return call_user_func_array('max', $args); },
	),
	'min' => array(
		PHPTAGS_MIN_VALUES=>1,
		''=>function($args) { return call_user_func_array('min', $args); },
	),
	'mt_getrandmax' => array( 0=>function() { return mt_getrandmax(); } ),
	'mt_rand' => array(
		0=>function() { return mt_rand(); },
		2=>function($args) { return mt_rand($args[0], $args[1]); },
	),
	'mt_srand' => array(
		0=>function() { return mt_srand(); },
		1=>function($args) { return mt_srand($args[0]); },
	),
	'octdec' => array( 1=>function($args) { return octdec($args[0]); } ),
	'pi' => array( 0=>function() { return pi(); } ),
	'pow' => array( 2=>function($args) { return pow($args[0], $args[1]); } ),
	'rad2deg' => array( 1=>function($args) { return rad2deg($args[0]); } ),
	'rand' => array(
		0=>function() { return rand(); },
		2=>function($args) { return rand($args[0], $args[1]); },
	),
	'round' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>0, 2=>PHP_ROUND_HALF_UP ),
		3=>function($args) { return round($args[0], $args[1], $args[2]); },
	),
	'sin' => array( 1=>function($args) { return sin($args[0]); } ),
	'sinh' => array( 1=>function($args) { return sinh($args[0]); } ),
	'sqrt' => array( 1=>function($args) { return sqrt($args[0]); } ),
	'srand' => array(
		0=>function() { return srand(); },
		1=>function($args) { return srand($args[0]); },
	),
	'tan' => array( 1=>function($args) { return tan($args[0]); } ),
	'tanh' => array( 1=>function($args) { return tanh($args[0]); } ),
);
