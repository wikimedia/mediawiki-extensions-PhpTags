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

	public function __construct( $message, $code = self::EXCEPTION_WARNING ) {
		parent::__construct( $code, $message );
	}

	public function isFatal() {
		return $this->code > self::EXCEPTION_WARNING;
	}

	public function isCatchable() {
		return $this->code !== self::EXCEPTION_FATAL;
	}

	public function __toString() {
		$arguments = $this->params;
		$originalFullName = $this->hookCallInfo[Hooks::INFO_ORIGINAL_FULL_NAME];

		$message = "$originalFullName: {$arguments}";

		return $this->formatMessage( $message, $this->code );
	}

	const EXCEPTION_NOTICE = 2;
	const EXCEPTION_WARNING = 3;
	const EXCEPTION_FATAL = 4;
	const EXCEPTION_CATCHABLE_FATAL = 5;

}
