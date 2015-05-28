<?php

if ( \PhpTags\Renderer::$needInitRuntime ) {
	wfRunHooks( 'PhpTagsRuntimeFirstInit' );
	\PhpTags\Hooks::loadData();
	\PhpTags\Runtime::$loopsLimit = 1000;
	\PhpTags\Renderer::$needInitRuntime = false;
}
