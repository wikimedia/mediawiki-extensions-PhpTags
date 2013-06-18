<?php
namespace Foxway;
/**
 * Fpcre class implements PCRE Functions for Foxway extension.
 *
 * @file Fpcre.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Fpcre extends BaseFunction {
	protected static $listFunction = array(
		'f_preg_filter' => array('preg_filter', 3, 5),
		'f_preg_grep' => array('preg_grep', 2, 3),
		//'f_preg_last_error' @see self::f_preg_last_error
		'f_preg_match_all' => array('preg_match_all', 3, 5), // @todo PHP 5.4.0: The matches parameter became optional. => array('preg_match_all', 2, 5)
		'f_preg_match' => array('preg_match', 2, 5),
		'f_preg_quote' => array('preg_quote', 1, 2),
		// @todo preg_replace_callback
		//'f_preg_replace' @see self::f_preg_replace
		'f_preg_split' => array('preg_split', 2, 4),
	);

	public static function __callStatic($name, $arguments) {
		global $wgFoxwayPassByReference;

		if( isset(self::$listFunction[$name]) ) {
			$funcData = &self::$listFunction[$name];
			$refarg = &$arguments[0];
			if( isset($wgFoxwayPassByReference[$funcData[0]]) ) {
				foreach ($refarg as $key => &$value) {
					if( $value instanceof RValue ) {
						$refarg[$key] = &$value->getReference();
					}
				}
			}
			$c = count($refarg);
			if( $c >= $funcData[1] && $c <= $funcData[2] ) {
				wfSuppressWarnings();
				$return = call_user_func_array($funcData[0], $refarg);
				wfRestoreWarnings();
				return new RValue( $return );
			}else{
				return self::wrongParameterCount($name, __LINE__);
			}
		} else {
			return self::callUnknownMethod($name, __LINE__);
		}
	}

	/**
	 * Returns the error code of the last PCRE regex execution
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_preg_last_error($arguments) {
		if( count($arguments) != 0 ) {
			return self::wrongParameterCount( __FUNCTION__, __LINE__ );
		}
		return new RValue( preg_last_error() );
	}

	/**
	 * Perform a regular expression search and replace
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_preg_replace($arguments) {
		if( count($arguments) < 3 || count($arguments) > 5 ) {
			return self::wrongParameterCount( __FUNCTION__, __LINE__ );
		}

		if( is_array($arguments[0]) ) {
			foreach ($arguments[0] as $key => $value) {
				$pattern = self::getValidPattern($value);
				if( $pattern instanceof ErrorMessage ) {
					return $pattern;
				}
				$arguments[0][$key] = $pattern;
			}
		}else{
			$pattern = self::getValidPattern($arguments[0]);
			 if( $pattern instanceof ErrorMessage ) {
				 return $pattern;
			 }
			 $arguments[0] = $pattern;
		}

		if( isset($arguments[4]) ) {
			$arguments[4] = &$arguments[4]->getReference();
		}

		\MWDebug::log( var_export($arguments, true) );

		wfSuppressWarnings();
		$return = call_user_func_array('preg_replace', $arguments);
		wfRestoreWarnings();
		return new RValue( $return );
	}

	private static function getValidPattern( $pattern ) {
		$pattern = str_replace(chr(0), '', $pattern);
		// Set basic statics
		static $regexStarts = '`~!@#$%^&*-_+=.,?"\':;|/<([{';
		static $regexEnds   = '`~!@#$%^&*-_+=.,?"\':;|/>)]}';
		static $regexModifiers = 'imsxADU';

		if ( false === $delimPos = strpos( $regexStarts, $pattern[0] ) ) {
			return new ErrorMessage( __LINE__, null, E_WARNING,	array( 'foxway-php-warning-exception-in-function', "preg_replace", 'n\a', wfMessage('foxway-error-bad-delimiter')->escaped(),) );
		}

		$end = $regexEnds[$delimPos];
		$pos = 1;
		$endPos = null;
		while ( !isset( $endPos ) ) {
			$pos = strpos( $pattern, $end, $pos );
			if ( $pos === false ) {
				return new ErrorMessage( __LINE__, null, E_WARNING,	array( 'foxway-php-warning-exception-in-function', "preg_replace", 'n\a', wfMessage('foxway-error-no-ending-matching-delimiter', $end)->escaped(),) );
			}
			$backslashes = 0;
			for ( $l = $pos - 1; $l >= 0; $l-- ) {
				if ( $pattern[$l] == '\\' ) $backslashes++;
				else break;
			}
			if ( $backslashes % 2 == 0 ) $endPos = $pos;
			$pos++;
		}
		$startRegex = (string)substr( $pattern, 0, $endPos ) . $end;
		$endRegex = (string)substr( $pattern, $endPos + 1 );
		$len = strlen( $endRegex );
		for ( $c = 0; $c < $len; $c++ ) {
			if ( strpos( $regexModifiers, $endRegex[$c] ) === false )
				return new ErrorMessage( __LINE__, null, E_WARNING,	array( 'foxway-php-warning-exception-in-function', "preg_replace", 'n\a', wfMessage('foxway-error-unknown-modifier', $endRegex[$c])->escaped(),) );
		}
		return $startRegex . $endRegex . 'u';
	}

}
