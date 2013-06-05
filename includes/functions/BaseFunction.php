<?php
namespace Foxway;
/**
 * BaseFunction base class for functions classes of Foxway extension.
 *
 * @file BaseFunction.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class BaseFunction {
	public static function wrongParameterCount($function, $line) {
		return new ErrorMessage(
				$line,
				null,
				E_WARNING,
				array(
					'foxway-php-wrong-parameter-count',
					substr( $function, 2 ),
					null,
				)
			);
	}

	public static function onlyVariablesCanBePassedByReference($function, $line) {
		return new ErrorMessage(
				$line,
				null,
				E_ERROR,
				array(
					'foxway-php-not-variable-passed-by-reference',
					substr( $function, 2 ),
					null,
				)
			);
	}

	/**
	 * @todo change message
	 * @param string $function
	 * @param int $line
	 * @return \Foxway\ErrorMessage
	 */
	public static function callUnknownMethod($function, $line) {
			return new ErrorMessage(
					$line,
					null,
					E_ERROR,
					array(
						'faxway-unexpected-result-work-function',
						substr( $function, 2 ),
						null,
					)
				);
	}
}
