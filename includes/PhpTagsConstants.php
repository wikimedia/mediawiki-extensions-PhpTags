<?php
namespace PhpTagsObjects;

use ExtensionRegistry;
use PhpTags\GenericObject;
use PhpTags\PhpTagsException;

/**
 *
 *
 * @file PhpTagsConstants.php
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class PhpTagsConstants extends GenericObject {

	/**
	 * @param string $constantName
	 * @return mixed
	 * @throws PhpTagsException
	 */
	public static function getConstantValue( $constantName ) {
		static $phptagsVersion = false;

		if ( $phptagsVersion === false ) {
			$phptagsVersion = ExtensionRegistry::getInstance()->getAllThings()['PhpTags']['version'];
		}

		switch ( $constantName ) {
			case 'PHPTAGS_VERSION':
				return $phptagsVersion;
			case 'PHPTAGS_MAJOR_VERSION':
				return (int)explode( '.', $phptagsVersion )[0];
			case 'PHPTAGS_MINOR_VERSION':
				$v = explode( '.', $phptagsVersion );
				return isset( $v[1] ) ? (int)$v[1] : 0;
			case 'PHPTAGS_RELEASE_VERSION':
				$v = explode( '.', $phptagsVersion );
				return isset( $v[2] ) ? (int)$v[2] : 0;
		}
		return parent::getConstantValue( $constantName );
	}
}
