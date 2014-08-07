<?php
namespace PhpTags;

/**
 * The error exception class of the extension PHP Tags.
 *
 * @file ExceptionPhpTags.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class HookException extends PhpTagsException {

	function __construct( $code, $message ) {
		parent::__construct( parent::EXCEPTION_FROM_HOOK, array( $message, $code ) );
	}

	const EXCEPTION_NOTICE = 2;
	const EXCEPTION_WARNING = 3;
	const EXCEPTION_FATAL = 4;
	const EXCEPTION_CATCHABLE_FATAL = 5;

}
