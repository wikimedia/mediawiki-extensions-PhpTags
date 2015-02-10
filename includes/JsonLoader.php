<?php
namespace PhpTags;

/**
 * @todo Description
 *
 * @file JsonLoader.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class JsonLoader {

	public static function load( $files ) {
		$objects = array();
		$functions = array();
		$constants = array();
		$constantValues = array();
		foreach ( $files as $f ) {
			$fileName = $f[0];
			if ( is_readable( $fileName ) !== true ) {
				throw new \MWException( __METHOD__ . ": JSON file is not readable: $fileName" );
			}
			$json = file_get_contents( $fileName );
			if ( $json === false ) {
				throw new \MWException( __METHOD__ . ": Cannot read JSON file: $fileName" );
			}
			$data = \FormatJson::decode( $json, true );
			if ( $data === null ) {
				throw new \MWException( __METHOD__ . ": Invalid JSON file: $fileName" );
			}

			if ( isset( $data['objects'] ) ) {
				self::loadObjects( $data['objects'], $objects );
			}
			if ( isset( $data['functions'] ) ) {
				self::loadFunctions( $data['functions'], $functions );
			}
			if ( isset( $data['constants'] ) ) {
				self::loadConstants( $data['constants'], $constants, $constantValues );
			}
		}
		return array( 'objects'=>$objects, 'functions'=>$functions, 'constants'=>$constants, 'constantValues'=>$constantValues );
	}

	private static function loadObjects( $data, &$objects ) {
		foreach ( $data as $key => $value ) {
			if ( isset( $value['alias'] ) ) {
				$alias = strtolower( $value['alias'] );
				if ( false === isset( $objects[$alias] ) ) {
					throw new \MWException( __METHOD__ . ": Bad alias '$alias' for object '$key' in JSON file" );
				}
				$objects[ strtolower( $key ) ] =& $objects[$alias];
				continue;
			}
			$methods = array();
			$staticMethods = array();
			$properties = array();
			$staticProperties = array();
			if ( isset( $value['parent'] ) ) {
				$parent = $value['parent'];
				if ( false === isset( $data[$parent] ) ) {
					throw new \MWException( __METHOD__ . ": Bad parent '$parent' for object '$key' in JSON file" );
				}
				self::loadParentObject( $data, $parent, $methods, $staticMethods, $properties, $staticProperties );
			}
			if ( false === isset( $value['class'] ) ) {
				continue;
			}
			$class = $value['class'];
			self::loadObjectMethodsAndProperties( $value, $methods, $staticMethods, $properties, $staticProperties );
			$objects[ strtolower( $key ) ] = array( $class, $methods, $staticMethods, $properties, $staticProperties, $key );
		}
	}

	private static function loadParentObject( $objects, $parent, &$methods, &$staticMethods, &$properties, &$staticProperties ) {
		static $cache = array();
		if ( false === isset( $cache[$parent] ) ) {
			$cache[$parent] = array( array(), array(), array(), array() );
			self::loadObjectMethodsAndProperties( $objects[$parent], $cache[$parent][0], $cache[$parent][1], $cache[$parent][2], $cache[$parent][3] );
		}
		$methods += $cache[$parent][0];
		$staticMethods += $cache[$parent][1];
		$properties += $cache[$parent][2];
		$staticProperties += $cache[$parent][3];
		return;
	}

	private static function loadObjectMethodsAndProperties( $value, &$methods, &$staticMethods, &$properties, &$staticProperties ) {
		if ( isset( $value['METHODS'] ) ) {
			self::loadObjectMethods( $value['METHODS'], $methods );
		}
		if ( isset( $value['STATIC METHODS'] ) ) {
			self::loadObjectMethods( $value['STATIC METHODS'], $staticMethods );
		}
		if ( isset( $value['PROPERTIES'] ) ) {
			self::loadObjectProperties( $value['PROPERTIES'], $properties );
		}
		if ( isset( $value['STATIC PROPERTIES'] ) ) {
			self::loadObjectProperties( $value['STATIC PROPERTIES'], $staticProperties );
		}
	}

	private static function loadObjectMethods( $data, &$methods ) {
		foreach ( $data as $key => $value ) {
			$expects = self::getExpects( $value, 'method' );
			$methods[strtolower($key)] = array( $expects, $key );
		}
	}

	private static function loadObjectProperties( $data, &$properties ) {
		foreach ( $data as $key => $value ) {
			if ( isset( $value['readonly'] ) ) {
				continue;
			}
			$type = self::getType( $value['type'] );
			$properties[strtolower($key)] = array( $type, $key );
		}
	}

	private static function loadFunctions( $data, &$functions ) {
		foreach ( $data as $key => $value ) {
			if ( isset( $value['alias'] ) ) {
				$alias = $value['alias'];
				if ( false === isset( $functions[$alias] ) ) {
					throw new \MWException( __METHOD__ . ": Bad alias '$alias' for function '$key' in JSON file" );
				}
				$functions[$key] =& $functions[$alias];
				continue;
			}

			$class = $value['class'];
			$expects = self::getExpects( $value, 'function' );
			$onfailure = self::getReturnsOnFailure( $value );
			$functions[strtolower($key)] = array( $expects, $key, $class, $onfailure );
		}
	}

	private static function getExpects( $value, $itis ) {
		$min = 0;
		$max = 0;
		$reference = false;
		$refArray = array();
		$expects = array();
		foreach ( $value['parameters'] as $param ) {
			$type = self::getType( $param['type'] );
			if ( isset( $param['refarray'] ) ) {
				$refArray[] = $param['refarray'] === 'true' ? true : ( $param['refarray'] === "1" ? 1 : false );
			} elseif ( isset( $param['reference'] ) ) {
				$reference |= 1 << $max;
			}
			if ( isset( $param['default'] ) === false ) {
				if ( $param['name'] === '...' ) {
					$max++;
					$expects[Hooks::EXPECTS_VALUE_N] = $type;
					$expects[Hooks::EXPECTS_MINIMUM_PARAMETERS] = $min;
					if ( $refArray ) {
						$reference = array_pop( $refArray );
						$refArray[Hooks::EXPECTS_VALUE_N] = $reference;
						$reference = $refArray;
					} elseif ( $reference !== false && $reference === (1 << $max)-1 ) { // all params passed by reference
						$reference = true;
					} elseif ( $reference === ($reference | (1 << ($max-1))) ) { // last N param passed by reference
						$reference |= ~((1 << $max)-1);
					}
					$expects[Hooks::EXPECTS_REFERENCE_PARAMETERS] = $reference;
					return $expects;
				} elseif ( $min !== $max ) {
					throw new \MWException( __METHOD__ . ": Default value is missed in $max param of $itis $value in JSON file" );
				} else {
					$min++;
				}
			}
			$max++;
			$expects[] = $type;
		}
		if ( $refArray ) {
			$reference = $refArray;
		} elseif ( $reference !== false && $reference === (1 << $max)-1 ) { // all params passed by reference
			$reference = true;
		}
		$expects[Hooks::EXPECTS_REFERENCE_PARAMETERS] = $reference;
		if ( $min === $max ) {
			$expects[Hooks::EXPECTS_EXACTLY_PARAMETERS] = $min;
		} else {
			$expects[Hooks::EXPECTS_MINIMUM_PARAMETERS] = $min;
			$expects[Hooks::EXPECTS_MAXIMUM_PARAMETERS] = $max;
		}
		return $expects;
	}

	private static function getType( $type ) {
		switch ( $type ) {
			case 'array':
				return Hooks::TYPE_ARRAY;
			case 'bool':
				return Hooks::TYPE_BOOL;
			case 'float':
				return Hooks::TYPE_FLOAT;
			case 'int':
				return Hooks::TYPE_INT;
			case 'mixed':
				return Hooks::TYPE_MIXED;
			case 'not object':
				return Hooks::TYPE_NOT_OBJECT;
			case 'scalar':
				return Hooks::TYPE_SCALAR;
			case 'string':
				return Hooks::TYPE_STRING;
			default:
				return $type;
		}
	}

	private static function loadConstants( $data, &$constants, &$constantValues ) {
		foreach ( $data as $key => $value ) {
			if ( isset( $value['class'] ) ) {
				$constants[$key] = $value['class'];
			} elseif ( defined( $key ) ) {
				$constantValues[$key] = constant( $key );
			}
		}
	}

	private static function getReturnsOnFailure( $value ) {
		if ( isset( $value['onfailure'] ) ) {
			switch ($value['onfailure'] ) {
				case 'false':
					return false;
				case 'true':
					return true;
				case '-1':
					return -1;
				case 'null':
					return null;
				default:
					throw new \MWException( __METHOD__ . ": wrong value '{$value['onfailure']}' for failure field in JSON file" );
			}
		}
		return null;
	}

}