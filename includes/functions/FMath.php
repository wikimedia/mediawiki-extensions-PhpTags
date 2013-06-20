<?php
namespace Foxway;
/**
 * FMaths class implements Mathematical Functions for Foxway extension.
 *
 * @file FMath.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class FMath extends BaseFunction {
	protected static $listFunction = array(
		'f_abs' => array('abs', 1, 1),
		'f_acos' => array('acos', 1, 1),
		'f_acosh' => array('acosh', 1, 1),
		'f_asin' => array('asin', 1, 1),
		'f_asinh' => array('asinh', 1, 1),
		'f_atan2' => array('atan2', 2, 2),
		'f_atan' => array('atan', 1, 1),
		'f_atanh' => array('atanh', 1, 1),
		'f_base_convert' => array('base_convert', 3, 3),
		'f_bindec' => array('bindec', 1, 1),
		'f_ceil' => array('ceil', 1, 1),
		'f_cos' => array('cos', 1, 1),
		'f_cosh' => array('cosh', 1, 1),
		'f_decbin' => array('decbin', 1, 1),
		'f_dechex' => array('dechex', 1, 1),
		'f_decoct' => array('decoct', 1, 1),
		'f_deg2rad' => array('deg2rad', 1, 1),
		'f_exp' => array('exp', 1, 1),
		'f_expm1' => array('expm1', 1, 1),
		'f_floor' => array('floor', 1, 1),
		'f_fmod' => array('fmod', 2, 2),
		'f_getrandmax' => array('getrandmax', 0, 0),
		'f_hexdec' => array('hexdec', 1, 1),
		'f_hypot' => array('hypot', 2, 2),
		'f_is_finite' => array('is_finite', 1, 1),
		'f_is_infinite' => array('is_infinite', 1, 1),
		'f_is_nan' => array('is_nan', 1, 1),
		'f_lcg_value' => array('lcg_value', 0, 0),
		'f_log10' => array('log10', 1, 1),
		'f_log1p' => array('log1p', 1, 1),
		'f_log' => array('log', 1, 2),
		'f_max' => array('max', 1, FOXWAY_MAX_PAST_PARAM),
		'f_min' => array('min', 1, FOXWAY_MAX_PAST_PARAM),
		'f_mt_getrandmax' => array('mt_getrandmax', 0, 0),
		'f_mt_rand' => array('mt_rand', 0, 2),
		'f_mt_srand' => array('mt_srand', 0, 1),
		'f_octdec' => array('octdec', 1, 1),
		'f_pi' => array('pi', 0, 0),
		'f_pow' => array('pow', 2, 2),
		'f_rad2deg' => array('rad2deg', 1, 1),
		'f_rand' => array('rand', 0, 2),
		'f_round' => array('round', 1, 3),
		'f_sin' => array('sin', 1, 1),
		'f_sinh' => array('sinh', 1, 1),
		'f_sqrt' => array('sqrt', 1, 1),
		'f_srand' => array('srand', 0, 1),
		'f_tan' => array('tan', 1, 1),
		'f_tanh' => array('tanh', 1, 1),
	);

	public static function __callStatic($name, $arguments) {
		if( isset(self::$listFunction[$name]) ) {
			$funcData = &self::$listFunction[$name];
			if( isset($arguments[0]) ) {
				$refarg = &$arguments[0];
			}else{
				$refarg = array();
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
}