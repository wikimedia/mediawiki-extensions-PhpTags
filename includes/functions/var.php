<?php
/**
 * Variable handling Functions
 * @see http://www.php.net/manual/en/ref.var.php
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

return array(
	'boolval' => array( 1=>function($args) { return (bool)$args[0]; } ),
// @todo debug_zval_dump()
	'doubleval' => array( 1=>function($args) { return doubleval($args[0]); } ),
	// empty() in runtime.php
	'floatval' => array( 1=>function($args) { return floatval($args[0]); } ),
// @todo get_defined_vars() in runtime.php
	// get_resource_type() it have not resources
	'gettype' => array( 1=>function($args) { return gettype($args[0]); } ),
// @todo bool import_request_variables ( string $types [, string $prefix ] )
	'intval' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>10 ),
		2=>function($args) { return intval($args[0], $args[1]); },
	),
	'is_array' => array( 1=>function($args) { return is_array($args[0]); } ),
	'is_bool' => array( 1=>function($args) { return is_bool($args[0]); } ),
// @todo bool is_callable ( callable $name [, bool $syntax_only = false [, string &$callable_name ]] )
	'is_double' => array( 1=>function($args) { return is_double($args[0]); } ),
	'is_float' => array( 1=>function($args) { return is_float($args[0]); } ),
	'is_int' => array( 1=>function($args) { return is_int($args[0]); } ),
	'is_integer' => array( 1=>function($args) { return is_integer($args[0]); } ),
	'is_long' => array( 1=>function($args) { return is_long($args[0]); } ),
	'is_null' => array( 1=>function($args) { return is_null($args[0]); } ),
	'is_numeric' => array( 1=>function($args) { return is_numeric($args[0]); } ),
// @todo bool is_object ( mixed $var )
	'is_real' => array( 1=>function($args) { return is_real($args[0]); } ),
	// is_resource it have not resources
	'is_scalar' => array( 1=>function($args) { return is_scalar($args[0]); } ),
	'is_string' => array( 1=>function($args) { return is_string($args[0]); } ),
	// isset() in runtime.php
	'print_r' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>false ),
		2=>function($args) { $ret = print_r($args[0], true); return $args[1] ? $ret : new Foxway\outPrint(true, $ret); },
	),
// @todo string serialize ( mixed $value )
	'settype' => array( 2=>function($args) { return settype($args[0], $args[1]); } ),
	'strval' => array( 1=>function($args) { return strval($args[0]); } ),
// @todo mixed unserialize ( string $str )
	// unset() in runtime.php
	'var_dump' => array(
		FOXWAY_MIN_VALUES=>1,
		''=>function($args) { ob_start(); call_user_func_array('var_dump', $args); return new Foxway\outPrint( null, ob_get_clean() ); },
	),
	'var_export' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>false ),
		2=>function($args) { $ret = var_export($args[0], true); return $args[1] ? $ret : new Foxway\outPrint(null, $ret); },
	),
);
