<?php
namespace Foxway;
/**
 * FString class implements String Functions for Foxway extension.
 *
 * @file FString.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class FString extends BaseFunction {
	protected static $listFunction = array(
		'f_addcslashes' => array('addcslashes', 2, 2),
		'f_addslashes' => array('addslashes', 1, 1),
		//'f_bin2hex' => array('bin2hex', 1, 1),
		'f_chop' => array('rtrim', 1, 2), //chop — Alias of rtrim()
		'f_chr' => array('chr', 1, 1),
		'f_chunk_split' => array('chunk_split', 1, 3),
		'f_convert_cyr_string' => array('convert_cyr_string', 3, 3),
		'f_convert_uudecode' => array('convert_uudecode', 1, 1),
		'f_convert_uuencode' => array('convert_uuencode', 1, 1),
		'f_count_chars' => array('count_chars', 1, 2),
		'f_crc32' => array('crc32', 1, 1),
		'f_crypt' => array('crypt', 1, 2),
		'f_explode' => array('explode', 2, 3),
		// ### fprintf — Write a formatted string to a stream ###
		//'f_get_html_translation_table' => array('get_html_translation_table', 0, 3),
		//'f_hebrev' => array('hebrev', 1, 2),
		//'f_hebrevc' => array('hebrevc', 1, 2),
		//'f_hex2bin' => array('hex2bin', 1, 1), PHP >= 5.4.0
		'f_html_entity_decode' => array('html_entity_decode', 1, 3),
		'f_htmlentities' => array('htmlentities', 1, 4),
		'f_htmlspecialchars_decode' => array('htmlspecialchars_decode', 1, 2),
		'f_htmlspecialchars' => array('htmlspecialchars', 1, 4),
		'f_implode' => array('implode', 1, 2),
		'f_join' => array('implode', 1, 2), //join — Alias of implode()
		'f_lcfirst' => array('lcfirst', 1, 1),
		//'levenshtein', @see self::f_levenshtein
		//'localeconv' @todo get info from MW
		'f_ltrim' => array('ltrim', 1, 2), //@todo check exsample
		// ### md5_ file
		'f_md5' => array('md5', 1, 2),
		'f_metaphone' => array('metaphone', 1, 2),
		'f_money_format' => array('money_format', 2, 2), // @todo need setlocale
		'f_nl_langinfo' => array('nl_langinfo', 1, 1), // @todo need setlocale
		'f_nl2br' => array('nl2br', 1, 2),
		// 'number_format' @see self::number_format
		'f_ord' => array('ord', 1, 1),
		// @todo parse_str
		// 'print' implemented in Runtime.php
		//'f_printf', @see self::f_printf
		//'f_quoted_printable_decode' => array('quoted_printable_decode', 1, 1),
		//'f_quoted_printable_encode' => array('quoted_printable_encode', 1, 1),
		'f_quotemeta' => array('quotemeta', 1, 1),
		'f_rtrim' => array('rtrim', 1, 2),
		// setlocale @todo need check for security
		// ### sha1_file
		'f_sha1' => array('sha1', 1, 2),
		'f_similar_text' => array('similar_text', 2, 3),
		'f_soundex' => array('soundex', 1, 1),
		'f_sprintf' => array('sprintf', 1, FOXWAY_MAX_PAST_PARAM),
		'f_sscanf' => array('sscanf', 2, FOXWAY_MAX_PAST_PARAM),
		//str_getcsv
		'f_str_ireplace' => array('str_ireplace', 3, 4),
		'f_str_pad' => array('str_pad', 2, 4),
		'f_str_repeat' => array('str_repeat', 2, 2),
		'f_str_replace' => array('str_replace', 3, 4),
		'f_str_rot13' => array('str_rot13', 1, 1),
		'f_str_shuffle' => array('str_shuffle', 1, 1),
		'f_str_split' => array('str_split', 1, 2),
		'f_str_word_count' => array('str_word_count', 1, 3),
		'f_strcasecmp' => array('strcasecmp', 2, 2),
		'f_strchr' => array('strstr', 2, 3), //strchr — Alias of strstr()
		'f_strcmp' => array('strcmp', 2, 2),
		// strcoll function is not binary safe.
		'f_strcspn' => array('strcspn', 2, 4),
		'f_strip_tags' => array('strip_tags', 1, 2),
		'f_stripcslashes' => array('stripcslashes', 1, 1),
		'f_stripos' => array('stripos', 2, 3),
		'f_stripslashes' => array('stripslashes', 1, 1),
		'f_stristr' => array('stristr', 2, 3),
		'f_strlen' => array('strlen', 1, 1),
		'f_strnatcasecmp' => array('strnatcasecmp', 2, 2),
		'f_strnatcmp' => array('strnatcmp', 2, 2),
		'f_strncasecmp' => array('strncasecmp', 3, 3),
		'f_strncmp' => array('strncmp', 3, 3),
		'f_strpbrk' => array('strpbrk', 2, 2),
		'f_strpos' => array('strpos', 2, 3),
		'f_strrchr' => array('strrchr', 2, 2),
		'f_strrev' => array('strrev', 1, 1),
		'f_strripos' => array('strripos', 2, 3),
		'f_strrpos' => array('strrpos', 2, 3),
		'f_strspn' => array('strspn', 2, 4),
		'f_strstr' => array('strstr', 2, 3),
		'f_strtok' => array('strtok', 1, 2),
		'f_strtolower' => array('strtolower', 1, 1),
		'f_strtoupper' => array('strtoupper', 1, 1),
		'f_strtr' => array('strtr', 2, 3),
		'f_substr_compare' => array('substr_compare', 3, 5),
		'f_substr_count' => array('substr_count', 2, 4),
		'f_substr_replace' => array('substr_replace', 3, 4),
		'f_substr' => array('substr', 2, 3),
		'f_trim' => array('trim', 1, 2),
		'f_ucfirst' => array('ucfirst', 1, 1),
		'f_ucwords' => array('ucwords', 1, 1),
		// ### vfprintf — Write a formatted string to a stream
		// 'f_vprintf', @see self::f_vprintf
		'f_vsprintf' => array('vsprintf', 2, 2),
		'f_wordwrap' => array('wordwrap', 1, 4),

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
				return new RValue( call_user_func_array($funcData[0], $refarg) );
			}else{
				return self::wrongParameterCount($name, __LINE__);
			}
		} else {
			return self::callUnknownMethod($name, __LINE__);
		}
	}

	/**
	 * Output a formatted string
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_printf($arguments) {
		if( count($arguments) == 0 ) {
			return self::wrongParameterCount( __FUNCTION__, __LINE__ );
		}
		ob_start();
		call_user_func_array('printf', $arguments);
		return new ROutput( null, ob_get_clean(), 'pre' );
	}

	/**
	 * Calculate Levenshtein distance between two strings
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_levenshtein($arguments) {
		switch ( count($arguments) ) {
			case 2:
			case 5:
				return new RValue( call_user_func_array('levenshtein', $arguments) );
				break;
		}
		return self::wrongParameterCount( __FUNCTION__, __LINE__ );
	}

	/**
	 * Calculate Levenshtein distance between two strings
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_number_format($arguments) {
		switch ( count($arguments) ) {
			case 1:
			case 2:
			case 4:
				return new RValue( call_user_func_array('number_format', $arguments) );
				break;
		}
		return self::wrongParameterCount( __FUNCTION__, __LINE__ );
	}

	/**
	 * Output a formatted string
	 * @param array $arguments
	 * @return \Foxway\RValue
	 */
	public static function f_vprintf($arguments) {
		if( count($arguments) != 2 ) {
			return self::wrongParameterCount( __FUNCTION__, __LINE__ );
		}
		ob_start();
		call_user_func_array('vprintf', $arguments);
		return new ROutput( null, ob_get_clean(), 'pre' );
	}

}
