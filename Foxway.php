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

define( 'FOXWAY_VERSION' , '1.0.0' );
define( 'FOXWAY_FUNCTION' , 1 );

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'Foxway',
	'version'			=> FOXWAY_VERSION,
	'url'				=> 'https://www.mediawiki.org/wiki/Extension:Foxway',
	'author'			=> '[https://www.mediawiki.org/wiki/User:Pastakhov Pavel Astakhov]',
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
   $parser->setFunctionHook( 'foxway', 'Foxway::renderFunction', SFH_OBJECT_ARGS );
   $parser->setHook( 'foxway', 'Foxway::render' );
   return true;
};

/**
 * @codeCoverageIgnore
 */
$wgHooks['ParserLimitReport'][] = function( $parser, &$limitReport ) {
	if( \Foxway\Runtime::$time > 0 ) {
		$limitReport .= sprintf( "Foxway time usage: %.3f secs\n", \Foxway\Runtime::$time );
	}
	return true;
};

// Preparing classes for autoloading
$wgAutoloadClasses['Foxway']					= $dir . '/Foxway.body.php';

$wgAutoloadClasses['Foxway\\iRawOutput']		= $dir . '/includes/iRawOutput.php';
$wgAutoloadClasses['Foxway\\outPrint']			= $dir . '/includes/outPrint.php';

//$wgAutoloadClasses['Foxway\\Debug']				= $dir . '/includes/Debug.php';
//$wgAutoloadClasses['Foxway\\ErrorMessage']		= $dir . '/includes/ErrorMessage.php';
$wgAutoloadClasses['Foxway\\ExceptionFoxway']	= $dir . '/includes/ExceptionFoxway.php';
$wgAutoloadClasses['Foxway\\Compiler']			= $dir . '/includes/Compiler.php';
$wgAutoloadClasses['Foxway\\Runtime']			= $dir . '/includes/Runtime.php';
//$wgAutoloadClasses['Foxway\\RuntimeDebug']		= $dir . '/includes/RuntimeDebug.php';

// Resources
$wgResourceModules['ext.Foxway.Debug'] = array(
	'styles' => 'resources/ext.foxway.debug.css',
	'scripts' => 'resources/ext.foxway.debug.js',
	'dependencies' => 'jquery.tipsy',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Foxway'
);

$wgResourceModules['ext.Foxway.DebugLoops'] = array(
	'scripts' => 'resources/ext.foxway.debugloops.js',
	'dependencies' => 'jquery',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Foxway'
);

/**
 * Add files to phpunit test
 * @codeCoverageIgnore
 */
$wgHooks['UnitTestsList'][] = function ( &$files ) {
		$testDir = __DIR__ . '/tests/phpunit';
		$files = array_merge( $files, glob( "$testDir/includes/*Test.php" ) );
		return true;
};
