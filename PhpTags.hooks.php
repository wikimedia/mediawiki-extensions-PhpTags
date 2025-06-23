<?php

use PhpTags\Renderer;


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
	 * @return void
	 */
	public static function onPhpTagsRuntimeFirstInit() {
		$version = ExtensionRegistry::getInstance()->getAllThings()['PhpTags']['version'];
		\PhpTags\Hooks::addJsonFile( __DIR__ . '/PhpTags.json', $version );
	}

	/**
	 * @param Parser $parser
	 * @return void
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'phptag', 'PhpTags\\Renderer::runParserFunction', Parser::SFH_OBJECT_ARGS );
		$parser->setHook( 'phptag', 'PhpTags\\Renderer::runTagHook' );
	}

	/**
	 * Register extension type for Special:Version used for PhpTags extensions
	 * @param array $extTypes
	 * @return void
	 */
	public static function onExtensionTypes( array &$extTypes ) {
		$extTypes['phptags'] = wfMessage( 'phptags-extension-type' )->text();
	}

	/**
	 * @global int $wgPhpTagsCallsCounter
	 * @param Parser $parser
	 * @param string $text
	 * @return void
	 */
	public static function onParserAfterTidy( $parser, &$text ) {
		global $wgPhpTagsCallsCounter;
		if ( $wgPhpTagsCallsCounter > 0 ) {
			Renderer::onParserAfterTidy( $parser, $text );
		}
	}

	/**
	 * @global boolean $wgPhpTagsLimitReport
	 * @param Parser $parser
	 * @param string $limitReport
	 * @return void
	 */
	public static function onParserLimitReport( $parser, &$limitReport ) {
		global $wgPhpTagsLimitReport;
		if ( $wgPhpTagsLimitReport !== false ) {
			$limitReport .= $wgPhpTagsLimitReport;
			$wgPhpTagsLimitReport = false;
		}
	}

	public static function onRegistration() {
		global $wgPhpTagsLimitReport, $wgPhpTagsCallsCounter;

		$wgPhpTagsLimitReport = false;
		$wgPhpTagsCallsCounter = 0;

		define ( 'PHPTAGS_HOOK_RELEASE', 8 );
		// @todo remove later, it is for backward compatibility only
		define ( 'PHPTAGS_VERSION', ExtensionRegistry::getInstance()->getAllThings()['PhpTags']['version'] );
	}

}
