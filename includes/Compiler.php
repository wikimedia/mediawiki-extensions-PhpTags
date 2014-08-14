<?php
namespace PhpTags;
require_once 'Runtime.php';

if ( false === defined( 'T_ONUMBER' ) ) { // T_ONUMBER defined in HHVM only
	define( 'T_ONUMBER', -20140814094314 );
}

/**
 * The compiler class of the extension PhpTags.
 * This class converts a php code as data for the class Runtime
 *
 * @file Compiler.php
 * @ingroup PhpTags
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence GNU General Public Licence 2.0 or later
 */
class Compiler {

	/**
	 * Operator Precedence
	 * @see http://www.php.net/manual/en/language.operators.precedence.php
	 * @var array
	 */
	protected static $operatorsPrecedence = array(
		//array('['),
		//		++		--		(int)			(float)		(string)		(array)		(bool)			(unset)
		array( T_INC, T_DEC, '~', T_INT_CAST, T_DOUBLE_CAST, T_STRING_CAST, T_ARRAY_CAST, T_BOOL_CAST, T_UNSET_CAST ),
		array( '!' ),
		array( '*', '/', '%' ),
		array( '+', '-', '.' ),
		//		<<	>>
		array( T_SL, T_SR ),
		//						<=						>=
		array( '<', '>', T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL ),
		//		==				!=				===				!==
		array( T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL ),
		array( '&' ),
		array( '^' ),
		array( '|' ),
		array( T_BOOLEAN_AND ), // &&
		array( T_BOOLEAN_OR ), // ||
		array( '?', ':' ),
		//				+=			-=				*=			/=			.=				%=				&=			|=			^=			<<=			>>=
		array( '=', T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL, T_MOD_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL ),
		array( T_LOGICAL_AND ), // and
		array( T_LOGICAL_XOR ), // xor
		array( T_LOGICAL_OR ), // or
		array( ',' ),
		array( ';' ),
	);

	/**
	 * Conformity operators PHP and PHPTAGS
	 * @var array
	 */
	protected static $runtimeOperators = array(
		'~' => '~',
		'!' => '!',
		'*' => '*',
		'/' => '/',
		'%' => '%',
		'+' => '+',
		'-' => '-',
		'.' => '.',
		'<' => '<',
		'>' => '>',
		'&' => '&',
		'^' => '^',
		'|' => '|',
		'=' => '=',
		T_LOGICAL_OR => PHPTAGS_T_LOGICAL_OR,
		T_BOOLEAN_OR => PHPTAGS_T_LOGICAL_OR,
		T_LOGICAL_XOR => PHPTAGS_T_LOGICAL_XOR,
		T_LOGICAL_AND => PHPTAGS_T_LOGICAL_AND,
		T_BOOLEAN_AND => PHPTAGS_T_LOGICAL_AND,
		T_SR_EQUAL => PHPTAGS_T_SR_EQUAL,
		T_SL_EQUAL => PHPTAGS_T_SL_EQUAL,
		T_XOR_EQUAL => PHPTAGS_T_XOR_EQUAL,
		T_OR_EQUAL => PHPTAGS_T_OR_EQUAL,
		T_AND_EQUAL => PHPTAGS_T_AND_EQUAL,
		T_MOD_EQUAL => PHPTAGS_T_MOD_EQUAL,
		T_CONCAT_EQUAL => PHPTAGS_T_CONCAT_EQUAL,
		T_DIV_EQUAL => PHPTAGS_T_DIV_EQUAL,
		T_MUL_EQUAL => PHPTAGS_T_MUL_EQUAL,
		T_MINUS_EQUAL => PHPTAGS_T_MINUS_EQUAL,
		T_PLUS_EQUAL => PHPTAGS_T_PLUS_EQUAL,
		T_IS_NOT_IDENTICAL => PHPTAGS_T_IS_NOT_IDENTICAL,
		T_IS_IDENTICAL => PHPTAGS_T_IS_IDENTICAL,
		T_IS_NOT_EQUAL => PHPTAGS_T_IS_NOT_EQUAL,
		T_IS_EQUAL => PHPTAGS_T_IS_EQUAL,
		T_IS_GREATER_OR_EQUAL => PHPTAGS_T_IS_GREATER_OR_EQUAL,
		T_IS_SMALLER_OR_EQUAL => PHPTAGS_T_IS_SMALLER_OR_EQUAL,
		T_SL => PHPTAGS_T_SL,
		T_SR => PHPTAGS_T_SR,
		T_UNSET_CAST => PHPTAGS_T_UNSET_CAST,
		T_BOOL_CAST => PHPTAGS_T_BOOL_CAST,
		T_ARRAY_CAST => PHPTAGS_T_ARRAY_CAST,
		T_STRING_CAST => PHPTAGS_T_STRING_CAST,
		T_DOUBLE_CAST => PHPTAGS_T_DOUBLE_CAST,
		T_INT_CAST => PHPTAGS_T_INT_CAST,
		T_DEC => PHPTAGS_T_DEC,
		T_INC => PHPTAGS_T_INC,
		T_BREAK => PHPTAGS_T_BREAK,
		T_CONTINUE => PHPTAGS_T_CONTINUE,
		T_UNSET => PHPTAGS_T_UNSET,
		T_ISSET => PHPTAGS_T_ISSET,
		T_EMPTY => PHPTAGS_T_EMPTY,
		T_NEW => PHPTAGS_T_NEW,
	);

	/**
	 * Unfurled operator precedence
	 * key - operator
	 * value - precedence
	 * @var array
	 */
	private static $precedencesMatrix=array();

	private $stack = array();
	private $tokens;
	private $id;
	private $text;
	private $tokenLine;
	private $place;
	private $debug = array();
	private $stackMemory = array();

	function __construct() {
		if ( !self::$precedencesMatrix ) {
			foreach ( self::$operatorsPrecedence as $key => &$value ) {
				self::$precedencesMatrix += array_fill_keys( $value, $key );
			}
		}
	}

	/**
	 * Make The Lexical analysis and fill $this->tokens
	 * @param string $source PHP source code
	 */
	private function setTokensFromSource( $source ) {
		$tokens = token_get_all( "<?php $source ?>" );

		$this->tokens = $tokens;
		reset( $this->tokens );
		$this->tokenLine = 0;
		$this->stepUP();
	}

	public static function compile( $source, $place = 'Command line code' ) {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance->getBytecode( $source, $place );
	}

	private function getBytecode( $source, $place ) {
		$this->place = $place;
		$this->setTokensFromSource( $source );

		$this->stepBlockOperators( T_CLOSE_TAG, false );

		$return = $this->stack;
		$this->stack = array();
		return $return;
	}

