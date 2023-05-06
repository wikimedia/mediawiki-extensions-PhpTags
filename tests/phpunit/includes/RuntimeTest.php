<?php
namespace PhpTags;

use ExtensionRegistry;
use MediaWikiIntegrationTestCase;
use MediaWiki\MediaWikiServices;
use PhpTags\Hooks as PhpTagsHooks;
use PhpTags\HookException as PhpTagsHookException;

/**
 * @covers \PhpTags\Runtime
 */
class RuntimeTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		self::initializePhpTagsForTesting();
		$this->setTemporaryHook( 'PhpTagsBeforeCallRuntimeHook', 'PhpTags\\RuntimeTest::onPhpTagsBeforeCallRuntimeHook' );
	}

	public static function initializePhpTagsForTesting() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			wfDebug( 'PHPTags: test initialization ' . __FILE__ );

			PhpTagsHooks::addJsonFile( __DIR__ . '/PhpTags_test.json' );
			define( 'PHPTAGS_TEST', 'Test' );
			define( 'PHPTAGS_TEST_BANNED', 'Test' );

			wfDebug( 'PHPTags: run hook PhpTagsRuntimeFirstInit ' . __FILE__ );
			MediaWikiServices::getInstance()->getHookContainer()->run( 'PhpTagsRuntimeFirstInit' );
			PhpTagsHooks::loadData();
			Runtime::$loopsLimit = 1000;
		}
	}

	public static function onPhpTagsBeforeCallRuntimeHook( $hookType, $objectName, $methodName, $values ) {
		if ( $hookType === Runtime::H_GET_CONSTANT || $hookType === Runtime::H_GET_OBJECT_CONSTANT ) {
			$methodName = strtolower( $methodName );
		}
		if ( substr( $methodName, -6 ) === 'banned' ) {
			$hookTypeString = PhpTagsHooks::getCallInfo( PhpTagsHooks::INFO_HOOK_TYPE_STRING );
			Runtime::pushException( new PhpTagsHookException( "Sorry, you cannot use this $hookTypeString" ) );
			return false;
		}
		return true;
	}

	public function testRun_echo_null_1() {
		$this->assertEquals(
			[ null ],
			Runtime::runSource('echo null;')
		);
	}
	public function testRun_echo_true_1() {
		$this->assertEquals(
			[ true ],
			Runtime::runSource('echo true;')
		);
	}
	public function testRun_echo_false_1() {
		$this->assertEquals(
			[ false ],
			Runtime::runSource('echo false;')
		);
	}
	public function testRun_no_echo_1() {
		$this->assertEquals(
			[],
			Runtime::runSource(';')
		);
	}
	public function testRun_echo_apostrophe_1() {
		$this->assertEquals(
			[ 'Hello!' ],
			Runtime::runSource('echo "Hello!";')
		);
	}
	public function testRun_echo_apostrophe_2() {
		$this->assertEquals(
			[ 'Hello!' ],
			Runtime::runSource('echo ("Hello!");')
		);
	}

	public function testRun_echo_quotes_1() {
		$this->assertEquals(
			[ 'Hello!' ],
			Runtime::runSource("echo 'Hello!';")
		);
	}
	public function testRun_echo_quotes_2() {
		$this->assertEquals(
			[ 'Hello!' ],
			Runtime::runSource("echo ('Hello!');")
		);
	}

	public function testRun_echo_heredoc_1() {
		$this->assertEquals(
			[ 'Example of string
spanning multiple lines
using heredoc syntax.
' ], Runtime::runSource('
echo <<<EOT
Example of string
spanning multiple lines
using heredoc syntax.
EOT;
')
		);
	}
	public function testRun_echo_heredoc_2() {
		$this->assertEquals(
			[ 'Example of string
spanning multiple lines
using "heredoc" syntax.
' ], Runtime::runSource('
echo <<<EOT
Example of string
spanning multiple lines
using "heredoc" syntax.
EOT;
')
		);
	}
	public function testRun_echo_heredoc_3() {
		$this->assertEquals(
			[ 'Example of string
spanning multiple
 lines

using "heredoc" syntax.
' ], Runtime::runSource('
echo <<<EOT
Example of string
spanning multiple\n lines\n
using "heredoc" syntax.
EOT;
')
		);
	}
	public function testRun_echo_heredoc_4() {
		$this->assertEquals(
			[ 'Example of string BAR
spanning multiple lines
using heredoc syntax.
' ], Runtime::runSource('
$foo = "BAR";
echo <<<"EOT"
Example of string $foo
spanning multiple lines
using heredoc syntax.
EOT;
')
		);
	}
	public function testRun_echo_nowdoc_1() {
		$this->assertEquals(
			[ 'Example of string
spanning multiple lines
using nowdoc syntax.
' ], Runtime::runSource('
echo <<<\'EOT\'
Example of string
spanning multiple lines
using nowdoc syntax.
EOT;
')
		);
	}
	public function testRun_echo_nowdoc_2() {
		$this->assertEquals(
			[ 'Example of string
spanning multiple lines
using "nowdoc" syntax.
' ], Runtime::runSource('
echo <<<\'EOT\'
Example of string
spanning multiple lines
using "nowdoc" syntax.
EOT;
')
		);
	}
	public function testRun_echo_nowdoc_3() {
		$this->assertEquals(
			[ 'Example of string
spanning multiple\n lines\n
using "nowdoc" syntax.
' ], Runtime::runSource('
echo <<<\'EOT\'
Example of string
spanning multiple\n lines\n
using "nowdoc" syntax.
EOT;
')
		);
	}
	public function testRun_echo_nowdoc_4() {
		$this->assertEquals(
			[ 'Example of string $foo
spanning multiple lines
using nowdoc syntax.
' ], Runtime::runSource('
$foo = "BAR";
echo <<<\'EOT\'
Example of string $foo
spanning multiple lines
using nowdoc syntax.
EOT;
')
		);
	}

	public function testRun_echo_union_1() {
		$this->assertEquals(
			[ 'StringUnion' ],
			Runtime::runSource('echo "String" . "Union";')
		);
	}
	public function testRun_echo_union_2() {
		$this->assertEquals(
			[ 'OneTwoThree' ],
			Runtime::runSource('echo "One" . "Two" . "Three";')
		);
	}
	public function testRun_echo_union_3() {
		$this->assertEquals(
			[ "This string was made with concatenation.\n" ],
			Runtime::runSource('echo \'This \' . \'string \' . \'was \' . \'made \' . \'with concatenation.\' . "\n";')
		);
	}
	public function testRun_echo_union_4() {
		$this->assertEquals(
			[ 'StringUnion' ],
			Runtime::runSource('echo ("String" . "Union");')
		);
	}

	public function testRun_echo_parameters_1() {
		$this->assertEquals(
			[ 'Parameter1', 'Parameter2', 'Parameter3' ],
			Runtime::runSource('echo "Parameter1","Parameter2" , "Parameter3";')
		);
	}
	public function testRun_echo_parameters_2() {
		$this->assertEquals(
			[ 'This ', 'string ', 'was ', 'made ', 'with multiple parameters.' ],
			Runtime::runSource('echo \'This \', \'string \', \'was \', \'made \', \'with multiple parameters.\';')
		);
	}

	public function testRun_echo_multiline_1() {
		$this->assertEquals(
			[ "This spans\nmultiple lines. The newlines will be\noutput as well" ],
			Runtime::runSource('echo "This spans
multiple lines. The newlines will be
output as well";')
		);
	}
	public function testRun_echo_multiline_2() {
		$this->assertEquals(
			[ "Again: This spans\nmultiple lines. The newlines will be\noutput as well." ],
			Runtime::runSource('echo "Again: This spans\nmultiple lines. The newlines will be\noutput as well.";')
		);
	}

	public function testRun_echo_negative_1() {
		$this->assertEquals(
			[ '-7' ],
			Runtime::runSource('echo -7;')
		);
	}
	public function testRun_echo_negative_2() {
		$this->assertEquals(
			[ '-7' ],
			Runtime::runSource('echo (int)-7;')
		);
	}
	public function testRun_echo_negative_3() {
		$this->assertEquals(
			[ '-7' ],
			Runtime::runSource('echo (int)-(int)7;')
		);
	}

	public function testRun_echo_variables_0() {
		$this->assertEquals(
			[ '111' ],
			Runtime::runSource('$foo=111; echo $foo;')
		);
	}
	public function testRun_echo_variables_1() {
		$this->assertEquals(
			[ 'foo is foobar' ],
			Runtime::runSource('
$foo = "foobar";
$bar = "barbaz";
echo "foo is $foo"; // foo is foobar')
		);
	}
	public function testRun_echo_variables_2() {
		$this->assertEquals(
			[ 'foo is foobar' ],
			Runtime::runSource('echo "foo is {$foo}";')
		);
	}
	public function testRun_echo_variables_3() {
		$this->assertEquals(
			[ 'foo is foobar.' ],
			Runtime::runSource('echo "foo is {$foo}.";')
		);
	}
	public function testRun_echo_variables_4() {
		$this->assertEquals(
			[ "foo is foobar\n\n" ],
			Runtime::runSource('echo "foo is $foo\n\n";')
		);
	}
	public function testRun_echo_variables_5() {
		$this->assertEquals(
			[ 'foo is $foo' ],
			Runtime::runSource('echo \'foo is $foo\';')
		);
	}
	public function testRun_echo_variables_6() {
		$this->assertEquals(
			[ 'foobar', 'barbaz' ],
			Runtime::runSource('echo $foo,$bar;')
		);
	}
	public function testRun_echo_variables_7() {
		$this->assertEquals(
			[ 'foobarbarbaz' ],
			Runtime::runSource('echo "$foo$bar";')
		);
	}
	public function testRun_echo_variables_8() {
		$this->assertEquals(
			[ 'sfoobarlbarbaze' ],
			Runtime::runSource('echo "s{$foo}l{$bar}e";')
		);
	}
	public function testRun_echo_variables_9() {
		$this->assertEquals(
			[ 'sfoobarlbarbaz' ],
			Runtime::runSource('echo "s{$foo}l$bar";')
		);
	}
	public function testRun_echo_variables_10() {
		$this->assertEquals(
			[ 'startfoobarend' ],
			 Runtime::runSource('echo "start" . $foo . "end";')
		);
	}
	public function testRun_echo_variables_11() {
		$this->assertEquals(
			[ 'This ', 'string ', 'was foobar ', 'with multiple parameters.' ],
			Runtime::runSource('echo "This ", \'string \', "was $foo ", \'with multiple parameters.\';')
		);
	}
	public function testRun_echo_variables_12() {
		$this->assertEquals(
			[ '7' ],
			Runtime::runSource('$foo=-7; echo -$foo;')
		);
	}
	public function testRun_echo_variables_13() {
		$this->assertEquals(
			[ '7' ],
			Runtime::runSource('$foo=(int)-7; echo -$foo;')
		);
	}
	public function testRun_echo_variables_14() {
		$this->assertEquals(
			[ '7' ],
			Runtime::runSource('$foo=-7; echo (int)-(int)$foo;')
		);
	}
	public function testRun_echo_variables_15() {
		$this->assertEquals(
			[ '-7', '7' ],
			Runtime::runSource('echo -$foo=7, $foo;')
		);
	}

	public function testRun_echo_escaping_1() {
		$this->assertEquals(
			[ 's\\\'e' ],	// echo 's\\\'e';
			Runtime::runSource('echo \'s\\\\\\\'e\';')                                // s\'e
		);
	}
	public function testRun_echo_escaping_2() {
		$this->assertEquals(
			[ 's\\"e' ],	// echo "s\\\"e";
			Runtime::runSource('echo "s\\\\\\"e";')                            // s\"e
		);
	}
	public function testRun_echo_escaping_3() {
		$this->assertEquals(
			[ '\\\\\\n' ],	// echo "\\\\\\n";
			Runtime::runSource('echo "\\\\\\\\\\\\n";')                            // \\\n
		);
	}
	public function testRun_echo_escaping_4() {
		$this->assertEquals(
			[ "\\\\\\\n" ],	// echo "\\\\\\\n";
			Runtime::runSource('echo "\\\\\\\\\\\\\\n";')                            // \\\<new line>
		);
	}

	public function testRun_echo_digit_1() {
		$this->assertEquals(
			[ '5' ],
			Runtime::runSource('echo 5;')
		);
	}

	public function testRun_echo_digit_2() {
		$this->assertEquals(
			[ '5.5' ],
			Runtime::runSource('echo 5.5;')
		);
	}

	public function testRun_echo_math_1() {
		$this->assertEquals(
			[ '5 + 5 * 10 = ', '55' ],
			Runtime::runSource('echo \'5 + 5 * 10 = \', 5 + 5 * 10;')
		);
	}
	public function testRun_echo_math_2() {
		$this->assertEquals(
			[ '-25' ],
			Runtime::runSource('echo -5 + 5 + 10 + 20 - 50 - 5;')
		);
	}
	public function testRun_echo_math_3() {
		$this->assertEquals(
			[ '6' ],
			Runtime::runSource('echo 5 + 5 / 10 + 50/100;')
		);
	}
	public function testRun_echo_math_4() {
		$this->assertEqualsWithDelta(
			[ -395.55555555556 ],
			Runtime::runSource('echo 10 * 10 + "20" * \'20\' - 30 * 30 + 40 / 9;'),
			0.00000000001
		);
	}
	public function testRun_echo_math_5() {
		$this->assertEquals(
			[ '552' ],
			Runtime::runSource('$foo = 5; echo 2 + "$foo$foo" * 10;')
		);
	}
	public function testRun_echo_math_6() {
		$this->assertEquals(
			[ '5502' ],
			Runtime::runSource('$foo = 5; echo 2 + "$foo{$foo}0" * 10;')
		);
	}
	public function testRun_echo_math_7() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$foo = 5; echo (2 xor $foo) === true ? "true" : "false";')
		);
	}
	public function testRun_echo_math_8() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo (true xor false) === true ? "true" : "false";')
		);
	}
	public function testRun_echo_math_9() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$foo = false; echo (true xor $foo) === true ? "true" : "false";')
		);
	}
	public function testRun_echo_math_10() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$foo = 0; echo (true xor $foo) === true ? "true" : "false";')
		);
	}
	public function testRun_echo_math_11() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$foo = 0; echo (1 xor $foo) === true ? "true" : "false";')
		);
	}
	public function testRun_echo_math_12() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$foo = 1; echo (1 xor $foo) === true ? "true" : "false";')
		);
	}

	public function testRun_echo_math_params() {
		$this->assertEquals(
			[ '10 + 5 * 5 = ', '35', "\n\n" ],
			Runtime::runSource('echo \'10 + 5 * 5 = \', 10 + 5 * 5, "\n\n";')
		);
	}

	public function testRun_echo_math_variables() {
		$this->assertEquals(
			[ '$foo * $bar = 100 * 5 = ', '500', "\n\n" ],
			Runtime::runSource('
$foo = 100;
$bar = \'5\';
echo "\$foo * \$bar = $foo * $bar = ", $foo * $bar, "\n\n";')
		);
		$this->assertEquals(
			[ '$foo / $bar = 100 / 5 = ', '20', "\n\n" ],
			Runtime::runSource('echo "\$foo / \$bar = $foo / $bar = ", $foo / $bar, "\n\n";')
		);
		$this->assertEquals(
			[ '-$foo / -$bar = {-100} / {-5} = ', '20', "\n\n" ],
			Runtime::runSource('echo "-\$foo / -\$bar = {-$foo} / {-$bar} = ", -$foo / -$bar, "\n\n";')
		);
	}

	public function testRun_echo_math_variables_1() {
		$this->assertEquals(
			[ '50', '50' ],
			Runtime::runSource('$foo = 100; $bar=-50; echo $foo+=$bar; echo $foo;')
		);
	}
	public function testRun_echo_math_variables_2() {
		$this->assertEquals(
			[ '82' ], // $foo = 40 + 1; echo 41 + 41
			Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo;')
		);
	}
	public function testRun_echo_math_variables_3() {
		$this->assertEquals(
			[ '82', '41' ], // $foo = 40 + 1; echo 41 + 41, 41
			Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo, $foo;')
		);
	}
	public function testRun_echo_math_variables_4() {
		$this->assertEquals(
			[ '882', '441' ], // $foo = 400 + 1; $foo = 40 + 401; echo 441 + 441, 441
			Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo = 400 + $foo, $foo;')
		);
	}
	public function testRun_echo_math_variables_4_increment_1() {
		$this->assertEquals(
			[ '882', '441' ], // $foo = 400 + 1; $foo = 40 + 401; echo 441 + 441, 441
			Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo = 400 + $foo++, $foo;')
		);
	}
	public function testRun_echo_math_variables_4_increment_2() {
		$this->assertEquals(
			[ '443', '442' ], // $foo = 400 + 2; $foo = 40 + 402; echo 1 + 442, 442
			Runtime::runSource('$foo=1; echo $foo++ + $foo = 40 + $foo = 400 + $foo, $foo;')
		);
	}
	public function testRun_echo_math_variables_short_circuit_1() {
		$this->assertEquals(
			[ true, '410' ], // $foo = 400 + 10; echo 441 or ... , 410
			Runtime::runSource('$foo=10; echo $foo = 400 + $foo or $foo = 10000, $foo;')
		);
	}
	public function testRun_echo_math_variables_short_circuit_2() {
		$this->assertEquals(
			[ true, '10000' ], // $foo = 10 - 10; echo 0 or $foo=10000 , 10000
			Runtime::runSource('$foo=10; echo $foo = 10 - $foo or $foo = 10000, $foo;')
		);
	}
	public function testRun_echo_math_variables_5() {
		$this->assertEquals(
			[ '10138', '10138' ], // $foo = 400 + 10 | 10000; echo 10138, 10138
			Runtime::runSource('$foo=10; echo $foo = 400 + $foo | $foo = 10000, $foo;')
		);
	}
	public function testRun_echo_math_variables_6() {
		$this->assertEquals(
			[ '(8)' ],
			Runtime::runSource('$foo=4; echo "(" . 2 * $foo . ")";')
		);
	}

	public function testRun_echo_math_union_1() {
		$this->assertEquals(
				[ '155' ],
				Runtime::runSource('echo 10 + 5 . 5;')
			);
	}
	public function testRun_echo_math_union_2() {
		$this->assertEquals(
			[ '1545' ],
			Runtime::runSource('echo 10 + 5 . 5  * 9;')
		);
	}
	public function testRun_echo_math_union_3() {
		$this->assertEquals(
			[ '154498' ],
			Runtime::runSource('echo 10 + 5 . 5  * 9 . 4 - 5 . 8;')
		);
	}

	public function testRun_echo_math_Modulus_1() {
		$this->assertEquals(
			[ '18' ],
			Runtime::runSource('echo 123 % 21;')
		);
	}
	public function testRun_echo_math_Modulus_2() {
		$this->assertEquals(
			[ '22' ],
			Runtime::runSource('echo 123 % 21 + 74 % -5;')
		);
	}
	public function testRun_echo_math_Modulus_3() {
		$this->markTestSkipped( 'php8.1+ loses precision on implicit float to int conversion' );
		$this->assertEquals(
			[ '264' ],
			Runtime::runSource('echo 123 % 21 + 74.5 % -5 * 4 / 2 . 5 + -1;')
		);
	}

	public function testRun_echo_math_BitwiseAnd_1() {
		$this->markTestSkipped( 'php8.1+ throws PhpTagsException' );
		$this->assertEquals(
			[ '17' ],
			Runtime::runSource('echo 123 & 21;')
		);
	}
	public function testRun_echo_math_BitwiseAnd_2() {
		$this->markTestSkipped( 'php8.1+ throws PhpTagsException' );
		$this->assertEquals(
			[ '50' ],
			Runtime::runSource('echo 123 & 21 + 94 & 54;')
		);
	}
	public function testRun_echo_math_BitwiseAnd_3() {
		$this->markTestSkipped( 'php8.1+ throws PhpTagsException' );
		$this->assertEquals(
			[ '66' ],
			Runtime::runSource('echo 123 & 21 + 94 & -54;')
		);
	}

	public function testRun_echo_math_BitwiseOr_1() {
		$this->assertEquals(
			[ '127' ],
			Runtime::runSource('echo 123 | 21;')
		);
	}
	public function testRun_echo_math_BitwiseOr_2() {
		$this->assertEquals(
			[ '-5' ],
			Runtime::runSource('echo 123 | -21 / 3;')
		);
	}

	public function testRun_echo_math_BitwiseXor() {
		$this->assertEquals(
			[ '-112' ],
			Runtime::runSource('echo -123 ^ 21;')
		);
	}

	public function testRun_echo_math_LeftShift_1() {
		$this->assertEquals(
			[ '492' ],
			Runtime::runSource('echo 123 << 2;')
		);
	}
	public function testRun_echo_math_LeftShift_2() {
		$this->assertEquals(
			[ '7872' ],
			Runtime::runSource('echo 123 << 2 + 4;')
		);
	}
	public function testRun_echo_math_LeftShift_3() {
		$this->assertEquals(
			[ '31488' ],
			Runtime::runSource('echo 123 << 2 + 4 << 2;')
		);
	}
	public function testRun_echo_math_LeftShift_4() {
		$this->assertEquals(
			[ '515899392' ],
			Runtime::runSource('echo 123 << 2 + 4 << 2 * 8;')
		);
	}

	public function testRun_echo_math_RightShift_1() {
		$this->assertEquals(
			[ '30' ],
			Runtime::runSource('echo 123 >> 2;')
		);
	}
	public function testRun_echo_math_RightShift_2() {
		$this->assertEquals(
			[ '3' ],
			Runtime::runSource('echo 123 >> 2 + 3;')
		);
	}
	public function testRun_echo_math_RightShift_3() {
		$this->assertEquals(
			[ '-4' ],
			Runtime::runSource('echo -123 >> 2 + 3;')
		);
	}

	public function testRun_echo_math_Increment_1() {
		$this->assertEquals(
			[ '10', '11', '12' ],
			Runtime::runSource('$a = 10; echo $a++, $a, ++$a;')
		);
	}
	public function testRun_echo_math_Increment_2() {
		$this->assertEquals(
			[ '33' ],
			Runtime::runSource('$a = 10; echo $a++ + $a + ++$a;')
		);
	}
	public function testRun_echo_math_Increment_3() {
		$this->assertEquals(
			[ '12, ', '7', ', 14', ', 14.' ],
			Runtime::runSource('
$a = 10;
$a++;
++$a;
echo "$a, ", $a++ + -5, ", " . ++$a, ", $a.";')
		);
	}
	public function testRun_echo_math_Increment_4() {
		$this->assertEquals(
			[ '23' ],
			Runtime::runSource('$a=2; $b=10; $c=30; echo $a + $b * $a++;')
		);
	}
	public function testRun_echo_math_Increment_5() {
		$this->assertEquals(
			[ '33' ],
			Runtime::runSource('$a=2; $b=10; $c=30; echo $a + $b * ++$a;')
		);
	}
	public function testRun_echo_math_Increment_6() {
		$this->assertEquals(
			[ '33' ],
			Runtime::runSource('$a=2; $b=10; $c=30; echo $a + $b * ++$a;')
		);
	}
	public function testRun_echo_math_Increment_7() {
		$this->assertEquals(
			[ '33' ],
			Runtime::runSource('$a=2; $b=10; $c=30; echo ++$a + $b * $a;')
		);
	}
	public function testRun_echo_math_Increment_8() {
		$this->assertEquals(
			[ '42' ],
			Runtime::runSource('$a=2; $b=10; $c=30; echo $a++ + $b * ++$a;')
		);
	}
	public function testRun_echo_math_Increment_9() {
		$this->assertEquals(
			[ '53' ],
			Runtime::runSource('$a=2; $b=10; $c=30; echo ++$a + $b * ++$a + $b;')
		);
	}
	public function testRun_echo_math_Increment_10() {
		$this->assertEquals(
			[ '56' ],
			Runtime::runSource('$a=2; $b=10; echo $a + ++$a + $b * ++$a + $b;')
		);
	}
	public function testRun_echo_math_Decrement_1() {
		$this->assertEquals(
			[ '10', '9', '8' ],
			Runtime::runSource('$a = 10; echo $a--, $a, --$a;')
		);
	}
	public function testRun_echo_math_Decrement_2() {
		$this->assertEquals(
			[ '8, ', '3', ', 6', ', 6.' ],
			Runtime::runSource('
$a = 10;
$a--;
--$a;
echo "$a, ", $a-- + -5, ", " . --$a, ", $a.";')
		);
	}

	public function testRun_echo_parentheses_1() {
		$this->assertEquals(
			[ '7' ],
			Runtime::runSource('echo (2+5);')
		);
	}
	public function testRun_echo_parentheses_1_n() {
		$this->assertEquals(
			[ '-7' ],
			Runtime::runSource('echo -(2+5);')
		);
	}
	public function testRun_echo_parentheses_2() {
		$this->assertEquals(
			[ 'hello' ],
			Runtime::runSource('echo ("hello");')
		);
	}
	public function testRun_echo_parentheses_3() {
		$this->assertEquals(
			[ '70' ],
			Runtime::runSource('echo (2+5)*10;')
		);
	}
	public function testRun_echo_parentheses_3_n() {
		$this->assertEquals(
			[ '-70' ],
			Runtime::runSource('echo -(2+5)*10;')
		);
	}
	public function testRun_echo_parentheses_3_n_n() {
		$this->assertEquals(
			[ '-30' ],
			Runtime::runSource('echo -(-2+5)*10;')
		);
	}
	public function testRun_echo_parentheses_4() {
		$this->assertEquals(
			[ '19' ],
			Runtime::runSource('$a=5; $a += (3+11); echo $a;')
		);
	}
	public function testRun_echo_parentheses_4_n() {
		$this->assertEquals(
			[ '-9' ],
			Runtime::runSource('$a=5; $a += -(3+11); echo $a;')
		);
	}
	public function testRun_echo_parentheses_5() {
		$this->assertEquals(
			[ '-2' ],
			Runtime::runSource('$a=5; $a += ++$a-(3+11); echo $a;')
		);
	}
	public function testRun_echo_parentheses_5_n() {
		$this->assertEquals(
			[ '19' ],
			Runtime::runSource('$a=5; $a += ++$a- -(3+11)/2; echo $a;')
		);
	}
	public function testRun_echo_parentheses_6() {
		$this->assertEquals(
			[ '14.05' ],
			Runtime::runSource('echo (5+8)/4 + (((2+1) * (3+2) + 4)/5 + 7);')
		);
	}
	public function testRun_echo_parentheses_7() {
		$this->assertEquals(
			[ 'hello foo' ],
			Runtime::runSource('$foo = "foo"; echo("hello $foo");')
		);
	}
	public function testRun_echo_parentheses_8() {
		$this->assertEquals(
			[ 'hello ', 'foo' ],
			Runtime::runSource('echo("hello "), $foo;')
		);
	}
	public function testRun_echo_parentheses_9() {
		$this->assertEquals(
			[ 'foo', ' is ', 'foo' ],
			Runtime::runSource('echo ($foo), (" is "), $foo;')
		);
	}
	public function testRun_echo_parentheses_10() {
		$this->assertEquals(
			[ 12 ],
			Runtime::runSource('echo (6)*(2);')
		);
	}
	public function testRun_echo_parentheses_10_n() {
		$this->assertEquals(
			[ -12 ],
			Runtime::runSource('echo (6)*(-2);')
		);
	}
	public function testRun_echo_parentheses_11() {
		$this->assertEquals(
			[ '-80' ],
			Runtime::runSource('$foo=3; echo -($foo+5)*10;')
		);
	}
	public function testRun_echo_parentheses_12() {
		$this->assertEquals(
			[ '-80' ],
			Runtime::runSource('$foo=3; echo -(-$foo+-5)*-10;')
		);
	}
	public function testRun_echo_parentheses_13() {
		$this->assertEquals(
			[ 65 ],
			Runtime::runSource('echo (3+10)*$foo=5;')
		);
	}
	public function testRun_echo_parentheses_14() {
		$this->assertEquals(
			[ 65, 5 ],
			Runtime::runSource('echo (3+10)*$foo=5, $foo;')
		);
	}
	public function testRun_echo_parentheses_15() {
		$this->assertEquals(
			[ 1040, 80 ],
			Runtime::runSource('echo (3+10)*$foo=5*(7+9), $foo;')
		);
	}

	public function testRun_echo_inverting_1() {
		$this->assertEquals(
			[ '-11' ],
			Runtime::runSource('echo ~10;')
		);
	}
	public function testRun_echo_inverting_2() {
		$this->assertEquals(
			[ '9' ],
			Runtime::runSource('echo ~-10;')
		);
	}
	public function testRun_echo_inverting_3() {
		$this->assertEquals(
			[ '11' ],
			Runtime::runSource('echo -~10;')
		);
	}

	public function testRun_echo_type_1() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo (bool)10;')
		);
	}
	public function testRun_echo_type_2() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo (bool)-10;')
		);
	}
	public function testRun_echo_type_3() {
		$this->assertEquals(
			[ '-1' ],
			Runtime::runSource('echo -(bool)10;')
		);
	}
	public function testRun_echo_type_4() {
		$this->assertEquals(
			[ '' ],
			Runtime::runSource('echo (bool)0;')
		);
	}
	public function testRun_echo_type_5() {
		$this->assertEquals(
			[ '5' ],
			Runtime::runSource('echo -(int)-5.5;')
		);
	}
	public function testRun_echo_type_6() {
		$this->assertEquals(
			[ '6' ],
			Runtime::runSource('echo -(int)-5.5 + (int)(bool)"2";')
		);
	}

	public function testRun_echo_true() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo true;')
		);
	}
	public function testRun_echo_false() {
		$this->assertEquals(
			[ '' ],
			Runtime::runSource('echo false;')
		);
	}

	public function testRun_echo_compare_1() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo 5 == 5;')
		);
	}
	public function testRun_echo_compare_2() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo 5 == 3+2;')
		);
	}
	public function testRun_echo_compare_3() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo -3 + 8 == 3 + 2;')
		);
	}
	public function testRun_echo_compare_4() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo -3 * -8 > 3 + 8;')
		);
	}
	public function testRun_echo_compare_5() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo -3 * 8 < 3 + 8;')
		);
	}
	public function testRun_echo_compare_6() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo 3 === (int)"3";')
		);
	}
	public function testRun_echo_compare_7() {
		$this->markTestSkipped( 'php8+ handled numeric comparison differently' );
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo 0 == "a";')
		);
	}
	public function testRun_echo_compare_8() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo "1" == "01";')
		);
	}
	public function testRun_echo_compare_9() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo "10" == "1e1";')
		);
	}
	public function testRun_echo_compare_10() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo 100 == "1e2";')
		);
	}
	public function testRun_echo_compare_11() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('$foo = 4; echo $foo != $foo*2;')
		);
	}
	public function testRun_echo_compare_12() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo $foo <= $foo*2;')
		);
	}
	public function testRun_echo_compare_13() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo $foo*4 >= $foo*2;')
		);
	}
	public function testRun_echo_compare_14() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo 5 !== (string)5;')
		);
	}
	public function testRun_echo_compare_15() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo 5.4321 === (double)(string)5.4321 ? "true" : "false";')
		);
	}
	public function testRun_echo_compare_16() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo null === (unset)(double)(string)5.4321 ? "true" : "false";')
		);
	}

	public function testRun_echo_compare_false() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo ( 5 === (string)5 ) === false;')
		);
	}
	public function testRun_echo_compare_true() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo (100 == "1e2") === true;')
		);
	}
	public function testRun_echo_compare_false_true() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo (false === true) == false;')
		);
	}
	public function testRun_echo_compare_true_true() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo true === true === true;')
		);
	}

	public function testRun_echo_assignment_1() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('echo $foo = 1;')
		);
	}
	public function testRun_echo_assignment_2() {
		$this->assertEquals(
			[ '3' ],
			Runtime::runSource('echo $foo = 1 + 2;')
		);
	}
	public function testRun_echo_assignment_3() {
		$this->assertEquals(
			[ '3' ],
			Runtime::runSource('$foo=1; echo $foo += 2;')
		);
	}
	public function testRun_echo_assignment_4() {
		$this->assertEquals(
			[ '6' ],
			Runtime::runSource('$foo=1; echo $foo += 2 + 3;')
		);
	}
	public function testRun_echo_assignment_5() {
		$this->assertEquals(
			[ '1', '1', '1' ],
			Runtime::runSource('echo $bar = $foo = 1, $foo, $bar;')
		);
	}
	public function testRun_echo_assignment_6() {
		$this->assertEquals(
			[ '3', '2' ],
			Runtime::runSource('$foo=1; $bar=2; $foo+=$bar; echo $foo,$bar;')
		);
	}
	public function testRun_echo_assignment_7() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY . 4,
				'4', ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo = $foo . $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_8() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				4 . Runtime::R_ARRAY,
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY, ],
			Runtime::runSource( '$foo="4"; $bar=["rrr"]; $foo = $foo . $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_9() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY . Runtime::R_ARRAY,
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY, ],
			Runtime::runSource( '$foo=["4"]; $bar=["rrr"]; $foo = $foo . $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_10() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				4 . Runtime::R_ARRAY,
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY, ],
			Runtime::runSource( '$foo="4"; $bar=["rrr"]; $foo .= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_11() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY . 'rrr',
				'rrr', ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo .= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_12() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY . Runtime::R_ARRAY,
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY, ],
			Runtime::runSource( '$foo=["4"]; $bar=["rrr"]; $foo .= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_13() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo=$foo+$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_14() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo+=$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_15() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null,	1, 'test'), ],
			Runtime::runSource( '$foo="rrr"; $bar=["4"]; $foo+=$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_16() {
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$foo=["rrr"]; $bar=["4"]; $foo+=$bar; echo $foo[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_17() {
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$foo=["rrr"]; $bar=["4"]; $foo = $foo + $bar; echo $foo[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_18() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo=$foo-$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_19() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo-=$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_20() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo=$foo*$bar; echo $foo,$bar;', [ 'test' ], 77777 )
			);
	}
	public function testRun_echo_assignment_21() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo*=$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_22() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo=$foo/$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_23() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=["rrr"]; $bar="4"; $foo/=$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_24() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ), ],
			Runtime::runSource( '$foo="rrr"; $bar=["4"]; $foo=$foo%$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_25() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ), ],
			Runtime::runSource( '$foo="rrr"; $bar=["4"]; $foo%=$bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_26() {
		$this->assertEquals(
			[ '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo and $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_27() {
		$this->assertEquals(
			[ '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo or $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_28() {
		$this->assertEquals(
			[ '', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo Xor $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_29() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '0', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo & $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_30() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo | $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_31() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo ^ $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_32() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo |= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_33() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo ^= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_34() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '0', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo &= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_35() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = ($foo >> $bar); echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_36() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '0', 'rrr' ],
			Runtime::runSource( '$foo="rrr"; $bar=["rrr"]; $foo = ($foo << $bar); echo $foo,$bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_37() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo >>= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_38() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ), '0', 'rrr' ],
			Runtime::runSource( '$foo="rrr"; $bar=["rrr"]; $foo <<= $bar; echo $foo,$bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_39() {
		$this->assertEquals(
			[ '1', 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = $foo > $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_40() {
		$this->assertEquals(
			[ true, 'rrr' ],
			Runtime::runSource( '$foo="rrr"; $bar=["rrr"]; $foo = $foo < $bar; echo $foo,$bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_41() {
		$this->assertEquals(
			[ false, 'rrr' ],
			Runtime::runSource( '$foo=["4"]; $bar="rrr"; $foo = $foo == $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_42() {
		$this->assertEquals(
			[ true, 'rrr' ],
			Runtime::runSource( '$foo="rrr"; $bar=["rrr"]; $foo = $foo != $bar; echo $foo,$bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_43() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$bar=["rrr"]; $foo = ~$bar; echo $foo,$bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_44() {
		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$bar=["rrr"]; $foo = !$bar; echo $foo === false;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_45() {
		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$bar=["rrr"]; $foo=(int)$bar; echo $foo === 1;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_46() {
		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$bar=["rrr"]; $foo=(double)$bar; echo $foo === (double)1;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_47() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY, ],
			Runtime::runSource( '$bar=["rrr"]; $foo=(string)$bar; echo $foo;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_48() {
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$bar=["rrr"]; $foo=(array)$bar; echo $foo[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_49() {
		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$bar=["rrr"]; $foo=(bool)$bar; echo $foo === true;', [ 'test' ], 77777 )
		);
	}
	public function testRun_echo_assignment_50() {
		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$bar=["rrr"]; $foo=(unset)$bar; echo $foo === null;', [ 'test' ], 77777 )
		);
	}

	public function testRun_array_increase_test_1() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$bar=["rrr"]; $bar++; echo $bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_array_increase_test_2() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$bar=["rrr"]; ++$bar; echo $bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_array_increase_test_3() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$bar=["rrr"]; --$bar; echo $bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_array_increase_test_4() {
		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[ 'rrr' ],
			Runtime::runSource( '$bar=["rrr"]; $bar--; echo $bar[0];', [ 'test' ], 77777 )
		);
	}

	public function testRun_echo_ternary_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo true?"true":"false";')
		);
	}
	public function testRun_echo_ternary_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo false?"true":"false";')
		);
	}
	public function testRun_echo_ternary_3() {
		$this->assertEquals(
			[ 't' ],
			Runtime::runSource('echo true?"true":false?"t":"f";')
		);
	}
	public function testRun_echo_ternary_4() {
		$this->assertEquals(
			[ 'f' ],
			Runtime::runSource('echo false?"true":false?"t":"f";')
		);
	}
	public function testRun_echo_ternary_5() {
		$this->assertEquals(
			[ 't' ],
			Runtime::runSource('echo true?true?"true":false:false?"t":"f";')
		);
	}
	public function testRun_echo_ternary_6() {
		$this->assertEquals(
			[ 'f' ],
			Runtime::runSource('echo true?true?false:false:false?"t":"f";')
		);
	}
	public function testRun_echo_ternary_7() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo true?true?"true":false:"false";')
		);
	}
	public function testRun_echo_ternary_8() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo false?true?false:false:"false";')
		);
	}
	public function testRun_echo_ternary_9() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo (true?"true":"false");')
		);
	}
	public function testRun_echo_ternary_10() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo (false?"true":"false");')
		);
	}
	public function testRun_echo_ternary_11() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo ((true)?("tr"."ue"):("fa"."lse"));')
		);
	}
	public function testRun_echo_ternary_12() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo ((false)?("tr"."ue"):("fa"."lse"));')
		);
	}
	public function testRun_echo_ternary_variable_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$foo=true; echo $foo?"true":"false";')
		);
	}
	public function testRun_echo_ternary_variable_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$foo=false; echo $foo?"true":"false";')
		);
	}
	public function testRun_echo_ternary_variable_3() {
		$this->assertEquals(
			[ 'true1' ],
			Runtime::runSource('$foo=true?"true1":"false0"; echo $foo;')
		);
	}
	public function testRun_echo_ternary_variable_4() {
		$this->assertEquals(
			[ 'false0' ],
			Runtime::runSource('$foo=false?"true1":"false0"; echo $foo;')
		);
	}
	public function testRun_echo_ternary_variable_5() {
		$this->assertEquals(
			[ 'true1' ],
			Runtime::runSource('$foo=true?"true"."1":"false"."0"; echo $foo;')
		);
	}
	public function testRun_echo_ternary_variable_6() {
		$this->assertEquals(
			[ 'false0' ],
			Runtime::runSource('$foo=false?"true"."1":"false"."0"; echo $foo;')
		);
	}
	public function testRun_echo_ternary_variable_7() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$f="false"; $t="true"; echo true ? $t : $f;')
		);
	}
	public function testRun_echo_ternary_variable_8() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$f="false"; $t="true"; echo false ? $t : $f;')
		);
	}
	public function testRun_echo_ternary_variable_9() {
		$this->assertEquals(
			[ 'true1' ],
			Runtime::runSource('$f="false"; $t="true"; echo true ? $t . "1" : $f . "0";')
		);
	}
	public function testRun_echo_ternary_variable_10() {
		$this->assertEquals(
			[ 'false0' ],
			Runtime::runSource('$f="false"; $t="true"; echo false ? $t . "1" : $f . "0";')
		);
	}
	public function testRun_echo_ternary_math_1() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo 1-1?"true":"false";')
		);
	}
	public function testRun_echo_ternary_math_2() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo 1+1?"true":"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_1() {
		$this->assertEquals(
			[ 'zzzz' ],
			Runtime::runSource('echo "zzzz"?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo false?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_3() {
		$this->assertEquals(
			[ '2' ],
			Runtime::runSource('echo 1+1?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_4() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo 1-1?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_5() {
		$this->assertEquals(
			[ '500' ],
			Runtime::runSource('$foo=500; echo $foo?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_6() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$foo=0; echo $foo?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_7() {
		$this->assertEquals(
			[ '1008' ],
			Runtime::runSource('$foo=500; echo $foo*2+8?:"false";')
		);
	}
	public function testRun_echo_ternary_math_noexpr2_8() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$foo=-4; echo $foo*2+8?:"false";')
		);
	}

	public function testRun_echo_if_simple_1() {
		$this->assertEquals(
			[ 'hello' ],
			Runtime::runSource('if(true) echo "hello";')
		);
	}
	public function testRun_echo_if_simple_2() {
		$this->assertEquals(
			[],
			Runtime::runSource('if ( false ) echo "hello";')
		);
	}
	public function testRun_echo_if_simple_3() {
		$this->assertEquals(
			[ 'hello' ],
			Runtime::runSource('if(1+1) echo "hello";')
		);
	}
	public function testRun_echo_if_simple_4() {
		$this->assertEquals(
			[ 'hello', 'world' ],
			Runtime::runSource('if(1+1) echo "hello"; echo "world";')
		);
	}
	public function testRun_echo_if_simple_5() {
		$this->assertEquals(
			[ 'world' ],
			Runtime::runSource('if(1-1) echo "hello"; echo "world";')
		);
	}
	public function testRun_echo_if_simple_6() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('if( (1+1)*10 ) echo "true";')
		);
	}
	public function testRun_echo_if_simple_7() {
		$this->assertEquals(
			[ 'hello', 'world', '!!!' ],
			Runtime::runSource('
if ( 5+5 ) echo "hello";
if ( 5-5 ) echo " === FALSE === ";
if ( (5+5)/4 ) echo "world";
if ( -5+5 ) echo " === FALSE === ";
if ( ((74+4)*(4+6)+88)*4 ) echo "!!!";')
		);
	}
	public function testRun_echo_if_block_1() {
		$this->assertEquals(
			[ 'true', 'BAR' ],
			Runtime::runSource('if ( true ) { echo "true"; } echo "BAR";')
		);
	}
	public function testRun_echo_if_block_2() {
		$this->assertEquals(
			[ 'BAR' ],
			Runtime::runSource('if ( false ) { echo "true";} echo "BAR";')
		);
	}
	public function testRun_echo_if_block_3() {
		$this->assertEquals(
			[ 'true', 'BAR' ],
			Runtime::runSource('if ( true ) { echo "true"; echo "BAR"; }')
		);
	}
	public function testRun_echo_if_block_4() {
		$this->assertEquals(
			[],
			Runtime::runSource('if ( false ) { echo "true"; echo "BAR"; }')
		);
	}
	public function testRun_echo_if_else_simple_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('if ( true ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_else_simple_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if ( false ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_else_simple_3() {
		$this->assertEquals(
			[ 'true', ' always!' ],
			Runtime::runSource('if ( true ) echo "true"; else echo "false"; echo " always!";')
		);
	}
	public function testRun_echo_if_else_simple_4() {
		$this->assertEquals(
			[ 'false', ' always!' ],
			Runtime::runSource('if ( false ) echo "true"; else echo "false"; echo " always!";')
		);
	}
	public function testRun_echo_if_else_block_1() {
		$this->assertEquals(
			[ 'true1', 'true2' ],
			Runtime::runSource('if ( true ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; }')
		);
	}
	public function testRun_echo_if_else_block_2() {
		$this->assertEquals(
			[ 'false1', 'false2' ],
			Runtime::runSource('if ( false ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; }')
		);
	}
	public function testRun_echo_if_else_block_3() {
		$this->assertEquals(
			[ 'true1', 'true2', ' always!' ],
			Runtime::runSource('if ( true ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; } echo " always!";')
		);
	}
	public function testRun_echo_if_else_block_4() {
		$this->assertEquals(
			[ 'false1', 'false2', ' always!' ],
			Runtime::runSource('if ( false ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; } echo " always!";')
		);
	}
	public function testRun_echo_if_else_block_5() {
		$this->assertEquals(
			[ 'true1', ' always!' ],
			Runtime::runSource('if ( true ) echo "true1"; else { echo "false1"; echo "false2"; } echo " always!";')
		);
	}
	public function testRun_echo_if_else_block_6() {
		$this->assertEquals(
			[ 'false1', 'false2', ' always!' ],
			Runtime::runSource('if ( false ) echo "true1"; else { echo "false1"; echo "false2"; } echo " always!";')
		);
	}
	public function testRun_echo_if_else_block_7() {
		$this->assertEquals(
			[ 'true1', 'true2', ' always!' ],
			Runtime::runSource('if ( true ) { echo "true1"; echo "true2";} else echo "false1"; echo " always!";')
		);
	}
	public function testRun_echo_if_else_block_8() {
		$this->assertEquals(
			[ 'false1', ' always!' ],
			Runtime::runSource('if ( false ) { echo "true1"; echo "true2";} else echo "false1"; echo " always!";')
		);
	}
	public function testRun_echo_if_variable_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$foo = 5; if ( $foo > 4 ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_variable_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( $foo*2 > 4*3 ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_variable_3() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('if( $foo === 5 ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_variable_4() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('if( $foo++ ==  5 ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_variable_5() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('if( ++$foo ==  7 ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_variable_6() {
		$this->assertEquals(
			[ '1', '$foo + $bar' ],
			Runtime::runSource('$foo = true;$bar = false;
if ( $foo ) echo $foo;
if ( $bar ) echo $bar;
if ( $foo + $bar ) echo "\$foo + \$bar";')
		);
	}
	public function testRun_echo_if_double_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('if( true ) if( true ) echo "true"; else echo "false";')
		);
	}
	public function testRun_echo_if_double_2() {
		$this->assertEquals(
			[ 'true1', 'true2' ],
			Runtime::runSource('if( true ) if( true ) {echo "true1"; echo "true2";} else echo "falsefalse";')
		);
	}
	public function testRun_echo_if_double_3() {
		$this->assertEquals(
			[],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else echo "falsefalse";')
		);
	}
	public function testRun_echo_if_double_4() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else echo "falsefalse"; else echo "false";')
		);
	}
	public function testRun_echo_if_double_5() {
		$this->assertEquals(
			[],
			Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else echo "falsefalse"; }')
		);
	}
	public function testRun_echo_if_double_6() {
		$this->assertEquals(
			[],
			Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } }')
		);
	}
	public function testRun_echo_if_double_7() {
		$this->assertEquals(
			[],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; }')
		);
	}
	public function testRun_echo_if_double_8() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else echo "false";')
		);
	}
	public function testRun_echo_if_double_9() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } } else echo "false";')
		);
	}
	public function testRun_echo_if_double_10() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { echo "false"; }')
		);
	}
	public function testRun_echo_if_double_11() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } } else { echo "false"; }')
		);
	}
	public function testRun_echo_if_double_12() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else if(true) echo "false";')
		);
	}
	public function testRun_echo_if_double_13() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else if(true) { echo "false"; }')
		);
	}
	public function testRun_echo_if_double_14() {
		$this->assertEquals(
			[ 'false second TRUE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) echo "false second TRUE"; }')
		);
	}
	public function testRun_echo_if_double_15() {
		$this->assertEquals(
			[ 'false second TRUE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) { echo "false second TRUE"; } }')
		);
	}
	public function testRun_echo_if_double_16() {
		$this->assertEquals(
			[ 'false second TRUE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) echo "false second TRUE"; else echo "false second FALSE"; }')
		);
	}
	public function testRun_echo_if_double_17() {
		$this->assertEquals(
			[ 'false second TRUE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) {echo "false second TRUE";} else echo "false second FALSE"; }')
		);
	}
	public function testRun_echo_if_double_18() {
		$this->assertEquals(
			[ 'false second TRUE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) echo "false second TRUE"; else {echo "false second FALSE";} }')
		);
	}
	public function testRun_echo_if_double_19() {
		$this->assertEquals(
			[ 'false second TRUE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) {echo "false second TRUE";} else {echo "false second FALSE";} }')
		);
	}
	public function testRun_echo_if_double_20() {
		$this->assertEquals(
			[ 'false second FALSE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) echo "false second TRUE"; else echo "false second FALSE"; }')
		);
	}
	public function testRun_echo_if_double_21() {
		$this->assertEquals(
			[ 'false second FALSE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) {echo "false second TRUE";} else echo "false second FALSE"; }')
		);
	}
	public function testRun_echo_if_double_22() {
		$this->assertEquals(
			[ 'false second FALSE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) echo "false second TRUE"; else {echo "false second FALSE";} }')
		);
	}
	public function testRun_echo_if_double_23() {
		$this->assertEquals(
			[ 'false second FALSE' ],
			Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) {echo "false second TRUE";} else {echo "false second FALSE";} }')
		);
	}
	public function testRun_echo_if_double_24() {
		$this->assertEquals(
			[ 'true2' ],
			Runtime::runSource('if( true ) if( true ) echo "true2"; else echo "false2"; else echo "false";')
		);
	}
	public function testRun_echo_if_double_25() {
		$this->assertEquals(
			[ 'false2' ],
			Runtime::runSource('if( true ) if( false ) echo "true2"; else echo "false2"; else echo "false";')
		);
	}
	public function testRun_echo_if_double_26() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('if( false ) if( false ) echo "true2"; else echo "false2"; else echo "false";')
		);
	}
	public function testRun_echo_if_double_27() {
		$this->assertEquals(
			[ 'true', 'truetrue2' ],
			Runtime::runSource('if( true ) { echo "true"; if( true ) echo "truetrue2"; else echo "truefalse2"; } else { echo "false"; if( true ) echo "falsetrue2"; else echo "falsefalse2"; }')
		);
	}
	public function testRun_echo_if_double_28() {
		$this->assertEquals(
			[ 'true', 'truefalse2' ],
			Runtime::runSource('if( true ) { echo "true"; if( false ) echo "truetrue2"; else echo "truefalse2"; } else { echo "false"; if( true ) echo "falsetrue2"; else echo "falsefalse2"; }')
		);
	}
	public function testRun_echo_if_double_29() {
		$this->assertEquals(
			[ 'false', 'falsetrue2' ],
			Runtime::runSource('if( false ) { echo "true"; if( true ) echo "truetrue2"; else echo "truefalse2"; } else { echo "false"; if( true ) echo "falsetrue2"; else echo "falsefalse2"; }')
		);
	}
	public function testRun_echo_if_double_30() {
		$this->assertEquals(
			[ 'false', 'falsefalse2' ],
			Runtime::runSource('if( false ) { echo "true"; if( true ) echo "truetrue2"; else echo "truefalse2"; } else { echo "false"; if( false ) echo "falsetrue2"; else echo "falsefalse2"; }')
		);
	}
	public function testRun_echo_if_double_31() {
		$this->assertEquals(
			[ 'true', 'truetrue2' ],
			Runtime::runSource('if( true ) { echo "true"; if( true ) { echo "truetrue2"; } else { echo "truefalse2"; } } else { echo "false"; if( true ) { echo "falsetrue2"; } else { echo "falsefalse2"; } }')
		);
	}
	public function testRun_echo_if_double_32() {
		$this->assertEquals(
			[ 'true', 'truefalse2' ],
			Runtime::runSource('if( true ) { echo "true"; if( false ) { echo "truetrue2"; } else { echo "truefalse2"; } } else { echo "false"; if( true ) { echo "falsetrue2"; } else { echo "falsefalse2"; } }')
		);
	}
	public function testRun_echo_if_double_33() {
		$this->assertEquals(
			[ 'false', 'falsetrue2' ],
			Runtime::runSource('if( false ) { echo "true"; if( true ) { echo "truetrue2"; } else { echo "truefalse2"; } } else { echo "false"; if( true ) { echo "falsetrue2"; } else { echo "falsefalse2"; } }')
		);
	}
	public function testRun_echo_if_double_34() {
		$this->assertEquals(
			[ 'false', 'falsefalse2' ],
			Runtime::runSource('if( false ) { echo "true"; if( true ) { echo "truetrue2"; } else { echo "truefalse2"; } } else { echo "false"; if( false ) { echo "falsetrue2"; } else { echo "falsefalse2"; } }')
		);
	}
	public function testRun_echo_elseif_1() {
		$this->assertEquals(
			[ 'one' ],
			Runtime::runSource('if( true ) echo "one"; elseif( true ) echo "two"; else echo "three";')
		);
	}
	public function testRun_echo_elseif_2() {
		$this->assertEquals(
			[ 'two' ],
			Runtime::runSource('if( false ) echo "one"; elseif( true ) echo "two"; else echo "three";')
		);
	}
	public function testRun_echo_elseif_3() {
		$this->assertEquals(
			[ 'three' ],
			Runtime::runSource('if( false ) echo "one"; elseif( false ) echo "two"; else echo "three";')
		);
	}
	public function testRun_echo_elseif_4() {
		$this->assertEquals(
			[ '*', 'one' ],
			Runtime::runSource('if( true ) { echo "*"; echo "one"; } elseif( true ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }')
		);
	}
	public function testRun_echo_elseif_5() {
		$this->assertEquals(
			[ '*', 'two' ],
			Runtime::runSource('if( false ) { echo "*"; echo "one"; } elseif( true ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }')
		);
	}
	public function testRun_echo_elseif_6() {
		$this->assertEquals(
			[ '*', 'three' ],
			Runtime::runSource('if( false ) { echo "*"; echo "one"; } elseif( false ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }')
		);
	}
	public function testRun_echo_elseif_7() {
		$this->assertEquals(
			[ 'one' ],
			Runtime::runSource('if( true ) if( true ) echo "one"; elseif( true ) echo "two"; else echo "three";')
		);
	}
	public function testRun_echo_elseif_8() {
		$this->assertEquals(
			[ 'two' ],
			Runtime::runSource('if( true ) if( false ) echo "one"; elseif( true ) echo "two"; else echo "three";')
		);
	}
	public function testRun_echo_elseif_9() {
		$this->assertEquals(
			[ 'three' ],
			Runtime::runSource('if( true ) if( false ) echo "one"; elseif( false ) echo "two"; else echo "three";')
		);
	}
	public function testRun_echo_elseif_10() {
		$this->assertEquals(
			[ 'one' ],
			Runtime::runSource('if( true ) { if( true ) echo "one"; elseif( true ) echo "two"; else echo "three"; }')
		);
	}
	public function testRun_echo_elseif_11() {
		$this->assertEquals(
			[ 'two' ],
			Runtime::runSource('if( true ) { if( false ) echo "one"; elseif( true ) echo "two"; else echo "three"; }')
		);
	}
	public function testRun_echo_elseif_12() {
		$this->assertEquals(
			[ 'three' ],
			Runtime::runSource('if( true ) { if( false ) echo "one"; elseif( false ) echo "two"; else echo "three"; }')
		);
	}
	public function testRun_echo_elseif_13() {
		$this->assertEquals(
			[ 'true', 'one', 'T' ],
			Runtime::runSource('if(true) { echo "true"; if(true) echo "one"; elseif(true) echo "two"; if(true) echo "T"; }')
		);
	}
	public function testRun_echo_elseif_variable_1() {
		$this->assertEquals(
			[ 'one 2' ],
			Runtime::runSource('$foo=1; if($foo++) echo "one $foo"; elseif($foo++) echo "two $foo"; else echo "three $foo";')
		);
	}
	public function testRun_echo_elseif_variable_2() {
		$this->assertEquals(
			[ 'two 2' ],
			Runtime::runSource('$foo=0; if($foo++) echo "one $foo"; elseif($foo++) echo "two $foo"; else echo "three $foo";')
		);
	}
	public function testRun_echo_elseif_variable_3() {
		$this->assertEquals(
			[ 'three 1' ],
			Runtime::runSource('$foo=0; if($foo) echo "one $foo"; elseif($foo++) echo "two $foo"; else echo "three $foo";')
		);
	}
	public function testRun_echo_elseif_variable_4() {
		$this->assertEquals(
			[ 'one 2' ],
			Runtime::runSource('$foo=1; if($foo++) {echo "one $foo";} elseif($foo++) {echo "two $foo";} else {echo "three $foo";}')
		);
	}
	public function testRun_echo_elseif_variable_5() {
		$this->assertEquals(
			[ 'two 2' ],
			Runtime::runSource('$foo=0; if($foo++) {echo "one $foo";} elseif($foo++) {echo "two $foo";} else {echo "three $foo";}')
		);
	}
	public function testRun_echo_elseif_variable_6() {
		$this->assertEquals(
			[ 'three 1' ],
			Runtime::runSource('$foo=0; if($foo) {echo "one $foo";} elseif($foo++) {echo "two $foo";} else {echo "three $foo";}')
		);
	}

	public function testRun_echo_array_1() {
		$this->assertEquals(
			[ '5' ],
			Runtime::runSource('$foo=array(5); echo $foo[0];')
		);
	}
	public function testRun_echo_array_2() {
		$this->assertEquals(
			[ '5' ],
			Runtime::runSource('$foo=array(5,); echo $foo[0];')
		);
	}
	public function testRun_echo_array_3() {
		$this->assertEquals(
			[ '5', '6', '7' ],
			Runtime::runSource('$foo=array( 5, 6, 7 ); echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_array_math_1() {
		$this->assertEquals(
			[ '5' ],
			Runtime::runSource('$foo=array(3+2); echo $foo[0];')
		);
	}
	public function testRun_echo_array_math_2() {
		$this->assertEquals(
			[ '5', '6', '7' ],
			Runtime::runSource( '$foo=array(3+2,6,7); echo $foo[0],$foo[1],$foo[2];' )
		);
	}

	public function testRun_echo_array_variable_1() {
		$this->assertEquals(
			[ 5, 6, 'BAR' ],
			Runtime::runSource('$bar="BAR"; $foo=array( 5, 6, $bar ); echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_array_variable_2() {
		$this->assertEquals(
			[ 5, 6, 'FOO' ],
			Runtime::runSource('$foo="FOO"; $foo=array( 5, 6, $foo ); echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_array_variable_3() {
		$this->assertEquals(
			[ 'FOO', 'BAR' ],
			Runtime::runSource('$foo=array(); $foo[$bar="BAR"]="FOO"; echo $foo[$bar], $bar;')
		);
	}
	public function testRun_echo_array_variable_math_1() {
		$this->assertEquals(
			[ '1', '2', '3' ],
			Runtime::runSource('$foo=1; $foo=array($foo++,$foo,++$foo); echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_array_variable_math_2() {
		$this->assertEquals(
			[ '2', '3', '4' ],
			Runtime::runSource('$foo=1; $foo=array($foo+1,$foo+2,$foo+3); echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_array_variable_math_3() {
		$this->assertEquals(
			[ '(8)' ],
			Runtime::runSource('$foo=array(4); echo "(" . 2 * $foo[0] . ")";')
		);
	}
	public function testRun_echo_array_variable_increment_1() {
		$this->assertEquals(
			[ '11' ],
			Runtime::runSource('$foo[5]=10; $foo[5]++; echo $foo[5];')
		);
	}
	public function testRun_echo_array_variable_increment_2() {
		$this->assertEquals(
			[ '11' ],
			Runtime::runSource('$foo[5]=10; echo ++$foo[5];')
		);
	}
	public function testRun_echo_array_variable_increment_3() {
		$this->assertEquals(
			[ '10', '11', '12' ],
			Runtime::runSource('$foo[5]=10; echo $foo[5]++,$foo[5],++$foo[5];')
		);
	}
	public function testRun_echo_array_variable_increment_4() {
		$this->assertEquals(
			[
				(string)new PhpTagsException( PhpTagsException::WARNING_SCALAR_VALUE_AS_ARRAY, null, 1, 'test' ),
				null,
				'-A-',
				10,
				'-B-',
				(string)new PhpTagsException( PhpTagsException::WARNING_SCALAR_VALUE_AS_ARRAY, null, 1, 'test' ),
				1,
				'-C-',
				10, ],
			Runtime::runSource( '$foo[5]=10; echo $foo[5][1]++, "-A-", $foo[5], "-B-", ++$foo[5][987], "-C-", $foo[5];', [ 'test' ], 1)
		);
	}
	public function testRun_echo_array_variable_increment_5() {
		$this->assertEquals(
			[
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'undefinedFoo', 1, 'test' ),
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 5, 1, 'test' ),
				1,
				1,
				'###',
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 6, 1, 'test' ),
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 7, 1, 'test' ),
				null,
				1, ],
			Runtime::runSource( 'echo ++$undefinedFoo[5], $undefinedFoo[5], "###", $undefinedFoo[6][7]++, $undefinedFoo[6][7];', [ 'test' ], 1)
		);
	}
	public function testRun_echo_array_variable_assignment_1() {
		$this->assertEquals(
			[ '30' ],
			Runtime::runSource('$foo[5]=10; $foo[5]+=20; echo $foo[5];')
		);
	}
	public function testRun_echo_array_set_by_index_1() {
		$this->assertEquals(
			[ '5', '6', '7' ],
			Runtime::runSource('$foo=array(); $foo[5]=5; $foo[6]=6; $foo[7]=7; echo $foo[5],$foo[6],$foo[7];')
		);
	}
	public function testRun_echo_array_set_by_index_2() {
		$this->assertEquals(
			[ '5' ],
			Runtime::runSource('$foo=array(); $foo[]=5; echo $foo[0];')
		);
	}
	public function testRun_echo_array_set_by_index_3() {
		$this->assertEquals(
			[ '5', '6', '7' ],
			Runtime::runSource('$foo=array(); $foo[]=5; $foo[]=6; $foo[]=7; echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_array_set_by_index_4() {
		$this->assertEquals(
			[ '5', '6', '7' ],
			Runtime::runSource('$foo=array(); $foo[50]=5; $foo[]=6; $foo[]=7; echo $foo[50],$foo[51],$foo[52];')
		);
	}
	public function testRun_echo_array_10() {
		$this->assertEquals(
			[ 'this is string' ],
			Runtime::runSource('$foo=(array)"this is string"; echo $foo[0];')
		);
	}
	public function testRun_echo_array_11() {
		$this->assertEquals(
			[ '3' ],
			Runtime::runSource('$a = array(8=>7); $b = array(7=>3); echo $b[$a[8]];')
		);
	}
	public function testRun_echo_array_12() {
		$this->assertEquals(
			[ 't', 'h', 'is ', 'i', (string) new PhpTagsException( PhpTagsException::NOTICE_UNINIT_STRING_OFFSET, 1000, 1, 'Test' ), null ],
			Runtime::runSource('$foo="this is string"; echo $foo[0], $foo[1], $foo[ 2 ] . $foo[3] . $foo[4], $foo[5], $foo[1000];', [ 'Test' ], 0)
		);
	}

	public function testRun_echo_array_double_arrow_1() {
		$this->assertEquals(
			[ '50' ],
			Runtime::runSource('$foo=array(4=>50); echo $foo[4];')
		);
	}
	public function testRun_echo_array_double_arrow_2() {
		$this->assertEquals(
			[ 'BAR' ],
			Runtime::runSource('$bar="BAR"; $foo=array( $bar => $bar ); echo $foo[$bar];')
		);
	}
	public function testRun_echo_array_double_arrow_3() {
		$this->assertEquals(
			[ 'BAR', 50, 'STRING' ],
			Runtime::runSource('$bar="BAR"; $foo=array( 5 => 50, $bar => $bar, "string" => "STRING" ); echo $foo[$bar], $foo[5], $foo["string"];')
		);
	}
	public function testRun_echo_array_double_arrow_4() {
		$this->assertEquals(
			[ 'BAR1', 50, 'STRING', 'BAR2' ],
			Runtime::runSource('$bar1="BAR1"; $bar2="BAR2"; $foo=array( 5 => 50, $bar1 => $bar1, "string" => "STRING", $bar2 => $bar2 ); echo $foo[$bar1], $foo[5], $foo["string"], $foo[$bar2];')
		);
	}
	public function testRun_echo_array_double_arrow_5() {
		$this->assertEquals(
			[ 'BAR1', 50, 'STRING', 'BAR2' ],
			Runtime::runSource('$bar1="BAR1"; $bar2="BAR2"; $foo=array( 5 => 50, $bar1 => $bar1, "STRING", $bar2 => $bar2 ); echo $foo[$bar1], $foo[5], $foo[6], $foo[$bar2];')
		);
	}
	public function testRun_echo_array_double_arrow_6() {
		$this->assertEquals(
			[ 'BAR1', 50, 'STRING', 'BAR2' ],
			Runtime::runSource('$bar1="BAR1"; $bar2="BAR2"; $foo=array( "STRING", 5 => 50, $bar1 => $bar1, $bar2 => $bar2 ); echo $foo[$bar1], $foo[5], $foo[0], $foo[$bar2];')
		);
	}
	public function testRun_echo_array_double_arrow_7() {
		$this->assertEquals(
			[ 'BAR1', 50, 'STRING', 'BAR2' ],
			Runtime::runSource('$bar1="BAR1"; $bar2="BAR2"; $foo=array( 5 => 50, $bar1 => $bar1, $bar2 => $bar2, "STRING" ); echo $foo[$bar1], $foo[5], $foo[6], $foo[$bar2];')
		);
	}
	public function testRun_echo_array_double_arrow_8() {
		$this->assertEquals(
			[ 'BAR1', 50, 'STRING', 'BAR2' ],
			Runtime::runSource('$bar1="BAR1"; $bar2="BAR2"; $bar3 = "STRING"; $foo=array( 5 => 50, $bar1 => $bar1, $bar2 => $bar2, $bar3 ); echo $foo[$bar1], $foo[5], $foo[6], $foo[$bar2];')
		);
	}
	public function testRun_echo_array_double_arrow_9() {
		$this->assertEquals(
			[ 'BAR1', 50, 'STRING' ],
			Runtime::runSource('$bar1="BAR1"; $bar2="BAR2"; $bar3 = "STRING"; $foo=array( 5 => 50, $bar1 => $bar1, $bar2 => $bar2, $bar2=>$bar3 ); echo $foo[$bar1], $foo[5], $foo[$bar2];')
		);
	}

	public function testRun_echo_empty_array_push_1() {
		$this->assertEquals(
			[ '5', '-6', '7' ],
			Runtime::runSource('$foo=array(); $foo[]+=5; $foo[]-=6; $foo[].=7; echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_empty_array_push_2() {
		$this->assertEquals(
			[ '5', '-6', '7' ],
			Runtime::runSource('$foo=array(); $foo[]+="5"; $foo[]-="6"; $foo[].="7"; echo $foo[0],$foo[1],$foo[2];')
		);
	}
	public function testRun_echo_empty_array_push_3() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				'0',
				'0',
				'n', ],
			Runtime::runSource('$foo=array(); $foo[]+="v"; $foo[]-="b"; $foo[].="n"; echo $foo[0],$foo[1],$foo[2];', [ 'test' ], 77777)
		);
	}
	public function testRun_echo_empty_array_push_4() {
		$this->assertEquals(
			[ '0', '0', '0', '0' ],
			Runtime::runSource('$foo=array(); $foo[]*=5; $foo[]/=6; $foo[]%=7; $foo[]&=8; echo $foo[0],$foo[1],$foo[2],$foo[3];')
		);
	}
	public function testRun_echo_empty_array_push_5() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::WARNING_NON_NUMERIC_VALUE, null, 1, 'test' ),
				0,
				0,
				0,
				0, ],
			Runtime::runSource('$foo=array(); $foo[]*="v"; $foo[]+="b"; $foo[]-="n"; $foo[]&="m"; echo $foo[0],$foo[1],$foo[2],$foo[3];', [ 'test' ], 77777)
		);
	}
	public function testRun_echo_empty_array_push_6() {
		$this->assertEquals(
			[ '5', '6', '0', '0' ],
			Runtime::runSource('$foo=array(); $foo[]|=5; $foo[]^=6; $foo[]<<=7; $foo[]>>=8; echo $foo[0],$foo[1],$foo[2],$foo[3];')
		);
	}
	public function testRun_echo_empty_array_push_exception_1() {
		$expExc = (string)new PhpTagsException( PhpTagsException::FATAL_CANNOT_USE_FOR_READING, null, 1 );
		try {
			Runtime::runSource( 'echo $foo[] + 4;' );
		} catch ( PhpTagsException $ex ) {
			$this->assertEquals( $expExc, (string)$ex );
			return;
		}
		$this->fail( 'An expected exception has not been raised.' );
	}
	public function testRun_echo_empty_array_exception_1() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_ILLEGAL_OFFSET_TYPE, null, 1, 'test' ),
				'true', ],
			Runtime::runSource( 'echo [4] === [ [666]=>"test", 4] ? "true" : "false";', [ 'test' ] )
		);
	}
	public function testRun_echo_array_encapsed_1() {
		$this->assertEquals(
			[ '*5*' ],
			Runtime::runSource('$foo=(array)5; echo "*$foo[0]*";')
		);
	}
	public function testRun_echo_array_encapsed_2() {
		$this->assertEquals(
			[ '*5*' ],
			Runtime::runSource('$foo=(array)5; echo "*{$foo[0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_3() {
		$this->assertEquals(
			[ '5', '*5*', '*5*', '*5*' ],
			Runtime::runSource('$foo=(array)5; echo $foo[0], "*".$foo[0]."*", "*$foo[0]*", "*{$foo[0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_4() {
		$this->assertEquals(
			[ '*5*', '*BAR*', '*string*' ],
			Runtime::runSource('$bar = "BAR"; $foo=array( 5 => 5, $bar => $bar, "string" => "string" ); echo "*$foo[5]*"; echo "*$foo[$bar]*"; echo "*{$foo["string"]}*";')
		);
	}
	public function testRun_echo_array_encapsed_5() {
		$this->assertEquals(
			[ '-=ddd=-' ],
			Runtime::runSource('$foo["DDD"]="ddd"; echo "-={$foo["DDD"]}=-";')
		);
	}
	public function testRun_echo_array_encapsed_6() {
		$this->assertEquals(
			[ '-=FOO=-', 'BAR' ],
			Runtime::runSource('$foo[$bar="BAR"]="FOO"; echo "-={$foo[$bar]}=-", $bar;')
		);
	}
	public function testRun_echo_array_encapsed_7() {
		$this->assertEquals(
			[ '*5*|*5*' ],
			Runtime::runSource('$foo=(array)5; echo "*$foo[0]*|*$foo[0]*";')
		);
	}
	public function testRun_echo_array_encapsed_8() {
		$this->assertEquals(
			[ '*55*' ],
			Runtime::runSource('$foo=(array)5; echo "*$foo[0]$foo[0]*";')
		);
	}
	public function testRun_echo_array_encapsed_9() {
		$this->assertEquals(
			[ '*5*|*5*' ],
			Runtime::runSource('$foo=(array)5; echo "*{$foo[0]}*|*{$foo[0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_10() {
		$this->assertEquals(
			[ '*55*' ],
			Runtime::runSource('$foo=(array)5; echo "*{$foo[0]}{$foo[0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_11() {
		$this->assertEquals(
			[ '*3*|*5*' ],
			Runtime::runSource('$foo=array(3,array(5)); echo "*$foo[0]*|*{$foo[1][0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_12() {
		$this->assertEquals(
			[ '*35*' ],
			Runtime::runSource('$foo=array(3,array(5)); echo "*$foo[0]{$foo[1][0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_13() {
		$this->assertEquals(
			[ '*35*' ],
			Runtime::runSource('$foo=array(3,array(5)); echo "*{$foo[0]}{$foo[1][0]}*";')
		);
	}
	public function testRun_echo_array_encapsed_14() {
		$this->assertEquals(
			[ '*35*' ],
			Runtime::runSource('$foo=array(3,(array)5); echo "*{$foo[0]}{$foo[1][0]}*";')
		);
	}
	public function testRun_echo_array_right_1() {
		$this->assertEquals(
			[ 1 ],
			Runtime::runSource('$foo=array("123"); echo (bool)$foo[0];')
		);
	}
	public function testRun_echo_array_right_increment_1() {
		$this->assertEquals(
			[ '2' ],
			Runtime::runSource('$foo=array(1); echo (string)++$foo[0];')
		);
	}
	public function testRun_echo_array_right_increment_2() {
		$this->assertEquals(
			[ '2', 2 ],
			Runtime::runSource('$foo=array(1); echo (string)++$foo[0], $foo[0];')
		);
	}
	public function testRun_echo_array_constructor_1() {
		$this->assertEquals(
			[ '(abc)--', '(abc)' ],
			Runtime::runSource('
$b = array( "a", "b", "c" );
$a = array( $b[0], $b[1], $b[2] );
echo "(" . $b[0] . $b[1] . $b[2] .")--";
echo "(" . $a[0] . $a[1] . $a[2] .")";')
		);
	}
	public function testRun_echo_array_exception_1() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_SCALAR_VALUE_AS_ARRAY, null, 1, 'test' ),
				'5', ],
			Runtime::runSource( '$t = 5; $t[] = 4; echo $t;', [ 'test' ] )
		);
	}
	public function testRun_echo_array_exception_2() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::WARNING_SCALAR_VALUE_AS_ARRAY, null, 1, 'test' ),
				'5', ],
			Runtime::runSource( '$t = "5"; $t[] = 4; echo $t;', [ 'test' ] )
		);
	}
	public function testRun_echo_array_no_exception_3() {
		$this->assertEquals(
			[ '4' ],
			Runtime::runSource( '$t = null; $t[] = 4; echo $t[0];', [ 'test' ] )
		);
	}
	public function testRun_echo_array_no_exception_4() {
		$this->assertEquals(
			[ '4' ],
			Runtime::runSource( '$t = false; $t[] = 4; echo $t[0];', [ 'test' ] )
		);
	}

	public function testRun_print_1() {
		$this->assertEquals(
			[ 'hello' ],
			Runtime::runSource('print "hello";')
		);
	}
	public function testRun_print_2() {
		$this->assertEquals(
			[ 'Hello World' ],
			Runtime::runSource('print("Hello World");')
		);
	}
	public function testRun_print_3() {
		$this->assertEquals(
			[ 'foobar' ],
			Runtime::runSource('$foo = "foobar"; print $foo;')
		);
	}
	public function testRun_print_4() {
		$this->assertEquals(
			[ 'foo is foobar' ],
			Runtime::runSource('print "foo is $foo";')
		);
	}
	public function testRun_echo_print_1() {
		$this->assertEquals(
			[ 'foobar', 1 ],
			Runtime::runSource('echo print $foo;')
		);
	}
	public function testRun_echo_print_2() {
		$this->assertEquals(
			[ 'foobar', -1 ],
			Runtime::runSource('echo -print $foo;')
		);
	}
	public function testRun_echo_print_3() {
		$this->assertEquals(
			[ 'foobar', 3 ],
			Runtime::runSource('echo 2+print $foo;')
		);
	}
	public function testRun_echo_print_4() {
		$this->assertEquals(
			[ 'foobar', 11 ],
			Runtime::runSource('echo 5*2+print $foo;')
		);
	}
	public function testRun_echo_print_5() {
		$this->assertEquals(
			[ 'foobar', 7 ],
			Runtime::runSource('echo 5+2*print $foo;')
		);
	}

	public function testRun_while_1() {
		$this->assertEquals(
			[ '1', '2', '3' ],
			Runtime::runSource('$i=1; while( $i <= 3 ) { echo $i++; }')
		);
	}
	public function testRun_while_2() {
		$this->assertEquals(
			[ '1', '2', '3' ],
			Runtime::runSource('$i=1; while( $i <= 3 ) echo $i++;')
		);
	}
	public function testRun_while_3() {
		$this->assertEquals(
			[ '3', '2', '1' ],
			Runtime::runSource('$i=3; while( $i ) echo $i--;')
		);
	}
	public function testRun_while_4() {
		$this->assertEquals(
			[ '2', '1', '0' ],
			Runtime::runSource('$i=3; while( $i-- ) echo $i;')
		);
	}
	public function testRun_while_continue_1() {
		$this->assertEquals(
			[ '1', '2', '3' ],
			Runtime::runSource('$i=1; while( $i <= 3 ) { echo $i++; continue; $i++; }')
		);
	}
	public function testRun_while_break_1() {
		$this->assertEquals(
			[ '1' ],
			Runtime::runSource('$i=1; while( $i <= 33 ) { echo $i++; break; $i++; }')
		);
	}
	public function testRun_while_if_break_1() {
		$this->assertEquals(
			[ '1', '2' ],
			Runtime::runSource('$i=1; while( $i <= 33 ) { echo $i++; if($i == 3) break; }')
		);
	}
	public function testRun_while_if_break_2() {
		$this->assertEquals(
			[ '1', '2', 'The end' ],
			Runtime::runSource('$i=1; while( $i <= 33 ) { echo $i++; if($i == 3){echo "The end"; break; echo "anything";} }')
		);
	}
	public function testRun_while_if_continue_1() {
		$this->assertEquals(
			[ '1', '3' ],
			Runtime::runSource('$i=0; while( $i <= 2 ) { $i++; if($i == 2) continue; echo $i; }')
		);
	}
	public function testRun_while_if_continue_2() {
		$this->assertEquals(
			[ '1', 'Two', '3' ],
			Runtime::runSource('$i=0; while( $i <= 2 ) { $i++; if($i == 2) { echo "Two"; continue; } echo $i; }')
		);
	}
	public function testRun_while_while_1() {
		$this->assertEquals(
			'|1|(1)|2|(1)(2)|3|(1)(2)(3)',
			implode( Runtime::runSource('$i=1; while( $i<=3 ){ echo "|$i|"; $y=1; while( $y<=$i ){ echo "($y)"; $y++; } $i++; }') )
		);
	}
	public function testRun_while_while_2() {
		$this->assertEquals(
			[ 1, 2, 3, 4, 4, 4 ],
			Runtime::runSource('$i=1; $y=2; while($i++<4 && $y--) { while($y<5) { echo $y++; } }')
		);
	}
	public function testRun_while_while_3() {
		$this->assertEquals(
			[ 1, 2, 3, 4, 4, 4 ],
			Runtime::runSource('$i=1; $y=2; while($i++<4 && $y--) while($y<5) { echo $y++; }')
		);
	}
	public function testRun_while_while_4() {
		$this->assertEquals(
			[ 1, 2, 3, 4, 4, 4 ],
			Runtime::runSource('$i=1; $y=2; while($i++<4 && $y--) while($y<5) echo $y++;')
		);
	}
	public function testRun_while_while_continue_1() {
		$this->assertEquals(
			'|1|(1)(3)|2|(1)(3)|3|(1)(3)',
			implode( Runtime::runSource('$i=1; while( $i<=3 ){ echo "|$i|"; $y=0; while( $y<3 ){ $y++; if( $y==2) continue; echo "($y)";  } $i++; }') )
		);
	}
	public function testRun_while_while_continue_2() {
		$this->assertEquals(
			'|1||2|(1)|3|(1)(2)|4|(1)(2)(3)|5|(1)(2)(3)',
			implode( Runtime::runSource('$i=0; while( $i<5 ){ $i++; echo "|$i|"; $y=0; while( $y<3 ){ $y++; if( $y==$i ){ continue 2; } echo "($y)";  } }') )
		);
	}
	public function testRun_while_while_break_1() {
		$this->assertEquals(
			'|1|(1)|2|(1)|3|(1)|4|(1)|5|(1)',
			implode( Runtime::runSource('$i=1; while( $i<=5 ){ echo "|$i|"; $y=0; while( $y<3 ){ $y++; if( $y==2) break; echo "($y)";  } $i++; }') )
		);
	}
	public function testRun_while_while_break_2() {
		$this->assertEquals(
			'(1)',
			implode( Runtime::runSource('$i=1; $y=0; while($y<4&&$y<$i){$y++; if($y==3){break; echo "hohoho";} echo "($y)";}') )
		);
	}
	public function testRun_while_while_break_3() {
		$this->assertEquals(
			'|1|(1)|2|(1)(2)|3|(1)(2)',
			implode( Runtime::runSource('$i=1; while( $i<=5 ){ echo "|$i|"; $y=0; while( $y<4 && $y<$i ){ $y++; if( $y==3) { break 2; echo "hohoho"; } echo "($y)";  } $i++; }') )
		);
	}
	public function testRun_while_if_while_1() {
		$this->assertEquals(
			'|1||2|(1)(2)|3||4|(1)(2)(3)(4)|5|',
			implode( Runtime::runSource('$i=1; while( $i<=5 ){ echo "|$i|"; $y=0; if( $i==2 || $i == 4 ) while( $y<$i ){ $y++; echo "($y)"; } $i++; } ') )
		);
	}
	public function testRun_while_if_while_2() {
		$this->assertEquals(
			'|1||2|.(1)(2)|3||4|.(1)(2)(3)(4)|5|',
			implode( Runtime::runSource('$i=1; while( $i<=5 ){ echo "|$i|"; $y=0; if( $i==2 || $i == 4 ) { echo "."; while( $y<$i ){ $y++; echo "($y)"; } } $i++; } ') )
		);
	}
	public function testRun_while_if_while_if_1() {
		$this->assertEquals(
			'.(1)(2)',
			implode( Runtime::runSource('$i=2; $y=0; if( $i==2 || $i == 4 ) { echo "."; while( $y<$i ){ $y++; if($y< 3) echo "($y)"; else break 2;} }') )
		);
	}
	public function testRun_while_if_while_if_2() {
		$this->assertEquals(
			'|1||2|.(1)(2)|3||4|.(1)(2)',
			implode( Runtime::runSource('$i=1; while( $i<=5 ){ echo "|$i|"; $y=0; if( $i==2 || $i == 4 ) { echo "."; while( $y<$i ){ $y++; if($y < 3) echo "($y)"; else break 2; } } $i++; } ') )
			);
	}

	public function testRun_do_while_1() {
		$this->assertEquals(
			[ '5', '*4*' ],
			Runtime::runSource('$foo = 5; do { echo $foo; $foo--; } while ( false ); echo "*$foo*";')
		);
	}
	public function testRun_do_while_2() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1' ],
			Runtime::runSource('$foo = 5; do { echo $foo; $foo--; } while ($foo);')
		);
	}
	public function testRun_do_while_3() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1', '0', '-1' ],
			Runtime::runSource('$foo = 5; do { echo $foo; $foo--; } while ( $foo > -2 );')
		);
	}
	public function testRun_do_while_4() {
		$this->assertEquals(
			[ '5', '*5*' ],
			Runtime::runSource('$foo = 5; do { echo $foo; break; $foo--; } while ( false ); echo "*$foo*";')
		);
	}
	public function testRun_do_while_if_1() {
		$this->assertEquals(
			[ '5', '4', '3', '2', 'TwO', '1' ],
			Runtime::runSource('$foo = 5; do { echo $foo; if ($foo === 2) echo "TwO"; $foo--; } while ($foo);')
		);
	}
	public function testRun_do_while_if_2() {
		$this->assertEquals(
			[ '5', '4', '3', '2', 'TwO', '1' ],
			Runtime::runSource('$foo = 5; do { echo $foo; if ($foo === 2) { echo "TwO"; } $foo--; } while ($foo);')
		);
	}
	public function testRun_do_while_if_3() {
		$this->assertEquals(
			[ '5', '4', '3', '2' ],
			Runtime::runSource('$foo = 5; do { echo $foo; if ($foo === 2) { break; } $foo--; } while ($foo);')
		);
	}

	public function testRun_for_1() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1' ],
			Runtime::runSource( 'for( $foo = 5; $foo; $foo-- ) echo $foo;' )
		);
	}
	public function testRun_for_2() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1', '*0*' ],
			Runtime::runSource( 'for( $foo = 5; $foo; $foo-- ) echo $foo; echo "*$foo*";' )
		);
	}
	public function testRun_for_3() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1', '*0*' ],
			Runtime::runSource( 'for( $foo = 5; $foo; ) { echo $foo; $foo--; } echo "*$foo*";' )
		);
	}
	public function testRun_for_4() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1', '*0*' ],
			Runtime::runSource( '$foo = 5; for( ; $foo; ) { echo $foo; $foo--; } echo "*$foo*";' )
		);
	}
	public function testRun_for_5() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1', '*0*' ],
			Runtime::runSource( '$foo = 5; for( ; ; ) { echo $foo; $foo--; if( !$foo ) break; } echo "*$foo*";' )
		);
	}
	public function testRun_for_6() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1', '*0*' ],
			Runtime::runSource( '$foo = 5; for( ; ; ) { echo $foo; $foo--; if( !$foo ) { break 1; } } echo "*$foo*";' )
		);
	}
	public function testRun_for_7() {
		$this->assertEquals(
			[ '5', '4', '3', '2', '1' ],
			Runtime::runSource( 'for( $foo = 5; $foo > 0; $foo-- ) echo $foo;' )
		);
	}
	public function testRun_for_8() {
		$this->assertEquals(
			[ '-2', '-1', '0', '1', '2' ],
			Runtime::runSource( 'for( $foo = -2; $foo <= 2; $foo++ ) echo $foo;' )
		);
	}

	public function testRun_break_1() {
		$this->assertEquals(
			[ '@@@@@@@@@', " TRUE " ],
			Runtime::runSource('echo "@@@@@@@@@";
if (true) {
echo " TRUE ";
break;
}
echo "^^^^^^^^^^^^^";')
		);
	}
	public function testRun_break_exc_1() {
		$this->assertEquals(
			[ '@@@@@@@@@', " TRUE ", (string) new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, 2, 4, 'test' ) ],
			Runtime::runSource( 'echo "@@@@@@@@@";
if (true) {
echo " TRUE ";
break 2;
}
echo "^^^^^^^^^^^^^";', [ 'test' ] )
		);
	}
	public function testRun_while_continue_exc_1() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, 1, 1, 'test' ) ],
			Runtime::runSource( 'continue;', [ 'test' ] )
		);
	}
	public function testRun_while_continue_exc_2() {
		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, 2, 1, 'test' ) ],
			Runtime::runSource( 'continue 2;', [ 'test' ] )
		);
	}
	public function testRun_while_continue_exc_3() {
		$this->assertEquals(
			[ 1, (string) new PhpTagsException( PhpTagsException::FATAL_WRONG_BREAK_LEVELS, 2, 1, 'test' ) ],
			Runtime::runSource( '$i=1; while( $i <= 3 ) { echo $i++; continue 2; $i++; }', [ 'test' ] )
		);
	}

