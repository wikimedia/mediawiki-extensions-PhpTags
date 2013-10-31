<?php
/**
 * String Functions
 * @see http://php.net/manual/en/ref.strings.php
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

return array(
	'addcslashes' => array( 2=>function($args) { return addcslashes($args[0], $args[1]); } ),
	'addslashes' => array( 1=>function($args) { return addslashes($args[0]); } ),
	'bin2hex' => array( 1=>function($args) { return bin2hex($args[0]); } ),
	'chop' => array(
		1=>function($args) { return chop($args[0]); },
		2=>function($args) { return chop($args[0], $args[1]); },
	),
	'chr' => array( 1=>function($args) { return chr($args[0]); } ),
	'chunk_split' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>76, 2=>"\r\n" ),
		3=>function($args) { return chunk_split($args[0], $args[1], $args[2]); },
	),
	'convert_cyr_string' => array( 3=>function($args) { return convert_cyr_string($args[0], $args[1], $args[2]); } ),
	'convert_uudecode' => array( 1=>function($args) { return convert_uudecode($args[0]); } ),
	'convert_uuencode' => array( 1=>function($args) { return convert_uuencode($args[0]); } ),
	'count_chars' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>0 ),
		2 => function($args) { return count_chars($args[0], $args[1]); },
	),
	'crc32' => array( 1=>function($args) { return crc32($args[0]); } ),
	'crypt' => array(
		1=>function($args) { return crypt($args[0]); },
		2=>function($args) { return crypt($args[0], $args[1]); } ,
	),
// echo() in runtime.php
	'explode' => array(
		2=>function($args) { return explode($args[0], $args[1]); },
		3=>function($args) { return explode($args[0], $args[1], $args[2]); },
	),
// int fprintf ( resource $handle , string $format [, mixed $args [, mixed $... ]] ) it have not resources
	'get_html_translation_table' => array(
		FOXWAY_DEFAULT_VALUES=>array( 0=>HTML_SPECIALCHARS, 1=>ENT_COMPAT/*|ENT_HTML401*/, 2=>'UTF-8' ),
		3=>function($args) { return get_html_translation_table($args[0], $args[1], $args[2]); },
	),
	'hebrev' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>0 ),
		2=>function($args) { return hebrev($args[0], $args[1]); },
	),
	'hebrevc' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>0 ),
		2=>function($args) { return hebrevc($args[0], $args[1]); },
	),
// @todo PHP >= 5.4.0 	'hex2bin' => array( 1=>function($args) { return hex2bin($args[0]); } ),
	'html_entity_decode' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>ENT_COMPAT/*|ENT_HTML401*/, 2=>'UTF-8' ),
		3=>function($args) { return html_entity_decode($args[0], $args[1], $args[2]); },
	),
	'htmlentities' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>ENT_COMPAT/*|ENT_HTML401*/, 2=>'UTF-8', 3=>true ),
		4=>function($args) { return htmlentities($args[0], $args[1], $args[2], $args[3]); },
	),
	'htmlspecialchars_decode' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>ENT_COMPAT/*|ENT_HTML401*/ ),
		2=>function($args) { return htmlspecialchars_decode($args[0], $args[1]); },
	),
	'htmlspecialchars' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>ENT_COMPAT/*|ENT_HTML401*/, 2=>'UTF-8', 3=>true ),
		4=>function($args) { return htmlspecialchars($args[0], $args[1], $args[2], $args[3]); },
	),
	'implode' => array(
		1=>function($args) { return implode($args[0]); },
		2=>function($args) { return implode($args[0], $args[1]); },
	),
	'join' => array(
		1=>function($args) { return join($args[0]); },
		2=>function($args) { return join($args[0], $args[1]); },
	),
	'lcfirst' => array( 1=>function($args) { return lcfirst($args[0]); } ),
	'levenshtein' => array(
		2=>function($args) { return abs($args[0], $args[1]); },
		5=>function($args) { return abs($args[0], $args[1], $args[2], $args[3], $args[4]); },
	),
	'localeconv' => array( 0=>function() { return localeconv(); } ),
	'ltrim' => array(
		1=>function($args) { return ltrim($args[0]); },
		2=>function($args) { return ltrim($args[0], $args[1]); },
	),
