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

define( 'PHPTAGS_MAJOR_VERSION' , 1 );
define( 'PHPTAGS_MINOR_VERSION' , 0 );
define( 'PHPTAGS_RELEASE_VERSION' , 2 );
define( 'PHPTAGS_VERSION' , PHPTAGS_MAJOR_VERSION . '.' . PHPTAGS_MINOR_VERSION . '.' . PHPTAGS_RELEASE_VERSION );

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
	\PhpTags\Runtime::setConstantsValue(
			array(
				'PHPTAGS_MAJOR_VERSION' => PHPTAGS_MAJOR_VERSION,
				'PHPTAGS_MINOR_VERSION' => PHPTAGS_MINOR_VERSION,
				'PHPTAGS_RELEASE_VERSION' => PHPTAGS_RELEASE_VERSION,
				'PHPTAGS_VERSION' => PHPTAGS_VERSION,
			)
		);
	return true;
};

/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserLimitReport'][] = function( $parser, &$limitReport ) {
	if ( \PhpTags\Runtime::$time > 0 ) {
		$limitReport .= sprintf( "PhpTags time usage: %.3f secs\n", \PhpTags\Runtime::$time );
	}
	return true;
};

// Preparing classes for autoloading
$wgAutoloadClasses['PhpTags']					= __DIR__ . '/PhpTags.body.php';

$wgAutoloadClasses['PhpTags\\iRawOutput']		= __DIR__ . '/includes/iRawOutput.php';
$wgAutoloadClasses['PhpTags\\outPrint']			= __DIR__ . '/includes/outPrint.php';
$wgAutoloadClasses['PhpTags\\ExceptionPhpTags']	= __DIR__ . '/includes/ExceptionPhpTags.php';
$wgAutoloadClasses['PhpTags\\Compiler']			= __DIR__ . '/includes/Compiler.php';
$wgAutoloadClasses['PhpTags\\Runtime']			= __DIR__ . '/includes/Runtime.php';
$wgAutoloadClasses['PhpTags\\BaseHooks']		= __DIR__ . '/includes/BaseHooks.php';

/**
 * Add files to phpunit test
 * @codeCoverageIgnore
 */
$wgHooks['UnitTestsList'][] = function ( &$files ) {
	$testDir = __DIR__ . '/tests/phpunit';
	$files = array_merge( $files, glob( "$testDir/includes/*Test.php" ) );
	return true;
};

define( 'PHPTAGS_GROUP_EXPENSIVE', 1 );

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
