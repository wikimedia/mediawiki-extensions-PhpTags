<?php

namespace Foxway;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-03-28 at 05:28:38.
 */
class InterpreterTest extends \PHPUnit_Framework_TestCase {

	public function testRun_echo_apostrophe() {
		$this->assertEquals(
				Interpreter::run('echo "Hello!";'),
				'Hello!'
				);
	}

	public function testRun_echo_quotes() {
		$this->assertEquals(
				Interpreter::run("echo 'Hello!';"),
				'Hello!'
				);
	}

	public function testRun_echo_union_1() {
		$this->assertEquals(
				Interpreter::run('echo "String" . "Union";'),
				'StringUnion'
				);
	}
	public function testRun_echo_union_2() {
		$this->assertEquals(
				Interpreter::run('echo \'This \' . \'string \' . \'was \' . \'made \' . \'with concatenation.\' . "\n";'),
				"This string was made with concatenation.\n"
				);
	}

	public function testRun_echo_parameters_1() {
		$this->assertEquals(
				Interpreter::run('echo "Parameter1","Parameter2" , "Parameter3";'),
				'Parameter1Parameter2Parameter3'
				);
	}
	public function testRun_echo_parameters_2() {
		$this->assertEquals(
				Interpreter::run('echo \'This \', \'string \', \'was \', \'made \', \'with multiple parameters.\';'),
				'This string was made with multiple parameters.'
				);
	}

