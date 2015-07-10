<?php
/**
 * Main entry point for the PhpTags extension.
 *
 * @link https://www.mediawiki.org/wiki/Extension:PhpTags Documentation
 * @file PhpTags.php
 * @defgroup PhpTags
 * @ingroup Extensions
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

const PHPTAGS_MAJOR_VERSION = 5;
const PHPTAGS_MINOR_VERSION = 1;
const PHPTAGS_RELEASE_VERSION = 4;
define( 'PHPTAGS_VERSION', PHPTAGS_MAJOR_VERSION . '.' . PHPTAGS_MINOR_VERSION . '.' . PHPTAGS_RELEASE_VERSION );

const PHPTAGS_HOOK_RELEASE = 8;
const PHPTAGS_RUNTIME_RELEASE = 5;
const PHPTAGS_JSONLOADER_RELEASE = 3;

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'PhpTags',
	'version'			=> PHPTAGS_VERSION,
	'url'				=> 'https://www.mediawiki.org/wiki/Extension:PhpTags',
	'author'			=> '[https://www.mediawiki.org/wiki/User:Pastakhov Pavel Astakhov]',
	'descriptionmsg'	=> 'phptags-desc',
	'license-name'		=> 'GPL-2.0+',
);

// Allow translations for this extension
$wgMessagesDirs['PhpTags'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['PhpTagsMagic'] = __DIR__ . '/PhpTags.i18n.magic.php';

//
$wgPhpTagsLimitReport = false;
$wgPhpTagsCounter = 0;

// Preparing classes for autoloading
$wgAutoloadClasses['PhpTagsHooks'] = __DIR__ . '/PhpTags.hooks.php';
$wgAutoloadClasses['PhpTags\\Renderer'] = __DIR__ . '/includes/Renderer.php';
$wgAutoloadClasses['PhpTags\\Timer'] = __DIR__ . '/includes/Renderer.php';
$wgAutoloadClasses['PhpTags\\iRawOutput'] = __DIR__ . '/includes/iRawOutput.php';
$wgAutoloadClasses['PhpTags\\outPrint'] = __DIR__ . '/includes/outPrint.php';
$wgAutoloadClasses['PhpTags\\ErrorHandler'] = __DIR__ . '/includes/ErrorHandler.php';
$wgAutoloadClasses['PhpTags\\PhpTagsException'] = __DIR__ . '/includes/PhpTagsException.php';
$wgAutoloadClasses['PhpTags\\HookException'] = __DIR__ . '/includes/HookException.php';
$wgAutoloadClasses['PhpTags\\Compiler'] = __DIR__ . '/includes/Compiler.php';
$wgAutoloadClasses['PhpTags\\Runtime'] = __DIR__ . '/includes/Runtime.php';
$wgAutoloadClasses['PhpTags\\GenericObject'] = __DIR__ . '/includes/GenericObject.php';
$wgAutoloadClasses['PhpTags\\Hooks'] = __DIR__ . '/includes/Hooks.php';
$wgAutoloadClasses['PhpTags\\JsonLoader'] = __DIR__ . '/includes/JsonLoader.php';

// Add tracking categories
$wgTrackingCategories[] = 'phptags-compiler-error-category';
$wgTrackingCategories[] = 'phptags-runtime-error-category';

//
$wgHooks['ParserFirstCallInit'][] = 'PhpTagsHooks::onParserFirstCallInit';
$wgHooks['PhpTagsRuntimeFirstInit'][] = 'PhpTagsHooks::onPhpTagsRuntimeFirstInit';
$wgHooks['CodeMirrorGetAdditionalResources'][] = 'PhpTagsHooks::onCodeMirrorGetAdditionalResources';
$wgHooks['ParserLimitReport'][] = 'PhpTagsHooks::onParserLimitReport';
$wgHooks['ParserAfterTidy'][] = 'PhpTagsHooks::onParserAfterTidy';
$wgHooks['ExtensionTypes'][] = 'PhpTagsHooks::onExtensionTypes';
$wgHooks['UnitTestsList'][] = 'PhpTagsHooks::onUnitTestsList';

// add parser tests
$wgParserTestFiles[] = __DIR__ . '/tests/parser/PhpTagsTests.txt';

/**
 * You can specify the namespaces in which allowed to use this extension.
 *
 * Thus it is possible to give permission to use this extension only for a special user group, example:
 * define("NS_PHPTAGS", 1000);
 * define("NS_PHPTAGS_TALK", 1001);
 * $wgExtraNamespaces[NS_PHPTAGS] = "PhpTags";
 * $wgExtraNamespaces[NS_PHPTAGS_TALK] = "PhpTags_Talk";
 *
 * $wgPhpTagsNamespaces = array( NS_PHPTAGS => true );
 * $wgNamespaceProtection[NS_PHPTAGS] = array( 'phptags_editor' );
 * $wgGroupPermissions['sysop']['phptags_editor'] = true;
 *
 * @var mixed $wgPhpTagsNamespaces Array of namespaces in which allowed to use the extension PhpTags, and if boolean 'true' then it is unlimited namespaces
 */
$wgPhpTagsNamespaces = true; // By default, this is unlimited namespaces

/**
 * Maximum number of allowed loops
 */
$wgPhpTagsMaxLoops = 1000;

/**
 * Storage time of the compiled bytecode at cache
 * By default it is 30 days
 */
$wgPhpTagsBytecodeExptime = 2592000;
