<?php
namespace Foxway;
/**
 * FArray class implements Array Functions for Foxway extension.
 *
 * @file FArray.php
 * @ingroup Foxway
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class FArray extends BaseFunction {
	protected static $listFunction = array(
		'f_array_change_key_case' => array('array_change_key_case', 1, 2),
		'f_array_chunk' => array('array_chunk', 2, 3),
		//'f_array_column' => array('array_column', 2, 3), @todo PHP 5 >= 5.5.0
		'f_array_combine' => array('array_combine', 2, 2),
		'f_array_count_values' => array('array_count_values', 1, 1),
		'f_array_diff_assoc' => array('array_diff_assoc', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_diff_key' => array('array_diff_key', 2, FOXWAY_MAX_PAST_PARAM),
		// @todo array_diff_uassoc
		// @todo array_diff_ukey
		'f_array_diff' => array('array_diff', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_fill_keys' => array('array_fill_keys', 2, 2),
		'f_array_fill' => array('array_fill', 3, 3),
		// @todo array_filter
		'f_array_flip' => array('array_flip', 1, 1),
		'f_array_intersect_assoc' => array('array_intersect_assoc', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_intersect_key' => array('array_intersect_key', 2, FOXWAY_MAX_PAST_PARAM),
		// @todo array_intersect_uassoc
		// @todo array_intersect_ukey
		'f_array_intersect' => array('array_intersect', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_key_exists' => array('array_key_exists', 2, 2),
		'f_array_keys' => array('array_keys', 1, 3),
		// @todo array_map
		'f_array_merge_recursive' => array('array_merge_recursive', 1, FOXWAY_MAX_PAST_PARAM),
		'f_array_merge' => array('array_merge', 1, FOXWAY_MAX_PAST_PARAM),
		'f_array_multisort' => array('array_multisort', 1, FOXWAY_MAX_PAST_PARAM),
		'f_array_pad' => array('array_pad', 3, 3),
		'f_array_pop' => array('array_pop', 1, 1),
		'f_array_product' => array('array_product', 1, 1),
		'f_array_push' => array('array_push', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_rand' => array('array_rand', 1, 2),
		// @todo array_reduce
		'f_array_replace_recursive' => array('array_replace_recursive', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_replace' => array('array_replace', 2, FOXWAY_MAX_PAST_PARAM),
		'f_array_reverse' => array('array_reverse', 1, 2),
		'f_array_search' => array('array_search', 2, 3),
		'f_array_shift' => array('array_shift', 1, 1),
		'f_array_slice' => array('array_slice', 2, 4),
		'f_array_splice' => array('array_splice', 2, 4),
		'f_array_sum' => array('array_sum', 1, 1),
		// @todo array_udiff_assoc
		// @todo array_udiff_uassoc
		// @todo array_udiff
		// @todo array_uintersect_assoc
		// @todo array_uintersect_uassoc
		// @todo array_uintersect
		'f_array_unique' => array('array_unique', 1, 2),
		'f_array_unshift' => array('array_unshift', 2, 3),
		'f_array_values' => array('array_values', 1, 1),
		//@todo array_walk_recursive
		//@todo array_walk
		'f_arsort' => array('arsort', 1, 2),
		'f_asort' => array('asort', 1, 2),
		// @todo compact
		'f_count' => array('count', 1, 2),
		'f_current' => array('current', 1, 1),
		'f_each' => array('each', 1, 1),
		'f_end' => array('end', 1, 1),
		// @todo extract
		'f_in_array' => array('in_array', 2, 3),
		'f_key' => array('key', 1, 1),
		'f_krsort' => array('krsort', 1, 2),
		'f_ksort' => array('ksort', 1, 2),
		// @todo list
		'f_natcasesort' => array('natcasesort', 1, 1),
		'f_natsort' => array('natsort', 1, 1),
		'f_next' => array('next', 1, 1),
		'f_pos' => array('current', 1, 1), // Alias of current()
		'f_prev' => array('prev', 1, 1),
		'f_range' => array('range', 2, 3),
		'f_reset' => array('reset', 1, 1),
		'f_rsort' => array('rsort', 1, 2),
		'f_shuffle' => array('shuffle', 1, 1),
		'f_sizeof' => array('count', 1, 2), //Alias of count()
		'f_sort' => array('sort', 1, 2),
		// @todo uasort
		// @todo uksort
		// @todo usort
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

}
