<?php
/**
 * @todo description
 *
 * @link https://www.mediawiki.org/wiki/Extension:PHP_Tags Documentation
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

define( 'PHP_TAGS_VERSION' , '1.0.0' );

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'PHP Tags',
	'version'			=> PHP_TAGS_VERSION,
	'url'				=> 'https://www.mediawiki.org/wiki/Extension:PHP_Tags',
	'author'			=> '[https://www.mediawiki.org/wiki/User:Pastakhov Pavel Astakhov]',
	'descriptionmsg'	=> 'php-tags-desc'
);

// Tell the whereabouts of files
$dir = __DIR__;

// Allow translations for this extension
$wgExtensionMessagesFiles['PhpTags'] =		$dir . '/PhpTags.i18n.php';
$wgExtensionMessagesFiles['PhpTagsMagic'] =	$dir . '/PhpTags.i18n.magic.php';

// Include the settings file.
//require_once $dir . '/Settings.php';

// Specify the function that will initialize the parser function.
/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserFirstCallInit'][] = function( Parser &$parser ) {
   $parser->setFunctionHook( 'php', 'PhpTags::renderFunction', SFH_OBJECT_ARGS );
   $parser->setHook( 'php', 'PhpTags::render' );
   return true;
};

/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserLimitReport'][] = function( $parser, &$limitReport ) {
	if( \PhpTags\Runtime::$time > 0 ) {
		$limitReport .= sprintf( "PHP Tags time usage: %.3f secs\n", \PhpTags\Runtime::$time );
	}
	return true;
};

// Preparing classes for autoloading
$wgAutoloadClasses['PhpTags']					= $dir . '/PhpTags.body.php';

$wgAutoloadClasses['PhpTags\\iRawOutput']		= $dir . '/includes/iRawOutput.php';
$wgAutoloadClasses['PhpTags\\outPrint']			= $dir . '/includes/outPrint.php';
$wgAutoloadClasses['PhpTags\\ExceptionPhpTags']	= $dir . '/includes/ExceptionPhpTags.php';
$wgAutoloadClasses['PhpTags\\Compiler']			= $dir . '/includes/Compiler.php';
$wgAutoloadClasses['PhpTags\\Runtime']			= $dir . '/includes/Runtime.php';

/**
 * Add files to phpunit test
 * @codeCoverageIgnore
 */
$wgHooks['UnitTestsList'][] = function ( &$files ) {
		$testDir = __DIR__ . '/tests/phpunit';
		$files = array_merge( $files, glob( "$testDir/includes/*Test.php" ) );
		return true;
};
