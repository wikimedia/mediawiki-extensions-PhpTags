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

define( 'PHPTAGS_MAJOR_VERSION', 2 );
define( 'PHPTAGS_MINOR_VERSION', 8 );
define( 'PHPTAGS_RELEASE_VERSION', 0 );
define( 'PHPTAGS_VERSION', PHPTAGS_MAJOR_VERSION . '.' . PHPTAGS_MINOR_VERSION . '.' . PHPTAGS_RELEASE_VERSION );

define( 'PHPTAGS_HOOK_RELEASE', 4 );
define( 'PHPTAGS_RUNTIME_RELEASE', 1 );

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'PhpTags',
	'version'			=> PHPTAGS_VERSION,
	'url'				=> 'https://www.mediawiki.org/wiki/Extension:PhpTags',
	'author'			=> '[https://www.mediawiki.org/wiki/User:Pastakhov Pavel Astakhov]',
	'descriptionmsg'	=> 'phptags-desc'
);

// Allow translations for this extension
$wgMessagesDirs['PhpTags'] =				__DIR__ . '/i18n';
$wgExtensionMessagesFiles['PhpTags'] =		__DIR__ . '/PhpTags.i18n.php';
$wgExtensionMessagesFiles['PhpTagsMagic'] =	__DIR__ . '/PhpTags.i18n.magic.php';

// Specify the function that will initialize the parser function.
/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserFirstCallInit'][] = function( Parser &$parser ) {
	$parser->setFunctionHook( 'phptag', 'PhpTags::renderFunction', SFH_OBJECT_ARGS );
	$parser->setHook( 'phptag', 'PhpTags::render' );
	return true;
};
$wgHooks['PhpTagsRuntimeFirstInit'][] = function() {
	\PhpTags\Hooks::setConstantValues(
			array(
				'PHPTAGS_MAJOR_VERSION' => PHPTAGS_MAJOR_VERSION,
				'PHPTAGS_MINOR_VERSION' => PHPTAGS_MINOR_VERSION,
				'PHPTAGS_RELEASE_VERSION' => PHPTAGS_RELEASE_VERSION,
				'PHPTAGS_VERSION' => PHPTAGS_VERSION,
			)
		);
	return true;
};

$wgPhpTagsTime = 0;
/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserLimitReport'][] = function( $parser, &$limitReport ) {
	global $wgPhpTagsTime;
			if ( $wgPhpTagsTime != 0 ) {
		$limitReport .= sprintf( "PhpTags time usage: %.3f sec\n          Compiler: %.3f sec\n           Runtime: %.3f sec\n", $wgPhpTagsTime, PhpTags::$compileTime, $wgPhpTagsTime-PhpTags::$compileTime );
	}
	return true;
};

// Preparing classes for autoloading
$wgAutoloadClasses['PhpTags']					= __DIR__ . '/PhpTags.body.php';

$wgAutoloadClasses['PhpTags\\iRawOutput']		= __DIR__ . '/includes/iRawOutput.php';
$wgAutoloadClasses['PhpTags\\outPrint']			= __DIR__ . '/includes/outPrint.php';
$wgAutoloadClasses['PhpTags\\ErrorHandler']		= __DIR__ . '/includes/ErrorHandler.php';
$wgAutoloadClasses['PhpTags\\PhpTagsException']	= __DIR__ . '/includes/PhpTagsException.php';
$wgAutoloadClasses['PhpTags\\Compiler']			= __DIR__ . '/includes/Compiler.php';
$wgAutoloadClasses['PhpTags\\Runtime']			= __DIR__ . '/includes/Runtime.php';
$wgAutoloadClasses['PhpTags\\GenericObject']	= __DIR__ . '/includes/GenericObject.php';
$wgAutoloadClasses['PhpTags\\GenericFunction']	= __DIR__ . '/includes/GenericFunction.php';
$wgAutoloadClasses['PhpTags\\Hooks']			= __DIR__ . '/includes/Hooks.php';

/**
 * Add files to phpunit test
 * @codeCoverageIgnore
 */
$wgHooks['UnitTestsList'][] = function ( &$files ) {
	$testDir = __DIR__ . '/tests/phpunit';
	$files = array_merge( $files, glob( "$testDir/includes/*Test.php" ) );
	return true;
};

$wgParserTestFiles[] = __DIR__ . '/tests/parser/PhpTagsTests.txt';

define( 'PHPTAGS_TRANSIT_VARIABLES', 'v' );
define( 'PHPTAGS_TRANSIT_PARSER', 'p' );
define( 'PHPTAGS_TRANSIT_PPFRAME', 'f' );
define( 'PHPTAGS_TRANSIT_EXCEPTION', '@' );

/**
 * You can specify the namespaces in which allowed to use this extension.
 *
 * Thus it is possible to give permission to use this extension only for a special user group, example:
 * define("NS_PHPTAGS", 1000);
 * define("NS_PHPTAGS_TALK", 1001);
 * $wgExtraNamespaces[NS_PHPTAGS] = "PhpTags";
 * $wgExtraNamespaces[NS_PHPTAGS_TALK] = "PhpTags_Talk";
 *
 * $wgPhpTagsNamespaces = array( NS_PHPTAGS );
 * $wgNamespaceProtection[NS_PHPTAGS] = array( 'phptags_editor' );
 * $wgGroupPermissions['sysop']['phptags_editor'] = true;
 *
 * @var mixed Namespaces Array of namespaces in which allowed to use the extension PhpTags, and if boolean 'true' then it is unlimited namespaces
 */
$wgPhpTagsNamespaces = true; // By default, this is unlimited namespaces

/**
 * Maximum number of allowed loops
 */
$wgPhpTagsMaxLoops = 1000;
