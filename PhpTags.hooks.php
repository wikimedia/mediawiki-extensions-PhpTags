<?php


/**
 * PhpTags MediaWiki Hooks.
 *
 * @file PhpTags.hooks.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class PhpTagsHooks {

	/**
	 *
	 * @return boolean
	 */
	public static function onPhpTagsRuntimeFirstInit() {
		\PhpTags\Hooks::addJsonFile( __DIR__ . '/PhpTags.json', PHPTAGS_VERSION );
		return true;
	}

	/**
	 *
	 * @param Parser $parser
	 * @return boolean
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'phptag', 'PhpTags\\Renderer::runParserFunction', Parser::SFH_OBJECT_ARGS );
		$parser->setHook( 'phptag', 'PhpTags\\Renderer::runTagHook' );
		return true;
	}

	/**
	 * Register extension type for Special:Version used for PhpTags extensions
	 * @param array $extTypes
	 */
	public static function onExtensionTypes( array &$extTypes ) {
		$extTypes['phptags'] = wfMessage( 'phptags-extension-type' )->text();
		return true;
	}

	/**
	 *
	 * @param array $extResources
	 * @param array $extMode
	 */
	public static function onCodeMirrorGetAdditionalResources( &$extResources, &$extMode ) {
		$extResources['scripts']['lib/codemirror/mode/php/php.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/htmlmixed/htmlmixed.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/xml/xml.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/javascript/javascript.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/css/css.js'] = true;
		$extResources['scripts']['lib/codemirror/mode/clike/clike.js'] = true;

		$extMode['tag']['phptag'] = 'text/x-php';

		return true;
	}

	/**
	 *
	 * @global int $wgPhpTagsCallsCounter
	 * @param Parser $parser
	 * @param string $text
	 * @return boolean
	 */
	public static function onParserAfterTidy( $parser, &$text ) {
		global $wgPhpTagsCallsCounter;
		if ( $wgPhpTagsCallsCounter > 0 ) {
			\PhpTags\Renderer::onParserAfterTidy( $parser, $text );
		}
		return true;
	}

	/**
	 *
	 * @global boolean $wgPhpTagsLimitReport
	 * @param Parser $parser
	 * @param string $limitReport
	 * @return boolean
	 */
	public static function onParserLimitReport( $parser, &$limitReport ) {
		global $wgPhpTagsLimitReport;
		if ( $wgPhpTagsLimitReport !== false ) {
			$limitReport .= $wgPhpTagsLimitReport;
			$wgPhpTagsLimitReport = false;
		}
		return true;
	}

	/**
	 *
	 * @param array $files
	 * @return boolean
	 */
	public static function onUnitTestsList( &$files ) {
		$testDir = __DIR__ . '/tests/phpunit';
		$files = array_merge( $files, glob( "$testDir/includes/*Test.php" ) );
		return true;
	}

	public static function onRegistration() {
		global $wgPhpTagsLimitReport, $wgPhpTagsCallsCounter;

		$wgPhpTagsLimitReport = false;
		$wgPhpTagsCallsCounter = 0;

		define ( 'PHPTAGS_HOOK_RELEASE', 8 );
		define ( 'PHPTAGS_VERSION', '5.9.0.27' ); //@todo remove later, it only for backward compatibility
	}

}
