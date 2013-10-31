<?php
/**
 * File defining the settings for the Foxway extension.
 * More info can be found at https://www.mediawiki.org/wiki/Extension:Foxway
 *
 *						  NOTICE:
 * Changing one of these settings can be done by copieng or cutting it,
 * and placing it in LocalSettings.php, AFTER the inclusion of Foxway.
 *
 * @file Settings.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

// Default settings
$wgFoxway_max_execution_time = 2;
$wgFoxway_max_execution_time_for_scope = 0.5;

/**
 * You can specify the namespaces in which is allowed to use the extension Foxway.
 *
 * Thus it is possible to give permission to use this extension is just a special user group, example:
 * define("NS_PHP", 1000);
 * define("NS_PHP_TALK", 1001);
 * $wgExtraNamespaces[NS_PHP] = "PHP";
 * $wgExtraNamespaces[NS_PHP_TALK] = "PHP_Talk";
 *
 * \Foxway\Runtime::$allowedNamespaces = array( NS_PHP );
 * $wgNamespaceProtection[NS_PHP] = array( 'php_editor' );
 * $wgGroupPermissions['sysop']['php_editor'] = true;
 *
 * @var array Namespaces in which is allowed to use the extension Foxway, boolean 'true' for unlimited
 */
// Foxway\Runtime::$allowedNamespaces = true; // true by default

Foxway\Runtime::$functions = array_merge( Foxway\Runtime::$functions
		, include __DIR__ . '/includes/functions/strings.php' // String Functions @see http://php.net/manual/en/ref.strings.php
		, include __DIR__ . '/includes/functions/array.php' // Array Functions @see http://www.php.net/manual/en/ref.array.php
		, include __DIR__ . '/includes/functions/math.php' // Math Functions @see http://www.php.net/manual/en/ref.math.php
		, include __DIR__ . '/includes/functions/var.php' // Variable handling Functions @see http://www.php.net/manual/en/ref.var.php
		, include __DIR__ . '/includes/functions/pcre.php' // PCRE Functions @see http://www.php.net/manual/en/ref.pcre.php
);

$wgFoxwayAllowedPHPConstants = array(
	'CASE_UPPER',
	'CASE_LOWER',
	'SORT_ASC',
	'SORT_DESC',
	'SORT_REGULAR',
	'SORT_NUMERIC',
	'SORT_STRING',
	'SORT_LOCALE_STRING',
	'SORT_NATURAL', // PHP >= 5.4.0
	'SORT_FLAG_CASE', // PHP >= 5.4.0
	'COUNT_RECURSIVE',
	'CRYPT_STD_DES',
	'CRYPT_EXT_DES',
	'CRYPT_MD5',
	'CRYPT_BLOWFISH',
	'CRYPT_SHA256',
	'CRYPT_SHA512',
	'ENT_COMPAT',
	'ENT_QUOTES',
	'ENT_NOQUOTES',
	'ENT_HTML401', // PHP >= 5.4.0
	'ENT_XML1', // PHP >= 5.4.0
	'ENT_XHTML', // PHP >= 5.4.0
	'ENT_HTML5', // PHP >= 5.4.0
	'ENT_IGNORE',
	'ENT_SUBSTITUTE', // PHP >= 5.4.0
	'ENT_DISALLOWED', // PHP >= 5.4.0
	'STR_PAD_RIGHT',
	'STR_PAD_LEFT',
	'STR_PAD_BOTH',
	'ABDAY_1',
	'ABDAY_2',
	'ABDAY_3',
	'ABDAY_4',
	'ABDAY_5',
	'ABDAY_6',
	'ABDAY_7',
	'DAY_1',
	'DAY_2',
	'DAY_3',
	'DAY_4',
	'DAY_5',
	'DAY_6',
	'DAY_7',
	'ABMON_1',
	'ABMON_2',
	'ABMON_3',
	'ABMON_4',
	'ABMON_5',
	'ABMON_6',
	'ABMON_7',
	'ABMON_8',
	'ABMON_9',
	'ABMON_10',
	'ABMON_11',
	'ABMON_12',
	'MON_1',
	'MON_2',
	'MON_3',
	'MON_4',
	'MON_5',
	'MON_6',
	'MON_7',
	'MON_8',
	'MON_9',
	'MON_10',
	'MON_11',
	'MON_12',
	'AM_STR',
	'PM_STR',
	'D_T_FMT',
	'D_FMT',
	'T_FMT',
	'T_FMT_AMPM',
	'ERA',
	'ERA_YEAR',
	'ERA_D_T_FMT',
	'ERA_D_FMT',
	'ERA_T_FMT',
	'INT_CURR_SYMBOL',
	'CURRENCY_SYMBOL',
	'CRNCYSTR',
	'MON_DECIMAL_POINT',
	'MON_THOUSANDS_SEP',
	'MON_GROUPING',
	'POSITIVE_SIGN',
	'NEGATIVE_SIGN',
	'INT_FRAC_DIGITS',
	'FRAC_DIGITS',
	'P_CS_PRECEDES',
	'P_SEP_BY_SPACE',
	'N_CS_PRECEDES',
	'N_SEP_BY_SPACE',
	'P_SIGN_POSN',
	'N_SIGN_POSN',
	'DECIMAL_POINT',
	'RADIXCHAR',
	'THOUSANDS_SEP',
	'THOUSEP',
	'GROUPING',
	'YESEXPR',
	'NOEXPR',
	'YESSTR',
	'NOSTR',
	'CODESET',
	// @see http://www.php.net/manual/en/pcre.constants.php
	'PREG_PATTERN_ORDER',
	'PREG_SET_ORDER',
	'PREG_OFFSET_CAPTURE',
	'PREG_SPLIT_NO_EMPTY',
	'PREG_SPLIT_DELIM_CAPTURE',
	'PREG_SPLIT_OFFSET_CAPTURE',
	'PREG_NO_ERROR',
	'PREG_INTERNAL_ERROR',
	'PREG_BACKTRACK_LIMIT_ERROR',
	'PREG_RECURSION_LIMIT_ERROR',
	'PREG_BAD_UTF8_ERROR',
	'PREG_BAD_UTF8_OFFSET_ERROR',
	'PCRE_VERSION',
	'PREG_GREP_INVERT',
	// @see http://www.php.net/manual/en/math.constants.php
	'M_PI',
	'M_E',
	'M_LOG2E',
	'M_LOG10E',
	'M_LN2',
	'M_LN10',
	'M_PI_2',
	'M_PI_4',
	'M_1_PI',
	'M_2_PI',
	'M_SQRTPI',
	'M_2_SQRTPI',
	'M_SQRT2',
	'M_SQRT3',
	'M_SQRT1_2',
	'M_LNPI',
	'M_EULER',
	'PHP_ROUND_HALF_UP',
	'PHP_ROUND_HALF_DOWN',
	'PHP_ROUND_HALF_EVEN',
	'PHP_ROUND_HALF_ODD',
	'NAN',
	'INF',
	'PHP_INT_MAX',
	'PHP_INT_SIZE',
);
