<?php
wfDebug( 'PHPTags test initialization' . __FILE__ );
\PhpTags\Hooks::addJsonFile( __DIR__ . '/PhpTags_test.json' );
const PHPTAGS_TEST = 'Test';
const PHPTAGS_TEST_BANNED = 'Test';
\Hooks::register( 'PhpTagsBeforeCallRuntimeHook', function ( $hookType, $objectName, $methodName, $values ) {
	if ( $hookType === \PhpTags\Runtime::H_GET_CONSTANT || $hookType === \PhpTags\Runtime::H_GET_OBJECT_CONSTANT ) {
		$methodName = strtolower( $methodName );
	}
	if ( substr( $methodName, -6 ) === 'banned' ) {
		$hookTypeString = \PhpTags\Hooks::getCallInfo( \PhpTags\Hooks::INFO_HOOK_TYPE_STRING );
		\PhpTags\Runtime::pushException( new \PhpTags\HookException( "Sorry, you cannot use this $hookTypeString" ) );
		return false;
	}
	return true;
} );

if ( \PhpTags\Renderer::$needInitRuntime ) {
	wfDebug( 'PHPTags: run hook PhpTagsRuntimeFirstInit ' . __FILE__ );
	\Hooks::run( 'PhpTagsRuntimeFirstInit' );
	\PhpTags\Hooks::loadData();
	\PhpTags\Runtime::$loopsLimit = 1000;
	\PhpTags\Renderer::$needInitRuntime = false;
}
