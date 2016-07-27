<?php
namespace PhpTagsObjects;

/**
 *
 *
 * @file PhpTagsConstants.php
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class PhpTagsConstants extends \PhpTags\GenericObject {

	public static function getConstantValue( $constantName ) {
		static $phptagsVersion = false;

		if ( $phptagsVersion === false ) {
			$phptagsVersion = \ExtensionRegistry::getInstance()->getAllThings()['PhpTags']['version'];
		}

		switch ( $constantName ) {
			case 'PHPTAGS_VERSION':
				return $phptagsVersion;
			case 'PHPTAGS_MAJOR_VERSION':
				return (int)split( $phptagsVersion, '.' )[0];
			case 'PHPTAGS_MINOR_VERSION':
				$v = split( $phptagsVersion, '.' );
				return isset( $v[1] ) ? (int)$v[1] : 0;
			case 'PHPTAGS_RELEASE_VERSION':
				$v = split( $phptagsVersion, '.' );
				return isset( $v[2] ) ? (int)$v[2] : 0;
		}
		return parent::getConstantValue( $constantName );
	}
}
