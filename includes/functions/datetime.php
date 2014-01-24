<?php
/**
 * Date/Time Functions
 * @see http://www.php.net/manual/en/ref.datetime.php
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

return array(
	'checkdate' => array( 3=>function($args) { return checkdate($args[0], $args[1], $args[2]); } ),
	'date_parse_from_format' => array( 2=>function($args) { return date_parse_from_format($args[0], $args[1]); } ),
	'date_parse' => array( 1=>function($args) { return date_parse($args[0]); } ),
	'date_sun_info' => array( 3=>function($args) { return date_sun_info($args[0], $args[1], $args[2]); } ),
// @todo mixed date_sunrise ( int $timestamp [, int $format = SUNFUNCS_RET_STRING [, float $latitude = ini_get("date.default_latitude") [, float $longitude = ini_get("date.default_longitude") [, float $zenith = ini_get("date.sunrise_zenith") [, float $gmt_offset = 0 ]]]]] )
// @todo mixed date_sunset ( int $timestamp [, int $format = SUNFUNCS_RET_STRING [, float $latitude = ini_get("date.default_latitude") [, float $longitude = ini_get("date.default_longitude") [, float $zenith = ini_get("date.sunset_zenith") [, float $gmt_offset = 0 ]]]]] )
	'date' => array(
		1=>function($args) { return date($args[0]); },
		2=>function($args) { return date($args[0], $args[1]); },
	),
	'getdate' => array(
		0=>function() { return getdate(); },
		1=>function($args) { return getdate($args[0]); },
	),
	'gettimeofday' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 0=>false ),
		1=>function($args) { return gettimeofday($args[0]); },
	),
	'gmdate' => array(
		1=>function($args) { return gmdate($args[0]); },
		2=>function($args) { return gmdate($args[0], $args[1]); },
	),
	'idate' => array(
		1=>function($args) { return idate($args[0]); },
		2=>function($args) { return idate($args[0], $args[1]); },
	),
	'localtime' => array(
		0=> function() { return localtime(); },
		1=>function($args) { return localtime($args[0]); },
		2=>function($args) { return localtime($args[0], $args[1]); },
	),
	'microtime' => array(
		PHPTAGS_DEFAULT_VALUES=>array( 0=>false ),
		1=>function($args) { return microtime($args[0]); },
	),
	'mktime' => array(
		0=> function() { return time(); },
		1=>function($args) { return mktime($args[0]); },
		2=>function($args) { return mktime($args[0], $args[1]); },
		3=>function($args) { return mktime($args[0], $args[1], $args[2]); },
		4=>function($args) { return mktime($args[0], $args[1], $args[2], $args[3]); },
		5=>function($args) { return mktime($args[0], $args[1], $args[2], $args[3], $args[4]); },
		6=>function($args) { return mktime($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]); },
		7=>function($args) { return mktime($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]); },
	),
	'strtotime' => array(
		1=>function($args) { return strtotime($args[0]); },
		2=>function($args) { return strtotime($args[0], $args[1]); },
	),
	'time' => array( 0=>function() { return time(); } ),
);