//	 *
//	 * Test static variable $stat in testTemplate
//	 *
	public function testRun_echo_scope_static_1() {
		// start testScope
		$this->assertEquals(
			[],
			Runtime::runSource('$foo = "local foo variable from testScope";', [ 'testScope' ], 0)
		);
	}
	public function testRun_echo_scope_static_2() {
		// {{testTemplate|HELLO!}}
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'bar', 4, 'testTemplate' ),
				'HELLO!', 'testTemplate', 2, 1, 1,
				(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_INDEX, 'test', 5, 'testTemplate' ), null ],
			Runtime::runSource( '
$foo = $argv[1];
static $stat = 0;
$bar++; $stat++;
echo $foo, $argv[0], $argc, $bar, $stat, $argv["test"];', [ 'testTemplate', 'HELLO!' ], 1 )
		);
	}
	public function testRun_echo_scope_static_3() {
		// {{testTemplate|HELLO!|test="TEST!!!"}}
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'bar', 4, 'testTemplate' ),
				'HELLO!', 'testTemplate', 3, 1, 2, 'TEST!!!' ],
			Runtime::runSource('
$foo = $argv[1];
static $stat = 0;
$bar++; $stat++;
echo $foo, $argv[0], $argc, $bar, $stat, $argv["test"];', [ 'testTemplate', 'HELLO!', 'test'=>'TEST!!!' ], 2)
		);
	}
	public function testRun_echo_scope_static_4() {
		// {{testTemplate|HELLO!}}
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'bar', 4, 'testTemplate' ),
				'HELLO!', 'testTemplate', 2, 1, 3,
				(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_INDEX, 'test', 5, 'testTemplate' ), null ],
			Runtime::runSource('
$foo = $argv[1];
static $stat = 0;
$bar++; $stat++;
echo $foo, $argv[0], $argc, $bar, $stat, $argv["test"];', [ 'testTemplate', 'HELLO!' ], 3)
		);
	}
	public function testRun_echo_scope_static_5() {
		// end testScope
		$this->assertEquals(
			[ 'local foo variable from testScope' ],
			Runtime::runSource('echo $foo;', [ 'testScope' ], 0)
		);
	}
	public function testRun_echo_static_math_1() {
		$this->assertEquals(
			[ 7 ],
			Runtime::runSource('$foo=40; static $foo=1+2*3; echo $foo++;', [ 'static_math' ], 1)
		);
	}
	public function testRun_echo_static_math_2() {
		$this->assertEquals(
			[ 8 ],
			Runtime::runSource('$foo=40; static $foo=1+2*3; echo $foo++;', [ 'static_math' ], 2)
		);
	}
	public function testRun_echo_static_math_3() {
		$this->assertEquals(
			[ 9 ],
			Runtime::runSource('$foo=40; static $foo=1+2*3; echo $foo++;', [ 'static_math' ], 3)
		);
	}
	public function testRun_echo_static_expression_1() {
		$expExc = (string)new PhpTagsException( PhpTagsException::PARSE_ERROR_EXPRESSION_IN_STATIC, null, 1 );
		try {
			Runtime::runSource( '$foo=40; static $foo=1+2*$foo; echo $foo++;' );
		} catch ( PhpTagsException $ex ) {
			$this->assertEquals( $expExc, (string)$ex );
			return;
		}
		$this->fail( 'An expected exception has not been raised.' );
	}
	public function testRun_echo_static_null_1() {
		$this->assertEquals(
			[ "true" ],
			Runtime::runSource('$foo=40; static $foo; echo $foo===null?"true":"false";', [ 'static_null' ], 0)
		);
	}
	public function testRun_echo_static_null_2() {
		$this->assertEquals(
			[ "false40" ],
			Runtime::runSource('$foo=40; static $foo; echo $foo===null?"true":"false$foo";', [ 'static_null' ], 0)
		);
	}

