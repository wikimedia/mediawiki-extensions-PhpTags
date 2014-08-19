<?php

if ( ! defined( 'PhpTagsRuntimeFirstInit' ) ) {
	wfRunHooks( 'PhpTagsRuntimeFirstInit' );
	\PhpTags\Runtime::$loopsLimit = 1000;
	define( 'PhpTagsRuntimeFirstInit', true );
}

