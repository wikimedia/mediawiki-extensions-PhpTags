<?php
/**
 * Array Functions
 * @see http://www.php.net/manual/en/ref.array.php
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

return array(
	'array_change_key_case' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>CASE_LOWER ),
		2=>function($args) { return array_change_key_case($args[0], $args[1]); },
	),
	'array_chunk' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 2=>false ),
		3=>function($args) { return array_chunk($args[0], $args[1], $args[2]); },
	),
// @todo PHP 5 >= 5.5.0 array array_column ( array $array , mixed $column_key [, mixed $index_key = null ] )
	'array_combine' => array( 2=>function($args) { return array_combine($args[0], $args[1]); } ),
	'array_count_values' => array( 1=>function($args) { return array_count_values($args[0]); } ),
	'array_diff_assoc' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_diff_assoc', $args); },
	),
	'array_diff_key' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_diff_key', $args); },
	),
// @todo array array_diff_uassoc ( array $array1 , array $array2 [, array $... ], callable $key_compare_func )
// @todo array array_diff_ukey ( array $array1 , array $array2 [, array $ ... ], callable $key_compare_func )
	'array_diff' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_diff', $args); },
	),
	'array_fill_keys' => array( 2=>function($args) { return array_fill_keys($args[0], $args[1]); } ),
	'array_fill' => array( 3=>function($args) { return array_fill($args[0], $args[1], $args[2]); } ),
// @todo array array_filter ( array $input [, callable $callback = "" ] )
	'array_flip' => array( 1=>function($args) { return array_flip($args[0]); } ),
	'array_intersect_assoc' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_intersect_assoc', $args); },
	),
	'array_intersect_key' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_intersect_key', $args); },
	),
// @todo array array_intersect_uassoc ( array $array1 , array $array2 [, array $ ... ], callable $key_compare_func )
// @todo array array_intersect_ukey ( array $array1 , array $array2 [, array $... ], callable $key_compare_func )
	'array_intersect' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_intersect', $args); },
	),
	'array_key_exists' => array( 2=>function($args) { return array_key_exists($args[0], $args[1]); } ),
	'array_keys' => array( 3=>function($args) { return array_keys($args[0], $args[1], $args[2]); } ),
// @todo array array_map ( callable $callback , array $arr1 [, array $... ] )
	'array_merge_recursive' => array(
		PHPTAGS_MIN_VALUES=>1,
		''=>function($args) { return call_user_func_array('array_merge_recursive', $args); },
	),
	'array_merge' => array(
		PHPTAGS_MIN_VALUES=>1,
		''=>function($args) { return call_user_func_array('array_merge', $args); },
	),
	'array_multisort' => array(
		PHPTAGS_MIN_VALUES=>1,
		''=>function($args) { return call_user_func_array('array_multisort', $args); },
	),
	'array_pad' => array( 3=>function($args) { return array_pad($args[0], $args[1], $args[2]); } ),
	'array_pop' => array( 1=>function($args) { return array_pop($args[0]); } ),
	'array_product' => array( 1=>function($args) { return array_product($args[0]); } ),
	'array_push' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_push', $args); },
	),
	'array_rand' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>1 ),
		2=>function($args) { return array_rand($args[0], $args[1]); },
	),
// @todo mixed array_reduce ( array $input , callable $function [, mixed $initial = NULL ] )
	'array_replace_recursive' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_replace_recursive', $args); },
	),
	'array_replace' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_replace', $args); },
	),
	'array_reverse' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>false ),
		2=>function($args) { return array_reverse($args[0], $args[1]); },
	),
	'array_search' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 2=>false ),
		3=>function($args) { return array_search($args[0], $args[1], $args[2]); },
	),
	'array_shift' => array( 1=>function($args) { return array_shift($args[0]); } ),
	'array_slice' => array(
		2=>function($args) { return array_slice($args[0], $args[1]); },
		3=>function($args) { return array_slice($args[0], $args[1], $args[2]); },
		4=>function($args) { return array_slice($args[0], $args[1], $args[2], $args[3]); },
	),
	'array_splice' => array(
		2=>function($args) { return array_splice($args[0], $args[1]); },
		3=>function($args) { return array_splice($args[0], $args[1], $args[2]); },
		4=>function($args) { return array_splice($args[0], $args[1], $args[2], $args[3]); },
	),
	'array_sum' => array( 1=>function($args) { return array_sum($args[0]); } ),
// @todo array array_udiff_assoc ( array $array1 , array $array2 [, array $ ... ], callable $data_compare_func )
// @todo array array_udiff_uassoc ( array $array1 , array $array2 [, array $ ... ], callable $data_compare_func , callable $key_compare_func )
// @todo array array_udiff ( array $array1 , array $array2 [, array $ ... ], callable $data_compare_func )
// @todo array array_uintersect_assoc ( array $array1 , array $array2 [, array $ ... ], callable $data_compare_func )
// @todo array array_uintersect_uassoc ( array $array1 , array $array2 [, array $ ... ], callable $data_compare_func , callable $key_compare_func )
// @todo array array_uintersect ( array $array1 , array $array2 [, array $ ... ], callable $data_compare_func )
	'array_unique' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_STRING ),
		2=>function($args) { return array_unique($args[0], $args[1]); },
	),
	'array_unshift' => array(
		PHPTAGS_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('array_unshift', $args); },
	),
	'array_values' => array( 1=>function($args) { return array_values($args[0]); } ),
// @todo bool array_walk_recursive ( array &$input , callable $funcname [, mixed $userdata = NULL ] )
// @todo bool array_walk ( array &$array , callable $funcname [, mixed $userdata = NULL ] )
	// array() in runtime.php
	'arsort' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_REGULAR ),
		2=>function($args) { return arsort($args[0], $args[1]); },
	),
	'asort' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_REGULAR ),
		2=>function($args) { return asort($args[0], $args[1]); },
	),
// @todo array compact ( mixed $varname [, mixed $... ] )
	'count' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>COUNT_NORMAL ),
		2=>function($args) { return count($args[0], $args[1]); },
	),
	'current' => array( 1=>function($args) { return current($args[0]); } ),
	'each' => array( 1=>function($args) { return each($args[0]); } ),
	'end' => array( 1=>function($args) { return end($args[0]); } ),
	// @todo ??? int extract ( array &$var_array [, int $extract_type = EXTR_OVERWRITE [, string $prefix = NULL ]] )
	'in_array' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 2=>false ),
		3=>function($args) { return in_array($args[0], $args[1], $args[2]); },
	),
	'key_exists' => array( 2=>function($args) { return key_exists($args[0], $args[1]); } ),
	'key' => array( 1=>function($args) { return key($args[0]); } ),
	'krsort' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_REGULAR ),
		2=>function($args) { return krsort($args[0], $args[1]); },
	),
	'ksort' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_REGULAR ),
		2=>function($args) { return ksort($args[0], $args[1]); },
	),
// @todo array list ( mixed $varname [, mixed $... ] )
	'natcasesort' => array( 1=>function($args) { return natcasesort($args[0]); } ),
	'natsort' => array( 1=>function($args) { return natsort($args[0]); } ),
	'next' => array( 1=>function($args) { return next($args[0]); } ),
	'pos' => array( 1=>function($args) { return pos($args[0]); } ),
	'prev' => array( 1=>function($args) { return prev($args[0]); } ),
	'range' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 2=>1 ),
		3=>function($args) { return range($args[0], $args[1], $args[2]); },
	),
	'reset' => array( 1=>function($args) { return reset($args[0]); } ),
	'rsort' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_REGULAR ),
		2=>function($args) { return rsort($args[0], $args[1]); },
	),
	'shuffle' => array( 1=>function($args) { return shuffle($args[0]); } ),
	'sizeof' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>COUNT_NORMAL ),
		2=>function($args) { return sizeof($args[0], $args[1]); },
	),
	'sort' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 1=>SORT_REGULAR ),
		2=>function($args) { return sort($args[0], $args[1]); },
	),
// @todo bool uasort ( array &$array , callable $cmp_function )
// @todo bool uksort ( array &$array , callable $cmp_function )
// @todo bool usort ( array &$array , callable $cmp_function )
);