	private function stepBlockOperators( $endToken, $throwEndTag = true ) {
		while ( $this->id != $endToken ) {
			$result = $this->stepFirstOperator( $throwEndTag );
			if ( !$result ) {
				if ( $this->id == ';' ) { // Example: ;;;;
					$this->stepUP( $throwEndTag ); // @todo fix it
				} else {
					$value =& $this->getNextValue();
					if ( $value ) { // Example: $foo=1;
						$dummy = array( PHPTAGS_STACK_RESULT=>null );
						$this->addValueIntoStack( $value, $dummy, PHPTAGS_STACK_RESULT );
						unset( $dummy );
						//$this->stack[] =& $value;
						if ( $this->id != ';' ) { // Example: $foo=1,
							// PHP Parse error:  syntax error, unexpected $id, expecting ';'
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
						}
						$this->stepUP( $throwEndTag );
					} else {
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
				}
			}
		}
	}

	private function stepFirstOperator( $throwEndTag = true ) {
		$id = $this->id;
		$text = $this->text;
		switch ( $id ) {
			case T_ECHO:
				$this->stepUP();
				$value =& $this->getNextValue();
				if( !$value ) { // Example: echo ;
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				do {
					$echo = array(
						PHPTAGS_STACK_COMMAND => PHPTAGS_T_PRINT,
						PHPTAGS_STACK_PARAM => null,
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => $text,
					);
					$this->addValueIntoStack( $value, $echo, PHPTAGS_STACK_PARAM );
					$this->stack[] =& $echo;
					unset( $echo );
					if ( current($this->tokens) != ',' ) {
						break;
					}
					$this->stepUP();
				} while ( $value =& $this->getNextValue() );

				if ( $this->id != ';' ) { // Example echo "foo"%
					// PHP Parse error:  syntax error, unexpected $id, expecting ';'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "';'" ), $this->tokenLine, $this->place );
				}
				$this->stepUP( $throwEndTag );
				return true;
			case T_IF:
				$tmp =& $this->stepIfConstruct( true, $throwEndTag );
				if ( $tmp !== true && $tmp !== false ) {
					$this->stack[] =& $tmp;
				}
				return true;
			case T_WHILE:
				$this->stepWhileConstruct( $throwEndTag );
				return true;
			case T_FOREACH:
				$this->stepForeachConstruct( $throwEndTag );
				return true;
			case T_CONTINUE:
			case T_BREAK:
				$this->stepUP();
				$value =& $this->getNextValue();

				if ( $this->id != ';' ) { // Example: continue 5#
					// PHP Parse error:  syntax error, unexpected $id, expecting ';'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
				}
				if( !$value ) { // Example: continue;
					unset( $value );
					$value = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>1 );
				}
				$operator = array( PHPTAGS_STACK_COMMAND=>self::$runtimeOperators[$id], PHPTAGS_STACK_RESULT=>null, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				$this->addValueIntoStack( $value, $operator, PHPTAGS_STACK_RESULT );
				$this->stack[] = $operator;
				return true;
			case T_GLOBAL:
				$variables = array();
				do {
					$this->stepUP();
					$value =& $this->getNextValue();

					if ( $this->id != ',' && $this->id != ';' ) { // Example: global $foo#
						// PHP Parse error:  syntax error, unexpected $id, expecting ',' or ';'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "';'" ), $this->tokenLine, $this->place );
					}
					if ( $value === false ) { // Example: global;
						// PHP Parse error:  syntax error, unexpected $id,
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					if ( $value[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_VARIABLE ) { // Example global $foo=5;
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[PHPTAGS_STACK_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
					}
					if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) {
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[', "','", "';'" ), $this->tokenLine, $this->place );
					}
					$variables[] = $value[PHPTAGS_STACK_PARAM];
				} while ( $this->id == ',' );
				$this->stepUP( $throwEndTag );

				$this->stack[] = array(
					PHPTAGS_STACK_COMMAND => PHPTAGS_T_GLOBAL,
					PHPTAGS_STACK_PARAM => $variables,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				return true;
			case T_STATIC:
				$this->stack_push_memory();
				do {
					$this->stepUP();
					$value =& $this->getNextValue();

					if ( $this->id != ',' && $this->id != ';' ) { // Example: static $foo#
						// PHP Parse error:  syntax error, unexpected $id, expecting ',' or ';'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "';'" ), $this->tokenLine, $this->place );
					}
					if ( $value === false ) { // Example: static;
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, token_name($id) ), $this->tokenLine, $this->place );
					}
					if ( $value[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
						if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) {
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[', "','", "';'" ), $this->tokenLine, $this->place );
						}
						$this->stack_pop_memory();
						$this->stack[] = array(
							PHPTAGS_STACK_COMMAND => PHPTAGS_T_STATIC,
							PHPTAGS_STACK_PARAM => $value[PHPTAGS_STACK_PARAM],
							PHPTAGS_STACK_RESULT => null,
							PHPTAGS_STACK_DO_TRUE => false,
							PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
							PHPTAGS_STACK_DEBUG => $text );
						$this->stack_push_memory();
					} elseif ( $value[PHPTAGS_STACK_COMMAND] == '=' ) {
						if ( isset($value[PHPTAGS_STACK_PARAM][PHPTAGS_STACK_ARRAY_INDEX]) ) {
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[', "','", "';'" ), $this->tokenLine, $this->place );
						}
						$operator = array(
							PHPTAGS_STACK_COMMAND => PHPTAGS_T_STATIC,
							PHPTAGS_STACK_PARAM => $value[PHPTAGS_STACK_PARAM][PHPTAGS_STACK_PARAM],
							PHPTAGS_STACK_RESULT => &$value[PHPTAGS_STACK_PARAM_2],
							PHPTAGS_STACK_DO_TRUE => $this->stack,
							PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
							PHPTAGS_STACK_DEBUG => $text );
						$this->stack_pop_memory();
						$this->stack[] = $operator;
						$this->stack_push_memory();
					} else { // Example static 5+5;
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[PHPTAGS_STACK_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
					}
				} while ( $this->id == ',' );
				$this->stepUP( $throwEndTag );
				$this->stack_pop_memory();
				return true;
		}
		return false;
	}

	private function & getFunctionParameters( $functionName, $objectName = false ) {
		$result = array();
		$i = 0;
		while ( $value =& $this->getNextValue() ) {
			if ( $value[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
				$value[PHPTAGS_STACK_PARAM_2] =& $result;
				$value[PHPTAGS_STACK_AIM] = $i;
				$result[$i] = null;
			} else {
				$result[$i] =& $value[PHPTAGS_STACK_RESULT];
			}
			if ( $value[PHPTAGS_STACK_COMMAND] ) {
				$this->stack[] =& $value;
			}
			if ( $objectName ) {
				$ref =& $objectName[0];
				$hookType = $ref[PHPTAGS_STACK_HOOK_TYPE];
			} else {
				$ref = false;
				$hookType = PHPTAGS_HOOK_FUNCTION;
			}
			$hookCheckParam = array(
				PHPTAGS_STACK_COMMAND => PHPTAGS_T_HOOK_CHECK_PARAM,
				PHPTAGS_STACK_HOOK_TYPE => $hookType,
				PHPTAGS_STACK_PARAM_3 => &$ref,
				PHPTAGS_STACK_PARAM => false, // $functionName
				PHPTAGS_STACK_PARAM_2 => $value[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE,
				PHPTAGS_STACK_AIM => $i,
				PHPTAGS_STACK_RESULT => &$result,
				PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
			);
			$func = $functionName; // clone the function name for use in the next loop
			$this->addValueIntoStack( $func, $hookCheckParam, PHPTAGS_STACK_PARAM );
			$this->stack[] =& $hookCheckParam;
			unset( $ref, $hookCheckParam, $func );

			if ( current($this->tokens) != ',' ) {
				break;
			}

			$this->stepUP();
			$i++;
		}
		return $result;
	}

	private function & getNextValue( $operator = ',' ) {
		$val =& $this->stepValue(); // Get a value
		if ( $val !== false ) { // The value was received
			if ( $val[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_INC || $val[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_DEC ) { // Operators Incrementing have the highest priority
				$tmp = array( PHPTAGS_STACK_COMMAND=>false, PHPTAGS_STACK_RESULT=>&$val[PHPTAGS_STACK_RESULT] );
				$this->stack[] =& $val;
				$val =& $tmp;
			}
			// Look for operator
			$operatorPrecedence = self::$precedencesMatrix[ $operator ]; // The precedence of the operator
			$oper =& $this->getOperator( $val, $operatorPrecedence ); // Get arithmetic operator
			if ( $oper ) {
				return $oper;
			}
		} // Value was not received
		return $val;
	}

	private function stepUP( $throwEndTag = true ) {
		static $matches = array(); // @todo remove in PHP 5.4
		$id = $text = false;

		while ( $token = next($this->tokens) ) {
			if ( is_string($token) ) {
				$id = $text = $token;
				$this->debug[] = array( $text );
			} else {
				list( $id, $text, $this->tokenLine ) = $token;
				$this->debug[] = array( $text, $id );
			}

			if ( $throwEndTag && $id == T_CLOSE_TAG ) {
				// PHP Parse error:  syntax error, unexpected '$end'
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '$end' ), $this->tokenLine, $this->place );
			} elseif ( $id != T_COMMENT && $id != T_DOC_COMMENT && $id != T_WHITESPACE ) {;
				break;
			} else {
				$this->tokenLine += preg_match_all( '#\n#', $text, $matches );
			}
		}
		if ( $token === false ) {
			// PHP Parse error:  syntax error, unexpected '$end'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '$end' ), $this->tokenLine, $this->place );
		}

		$this->id = $id;
		$this->text = $text;
	}

	private function stack_push_memory() {
		$this->stackMemory[] = $this->stack;
		$this->stack = array();
	}

	private function stack_pop_memory() {
		if ( $this->stackMemory ) {
			$this->stack = array_pop( $this->stackMemory );
//		} else {
//			throw new Exception;  // @todo
		}
	}

	private function & stepValue( $owner = false ) {
		$result = false;

		$id = $this->id;
		$text = $this->text;
		switch ( $id ) {
			case T_LNUMBER:
			case T_ONUMBER:
			case T_NUM_STRING:
				if ( isset($text[0]) && $text[0] == 0 ) {
					if ( isset($text[1]) && ($text[1] == 'x' || $text[1] == 'X') ) {
						$tmp = intval( $text, 16 );
					} else {
						$tmp = intval( $text, 8 );
					}
				} else {
					$tmp = (int)$text;
				}
				$result = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>$tmp, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				break;
			case T_DNUMBER:
				$epos = stripos($text, 'e');
				if ( $epos === false ) {
					$tmp = (float)$text;
				} else {
					$tmp = (float)( substr($text, 0, $epos) * pow(10, substr($text, $epos+1)) );
				}
				$result = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>$tmp, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				break;
			case T_CONSTANT_ENCAPSED_STRING:
				if ( $text[0] == '\'' ) {
					static $pattern_apostrophe = array(
						'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\\'/', # (\\)*\'
						'/\\\\\\\\/', #							\\
					);
					static $replacement_apostrophe = array( '$1\'', '\\' );
					$tmp = preg_replace( $pattern_apostrophe, $replacement_apostrophe, substr($text, 1, -1) );
					$result = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>$tmp, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
					break; // ************** EXIT **************
				}
				$text = substr($text, 1, -1);
				// break is not necessary here
			case T_ENCAPSED_AND_WHITESPACE:
				static $pattern = array(
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\"/', # (\\)*\"
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\n/', # (\\)*\n
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\r/', # (\\)*\r
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\t/', # (\\)*\t
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\v/', # (\\)*\v
					'/(?<!\\\\)((?:\\\\\\\\)*+)\\\\\$/', # (\\)*\$
					'/\\\\\\\\/', #						  \\
				);
				static $replacement = array( '$1"', "$1\n", "$1\r", "$1\t", "$1\v", '$1$', '\\' );
				$tmp = preg_replace( $pattern, $replacement, $text );
				$result = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>$tmp, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				break;
			case T_STRING:
				if( strcasecmp($text, 'true') == 0 ) { // $id here must be T_STRING
					$tmp = true;
				} elseif( strcasecmp($text, 'false') == 0 ) {
					$tmp = false;
				} elseif( strcasecmp($text, 'null') == 0 ) {
					$tmp = null;
				} else { // constant, function, etc...

					$this->stepUP();

					$result = array( // define blank hook as the constant
						PHPTAGS_STACK_COMMAND => PHPTAGS_T_HOOK,
						PHPTAGS_STACK_HOOK_TYPE => PHPTAGS_HOOK_GET_CONSTANT,
						PHPTAGS_STACK_PARAM => $text,  // function or method
						PHPTAGS_STACK_PARAM_2 => false, // &$functionParameters
						PHPTAGS_STACK_PARAM_3 => false, // false or &object
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => $text,
					);

					if ( $this->id == '(' ) { // it is function
						$this->stepFunction( $result, array(PHPTAGS_STACK_COMMAND=>false, PHPTAGS_STACK_RESULT=>$text), $owner );
					} elseif ( $owner !== false ) { // it is an objects property. Example: it's 'bar' for FOO::bar
						$result[PHPTAGS_STACK_HOOK_TYPE] = $owner[1] === true ? PHPTAGS_HOOK_GET_OBJECT_CONSTANT : PHPTAGS_HOOK_GET_OBJECT_PROPERTY;
						$this->addValueIntoStack( $owner[0], $result, PHPTAGS_STACK_PARAM_3 );
					} elseif ( $this->id == T_DOUBLE_COLON ) { // it is static constant or method of an object. Examples: FOO::property or FOO::method()
						$result[PHPTAGS_STACK_COMMAND] = false;
						$result[PHPTAGS_STACK_RESULT] = $text;

						$result = & $this->stepMethodChaining( $result, true );
					}

					return $result;
				}
				$result = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>$tmp, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				break;
			case '"':
				$this->stepUP();
				$strings = array();
				$i = 0;
				while ( $this->id != '"' ) {
					if ( $this->id == T_CURLY_OPEN || $this->id == '}' ) {
						$this->stepUP();
					} else {
						$val =& $this->stepValue();
						if ( $val ) { // echo "abcd$foo
							$strings[$i] = null;
							$this->addValueIntoStack( $val, $strings, $i );
						} else {
							// PHP Parse error:  syntax error, unexpected $id, expecting '"'
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'\"'" ), $this->tokenLine, $this->place );
						}
						$i++;
					}
				}
				$result = array( PHPTAGS_STACK_COMMAND=>'"', PHPTAGS_STACK_PARAM=>&$strings, PHPTAGS_STACK_RESULT=>null, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				break;
			case T_VARIABLE:
				$cannotRead = false;

				$variable = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_VARIABLE, PHPTAGS_STACK_PARAM=>substr($text, 1), PHPTAGS_STACK_PARAM_2=>null, PHPTAGS_STACK_RESULT=>null, PHPTAGS_STACK_TOKEN_LINE=>$this->tokenLine, PHPTAGS_STACK_DEBUG=>$text );
				$this->stepUP();

checkOperators:
				if ( $this->id == '(' ) { // it is function
					$variable =& $this->stepFunctionFromVariable( $variable, $text, $owner );
				} elseif ( $this->id == '[' ) { // There is array index
					$variable[PHPTAGS_STACK_ARRAY_INDEX] = array();
					$i = 0;
					do { // Example: $foo[
						$this->stepUP();
						$indexVal =& $this->getNextValue();
						if ( $this->id != ']' ) { // Example: $foo[1] or $foo[]
							// PHP Parse error:  syntax error, unexpected $id, expecting ']'
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "']'" ), $this->tokenLine, $this->place );
						}
						if ( $indexVal ) { // Example: $foo[1]
							$variable[PHPTAGS_STACK_ARRAY_INDEX][$i] = null;
							$this->addValueIntoStack( $indexVal, $variable[PHPTAGS_STACK_ARRAY_INDEX], $i );
						} else { // Example: $foo[]
							$variable[PHPTAGS_STACK_ARRAY_INDEX][$i] = INF;
							$cannotRead = true;
						}
						$this->stepUP();
						$i++;
					} while ( $this->id == '[' );
				} // There is not array index

				$id = $this->id;
				$text = $this->text;
				if ( in_array($id, self::$operatorsPrecedence[13]) ) { // It is assignment operator
					$this->stepUP();
					$val =& $this->getNextValue( '=' );
					if ( $val == false ) { // Example: $foo=;
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					if ( $owner === false && $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
						$return = array(
							PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
							PHPTAGS_STACK_PARAM => $variable,
							PHPTAGS_STACK_PARAM_2 => null,
							PHPTAGS_STACK_RESULT => null,
							PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
							PHPTAGS_STACK_DEBUG => $text,
						);
						$this->addValueIntoStack( $val, $return, PHPTAGS_STACK_PARAM_2 );
						return $return; // *********** EXIT ***********
					} elseif ( $owner === false ) {
						// $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_HOOK
						switch ( $variable[PHPTAGS_STACK_HOOK_TYPE] ) {
							case PHPTAGS_HOOK_GET_OBJECT_PROPERTY: // Example: $foo = new FOO(); $foo->bar =
								$variable[PHPTAGS_STACK_HOOK_TYPE] = PHPTAGS_HOOK_SET_OBJECT_PROPERTY;
								break;
							case PHPTAGS_HOOK_GET_STATIC_PROPERTY: // Example: $foo = new FOO(); $foo::$bar =
								$variable[PHPTAGS_STACK_HOOK_TYPE] = PHPTAGS_HOOK_SET_STATIC_PROPERTY;
								break;
							default :  // Example: FOO->$bar() =
								// PHP Parse error:  syntax error, unexpected $id
								throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
						}
						$this->addValueIntoStack( $val, $variable, PHPTAGS_STACK_PARAM_2 );
						return $variable; // *********** EXIT ***********
					} else {
						$return = array( // define hook
							PHPTAGS_STACK_COMMAND => PHPTAGS_T_HOOK,
							PHPTAGS_STACK_HOOK_TYPE => $owner[1] === true ? PHPTAGS_HOOK_SET_STATIC_PROPERTY : PHPTAGS_HOOK_SET_OBJECT_PROPERTY,
							PHPTAGS_STACK_PARAM => $owner[1] === true ? $variable[PHPTAGS_STACK_PARAM] : false,  // function or method
							PHPTAGS_STACK_PARAM_2 => false, // &$functionParameters
							PHPTAGS_STACK_PARAM_3 => false, // false or &object
							PHPTAGS_STACK_RESULT => null,
							PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
							PHPTAGS_STACK_DEBUG => $text,
						);
						if ( $owner[1] !== true ) { // Property name as variable. Example: $foo = new FOO(); $bar='anyproperty'; $foo->$bar =
							$this->addValueIntoStack( $variable, $return, PHPTAGS_STACK_PARAM ); // property name
						}
						$this->addValueIntoStack( $val, $return, PHPTAGS_STACK_PARAM_2 ); // value
						$this->addValueIntoStack( $owner[0], $return, PHPTAGS_STACK_PARAM_3 ); // object
						return $return;
					}
				} elseif ( $id == T_INC || $id == T_DEC ) {
					if ( $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
						$variable = array(
							PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
							PHPTAGS_STACK_PARAM => $variable,
							PHPTAGS_STACK_PARAM_2 => true, // Example: $foo++
							PHPTAGS_STACK_RESULT => null,
							PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
							PHPTAGS_STACK_DEBUG => $text,
						);
						$this->stepUP();
					} else { // $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_HOOK Example: FOO->$bar()++
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
				} elseif ( $cannotRead ) { // Example: echo $foo[];
					// PHP Fatal error:  Cannot use [] for reading
					throw new PhpTagsException( PhpTagsException::FATAL_CANNOT_USE_FOR_READING, null, $this->tokenLine, $this->place );
				} elseif ( $owner !== false ) {
					if ( $variable[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_HOOK ) { // Example: $bar = 'anymethod'; echo $foo->$bar();
						return $variable;
					} // Example: $bar = 'anyproperty'; echo $foo->$bar
					$return = array( // define hook
						PHPTAGS_STACK_COMMAND => PHPTAGS_T_HOOK,
						PHPTAGS_STACK_HOOK_TYPE => $owner[1] === true ? PHPTAGS_HOOK_GET_STATIC_PROPERTY : PHPTAGS_HOOK_GET_OBJECT_PROPERTY,
						PHPTAGS_STACK_PARAM => $owner[1] === true ? $variable[PHPTAGS_STACK_PARAM] : false,  // function or method
						PHPTAGS_STACK_PARAM_2 => false, // &$functionParameters
						PHPTAGS_STACK_PARAM_3 => false, // false or &object
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => $text,
					);
					if ( $owner[1] !== true ) { // Property name as variable. Example: $foo = new FOO(); $bar='anyproperty'; echo $foo->$bar;
						$this->addValueIntoStack( $variable, $return, PHPTAGS_STACK_PARAM ); // property name
					}
					$this->addValueIntoStack( $owner[0], $return, PHPTAGS_STACK_PARAM_3 ); // object

					$id = $this->id;
					if ( $id == T_OBJECT_OPERATOR || $id == T_DOUBLE_COLON ) {
						$return =& $this->stepMethodChaining( $return, $id == T_DOUBLE_COLON );
					}
					return $return;
				} elseif ( $id == T_OBJECT_OPERATOR || $id == T_DOUBLE_COLON ) { // Example: $foo->
					$this->stepUP();
					$variable =& $this->stepValue( array(&$variable, $id == T_DOUBLE_COLON) );
					if ( $variable == false || $variable[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_HOOK ) { // Example: $foo->;
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}

					$id = $this->id;
					if ( $id == T_OBJECT_OPERATOR || $id == T_DOUBLE_COLON ) {
						$variable =& $this->stepMethodChaining( $variable, $id == T_DOUBLE_COLON );
					}
					goto checkOperators;
				}
				return $variable; // *********** EXIT ***********
			case T_INC:
			case T_DEC:
				$this->stepUP();
				if ( $this->id == T_VARIABLE ) {
					$variable = $this->stepValue();
					//$value[PHPTAGS_STACK_RESULT] =& $variable;
					//$value[PHPTAGS_STACK_AIM] = ;
					//$variable[PHPTAGS_STACK_AIM]
					$result = array(
						PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
						PHPTAGS_STACK_PARAM => $variable,
						PHPTAGS_STACK_PARAM_2 => false, // Example: ++$foo
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => $text,
					);
				} else {
					// PHP Parse error:  syntax error, unexpected $id, expecting 'T_VARIABLE'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, 'T_VARIABLE' ), $this->tokenLine, $this->place );
				}
				return $result; // *********** EXIT ***********
			case '(':
				$this->stepUP();
				$result =& $this->getNextValue();
				if ( $this->id != ')' ) {
					// PHP Parse error:  syntax error, unexpected $tmp_id, expecting ')'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
				}
				break;
			case '+': // The left operators
			case '-':
			case '~':
			case '!':
			case T_ARRAY_CAST:	// (array)
			case T_INT_CAST:	// (int)
			case T_DOUBLE_CAST:	// (double)
			case T_STRING_CAST:	// (string)
			case T_BOOL_CAST:	// (bool)
			case T_UNSET_CAST:	// (unset)
				$this->stepUP();
				$tmp =& $this->stepValue();
				if ( $tmp ) {
					$result = array(
						PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_PARAM_2 => null,
						PHPTAGS_STACK_PARAM => 0,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => $text,
					);
					$this->addValueIntoStack( $tmp, $result, PHPTAGS_STACK_PARAM_2, true );
				}
				return $result;
			case T_ARRAY:
				$this->stepUP();
				if ( $this->id != '(' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting '('
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
				}
				// break is not necessary here
			case '[':
				$this->stepUP();
				$result =& $this->stepArrayConstruct( $id );
				break;
			case T_LIST:
				$this->stepUP();
				if ( $this->id != '(' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting '('
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
				}
				$param = array();
				$i = 0;
				do {
					$this->stepUP();
					if ( $this->id == T_LIST ) { // T_LIST inside T_LIST. Example: list( $foo, list
						$value =& $this->stepValue( T_LIST );
					} else {
						$value =& $this->getNextValue();
						if ( $value === false ) { // Example: list($foo, ,
							$value = null;
						} elseif ( $value[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_VARIABLE && $value[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_LIST ) { // Example: unset( $foo+1 );
							throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[PHPTAGS_STACK_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
						}
					}
					$tmp = array( PHPTAGS_STACK_COMMAND=>null, PHPTAGS_STACK_RESULT=>&$value );
					$param[$i] = null;
					$this->addValueIntoStack( $tmp, $param, $i++ );
					unset( $value, $tmp );
				} while ( $this->id == ',' );
				if ( $this->id != ')' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting ')'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
				}
				$result = array(
					PHPTAGS_STACK_COMMAND => PHPTAGS_T_LIST,
					PHPTAGS_STACK_PARAM => &$param,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				if ( $owner != T_LIST ) {
					$this->stepUP();
					if ( $this->id != '=' ) { // It is not assignment operator
						// PHP Parse error:  syntax error, unexpected $id, expecting '='
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'='" ), $this->tokenLine, $this->place );
					}
					$this->stepUP();
					$val =& $this->getNextValue( '=' );
					if ( $val == false ) {
						// PHP Parse error:  syntax error, unexpected $id
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					$return = array(
						PHPTAGS_STACK_COMMAND => '=',
						PHPTAGS_STACK_PARAM => $result,
						PHPTAGS_STACK_PARAM_2 => null,
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => '=',
					);
					$this->addValueIntoStack( $val, $return, PHPTAGS_STACK_PARAM_2 );
					return $return; // *********** EXIT ***********
				}
				break;
			case T_PRINT:
				$this->stepUP();
				$value =& $this->getNextValue();
				if ( $this->id != ';' ) { // Example print "foo"%
					// PHP Parse error:  syntax error, unexpected $id, expecting ';'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "';'" ), $this->tokenLine, $this->place );
				}
				if( $value === false ) { // Example: print ;
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				$result = array(
					PHPTAGS_STACK_COMMAND => PHPTAGS_T_PRINT,
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_RESULT => 1,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text
				);
				$this->addValueIntoStack( $value, $result, PHPTAGS_STACK_PARAM );
				return $result;
			case T_EMPTY:
			case T_ISSET:
			case T_UNSET:
				$this->stepUP();
				if ( $this->id != '(' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting '('
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
				}
				$param = array();
				do {
					$this->stepUP();
					$value =& $this->getNextValue();
					if ( $value === false ) { // Example: unset();
						// PHP Parse error:  syntax error, unexpected $id,
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
					}
					if ( $value[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_VARIABLE ) { // Example: unset( $foo+1 );
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[PHPTAGS_STACK_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
					}
					$param[] =& $value;
				} while ( $this->id == ',' );
				if ( $this->id != ')' ) {
					// PHP Parse error:  syntax error, unexpected $id, expecting ')'
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
				}
				$result = array(
					PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
					PHPTAGS_STACK_PARAM => $param,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				break;
			case T_NEW:
				$result = array(
					PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
					PHPTAGS_STACK_HOOK_TYPE => PHPTAGS_HOOK_OBJECT_METHOD,
					PHPTAGS_STACK_PARAM_2 => null,
					PHPTAGS_STACK_PARAM_3 => null, // object name
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				$this->stepUP();

				if ( $this->id == T_STRING ) {
					$result[PHPTAGS_STACK_PARAM_3] = $this->text;
					$this->stepUP();
				} elseif ( $this->id == T_VARIABLE ) {
					$value =& $this->stepValue();
					$this->addValueIntoStack( $value, $result, PHPTAGS_STACK_PARAM_3, false );
				} else {
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array($this->id), $this->tokenLine, $this->place );
				}
				if ( $this->id == '(' ) { // it has parameters
					$this->stepUP();
					$objectParameters =& $this->getFunctionParameters(
							array( PHPTAGS_STACK_COMMAND => false, PHPTAGS_STACK_RESULT => PHPTAGS_METHOD_CONSTRUCTOR ),
							array( &$result )
						);
					if ( $this->id != ')' ) {
						// PHP Parse error:  syntax error, unexpected $tmp_id, expecting ')'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
					}
					$this->stepUP();
					$result[PHPTAGS_STACK_PARAM_2] =& $objectParameters;
				} else {
					$result[PHPTAGS_STACK_PARAM_2] = null;
				}
				return $result;
		}
		if ( $result !== false ) {
			$this->stepUP();
		}
		return $result;
	}

	private function & getOperator( &$value, $precedence ) {
		$result = false;
		while ( true ) {
			$id = $this->id;
			$text = $this->text;
			if ( array_key_exists($id, self::$precedencesMatrix) && self::$precedencesMatrix[$id] < $precedence ) { // @todo isset()
				// if the current token is an operator and it precedence is less the variable $precedence
				// else leave loop

				// Ternary operators handled function getTernaryOperator()
				$ternary =& $this->getTernaryOperator( $value );
				if ( $ternary !== false ) {
					$value =& $ternary;
					$result =& $ternary;
					continue;
				}

				$this->stepUP();

				// Make the operator
				$operator = array(
					PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
					PHPTAGS_STACK_PARAM => null, //&$value[PHPTAGS_STACK_RESULT],
					PHPTAGS_STACK_PARAM_2 => null, //&$nextValue[PHPTAGS_STACK_RESULT],
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				$didit = $this->addValueIntoStack( $value, $operator, PHPTAGS_STACK_PARAM, false ); // Add the first value into the stack

				$nextValue =& $this->getNextValue( $id ); // Get the next value, it is the second value for the operator
				// $nextValue can be as the result of other operators if them the precedence larger the precedence of the current operator
				// Example: 1*2+3; $nextValue will be '2'
				// Example: 1+2*3; $nextValue will be the result of the operator '2*3'
				if ( $nextValue === false ) { // $nextValue must be not false, throw the exception
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				$this->addValueIntoStack( $nextValue, $operator, PHPTAGS_STACK_PARAM_2, $didit ); // Add the second value into the stack
				$value =& $operator; // Set the operator as the value for the next loop
				$result =& $operator;
				unset( $operator );
			} else {
				break;
			}
		}
		return $result;
	}

	private function & getTernaryOperator( &$value ) {
		static $ternaryOperators = array();
		$result = false;
		$id = $this->id;
		$text = $this->text;
		switch ( $id ) {
			case '?':
				// Make the operator without the second value
				$ternary = array(
					PHPTAGS_STACK_COMMAND => '?',
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_PARAM_2 => null,
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				array_unshift( $ternaryOperators, $ternary );
				$didValue = $this->addValueIntoStack( $value, $ternaryOperators[0], PHPTAGS_STACK_PARAM, false );
				if ( $didValue && $ternaryOperators[0][PHPTAGS_STACK_PARAM] === null ) {
					$ternaryOperators[0][PHPTAGS_STACK_PARAM] = false;
				}

				$this->stepUP();
				$this->stack_push_memory();
				$result =& $this->getNextValue(); // Get next value, it must be the ternary operator
				if ( $result !== false || $this->id != ':' ) {
					break;
				}
				// Example: $foo ?:
				$value_clone = $value;
				unset( $value );
				$value = $value_clone;
				$id = $this->id;
				// break is not necessary here
			case ':':
				if ( !isset($ternaryOperators[0]) ) {
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $id ), $this->tokenLine, $this->place );
				}

				$ternaryOperators[0][PHPTAGS_STACK_PARAM_2] = array(
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_PARAM_2 => null,
				);
				if ( $value[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
					$copy1 = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_COPY, PHPTAGS_STACK_PARAM=>null, PHPTAGS_STACK_RESULT=>&$ternaryOperators[0][PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM] );
					$this->addValueIntoStack( $value, $copy1, PHPTAGS_STACK_PARAM, false );
					$this->stack[] =& $copy1;
				} else {
					$this->addValueIntoStack( $value, $ternaryOperators[0][PHPTAGS_STACK_PARAM_2], PHPTAGS_STACK_PARAM, false );
				}

				if ( $this->stack ) {
					$stack_true = $this->stack;
					$this->stack = array();
				} else {
					$stack_true = false;
				}

				$this->stepUP();
				$nextValue =& $this->getNextValue( ':' ); // Get the next value, it is the right part of the ternary operator
				if ( $nextValue === false ) { // $nextValue must be not false, otherwise throw the exception
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}
				if ( $nextValue[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
					$copy2 = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_COPY, PHPTAGS_STACK_PARAM=>null, PHPTAGS_STACK_RESULT=>&$ternaryOperators[0][PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM_2] );
					$this->addValueIntoStack( $nextValue, $copy2, PHPTAGS_STACK_PARAM, false );
					$this->stack[] =& $copy2;
				} else {
					$this->addValueIntoStack( $nextValue, $ternaryOperators[0][PHPTAGS_STACK_PARAM_2], PHPTAGS_STACK_PARAM_2, false );
				}
				$stack_false = $this->stack ?: false;
				$this->stack_pop_memory();

				if ( $ternaryOperators[0][PHPTAGS_STACK_PARAM] == true ) { // Example: echo true ? ...
					$result = array( PHPTAGS_STACK_COMMAND=>false, PHPTAGS_STACK_RESULT=>&$ternaryOperators[0][PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM] );
					if ( $stack_true !== false ) {
						$this->stack = array_merge( $this->stack, $stack_true );
					}
				} elseif ( $ternaryOperators[0][PHPTAGS_STACK_PARAM] === false ) { // Example: echo false ? ...
					$result = array( PHPTAGS_STACK_COMMAND=>false, PHPTAGS_STACK_RESULT=>&$ternaryOperators[0][PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_PARAM_2] );
					if ( $stack_false !== false ) {
						$this->stack = array_merge( $this->stack, $stack_false );
					}
				} else { // It is not static value, Example: echo $foo ? ...
					$ternaryOperators[0][PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_TRUE] = $stack_true;
					$ternaryOperators[0][PHPTAGS_STACK_PARAM_2][PHPTAGS_STACK_DO_FALSE] = $stack_false;
					$result =& $ternaryOperators[0];
				}
				array_shift( $ternaryOperators );
				break;
			case T_BOOLEAN_AND:	// &&
			case T_LOGICAL_AND:	// and
				$this->stepUP();
				$this->stack_push_memory();
				$nextValue =& $this->getNextValue( $id );
				if ( $nextValue === false ) {
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}

				$operator = array(
					PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_PARAM_2 => null,
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);

				$this->addValueIntoStack( $nextValue, $operator, PHPTAGS_STACK_PARAM_2, true );
				$stack_true = $this->stack ?: false;
				$this->stack_pop_memory();
				$doit = $this->addValueIntoStack( $value, $operator, PHPTAGS_STACK_PARAM, true );

				if ( $stack_true === false && $operator[PHPTAGS_STACK_PARAM_2] == false || $doit === true && $operator[PHPTAGS_STACK_PARAM] == false ) {
					$result = array( PHPTAGS_STACK_COMMAND => null,	PHPTAGS_STACK_RESULT => false ); // it's always false
					break;
				}
				if ( $stack_true === false && $operator[PHPTAGS_STACK_PARAM_2] == true && $doit === true && $operator[PHPTAGS_STACK_PARAM] == true ) {
					$result = array( PHPTAGS_STACK_COMMAND => null,	PHPTAGS_STACK_RESULT => true ); // it's always true
					break;
				}

				if ( $stack_true === false ) {
					$stack_true = array( &$operator );
				} else {
					$stack_true[] =& $operator;
				}

				$param2 = array(
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_PARAM => &$operator[PHPTAGS_STACK_RESULT],
					PHPTAGS_STACK_DO_TRUE => $stack_true,
					PHPTAGS_STACK_PARAM_2 => false,
					PHPTAGS_STACK_DO_FALSE => false,
				);
				$result = array(
					PHPTAGS_STACK_COMMAND => '?',
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_PARAM_2 => $param2,
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				$this->addValueIntoStack( $value, $result, PHPTAGS_STACK_PARAM, true );
				break;
			case T_BOOLEAN_OR:	// ||
			case T_LOGICAL_OR:	// or
				$this->stepUP();
				$this->stack_push_memory();
				$nextValue =& $this->getNextValue( $id );
				if ( $nextValue === false ) {
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
				}

				$operator = array(
					PHPTAGS_STACK_COMMAND => self::$runtimeOperators[$id],
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_PARAM_2 => null,
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);

				$this->addValueIntoStack( $nextValue, $operator, PHPTAGS_STACK_PARAM_2, true );
				$stack_false = $this->stack ?: false;
				$this->stack_pop_memory();
				$doit = $this->addValueIntoStack( $value, $operator, PHPTAGS_STACK_PARAM, true );

				if ( $stack_false === false && $operator[PHPTAGS_STACK_PARAM_2] == true || $doit === true && $operator[PHPTAGS_STACK_PARAM] == true ) {
					$result = array( PHPTAGS_STACK_COMMAND => null,	PHPTAGS_STACK_RESULT => true ); // it's always true
					break;
				}
				if ( $stack_false === false && $operator[PHPTAGS_STACK_PARAM_2] == false && $doit === true && $operator[PHPTAGS_STACK_PARAM] == false ) {
					$result = array( PHPTAGS_STACK_COMMAND => null,	PHPTAGS_STACK_RESULT => false ); // it's always false
					break;
				}

				if ( $stack_false === false ) {
					$stack_false = array( &$operator );
				} else {
					$stack_false[] =& $operator;
				}

				$param2 = array(
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_PARAM => true,
					PHPTAGS_STACK_DO_TRUE => false,
					PHPTAGS_STACK_PARAM_2 => &$operator[PHPTAGS_STACK_RESULT],
					PHPTAGS_STACK_DO_FALSE => $stack_false,
				);
				$result = array(
					PHPTAGS_STACK_COMMAND => '?',
					PHPTAGS_STACK_PARAM => null,
					PHPTAGS_STACK_PARAM_2 => $param2,
					PHPTAGS_STACK_RESULT => null,
					PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
					PHPTAGS_STACK_DEBUG => $text,
				);
				$this->addValueIntoStack( $value, $result, PHPTAGS_STACK_PARAM, true );
				break;
		}
		return $result;
	}

	private function & stepIfConstruct( $allowElse, $throwEndTag = true ) {
		$return = false;
		$text = $this->text; // if
		$tokenLine = $this->tokenLine;
		$this->stepUP();

		if ( $this->id != '(' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting '('
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		$value =& $this->getNextValue();
		if ( $value === false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $this->id != ')' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting ')'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
		}

		// Make the 'if' operator
		$if = array(
			PHPTAGS_STACK_COMMAND => PHPTAGS_T_IF,
			PHPTAGS_STACK_PARAM => null,
			PHPTAGS_STACK_RESULT => null,
			PHPTAGS_STACK_TOKEN_LINE => $tokenLine,
			PHPTAGS_STACK_DEBUG => $text,
		);
		$this->addValueIntoStack( $value, $if, PHPTAGS_STACK_PARAM, false );

		$this->stack_push_memory();
		$this->stepUP();
		if ( $this->id == '{' ) {
			$this->stepUP();
			$this->stepBlockOperators( '}' );
			$this->stepUP( $throwEndTag );
		} else {
			$this->stepFirstOperator( $throwEndTag );
		}
		if ( $this->stack ) {
			$if[PHPTAGS_STACK_DO_TRUE] = $this->stack;
			$this->stack = array();
		}

		if ( $allowElse ) {
			if ( $this->id == T_ELSE ) {
				$this->stepUP();
				if ( $this->id == '{' ) {
					$this->stepUP();
					$this->stepBlockOperators( '}' );
					$this->stepUP( $throwEndTag );
				} else {
					$this->stepFirstOperator( $throwEndTag );
				}
				if ( $this->stack ) {
					$if[PHPTAGS_STACK_DO_FALSE] = $this->stack;
				}
			} elseif ( $this->id == T_ELSEIF ) {
				$tmp =& $this->stepIfConstruct( true, $throwEndTag );
				if ( $tmp !== true  && $tmp !== false ) {
					$this->stack[] =& $tmp;
					$if[PHPTAGS_STACK_DO_FALSE] = $this->stack;
				} elseif ( $this->stack ) {
					$if[PHPTAGS_STACK_DO_FALSE] = $this->stack;
				}
			}
		}

		$this->stack_pop_memory();
		if ( $if[PHPTAGS_STACK_PARAM] == true ) {
			if ( $if[PHPTAGS_STACK_DO_TRUE] ) {
				$this->stack = array_merge( $this->stack, $if[PHPTAGS_STACK_DO_TRUE] );
			}
			$return = true;
		} elseif ( $if[PHPTAGS_STACK_PARAM] === false ) {
			if ( isset($if[PHPTAGS_STACK_DO_FALSE]) && $if[PHPTAGS_STACK_DO_FALSE] ) {
				$this->stack = array_merge( $this->stack, $if[PHPTAGS_STACK_DO_FALSE] );
			}
			// $return = false; it is already false
		} else {
			$return =& $if;
		}
		return $return;
	}

	private function & stepArrayConstruct( $startToken ) {
		$key = false;
		$result = false;
		$endToken = $startToken == '[' ? ']' : ')';
		$array = array();
		$i = 0;
		$r = 0;
		while ( $value =& $this->getNextValue() ) {
			switch ( $this->id ) {
				case ',':
				case $endToken:
					if ( $key === false ) {
						if ( $value[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
							$array[$i] = null;
							$copy = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_COPY, PHPTAGS_STACK_PARAM=>null, PHPTAGS_STACK_RESULT=>&$array[$i] );
							$this->addValueIntoStack( $value, $copy, PHPTAGS_STACK_PARAM, false );
							$this->stack[] =& $copy;
						} else {
							$this->addValueIntoStack( $value, $array, $i );
						}
						$i++;
					} else {
						if ( $result === false && $key[PHPTAGS_STACK_COMMAND] === null ) {
							$this->addValueIntoStack( $value, $array, $key[PHPTAGS_STACK_RESULT] );
						} elseif ( $result === false ) {
							$result = array(
								PHPTAGS_STACK_COMMAND => PHPTAGS_T_ARRAY,
								PHPTAGS_STACK_PARAM => array( &$array ) ,
								PHPTAGS_STACK_PARAM_2 => array( array(null, null) ),
								PHPTAGS_STACK_RESULT => null,
							);
							$this->addValueIntoStack( $key, $result[PHPTAGS_STACK_PARAM_2][$r], 0 );
							$this->addValueIntoStack( $value, $result[PHPTAGS_STACK_PARAM_2][$r], 1 );
							$r++;
							unset( $array );
							$array = array();
						} else {
							$result[PHPTAGS_STACK_PARAM][$r] = &$array;
							$result[PHPTAGS_STACK_PARAM_2][$r] = array( null, null );
							$this->addValueIntoStack( $key, $result[PHPTAGS_STACK_PARAM_2][$r], 0 );
							$this->addValueIntoStack( $value, $result[PHPTAGS_STACK_PARAM_2][$r], 1 );
							unset( $array );
							$array = array();
						}
						unset( $key );
						$key = false;
					}
					if ( $this->id == $endToken ) {
						break 2;
					}
					break;
				case T_DOUBLE_ARROW:
					if ( $key !== false ) {
						// PHP Parse error:  syntax error, unexpected $id, expecting ',' or ')'
						throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "','", "'$endToken'" ), $this->tokenLine, $this->place );
					}
					$key = &$value;
					unset( $value );
					if ( $key[PHPTAGS_STACK_COMMAND] ) {
						$this->stack[] = &$key; // Add the command for receive value into the stack
					}
					break;
			}
			$this->stepUP();
		}
		if ( $key !== false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $result === false ) {
			$result = array( PHPTAGS_STACK_COMMAND=>false, PHPTAGS_STACK_RESULT=>&$array );
		} elseif ( $array ) {
			$result[PHPTAGS_STACK_PARAM][] = &$array;
		}
		return $result;
	}

	private function stepWhileConstruct( $throwEndTag = true ) {
		$text = $this->text; // while
		$this->stack_push_memory();
		$tokenLine = $this->tokenLine;
		$operator =& $this->stepIfConstruct( false, $throwEndTag );
		$stack = $this->stack;
		$this->stack_pop_memory();
		if ( $operator !== false ) {
			if ( $operator !== true ) {
				$stack[] =& $operator;
				$stack = array_merge( $stack, $operator[PHPTAGS_STACK_DO_TRUE] );
				$operator[PHPTAGS_STACK_DO_FALSE] = array( array(PHPTAGS_STACK_COMMAND=>PHPTAGS_T_BREAK, PHPTAGS_STACK_RESULT=>1) );
				$operator[PHPTAGS_STACK_DO_TRUE] = false;
				$operator[PHPTAGS_STACK_DEBUG] = $text;
			}
			$stack[] = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_CONTINUE, PHPTAGS_STACK_RESULT=>1, PHPTAGS_STACK_TOKEN_LINE=>$tokenLine ); // Add operator T_CONTINUE to the end of the cycle

			$this->stack[] = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_WHILE, PHPTAGS_STACK_DO_TRUE=>$stack );
		}
		return true;
	}

	private function stepForeachConstruct( $throwEndTag = true ) {
		$text = $this->text;
		$tokenLine = $this->tokenLine;
		$this->stepUP();

		if ( $this->id != '(' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting '('
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "'('" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		$arrayExpression =& $this->stepValue();
		if ( $arrayExpression === false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $arrayExpression[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_VARIABLE && $arrayExpression[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_ARRAY ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_VARIABLE
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $arrayExpression[PHPTAGS_STACK_COMMAND], 'T_VARIABLE', 'T_ARRAY' ), $this->tokenLine, $this->place );
		}

		if ( $this->id != T_AS ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting 'T_AS'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, 'T_AS' ), $this->tokenLine, $this->place );
		}
		$text_as = $this->text;
		$this->stepUP();

		$value =& $this->stepValue();
		if ( $value === false ) {
			// PHP Parse error:  syntax error, unexpected $id
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
		}
		if ( $value[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_VARIABLE ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_VARIABLE
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[PHPTAGS_STACK_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
		}
		if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) {
			// PHP Parse error:  syntax error, unexpected '['
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[' ), $this->tokenLine, $this->place );
		}
		$t_as = array(
			PHPTAGS_STACK_COMMAND => PHPTAGS_T_AS,
			PHPTAGS_STACK_RESULT => null,
			PHPTAGS_STACK_PARAM => $value[PHPTAGS_STACK_PARAM], // Variable name
			PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
			PHPTAGS_STACK_DEBUG => $text_as,
		);

		if ( $this->id == T_DOUBLE_ARROW ) { // =>
			$this->stepUP();
			$value =& $this->stepValue();
			if ( $value === false ) {
				// PHP Parse error:  syntax error, unexpected $id
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
			if ( $value[PHPTAGS_STACK_COMMAND] != PHPTAGS_T_VARIABLE ) {
				// PHP Parse error:  syntax error, unexpected $id, expecting T_VARIABLE
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $value[PHPTAGS_STACK_COMMAND], 'T_VARIABLE' ), $this->tokenLine, $this->place );
			}
			if ( isset($value[PHPTAGS_STACK_ARRAY_INDEX]) ) {
				// PHP Parse error:  syntax error, unexpected '['
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( '[' ), $this->tokenLine, $this->place );
			}
			$t_as[PHPTAGS_STACK_PARAM_2] = $value[PHPTAGS_STACK_PARAM]; // Variable name
		}

		if ( $this->id != ')' ) {
			// PHP Parse error:  syntax error, unexpected $id, expecting T_DOUBLE_ARROW or ')'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $arrayExpression[PHPTAGS_STACK_COMMAND], 'T_DOUBLE_ARROW', "')'" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();

		$this->stack_push_memory();
		$asExpression = $arrayExpression;
		$this->addValueIntoStack( $asExpression, $t_as, PHPTAGS_STACK_RESULT );
		$this->stack[] =& $t_as;

		if ( $this->id == '{' ) {
			$this->stepUP();
			$this->stepBlockOperators( '}' );
			$this->stepUP( $throwEndTag );
		} else {
			$this->stepFirstOperator( $throwEndTag );
		}

		$this->stack[] = array( PHPTAGS_STACK_COMMAND=>PHPTAGS_T_CONTINUE, PHPTAGS_STACK_RESULT=>1, PHPTAGS_STACK_TOKEN_LINE=>$tokenLine ); // Add operator T_CONTINUE to the end of the cycle
		$foreach = array(
			PHPTAGS_STACK_COMMAND => PHPTAGS_T_FOREACH,
			PHPTAGS_STACK_PARAM => null,
			PHPTAGS_STACK_DO_TRUE => $this->stack,
			PHPTAGS_STACK_TOKEN_LINE => $tokenLine,
			PHPTAGS_STACK_DEBUG => $text,
		);
		$this->stack_pop_memory();
		$this->addValueIntoStack( $arrayExpression, $foreach, PHPTAGS_STACK_PARAM );
		$this->stack[] =& $foreach;
		return true;
	}

	private function & stepMethodChaining( &$result, $isStatic ) {
		do {
			$this->stepUP();
			$val =& $this->stepValue( array(&$result, $isStatic) );
			if ( $val == false ) { // Example: FOO::bar-> ;
				// PHP Parse error:  syntax error, unexpected $id
				throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
			switch ( $val[PHPTAGS_STACK_COMMAND] ) {
				case PHPTAGS_T_HOOK: // Examples: FOO::bar->too
					$result =& $val;
					break;
				case PHPTAGS_T_VARIABLE: // Example: FOO::bar->$variable
					$tmpresult = array( // define hook as the constant
						PHPTAGS_STACK_COMMAND => PHPTAGS_T_HOOK,
						PHPTAGS_STACK_PARAM => false,  // function or method
						PHPTAGS_STACK_PARAM_2 => false, // &$functionParameters
						PHPTAGS_STACK_PARAM_3 => false, // false or &object
						PHPTAGS_STACK_RESULT => null,
						PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
						PHPTAGS_STACK_DEBUG => $this->text,
					);
					$this->addValueIntoStack( $result, $tmpresult, PHPTAGS_STACK_PARAM_3 );
					$this->addValueIntoStack( $val, $tmpresult, PHPTAGS_STACK_PARAM );
					$result =& $tmpresult;
					break;
				default: // Example: FOO::bar-> #
					// PHP Parse error:  syntax error, unexpected $id
					throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id ), $this->tokenLine, $this->place );
			}
		} while ( $this->id == T_OBJECT_OPERATOR || $this->id == T_DOUBLE_COLON ); // Example: FOO::bar->
		return $result;
	}

	/**
	 * This function adds $value to the $aim in $command
	 * If $value has a variable then it will be added to stack
	 * If the values of the command are scalar then command can be processed
	 * and if $doit is TRUE then the command will be processed
	 * and in this case the function returns TRUE otherwise FALSE.
	 * @param array $value Array with PHPTAGS_STACK_COMMAND
	 * @param array $command Array with $aim
	 * @param string $aim The aim in $command
	 * @param boolean $doit Process the command if possible
	 * @return boolean Returns TRUE, if the command was processed
	 */
	private function addValueIntoStack( &$value, &$command, $aim, $doit = false ) {
		if ( $value[PHPTAGS_STACK_COMMAND] == PHPTAGS_T_VARIABLE ) {
			$value[PHPTAGS_STACK_PARAM_2] =& $command;
			$value[PHPTAGS_STACK_AIM] = $aim;
		} else {
			$command[$aim] =& $value[PHPTAGS_STACK_RESULT];
		}

		if ( $value[PHPTAGS_STACK_COMMAND] === null ) {
			// The values of the command are scalar
			if ( $doit ) {
				$tmp = array( PHPTAGS_STACK_COMMAND => PHPTAGS_T_RETURN, PHPTAGS_STACK_PARAM => &$command[PHPTAGS_STACK_RESULT] );
				$runtimeReturn = Runtime::run( array($command, $tmp),	array('PhpTags\\Compiler') );
				if ( $runtimeReturn instanceof PhpTagsException ) {
					return false;
				}
				$command = array(
					PHPTAGS_STACK_COMMAND => null, // Mark the operator as the already processed.
					PHPTAGS_STACK_RESULT => $runtimeReturn,
				);
			}
			return true;
		} elseif ( $value[PHPTAGS_STACK_COMMAND] !== false ) {
			$this->stack[] =& $value;
		}

		return false;
	}

	/**
	 * Returns Hook as function() or $object->method()
	 * @param array $hook The blank hook
	 * @param array $funcName The function name
	 * @param mixed $owner Object as array or FALSE for function
	 * @return array Hook
	 * @throws PhpTagsException
	 */
	private function & stepFunction( &$hook, $funcName, $owner ) {
		$this->stepUP();

		if ( $owner !== false ) { // $owner is object
			$hook[PHPTAGS_STACK_HOOK_TYPE] = $owner[1] === true ? PHPTAGS_HOOK_STATIC_METHOD : PHPTAGS_HOOK_OBJECT_METHOD;
			$this->addValueIntoStack( $owner[0], $hook, PHPTAGS_STACK_PARAM_3 );
			$hook[PHPTAGS_STACK_PARAM_2] =& $this->getFunctionParameters( $funcName, array( &$hook ) );
		} else { // it is function
			$hook[PHPTAGS_STACK_HOOK_TYPE] = PHPTAGS_HOOK_FUNCTION;
			$hook[PHPTAGS_STACK_PARAM_2] =& $this->getFunctionParameters( $funcName );
		}

		if ( $this->id != ')' ) {
			// PHP Parse error:  syntax error, unexpected $tmp_id, expecting ')'
			throw new PhpTagsException( PhpTagsException::PARSE_SYNTAX_ERROR_UNEXPECTED, array( $this->id, "')'" ), $this->tokenLine, $this->place );
		}
		$this->stepUP();
		return $hook;
	}

	/**
	 * Returns Hook as $function() or objects->$method() where name will be received from variable
	 * @param array $variable The variable as function or method name
	 * @param string $text Text of variable for debug
	 * @param mixed $owner Object as array or FALSE for function
	 * @return array Hook
	 * @throws PhpTagsException
	 */
	private function & stepFunctionFromVariable( $variable, $text, $owner ) {
		$hook = array( // define the blank hook
			PHPTAGS_STACK_COMMAND => PHPTAGS_T_HOOK,
			PHPTAGS_STACK_HOOK_TYPE => false,
			PHPTAGS_STACK_PARAM => false, // function or method name from $variable
			PHPTAGS_STACK_PARAM_2 => false, // &$functionParameters
			PHPTAGS_STACK_PARAM_3 => false, // false or &object
			PHPTAGS_STACK_RESULT => null,
			PHPTAGS_STACK_TOKEN_LINE => $this->tokenLine,
			PHPTAGS_STACK_DEBUG => $text,
		);

		$return =& $this->stepFunction( $hook, $variable, $owner );
		$this->addValueIntoStack( $variable, $return, PHPTAGS_STACK_PARAM ); // Add function or method name to hook
		return $return;
	}

}

