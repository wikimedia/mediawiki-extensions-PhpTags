<?php

if ( PhpTags::$needInitRuntime ) {
	wfRunHooks( 'PhpTagsRuntimeFirstInit' );
	\PhpTags\Hooks::loadData();
	\PhpTags\Runtime::$loopsLimit = 1000;
	PhpTags::$needInitRuntime = false;
}
