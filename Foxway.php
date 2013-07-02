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

define( 'Foxway_VERSION' , '0.5.0' );

// Register this extension on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'Foxway',
	'version'			=> Foxway_VERSION,
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
	if( Foxway::$time !== false ) {
		$limitReport .= sprintf( "Foxway time usage: %.3f secs\n", Foxway::$time );
		# print_r(Foxway\Runtime::getTime(),true);
	}
	return true;
};

// Preparing classes for autoloading
$wgAutoloadClasses['Foxway']					= $dir . '/Foxway.body.php';

$wgAutoloadClasses['Foxway\\iRawOutput']		= $dir . '/includes/iRawOutput.php';

$wgAutoloadClasses['Foxway\\Debug']				= $dir . '/includes/Debug.php';
$wgAutoloadClasses['Foxway\\ErrorMessage']		= $dir . '/includes/ErrorMessage.php';
$wgAutoloadClasses['Foxway\\Interpreter']		= $dir . '/includes/Interpreter.php';
$wgAutoloadClasses['Foxway\\RArray']			= $dir . '/includes/RArray.php';
$wgAutoloadClasses['Foxway\\ROutput']			= $dir . '/includes/ROutput.php';
$wgAutoloadClasses['Foxway\\RValue']			= $dir . '/includes/RValue.php';
$wgAutoloadClasses['Foxway\\RVariable']			= $dir . '/includes/RVariable.php';
$wgAutoloadClasses['Foxway\\Runtime']			= $dir . '/includes/Runtime.php';
$wgAutoloadClasses['Foxway\\RuntimeDebug']		= $dir . '/includes/RuntimeDebug.php';

$wgAutoloadClasses['Foxway\\BaseFunction']		= $dir . '/includes/functions/BaseFunction.php';
$wgAutoloadClasses['Foxway\\FArray']			= $dir . '/includes/functions/FArray.php';
$wgAutoloadClasses['Foxway\\FMath']				= $dir . '/includes/functions/FMath.php';
$wgAutoloadClasses['Foxway\\Fpcre']				= $dir . '/includes/functions/Fpcre.php';
$wgAutoloadClasses['Foxway\\FString']			= $dir . '/includes/functions/FString.php';
$wgAutoloadClasses['Foxway\\FVariable']			= $dir . '/includes/functions/FVariable.php';

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

include_once 'Settings.php';

// @todo Reverse shift ???
// Do not change the value of this variable in LocalSettings.php!!!
$wgFoxwayPassByReference = array(
	'settype' => 1,
	'unset' => -1,
	'array_multisort' => -1,
	'array_pop' => 1,
	'array_push' => 1,
	'array_shift' => 1,
	'array_splice' => 1,
	'array_unshift' => 1,
	'arsort' => 1,
	'asort' => 1,
	'current' => 1,
	'each' => 1,
	'end' => 1,
	'key' => 1,
	'krsort' => 1,
	'ksort' => 1,
	'natcasesort' => 1,
	'natsort' => 1,
	'next' => 1,
	'pos' => 1,
	'prev' => 1,
	'reset' => 1,
	'rsort' => 1,
	'shuffle' => 1,
	'sort' => 1,
	'similar_text' => 4, // 0b100
	'sscanf' => 2147483644, // 0b1111111111111111111111111111100
	'str_ireplace' => 8, // 0b1000
	'str_replace' => 8, // 0b1000
	'preg_filter' => 16, // 0b10000
	'preg_match_all' => 4, // 0b100
	'preg_match' => 4, // 0b100
	'preg_replace' => 16, // 0b10000
);