	public function testRun_echo_multiline_1() {
		$this->assertEquals(
				Interpreter::run('echo "This spans
multiple lines. The newlines will be
output as well";'),
				"This spans\nmultiple lines. The newlines will be\noutput as well"
				);
	}
	public function testRun_echo_multiline_2() {
		$this->assertEquals(
				Interpreter::run('echo "Again: This spans\nmultiple lines. The newlines will be\noutput as well.";'),
				"Again: This spans\nmultiple lines. The newlines will be\noutput as well."
				);
	}

	public function testRun_echo_variables_1() {
		$this->assertEquals(
				Interpreter::run('
$foo = "foobar";
$bar = "barbaz";
echo "foo is $foo"; // foo is foobar'),
				'foo is foobar'
				);
	}
	public function testRun_echo_variables_2() {
		$this->assertEquals(
				Interpreter::run('echo "foo is {$foo}";'),
				'foo is foobar'
				);
	}
	public function testRun_echo_variables_3() {
		$this->assertEquals(
				Interpreter::run('echo "foo is {$foo}.";'),
				'foo is foobar.'
				);
	}
	public function testRun_echo_variables_4() {
		$this->assertEquals(
				Interpreter::run('echo "foo is $foo\n\n";'),
				"foo is foobar\n\n"
				);
	}
	public function testRun_echo_variables_5() {
		$this->assertEquals(
				Interpreter::run('echo \'foo is $foo\';'),
				'foo is $foo'
				);
	}
	public function testRun_echo_variables_6() {
		$this->assertEquals(
				Interpreter::run('echo $foo,$bar;'),
				'foobarbarbaz'
				);
	}
	public function testRun_echo_variables_7() {
		$this->assertEquals(
				Interpreter::run('echo "$foo$bar";'),
				'foobarbarbaz'
				);
	}
	public function testRun_echo_variables_8() {
		$this->assertEquals(
				Interpreter::run('echo "s{$foo}l{$bar}e";'),
				'sfoobarlbarbaze'
				);
	}
	public function testRun_echo_variables_9() {
		$this->assertEquals(
				Interpreter::run('echo "s{$foo}l$bar";'),
				'sfoobarlbarbaz'
				);
	}
	public function testRun_echo_variables_10() {
		$this->assertEquals(
				Interpreter::run('echo "start" . $foo . "end";'),
				'startfoobarend'
				);
	}
	public function testRun_echo_variables_11() {
		$this->assertEquals(
				Interpreter::run('echo "This ", \'string \', "was $foo ", \'with multiple parameters.\';'),
				'This string was foobar with multiple parameters.'
				);
	}

	public function testRun_echo_escaping_1() {
		$this->assertEquals(
				Interpreter::run('echo \'s\\\\\\\'e\';'),	// echo 's\\\'e';
				's\\\'e'									// s\'e
				);
	}
	public function testRun_echo_escaping_2() {
		$this->assertEquals(
				Interpreter::run('echo "s\\\\\\"e";'),	// echo "s\\\"e";
				's\\"e'									// s\"e
				);
	}

	public function testRun_echo_digit() {
		$this->assertEquals(
				Interpreter::run('echo 5;'),
				'5'
				);
	}

	public function testRun_echo_math_1() {
		$this->assertEquals(
				Interpreter::run('echo \'5 + 5 * 10 = \', 5 + 5 * 10;'),
				'5 + 5 * 10 = 55'
				);
	}
	public function testRun_echo_math_2() {
		$this->assertEquals(
				Interpreter::run('echo -5 + 5 + 10 + 20 - 50 - 5;'),
				'-25'
				);
	}
	public function testRun_echo_math_3() {
		$this->assertEquals(
				Interpreter::run('echo 5 + 5 / 10 + 50/100;'),
				'6'
				);
	}
	public function testRun_echo_math_4() {
		$this->assertEquals(
				Interpreter::run('echo 10 * 10 + "20" * \'20\' - 30 * 30 + 40 / 9;'),
				'-395.55555555556'
				);
	}

	public function testRun_echo_math_params() {
		$this->assertEquals(
				Interpreter::run('echo \'10 + 5 * 5 = \', 10 + 5 * 5, "\n\n";'),
				"10 + 5 * 5 = 35\n\n"
				);
	}

	public function testRun_echo_math_variables() {
		$this->assertEquals(
				Interpreter::run('
$foo = 100;
$bar = \'5\';
echo "\$foo * \$bar = $foo * $bar = ", $foo * $bar, "\n\n";'),
				"\$foo * \$bar = 100 * 5 = 500\n\n"
				);
		$this->assertEquals(
				Interpreter::run('echo "\$foo / \$bar = $foo / $bar = ", $foo / $bar, "\n\n";'),
				"\$foo / \$bar = 100 / 5 = 20\n\n"
				);
		$this->assertEquals(
				Interpreter::run('echo "-\$foo / -\$bar = {-$foo} / {-$bar} = ", -$foo / -$bar, "\n\n";'),
				"-\$foo / -\$bar = {-100} / {-5} = 20\n\n"
				);
	}

	public function testRun_echo_math_union_1() {
		$this->assertEquals(
				Interpreter::run('echo 10 + 5 . 5;'),
				'155'
				);
	}
	public function testRun_echo_math_union_2() {
		$this->assertEquals(
				Interpreter::run('echo 10 + 5 . 5  * 9;'),
				'1545'
				);
	}
	public function testRun_echo_math_union_3() {
		$this->assertEquals(
				Interpreter::run('echo 10 + 5 . 5  * 9 . 4 - 5 . 8;'),
				'154498'
				);
	}

	public function testRun_echo_math_Modulus_1() {
		$this->assertEquals(
				Interpreter::run('echo 123 % 21;'),
				'18'
				);
	}
	public function testRun_echo_math_Modulus_2() {
		$this->assertEquals(
				Interpreter::run('echo 123 % 21 + 74 % -5;'),
				'22'
				);
	}
	public function testRun_echo_math_Modulus_3() {
		$this->assertEquals(
				Interpreter::run('echo 123 % 21 + 74.5 % -5 * 4 / 2 . 5 + -1;'),
				'264'
				);
	}

	public function testRun_echo_math_BitwiseAnd_1() {
		$this->assertEquals(
				Interpreter::run('echo 123 & 21;'),
				'17'
				);
	}
	public function testRun_echo_math_BitwiseAnd_2() {
		$this->assertEquals(
				Interpreter::run('echo 123 & 21 + 94 & 54;'),
				'50'
				);
	}
	public function testRun_echo_math_BitwiseAnd_3() {
		$this->assertEquals(
				Interpreter::run('echo 123 & 21 + 94 & -54;'),
				'66'
				);
	}

	public function testRun_echo_math_BitwiseOr_1() {
		$this->assertEquals(
				Interpreter::run('echo 123 | 21;'),
				'127'
				);
	}
	public function testRun_echo_math_BitwiseOr_2() {
		$this->assertEquals(
				Interpreter::run('echo 123 | -21 / 3;'),
				'-5'
				);
	}

	public function testRun_echo_math_BitwiseXor() {
		$this->assertEquals(
				Interpreter::run('echo -123 ^ 21;'),
				'-112'
				);
	}

	public function testRun_echo_math_LeftShift_1() {
		$this->assertEquals(
				Interpreter::run('echo 123 << 2;'),
				'492'
				);
	}
	public function testRun_echo_math_LeftShift_2() {
		$this->assertEquals(
				Interpreter::run('echo 123 << 2 + 4;'),
				'7872'
				);
	}
	public function testRun_echo_math_LeftShift_3() {
		$this->assertEquals(
				Interpreter::run('echo 123 << 2 + 4 << 2;'),
				'31488'
				);
	}
	public function testRun_echo_math_LeftShift_4() {
		$this->assertEquals(
				Interpreter::run('echo 123 << 2 + 4 << 2 * 8;'),
				'515899392'
				);
	}

	public function testRun_echo_math_RightShift_1() {
		$this->assertEquals(
				Interpreter::run('echo 123 >> 2;'),
				'30'
				);
	}
	public function testRun_echo_math_RightShift_2() {
		$this->assertEquals(
				Interpreter::run('echo 123 >> 2 + 3;'),
				'3'
				);
	}
	public function testRun_echo_math_RightShift_3() {
		$this->assertEquals(
				Interpreter::run('echo -123 >> 2 + 3;'),
				'-4'
				);
	}

	public function testRun_echo_math_Increment_1() {
		$this->assertEquals(
				Interpreter::run('$a = 10; echo $a++, $a, ++$a;'),
				'101112'
				);
	}
	public function testRun_echo_math_Increment_2() {
		$this->assertEquals(
				Interpreter::run('
$a = 10;
$a++;
++$a;
echo "$a, ", $a++ + -5, ", " . ++$a, ", $a.";'),
				'12, 7, 14, 14.'
				);
	}
	public function testRun_echo_math_Decrement_1() {
		$this->assertEquals(
				Interpreter::run('$a = 10; echo $a--, $a, --$a;'),
				'1098'
				);
	}
	public function testRun_echo_math_Decrement_2() {
		$this->assertEquals(
				Interpreter::run('
$a = 10;
$a--;
--$a;
echo "$a, ", $a-- + -5, ", " . --$a, ", $a.";'),
				'8, 3, 6, 6.'
				);
	}

	public function testRun_echo_parentheses_1() {
		$this->assertEquals(
				Interpreter::run('echo (2+5)*10;'),
				'70'
				);
	}
	public function testRun_echo_parentheses_2() {
		$this->assertEquals(
				Interpreter::run('$a=5; $a += ++$a - ( 9 + 9 ) / 9; echo $a;'),
				'10'
				);
		$this->assertEquals(
				Interpreter::run('$a=5; $a += ++$a - -( 9 + 9 ) / 9; echo $a;'),
				'14'
				);
	}
	public function testRun_echo_parentheses_3() {
		$this->assertEquals(
				Interpreter::run('echo (5+8)/4 + (((2+1) * (3+2) + 4)/5 + 7);'),
				'14.05'
				);
	}
	public function testRun_echo_parentheses_4() {
		$this->assertEquals(
				Interpreter::run('echo (5+8);'),
				'13'
				);
	}
	public function testRun_echo_parentheses_5() {
		$this->assertEquals(
				Interpreter::run('echo ("hello");'),
				'hello'
				);
	}
	public function testRun_echo_parentheses_6() {
		$this->assertEquals(
				Interpreter::run('$foo = "foo"; echo("hello $foo");'),
				'hello foo'
				);
	}/*
	public function testRun_echo_parentheses_7() {
		$this->assertEquals(
				Interpreter::run('echo("hello "), $foo;'),
				'hello foo'
				);
	}
	public function testRun_echo_parentheses_8() {
		$this->assertEquals(
				Interpreter::run('echo ($foo), (" is "), $foo;'),
				'foo is foo'
				);
	}*/

	public function testRun_echo_inverting_1() {
		$this->assertEquals(
				Interpreter::run('echo ~10;'),
				'-11'
				);
	}
	public function testRun_echo_inverting_2() {
		$this->assertEquals(
				Interpreter::run('echo ~-10;'),
				'9'
				);
	}
	public function testRun_echo_inverting_3() {
		$this->assertEquals(
				Interpreter::run('echo -~10;'),
				'11'
				);
	}

	public function testRun_echo_type_1() {
		$this->assertEquals(
				Interpreter::run('echo (bool)10;'),
				'1'
				);
	}
	public function testRun_echo_type_2() {
		$this->assertEquals(
				Interpreter::run('echo (bool)-10;'),
				'1'
				);
	}
	public function testRun_echo_type_3() {
		$this->assertEquals(
				Interpreter::run('echo -(bool)10;'),
				'-1'
				);
	}
	public function testRun_echo_type_4() {
		$this->assertEquals(
				Interpreter::run('echo (bool)0;'),
				''
				);
	}
	public function testRun_echo_type_5() {
		$this->assertEquals(
				Interpreter::run('echo -(int)-5.5;'),
				'5'
				);
	}

	public function testRun_echo_compare_1() {
		$this->assertEquals(
				Interpreter::run('echo 5 == 5;'),
				'1'
				);
	}
	public function testRun_echo_compare_2() {
		$this->assertEquals(
				Interpreter::run('echo 5 == 3+2;'),
				'1'
				);
	}
	public function testRun_echo_compare_3() {
		$this->assertEquals(
				Interpreter::run('echo -3 + 8 == 3 + 2;'),
				'1'
				);
	}
	public function testRun_echo_compare_4() {
		$this->assertEquals(
				Interpreter::run('echo -3 * -8 > 3 + 8;'),
				'1'
				);
	}
	public function testRun_echo_compare_5() {
		$this->assertEquals(
				Interpreter::run('echo -3 * 8 < 3 + 8;'),
				'1'
				);
	}
	public function testRun_echo_compare_6() {
		$this->assertEquals(
				Interpreter::run('echo 3 === (int)"3";'),
				'1'
				);
	}
	public function testRun_echo_compare_7() {
		$this->assertEquals(
				Interpreter::run('echo 0 == "a";'),
				'1'
				);
	}
	public function testRun_echo_compare_8() {
		$this->assertEquals(
				Interpreter::run('echo "1" == "01";'),
				'1'
				);
	}
	public function testRun_echo_compare_9() {
		$this->assertEquals(
				Interpreter::run('echo "10" == "1e1";'),
				'1'
				);
	}
	public function testRun_echo_compare_10() {
		$this->assertEquals(
				Interpreter::run('echo 100 == "1e2";'),
				'1'
				);
	}
	public function testRun_echo_compare_11() {
		$this->assertEquals(
				Interpreter::run('$foo = 4; echo $foo != $foo*2;'),
				'1'
				);
	}
	public function testRun_echo_compare_12() {
		$this->assertEquals(
				Interpreter::run('echo $foo <= $foo*2;'),
				'1'
				);
	}
	public function testRun_echo_compare_13() {
		$this->assertEquals(
				Interpreter::run('echo $foo*4 >= $foo*2;'),
				'1'
				);
	}
	public function testRun_echo_compare_14() {
		$this->assertEquals(
				Interpreter::run('echo 5 !== (string)5;'),
				'1'
				);
	}

	public function testRun_echo_compare_false() {
		$this->assertEquals(
				Interpreter::run('echo ( 5 === (string)5 ) === false;'),
				'1'
				);
	}
	public function testRun_echo_compare_true() {
		$this->assertEquals(
				Interpreter::run('echo (100 == "1e2") === true;'),
				'1'
				);
	}
	public function testRun_echo_compare_false_true() {
		$this->assertEquals(
				Interpreter::run('echo (false === true) == false;'),
				'1'
				);
	}
	public function testRun_echo_compare_true_true() {
		$this->assertEquals(
				Interpreter::run('echo true === true === true;'),
				'1'
				);
	}

	public function testRun_echo_true() {
		$this->assertEquals(
				Interpreter::run('echo true;'),
				'1'
				);
	}
	public function testRun_echo_false() {
		$this->assertEquals(
				Interpreter::run('echo false;'),
				''
				);
	}

	public function testRun_echo_ternary_1() {
		$this->assertEquals(
				Interpreter::run('echo true?"true":"false";'),
				'true'
				);
	}
	public function testRun_echo_ternary_2() {
		$this->assertEquals(
				Interpreter::run('echo false?"true":"false";'),
				'false'
				);
	}
	public function testRun_echo_ternary_3() {
		$this->assertEquals(
				Interpreter::run('echo true?"true":false?"t":"f";'),
				't'
				);
	}
	public function testRun_echo_ternary_4() {
		$this->assertEquals(
				Interpreter::run('echo false?"true":false?"t":"f";'),
				'f'
				);
	}
	public function testRun_echo_ternary_5() {
		$this->assertEquals(
				Interpreter::run('echo true?true?"true":false:false?"t":"f";'),
				't'
				);
	}
	public function testRun_echo_ternary_6() {
		$this->assertEquals(
				Interpreter::run('echo true?true?false:false:false?"t":"f";'),
				'f'
				);
	}
	public function testRun_echo_ternary_7() {
		$this->assertEquals(
				Interpreter::run('echo true?true?"true":false:"false";'),
				'true'
				);
	}
	public function testRun_echo_ternary_8() {
		$this->assertEquals(
				Interpreter::run('echo false?true?false:false:"false";'),
				'false'
				);
	}

	public function testRun_echo_if_simple_1() {
		$this->assertEquals(
				Interpreter::run('if ( true ) echo "hello";'),
				'hello'
				);
	}
	public function testRun_echo_if_simple_2() {
		$this->assertEquals(
				Interpreter::run('if ( false ) echo "hello";'),
				''
				);
	}
	public function testRun_echo_if_simple_3() {
		$this->assertEquals(
				Interpreter::run('
if ( 5+5 ) echo "hello";
if ( 5-5 ) echo " === FALSE === ";
if ( (5+5)/4 ) echo "world";
if ( -5+5 ) echo " === FALSE === ";
if ( ((74+4)*(4+6)+88)*4 ) echo "!!!";'),
				'helloworld!!!'
				);
	}
	public function testRun_echo_if_else_simple_1() {
		$this->assertEquals(
				Interpreter::run('if ( true ) echo "true"; else echo "false";'),
				'true'
				);
	}
	public function testRun_echo_if_else_simple_2() {
		$this->assertEquals(
				Interpreter::run('if ( false ) echo "true"; else echo "false";'),
				'false'
				);
	}
	public function testRun_echo_if_else_simple_3() {
		$this->assertEquals(
				Interpreter::run('if ( true ) echo "true"; else echo "false"; echo " always!";'),
				'true always!'
				);
	}
	public function testRun_echo_if_else_simple_4() {
		$this->assertEquals(
				Interpreter::run('if ( false ) echo "true"; else echo "false"; echo " always!";'),
				'false always!'
				);
	}
	public function testRun_echo_if_else_block_1() {
		$this->assertEquals(
				Interpreter::run('if ( true ) { echo "true"; echo "true";} else { echo "false"; echo "false"; }'),
				'truetrue'
				);
	}
	public function testRun_echo_if_else_block_2() {
		$this->assertEquals(
				Interpreter::run('if ( false ) { echo "true"; echo "true";} else { echo "false"; echo "false"; }'),
				'falsefalse'
				);
	}
	public function testRun_echo_if_variable_1() {
		$this->assertEquals(
				Interpreter::run('$foo = 5; if ( $foo > 4 ) echo "true"; else echo "false";'),
				'true'
				);
	}
	public function testRun_echo_if_variable_2() {
		$this->assertEquals(
				Interpreter::run('if( $foo*2 > 4*3 ) echo "true"; else echo "false";'),
				'false'
				);
	}
	public function testRun_echo_if_variable_3() {
		$this->assertEquals(
				Interpreter::run('if( $foo === 5 ) echo "true"; else echo "false";'),
				'true'
				);
	}
	public function testRun_echo_if_variable_4() {
		$this->assertEquals(
				Interpreter::run('if( $foo++ ==  5 ) echo "true"; else echo "false";'),
				'true'
				);
	}
	public function testRun_echo_if_variable_5() {
		$this->assertEquals(
				Interpreter::run('if( ++$foo ==  7 ) echo "true"; else echo "false";'),
				'true'
				);
	}
	public function testRun_echo_if_double_1() {
		$this->assertEquals(
				Interpreter::run('if( true ) if( true ) echo "true"; else echo "false";'),
				'true'
				);
	}
	public function testRun_echo_if_double_2() {
		$this->assertEquals(
				Interpreter::run('if( true ) if( true ) {echo "true"; echo "true";} else echo "falsefalse";'),
				'truetrue'
				);
	}
	public function testRun_echo_if_double_3() {
		$this->assertEquals(
				Interpreter::run('if( false ) if( true ) {echo "true"; echo "true";} else echo "falsefalse";'),
				''
				);
	}
	public function testRun_echo_if_double_4() {
		$this->assertEquals(
				Interpreter::run('if( false ) if( true ) {echo "true"; echo "true";} else echo "falsefalse"; else echo "false";'),
				'false'
				);
	}
	public function testRun_echo_elseif_1() {
		$this->assertEquals(
				Interpreter::run('if( true ) echo "one"; elseif( true ) echo "two"; else echo "three";'),
				'one'
				);
	}
	public function testRun_echo_elseif_2() {
		$this->assertEquals(
				Interpreter::run('if( false ) echo "one"; elseif( true ) echo "two"; else echo "three";'),
				'two'
				);
	}
	public function testRun_echo_elseif_3() {
		$this->assertEquals(
				Interpreter::run('if( false ) echo "one"; elseif( false ) echo "two"; else echo "three";'),
				'three'
				);
	}
	public function testRun_echo_elseif_4() {
		$this->assertEquals(
				Interpreter::run('if( true ) { echo "*"; echo "one"; } elseif( true ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }'),
				'*one'
				);
	}
	public function testRun_echo_elseif_5() {
		$this->assertEquals(
				Interpreter::run('if( false ) { echo "*"; echo "one"; } elseif( true ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }'),
				'*two'
				);
	}
	public function testRun_echo_elseif_6() {
		$this->assertEquals(
				Interpreter::run('if( false ) { echo "*"; echo "one"; } elseif( false ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }'),
				'*three'
				);
	}

}