//	 *
//	 * Test global variable $glob
//	 *
	public function testRun_echo_scope_global_1() {
		// start testScope
		$this->assertEquals(
			[],
			Runtime::runSource('global $glob; $glob=1000;', [ 'testScope' ], 0)
		);
	}
	public function testRun_echo_scope_global_2() {
		// {{testTemplate}}
		$this->assertEquals(
			[ '1001' ],
			Runtime::runSource('global $glob; echo ++$glob;', [ 'testTemplate' ], 1)
		);
	}
	public function testRun_echo_scope_global_3() {
		// {{testTemplate}}
		$this->assertEquals(
			[ '1002' ],
			Runtime::runSource('global $glob; echo ++$glob;', [ 'testTemplate' ], 2)
		);
	}
	public function testRun_echo_scope_global_4() {
		// {{testTemplateGLOBAL}}
		$this->assertEquals(
			[ '1003' ],
			Runtime::runSource('echo ++$GLOBALS["glob"];', [ 'testTemplateGLOBAL' ], 3)
		);
	}
	public function testRun_echo_scope_global_5() {
		// end testScope
		$this->assertEquals(
			[ '1003' ],
			Runtime::runSource('echo $glob;', [ 'testScope' ], 0)
		);
	}
	public function testRun_echo_scope_global_6() {
		// end testScope
		$this->assertEquals(
			[ 'GLOBAL', 'GLOBAL', 'GLOBAL' ],
			Runtime::runSource('global $glob, $glob2, $glob3; $glob2 = $glob3 = $glob = "GLOBAL"; echo $glob, $glob2, $glob3;', [ 'testGlobalList' ], 0)
		);
	}
	public function testRun_echo_scope_global_7() {
		// end testScope
		$this->assertEquals(
			[ 'GLOBAL', 'GLOBAL', 'GLOBAL' ],
			Runtime::runSource('global $glob, $glob2, $glob3; echo $glob, $glob2, $glob3;', [ 'testGlobalList2' ], 0)
		);
	}

	public function testRun_echo_empty_1() {
		$this->assertEquals(
			[ 'empty' ],
			Runtime::runSource('$a = 0.00; echo (empty($a)? "empty": "not empty");')
		);
	}
	public function testRun_echo_empty_2() {
		$this->assertEquals(
			[ 'not empty' ],
			Runtime::runSource('$b = "0.00"; echo (empty($b)? "empty": "not empty");')
		);
	}
	public function testRun_echo_empty_3() {
		$this->assertEquals(
			[ 'empty' ],
			Runtime::runSource('echo (empty($undefined_variable)? "empty": "not empty");')
		);
	}
	public function testRun_echo_empty_array_1() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$a = array ("test" => 1, "hello" => NULL, "pie" => array("a" => "apple"));