// string md5_file ( string $filename [, bool $raw_output = false ] ) Can't use file
	'md5' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>false ),
		2=>function($args) { return md5($args[0], $args[1]); },
	),
	'metaphone' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>0 ),
		2=>function($args) { return metaphone($args[0], $args[1]); },
	),
	'money_format' => array( 2=>function($args) { return money_format($args[0]); } ),
	'nl_langinfo' => array( 1=>function($args) { return nl_langinfo($args[0]); } ),
	'nl2br' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>true ),
		2=>function($args) { return nl2br($args[0], $args[1]); },
	),
	'number_format' => array(
		1=>function($args) { return number_format($args[0]); },
		2=>function($args) { return number_format($args[0], $args[1]); },
		4=>function($args) { return number_format($args[0], $args[1], $args[2], $args[3]); },
	),
	'ord' => array( 1=>function($args) { return ord($args[0]); } ),
// void parse_str ( string $str [, array &$arr ] )  use variables
// print() in runtume.php
	'printf' => array(
		FOXWAY_MIN_VALUES=>1,
		''=>function($args) { ob_start(); call_user_func_array('printf', $args); return new Foxway\outPrint( null, ob_get_clean() ); },
	),
	'quoted_printable_decode' => array( 1=>function($args) { return quoted_printable_decode($args[0]); } ),
	'quoted_printable_encode' => array( 1=>function($args) { return quoted_printable_encode($args[0]); } ),
	'quotemeta' => array( 1=>function($args) { return quotemeta($args[0]); } ),
	'rtrim' => array(
		1=>function($args) { return rtrim($args[0]); },
		2=>function($args) { return rtrim($args[0], $args[1]); },
	),
