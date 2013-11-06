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

Foxway\Runtime::$functions = array_merge(
		include __DIR__ . '/includes/functions/strings.php', // String Functions @see http://php.net/manual/en/ref.strings.php
		include __DIR__ . '/includes/functions/array.php', // Array Functions @see http://www.php.net/manual/en/ref.array.php
		include __DIR__ . '/includes/functions/math.php', // Math Functions @see http://www.php.net/manual/en/ref.math.php
		include __DIR__ . '/includes/functions/var.php', // Variable handling Functions @see http://www.php.net/manual/en/ref.var.php
		include __DIR__ . '/includes/functions/pcre.php', // PCRE Functions @see http://www.php.net/manual/en/ref.pcre.php
		Foxway\Runtime::$functions
);


Foxway\Runtime::$constants = array_merge(
		include __DIR__ . '/includes/constants.php',
		Foxway\Runtime::$constants
);