echo empty($a["test"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_array_2() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($a["foo"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_array_3() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($a["hello"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_array_4() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo empty($a["pie"]["a"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_array_5() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($a["pie"]["b"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_array_6() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($a["pie"]["a"]["b"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_array_7() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($a["pie"]["b"]["a"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_key_string_1() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$expected_array_got_string = "somestring";
echo empty($expected_array_got_string[0]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_key_string_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo empty($expected_array_got_string["0"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_key_string_3() {
		$this->markTestSkipped( 'php8.1+ loses precision on implicit float to int conversion' );
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo empty($expected_array_got_string[0.5]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_key_string_4() { //PHP 5.4 changes how isset() behaves when passed string offsets.
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($expected_array_got_string["some_key"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_key_string_5() { //PHP 5.4 changes how isset() behaves when passed string offsets.
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($expected_array_got_string["0.5"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_empty_key_string_6() { //PHP 5.4 changes how isset() behaves when passed string offsets.
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo empty($expected_array_got_string["0 Mostel"]) ? "true" : "false";')
		);
	}

	public function testRun_echo_isset_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$var = ""; echo isset($var) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($varForIsset) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_3() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($var, $varForIsset) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_4() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($varForIsset, $var) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_5() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$varForIsset = "test"; echo isset($varForIsset, $var) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_6() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('$varForIsset = NULL; echo isset($varForIsset, $var) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$a = array ("test" => 1, "hello" => NULL, "pie" => array("a" => "apple"));
echo isset($a["test"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_2() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($a["foo"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_3() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($a["hello"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_4() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo isset($a["pie"]["a"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_5() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($a["pie"]["b"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_6() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($a["pie"]["a"]["b"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_array_7() {
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($a["pie"]["b"]["a"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_key_string_1() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('$expected_array_got_string = "somestring";
echo isset($expected_array_got_string[0]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_key_string_2() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo isset($expected_array_got_string["0"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_key_string_3() {
		$this->markTestSkipped( 'php8.1+ loses precision on implicit float to int conversion' );
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('echo isset($expected_array_got_string[0.5]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_key_string_4() { //PHP 5.4 changes how isset() behaves when passed string offsets.
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($expected_array_got_string["some_key"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_key_string_5() { //PHP 5.4 changes how isset() behaves when passed string offsets.
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($expected_array_got_string["0.5"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_key_string_6() { //PHP 5.4 changes how isset() behaves when passed string offsets.
		$this->assertEquals(
			[ 'false' ],
			Runtime::runSource('echo isset($expected_array_got_string["0 Mostel"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_isset_boolean_and_1() {
		$this->assertEquals(
			[ 'TRUE' ],
			Runtime::runSource( '
	$g = ["a" => 0]; $x = "a";
	if ( isset( $g[$x] ) && $x !== "kf" ) {
		echo "TRUE";
	} else {
		echo "FALSE";
	}')
		);
	}
	public function testRun_echo_isset_boolean_or_1() {
		$this->assertEquals(
			[ 'TRUE' ],
			Runtime::runSource( '
	$g = ["a" => 0]; $x = "a";
	if ( isset( $g[$x] ) || $x === "kf" ) {
		echo "TRUE";
	} else {
	    echo "FALSE";
	}')
		);
	}

	public function testRun_echo_unset_1() {
		$this->assertEquals(
			[ 'true', 'false' ],
			Runtime::runSource('$var = "string"; echo isset($var) ? "true" : "false"; unset($var); echo isset($var) ? "true" : "false";')
		);
	}
	public function testRun_echo_unset_2() {
		$this->assertEquals(
			[ 'true', 'false' ],
			Runtime::runSource('$var = array("string"); echo isset($var[0]) ? "true" : "false"; unset($var[0]); echo isset($var[0]) ? "true" : "false";')
		);
	}
	public function testRun_echo_unset_3() {
		$this->assertEquals(
			[ 'true', 'false' ],
			Runtime::runSource('$var = array("foo" => "string"); echo isset($var["foo"]) ? "true" : "false"; unset($var["foo"]); echo isset($var["foo"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_unset_4() {
		$this->assertEquals(
			[ 'true', 'false' ],
			Runtime::runSource('$var = [ array("foo" => "string") ]; echo isset($var[0]["foo"]) ? "true" : "false"; unset($var[0]["foo"]); echo isset($var[0]["foo"]) ? "true" : "false";')
		);
	}
	public function testRun_echo_unset_5() {
		$this->assertEquals(
			[ 'false', 'false' ],
			Runtime::runSource('$var = [ array("foo" => "string") ]; echo isset($var[0][345][678]) ? "true" : "false"; unset($var[0][345][678]); echo isset($var[0][345][678]) ? "true" : "false";')
		);
	}
	public function testRun_echo_unset_exception_1() {
		$this->assertEquals(
			[ (string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'undefined', 1, 'test_unset' ) ],
			Runtime::runSource( 'unset( $undefined[0] );', [ 'test_unset' ], 1 )
		);
	}
	public function testRun_echo_unset_exception_2() {
		$this->assertEquals(
			[ (string)new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null, 1, 'test_unset' ) ],
			Runtime::runSource( '$str = "string"; unset( $str[2] );', [ 'test_unset' ], 2 )
		);
	}
	public function testRun_echo_unset_exception_3() {
		$this->assertEquals(
			[ (string)new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null, 1, 'test_unset' ) ],
			Runtime::runSource( '$strarr = ["string"]; unset( $str[0][2] );', [ 'test_unset' ], 2 )
		);
	}
	public function testRun_echo_unset_exception_4() {
		$this->assertEquals(
			[ 'true', (string)new PhpTagsException( PhpTagsException::FATAL_CANNOT_UNSET_STRING_OFFSETS, null, 1, 'test_unset' ) ],
			Runtime::runSource('$var = [ array("foo" => "string") ]; echo isset($var[0]["foo"][2]) ? "true" : "false"; unset($var[0]["foo"][2]); echo isset($var[0]["foo"][2]) ? "true" : "false";', [ 'test_unset' ], 2)
		);
	}

	public function testRun_echo_list_1() {
		$this->assertEquals(
			[ 'coffee is brown and caffeine makes it special.' ],
			Runtime::runSource('$info = array("coffee", "brown", "caffeine"); list($drink, $color, $power) = $info; echo "$drink is $color and $power makes it special.";', [ 'testList' ], 1)
		);
	}
	public function testRun_echo_list_2() {
		$this->assertEquals(
			[ 'coffee has caffeine.' ],
			Runtime::runSource('$info = array("coffee", "brown", "caffeine"); list($drink, , $power) = $info; echo "$drink has $power.";', [ 'testList' ], 2)
		);
	}
	public function testRun_echo_list_3() {
		$this->assertEquals(
			[ 'I need caffeine!' ],
			Runtime::runSource('$info = array("coffee", "brown", "caffeine"); list( , , $power) = $info; echo "I need $power!";', [ 'testList' ], 3)
		);
	}
	public function testRun_echo_list_4() {
		$this->assertEquals(
			[ 'true' ],
			Runtime::runSource('list($bar) = "abcde"; echo $bar===null ? "true" : "false";')
		);
	}
	public function testRun_echo_list_5() {
		$this->assertEquals(
			[ 1, 2, 3 ],
			Runtime::runSource('list($a, list($b, $c)) = array(1, array(2, 3)); echo $a, $b, $c;')
		);
	}
	public function testRun_echo_list_6() {
		$this->assertEquals(
			[ 1, null, null ],
			Runtime::runSource('list($a, list($b, $c)) = array(1, "string"); echo $a, $b, $c;')
		);
	}
	public function testRun_echo_list_7() {
		$this->assertEquals(
			[ 1, null, null ],
			Runtime::runSource('list($a, list($b, $c)) = array(1, 456789); echo $a, $b, $c;')
		);
	}
	public function testRun_echo_list_8() {
		$this->assertEquals(
			[ (string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 2, 1, 'T'), 1, null, null, null ],
			Runtime::runSource( 'list($a, list($b, $c), $d) = array(1, 456789); echo $a, $b, $c, $d;', [ 'T' ], 1 )
		);
	}
	public function testRun_echo_list_9() {
		$this->assertEquals(
			[
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 2, 1, 'T'),
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 1, 1, 'T'),
				(string)new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_OFFSET, 1, 1, 'T'),
				'ddddd', null, null, null ],
			Runtime::runSource( 'list($a, list($b, $c), $d) = array("ddddd"); echo $a, $b, $c, $d;', [ 'T' ], 1 )
		);
	}
	public function testRun_echo_list_10() {
		$this->assertEquals(
			[ 'abcde' ],
			Runtime::runSource('list($bar[4]) = ["abcde"]; echo $bar[4];')
		);
	}
	public function testRun_echo_list_11() {
		$this->assertEquals(
			[ 'abcde', 'end', '-678678-' ],
			Runtime::runSource('list($bar[4], list($bar[], $bar[6][7][8])) = ["abcde", ["end", "-678678-"]]; echo $bar[4], $bar[7], $bar[6][7][8];')
		);
	}
	public function testRun_foreach_1() {
		$this->assertEquals(
			[ "* Value: one\n", "* Value: two\n", "* Value: three\n" ],
			Runtime::runSource('$arr = ["one", "two", "three"]; foreach ($arr as $value) echo "* Value: $value\n";')
		);
	}
	public function testRun_foreach_2() {
		$this->assertEquals(
			[ "* Value: one\n", "* Value: two\n", "* Value: three\n" ],
			Runtime::runSource('foreach ($arr as $value) { echo "* Value: $value\n"; }')
		);
	}
	public function testRun_foreach_3() {
		$this->assertEquals(
			[ "* Value: one\n", "* Value: two\n", "* Value: three\n", 'end' ],
			Runtime::runSource('$arr = array("one", "two", "three"); foreach ($arr as $value) echo "* Value: $value\n"; echo "end";')
		);
	}
	public function testRun_foreach_4() {
		$this->assertEquals(
			[ "* Value: one\n", "* Value: two\n", "* Value: three\n", 'end' ],
			Runtime::runSource('foreach ($arr as $value) { echo "* Value: $value\n"; } echo "end";')
		);
	}
	public function testRun_foreach_5() {
		$this->assertEquals(
			[ 0, 'one', 1, 'two', 2, 'three' ],
			Runtime::runSource('foreach ($arr as $key => $value) { echo $key, $value; }')
		);
	}
	public function testRun_foreach_6() {
		$this->assertEquals(
			[ "* Key: 0; Value: one\n", "* Key: 1; Value: two\n", "* Key: 2; Value: three\n", 'end' ],
			Runtime::runSource('foreach ($arr as $key => $value) echo "* Key: $key; Value: $value\n"; echo "end";')
		);
	}
	public function testRun_foreach_7() {
		$this->assertEquals(
			[ "* Key: 0; Value: one\n", "* Key: 1; Value: two\n", "* Key: 2; Value: three\n", 'end' ],
			Runtime::runSource('foreach ($arr as $key => $value) { echo "* Key: $key; Value: $value\n"; } echo "end";')
		);
	}
	public function testRun_foreach_8() {
		$this->assertEquals(
			[ '$a[one] => 1.', '$a[two] => 2.', '$a[three] => 3.', '$a[seventeen] => 17.' ],
			Runtime::runSource('$a = ["one" => 1,"two" => 2,"three" => 3,"seventeen" => 17]; foreach ($a as $k => $v) {echo "\$a[$k] => $v.";}')
		);
	}
	public function testRun_foreach_9() {
		$this->assertEquals(
			[ 'a', 'b', 'y', 'z' ],
			Runtime::runSource('$a=array(); $a[0][0]="a"; $a[0][1]="b"; $a[1][0]="y"; $a[1][1]="z"; foreach ($a as $v1) { foreach ($v1 as $v2) { echo $v2; } }')
		);
	}
	public function testRun_foreach_10() {
		$this->assertEquals(
			[ 'a', 'b', 'y', 'z' ],
			Runtime::runSource('foreach ($a as $v1) foreach ($v1 as $v2) { echo $v2; }')
		);
	}
	public function testRun_foreach_11() {
		$this->assertEquals(
			[ 'a', 'b', 'y', 'z' ],
			Runtime::runSource('foreach ($a as $v1) foreach ($v1 as $v2) echo $v2;')
		);
	}
	public function testRun_foreach_12() {
		$this->assertEquals(
			[ (string)new PhpTagsException( PhpTagsException::WARNING_INVALID_ARGUMENT_FOR_FOREACH, null, 1, 'test' ) ],
			Runtime::runSource( '$a = false; foreach ($a as $v) echo $v;', [ 'test' ], 1 )
		);
	}
	public function testRun_foreach_13() {
		$this->assertEquals(
			[ (string)new PhpTagsException( PhpTagsException::WARNING_INVALID_ARGUMENT_FOR_FOREACH, null, 1, 'test' ) ],
			Runtime::runSource( '$a = false; foreach ($a as $k => $v) echo $v;', [ 'test' ], 1 )
		);
	}
	public function testRun_foreach_14() {
		$this->assertEquals(
			[ '-one-', '-two-', 'false' ],
			Runtime::runSource( '$a = ["one", "two"]; foreach ($a as $v) { echo "-$v-"; $a = false; } echo $a === false ? "false" : "not false";', [ 'test' ], 1 )
		);
	}

	public function testRun_constant_version() {
		$this->assertEquals(
			[ PHPTAGS_VERSION ],
			Runtime::runSource('echo PHPTAGS_VERSION;')
		);
		$this->assertEquals(
			[ ExtensionRegistry::getInstance()->getAllThings()['PhpTags']['version'] ],
			Runtime::runSource('echo PHPTAGS_VERSION;')
			);
	}

	public function testRun_echo_exception_1() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'itIsUndefined', 1, 'Test' ),
					null, ],
				Runtime::runSource( 'echo $itIsUndefined;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_2() {
		$this->assertEquals(
				[ null ],
				Runtime::runSource( 'echo @$itIsUndefined;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_3() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'itIsUndefined1', 1, 'Test' ),
					(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'itIsUndefined2', 1, 'Test' ),
					'0', ],
				Runtime::runSource( 'echo $itIsUndefined1 + $itIsUndefined2;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_4() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'itIsUndefined2', 1, 'Test' ),
					'0', ],
				Runtime::runSource( 'echo @$itIsUndefined1 + $itIsUndefined2;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_5() {
		$this->assertEquals(
				[ '0' ],
				Runtime::runSource( 'echo @$itIsUndefined1 + @$itIsUndefined2;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_6() {
		$this->assertEquals(
				[ '0' ],
				Runtime::runSource( 'echo @($itIsUndefined1 + $itIsUndefined2);', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_7() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'itIsUndefined', 1, 'Test' ),
					'0', ],
				Runtime::runSource( 'echo @ $itIsUndefined[1] + $itIsUndefined[2];', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_8() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::NOTICE_UNDEFINED_VARIABLE, 'itIsUndefined', 1, 'Test' ),
					'0', ],
				Runtime::runSource( 'echo @ $itIsUndefined[1][2] + $itIsUndefined[3][4];', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_9() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null, 1, 'Test' ),
					false, ],
				Runtime::runSource( 'echo 5/0;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_10() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null, 1, 'Test' ),
					false, ],
				Runtime::runSource( 'echo @5/0;', [ 'Test' ] )
			);
	}
	public function testRun_echo_exception_11() {
		$this->assertEquals( [ false ], Runtime::runSource( 'echo @(5/0);', [ 'Test' ] ) );
	}
	public function testRun_echo_exception_12() {
		$this->assertEquals(
				[
					(string) new PhpTagsException( PhpTagsException::WARNING_DIVISION_BY_ZERO, null, 1, 'Test' ),
					false, ],
				Runtime::runSource( 'echo 5%0;', [ 'Test' ] )
			);
	}

	public function testRun_constant_test() {
		wfDebug( 'PHPTags: this message must be after PHPTags test initialization. ' . __METHOD__ );
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			wfDebug( 'PHPTags: constant PHPTAGS_TEST is not defined, skip PhpTagsRuntimeFirstInit hook tests. ' . __METHOD__ );
			return;
		}

		$this->assertEquals(
			[ 'Test' ],
			Runtime::runSource( 'echo PHPTAGS_TEST;' )
		);
	}
	public function testRun_constant_test_banned() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$result = Runtime::runSource( 'echo PHPTAGS_TEST_BANNED;', [ 'Test ban' ] );

		$exc1 = new \PhpTags\HookException( 'Sorry, you cannot use this constant' );
		$exc1->place = 'Test ban';
		$exc1->tokenLine = 1;
		$exc2 = new \PhpTags\HookException( 'banned by administrator' );
		$exc2->place = 'Test ban';
		$exc2->tokenLine = 1;

		$this->assertEquals(
			[ (string)$exc1, (string)$exc2, false ],
			$result
		);
	}
	public function testRun_constant_class_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ 'I am constant PHPTAGS_TEST_IN_CLASS' ],
			Runtime::runSource( 'echo PHPTAGS_TEST_IN_CLASS;' )
		);
	}
	public function testRun_constant_class_banned_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$result = Runtime::runSource( 'echo PHPTAGS_TEST_IN_CLASS_BANNED;', [ 'Test ban' ] );

		$exc1 = new \PhpTags\HookException( 'Sorry, you cannot use this constant' );
		$exc1->place = 'Test ban';
		$exc1->tokenLine = 1;
		$exc2 = new \PhpTags\HookException( 'banned by administrator' );
		$exc2->place = 'Test ban';
		$exc2->tokenLine = 1;

		$this->assertEquals(
			[ (string)$exc1, (string)$exc2, false ],
			$result
		);
	}
	public function testRun_object_constant_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ 'c_OBJ_TEST' ],
			Runtime::runSource( 'echo PhpTagsTest::OBJ_TEST;' )
		);
	}
	public function testRun_object_constant_banned_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$result = Runtime::runSource( 'echo PhpTagsTest::OBJ_TEST_BANNED;', [ 'Test ban' ] );

		$exc1 = new \PhpTags\HookException( 'Sorry, you cannot use this object constant' );
		$exc1->place = 'Test ban';
		$exc1->tokenLine = 1;
		$exc2 = new \PhpTags\HookException( 'banned by administrator' );
		$exc2->place = 'Test ban';
		$exc2->tokenLine = 1;

		$this->assertEquals(
			[ (string)$exc1, (string)$exc2, false ],
			$result
		);
	}
	public function testRun_function_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ 'f_phptagstestfunction' ],
			Runtime::runSource( 'echo PhpTagsTestfunction();' )
		);
	}
	public function testRun_function_banned_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$result = Runtime::runSource( 'echo PhpTagsTestfunction_BANNED();', [ 'Test ban' ] );

		$exc1 = new \PhpTags\HookException( 'Sorry, you cannot use this function' );
		$exc1->place = 'Test ban';
		$exc1->tokenLine = 1;
		$exc2 = new \PhpTags\HookException( 'banned by administrator' );
		$exc2->place = 'Test ban';
		$exc2->tokenLine = 1;

		$this->assertEquals(
			[ (string)$exc1, (string)$exc2, false ],
			$result
		);
	}
	public function testRun_object_method_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ 'm_mymethod' ],
			Runtime::runSource( '$obj = new PhpTagsTest(); echo $obj->myMETHOD();' )
		);
	}
	public function testRun_object_method_banned_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$result = Runtime::runSource( '$obj = new PhpTagsTest(); echo $obj->myMETHOD_BaNnEd();', [ 'Test ban' ] );

		$exc1 = new \PhpTags\HookException( 'Sorry, you cannot use this method' );
		$exc1->place = 'Test ban';
		$exc1->tokenLine = 1;
		$exc2 = new \PhpTags\HookException( 'banned by administrator' );
		$exc2->place = 'Test ban';
		$exc2->tokenLine = 1;

		$this->assertEquals(
			[ (string)$exc1, (string)$exc2, false ],
			$result
		);
	}
	public function testRun_static_method_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ 's_mystaticmethod' ],
			Runtime::runSource( 'echo PhpTagsTest::mystaticMETHOD();' )
		);
	}
	public function testRun_static_method_banned_test() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$result = Runtime::runSource( 'echo PhpTagsTest::mystaticMETHOD_BaNnEd();', [ 'Test ban' ] );

		$exc1 = new \PhpTags\HookException( 'Sorry, you cannot use this static method' );
		$exc1->place = 'Test ban';
		$exc1->tokenLine = 1;
		$exc2 = new \PhpTags\HookException( 'banned by administrator' );
		$exc2->place = 'Test ban';
		$exc2->tokenLine = 1;

		$this->assertEquals(
			[ (string)$exc1, (string)$exc2, false ],
			$result
		);
	}

	public function testRun_object_operation_test_1() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=["rrr"]; $bar=new PhpTagsTest(); $foo = $foo . $bar; echo $foo[0],$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_2() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ), ],
			Runtime::runSource( '$foo=["rrr"]; $bar=new PhpTagsTest(); $foo = $foo + $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_3() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				(string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=["rrr"]; $foo += $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_4() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				6,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar + $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_5() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				4,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar - $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_6() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				-4,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo -= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_7() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				false,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $foo > $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_8() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				true,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $foo < $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_9() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				false,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar <= $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_10() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				true,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar >= $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_11() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				false,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar == $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_12() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				true,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar != $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_13() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				false,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar === $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_14() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				true,
				5, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=5; $foo = $bar !== $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_15() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				9,
				8, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=8; $foo = $bar | $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_16() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				9,
				8, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=8; $foo |= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_17() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				4,
				8, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=8; $foo = $bar >> $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_18() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				256,
				8, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=8; $foo <<= $bar; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_19() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				true,
				8, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=8; $foo = $bar && $foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_20() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ (string) new PhpTagsException( PhpTagsException::FATAL_UNSUPPORTED_OPERAND_TYPES, null, 1, 'test' ) ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar= ~$foo; echo $foo,$bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_21() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar= !$foo; echo $bar === false;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_22() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'int' ], 1, 'test' ),
				1, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=(int)$foo; echo $bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_23() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_OBJECT_CONVERTED, [ 'PhpTagsTest', 'double' ], 1, 'test' ),
				1, ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=(double)$foo; echo $bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_24() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=(string)$foo; echo $bar;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_25() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ '(object <PhpTagsTest>)' ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=(array)$foo; echo $bar[0];', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_26() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=(bool)$foo; echo $bar === true;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_operation_test_27() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[ true ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $bar=(unset)$foo; echo $bar === null;', [ 'test' ], 77777 )
		);
	}

	public function testRun_object_increase_test_1() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $foo++; echo $foo;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_increase_test_2() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); ++$foo; echo $foo;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_increase_test_3() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); --$foo; echo $foo;', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_increase_test_4() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->markTestSkipped( 'php8+ throws TypeError, not returning stringify exception' );
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); $foo--; echo $foo;', [ 'test' ], 77777 )
		);
	}

	public function testRun_array_quote_test_1() {
		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::NOTICE_ARRAY_TO_STRING, null, 1, 'test' ),
				Runtime::R_ARRAY, ],
			Runtime::runSource( '$foo=["bar"]; echo "$foo";', [ 'test' ], 77777 )
		);
	}
	public function testRun_object_quote_test_1() {
		if ( !defined( 'PHPTAGS_TEST' ) ) {
			return;
		}

		$this->assertEquals(
			[
				(string) new PhpTagsException( PhpTagsException::FATAL_OBJECT_COULD_NOT_BE_CONVERTED, [ 'PhpTagsTest', 'string' ], 1, 'test' ), ],
			Runtime::runSource( '$foo=new PhpTagsTest(); echo "$foo";', [ 'test' ], 77777 )
		);
	}

}