// @todo make virtual string setlocale ( int $category , string $locale [, string $... ] )
// string sha1_file ( string $filename [, bool $raw_output = false ] ) file do not allowed
	'sha1' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>false ),
		2=>function($args) { return sha1($args[0], $args[1]); },
	),
	'similar_text' => array(
		2=>function($args) { return similar_text($args[0], $args[1]); },
		3=>function($args) { return similar_text($args[0], $args[1], $args[2]); },
	),
	'soundex' => array( 1=>function($args) { return soundex($args[0]); } ),
	'sprintf' => array(
		FOXWAY_MIN_VALUES=>1,
		''=>function($args) { return call_user_func_array('sprintf', $args); },
	),
	'sscanf' => array(
		FOXWAY_MIN_VALUES=>2,
		''=>function($args) { return call_user_func_array('sscanf', $args); },
	),
	'str_getcsv' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>',', 2=>'"', 3=>'\\' ),
		4=>function($args) { return str_getcsv($args[0], $args[1], $args[2], $args[3]); },
	),
	'str_ireplace' => array(
		2=>function($args) { return str_ireplace($args[0], $args[1]); },
		3=>function($args) { return str_ireplace($args[0], $args[1], $args[2]); },
	),
	'str_pad' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>' ', 3=>STR_PAD_RIGHT ),
		4=>function($args) { return str_pad($args[0], $args[1], $args[2], $args[3]); },
	),
	'str_repeat' => array( 2=>function($args) { return str_repeat($args[0], $args[1]); } ),
	'str_replace' => array(
		3=>function($args) { return str_replace($args[0], $args[1], $args[2]); },
		4=>function($args) { return str_replace($args[0], $args[1], $args[2], $args[3]); },
	),
	'str_rot13' => array( 1=>function($args) { return str_rot13($args[0]); } ),
	'str_shuffle' => array( 1=>function($args) { return str_shuffle($args[0]); } ),
	'str_split' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>1 ),
		2=>function($args) { return str_split($args[0], $args[1]); },
	),
	'str_word_count' => array(
		1=>function($args) { return str_word_count($args[0]); } ,
		2=>function($args) { return str_word_count($args[0], $args[1]); },
		3=>function($args) { return str_word_count($args[0], $args[1], $args[2]); },
	),
	'strcasecmp' => array( 2=>function($args) { return strcasecmp($args[0], $args[1]); } ),
	'strchr' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>false ),
		3=>function($args) { return strchr($args[0], $args[1], $args[2]); },
	),
	'strcmp' => array( 2=>function($args) { return strcmp($args[0], $args[1]); } ),
	'strcoll' => array( 2=>function($args) { return strcoll($args[0], $args[1]); } ),
	'strcspn' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>null, 3=>null ),
		4=>function($args) { return strcspn($args[0], $args[1], $args[2], $args[3]); },
	),
	'strip_tags' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>null ),
		2=>function($args) { return strip_tags($args[0], $args[1]); },
	),
	'stripcslashes' => array( 1=>function($args) { return stripcslashes($args[0]); } ),
	'stripos' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>0 ),
		3=>function($args) { return stripos($args[0], $args[1], $args[2]); },
	),
	'stripslashes' => array( 1=>function($args) { return stripslashes($args[0]); } ),
	'stristr' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>false ),
		3=>function($args) { return stristr($args[0], $args[1], $args[2]); },
	),
	'strlen' => array( 1=>function($args) { return strlen($args[0]); } ),
	'strnatcasecmp' => array( 2=>function($args) { return strnatcasecmp($args[0], $args[1]); } ),
	'strnatcmp' => array( 2=>function($args) { return strnatcmp($args[0], $args[1]); } ),
	'strncasecmp' => array( 3=>function($args) { return strncasecmp($args[0], $args[1], $args[2]); } ),
	'strncmp' => array( 3=>function($args) { return strncmp($args[0], $args[1], $args[2]); } ),
	'strpbrk' => array( 2=>function($args) { return strpbrk($args[0], $args[1]); } ),
	'strpos' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>0 ),
		3=>function($args) { return strpos($args[0], $args[1], $args[2]); },
	),
	'strrchr' => array( 2=>function($args) { return strrchr($args[0], $args[1]); } ),
	'strrev' => array( 1=>function($args) { return strrev($args[0]); } ),
	'strripos' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>0 ),
		3=>function($args) { return strripos($args[0], $args[1], $args[2]); },
	),
	'strrpos' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>0 ),
		3=>function($args) { return strrpos($args[0], $args[1], $args[2]); },
	),
	'strspn' => array(
		2=>function($args) { return strspn($args[0], $args[1]); },
		3=>function($args) { return strspn($args[0], $args[1], $args[2]); },
		4=>function($args) { return strspn($args[0], $args[1], $args[2], $args[3]); },
	),
	'strstr' => array(
		FOXWAY_DEFAULT_VALUES=>array( 2=>0 ),
		3=>function($args) { return strstr($args[0], $args[1], $args[2]); },
	),
	'strtok' => array(
		1=>function($args) { return strtok($args[0]); },
		2=>function($args) { return strtok($args[0], $args[1]); },
	),
	'strtolower' => array( 1=>function($args) { return strtolower($args[0]); } ),
	'strtoupper' => array( 1=>function($args) { return strtoupper($args[0]); } ),
	'strtr' => array(
		2=>function($args) { return strtr($args[0], $args[1]); },
		3=>function($args) { return strtr($args[0], $args[1], $args[2]); },
	),
	'substr_compare' => array(
		3=>function($args) { return substr_compare($args[0], $args[1], $args[2]); },
		4=>function($args) { return substr_compare($args[0], $args[1], $args[2], $args[3]); },
		5=>function($args) { return substr_compare($args[0], $args[1], $args[2], $args[3], $args[4]); },
	),
	'substr_count' => array(
		2=>function($args) { return substr_count($args[0], $args[1]); },
		3=>function($args) { return substr_count($args[0], $args[1], $args[2]); },
		4=>function($args) { return substr_count($args[0], $args[1], $args[2], $args[3]); },
	),
	'substr_replace' => array(
		3=>function($args) { return substr_replace($args[0], $args[1], $args[2]); },
		4=>function($args) { return substr_replace($args[0], $args[1], $args[2], $args[3]); },
	),
	'substr' => array(
		2=>function($args) { return substr($args[0], $args[1]); },
		3=>function($args) { return substr($args[0], $args[1], $args[2]); },
	),
	'trim' => array(
		1=>function($args) { return trim($args[0]); },
		2=>function($args) { return trim($args[0], $args[1]); },
	),
	'ucfirst' => array( 1=>function($args) { return ucfirst($args[0]); } ),
	'ucwords' => array( 1=>function($args) { return ucwords($args[0]); } ),
// int vfprintf ( resource $handle , string $format , array $args ) it have not resources
	'vprintf' => array( 2=>function($args) { ob_start(); $r = vprintf($args[0], $args[1]); return new Foxway\outPrint( $r, ob_get_clean() ); } ),
	'vsprintf' => array( 2=>function($args) { return vsprintf($args[0], $args[1]); } ),
	'wordwrap' => array(
		FOXWAY_DEFAULT_VALUES=>array( 1=>75, 2=>"\n", 3=>false ),
		4=>function($args) { return wordwrap($args[0], $args[1], $args[2], $args[3]); },
	),
);
