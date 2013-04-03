<?php
/**
 * Foxway - An extension that allows to store an object-oriented data and implements its own runtime for php code on wikipage
 *
 * @link https://www.mediawiki.org/wiki/Extension:Foxway Documentation
 * @file Foxway.php
 * @defgroup Foxway
 * @ingroup Extensions
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */

// Check to see if we are being called as an extension or directly
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is an extension to MediaWiki and thus not a valid entry point.' );
}

define( 'Foxway_VERSION' , '0.0.3' );

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'Foxway',
	'version'			=> Foxway_VERSION,
	'url'				=> 'https://www.mediawiki.org/wiki/Extension:Foxway',
	'author'			=> array( '[[mw:User:Pastakhov|Pavel Astakhov]]' ),
	'descriptionmsg'	=> 'foxway-desc'
);

// Tell the whereabouts of files
$dir = __DIR__;

// Allow translations for this extension
$wgExtensionMessagesFiles['Foxway'] =		$dir . '/Foxway.i18n.php';
$wgExtensionMessagesFiles['FoxwayMagic'] =	$dir . '/Foxway.i18n.magic.php';

// Include the settings file.
//require_once $dir . '/Settings.php';

// Specify the function that will initialize the parser function.
/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserFirstCallInit'][] = function( Parser &$parser ) {
   //$parser->setFunctionHook( 'foxway', 'Foxway::renderParserFunction' );
   $parser->setHook( 'foxway', 'Foxway::render' );
   return true;
};

//Preparing classes for autoloading
$wgAutoloadClasses['Foxway']				= $dir . '/Foxway.body.php';

$wgAutoloadClasses['Foxway\\Interpreter']	= $dir . '/includes/Interpreter.php';
//$wgAutoloadClasses['Foxway\\ORM']			= $dir . '/includes/ORM.php';
$wgAutoloadClasses['Foxway\\Runtime']		= $dir . '/includes/Runtime.php';

//$wgAutoloadClasses['Foxway\\BaseValue']		= $dir . '/includes/ORMValues/BaseValue.php';
//$wgAutoloadClasses['Foxway\\ValueNumber']	= $dir . '/includes/ORMValues/ValueNumber.php';

/******** DB UPDATING ********/
/**
 * Schema changes
 * @codeCoverageIgnore
 */
$wgHooks['LoadExtensionSchemaUpdates'][] = function( DatabaseUpdater $updater ) {
	$dirsql = __DIR__ . '/sql';

	//$updater->addExtensionTable( 'foxway_properties', "$dirsql/foxway_properties.sql" );
	//$updater->addExtensionTable( 'foxway_links', "$dirsql/foxway_links.sql" );

	return true;
};

/**
 * Add files to phpunit test
 * @codeCoverageIgnore
 */
$wgHooks['UnitTestsList'][] = function ( &$files ) {
		$testDir = __DIR__ . '/tests/phpunit';
		$files = array_merge( $files, glob( "$testDir/includes/*Test.php" ) );
		return true;
};