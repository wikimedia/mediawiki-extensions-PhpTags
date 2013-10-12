<?php

namespace Foxway;

class RuntimeTest extends \PHPUnit_Framework_TestCase {

	public function testRun_echo_apostrophe_1() {
		$this->assertEquals(
				Runtime::runSource('echo "Hello!";'),
				array('Hello!')
				);
	}
	public function testRun_echo_apostrophe_2() {
		$this->assertEquals(
				Runtime::runSource('echo ("Hello!");'),
				array('Hello!')
				);
	}

	public function testRun_echo_quotes_1() {
		$this->assertEquals(
				Runtime::runSource("echo 'Hello!';"),
				array('Hello!')
				);
	}
	public function testRun_echo_quotes_2() {
		$this->assertEquals(
				Runtime::runSource("echo ('Hello!');"),
				array('Hello!')
				);
	}

	public function testRun_echo_union_1() {
		$this->assertEquals(
				Runtime::runSource('echo "String" . "Union";'),
				array('StringUnion')
				);
	}
	public function testRun_echo_union_2() {
		$this->assertEquals(
				Runtime::runSource('echo "One" . "Two" . "Three";'),
				array('OneTwoThree')
				);
	}
	public function testRun_echo_union_3() {
		$this->assertEquals(
				Runtime::runSource('echo \'This \' . \'string \' . \'was \' . \'made \' . \'with concatenation.\' . "\n";'),
				array("This string was made with concatenation.\n")
				);
	}
	public function testRun_echo_union_4() {
		$this->assertEquals(
				Runtime::runSource('echo ("String" . "Union");'),
				array('StringUnion')
				);
	}

	public function testRun_echo_parameters_1() {
		$this->assertEquals(
				Runtime::runSource('echo "Parameter1","Parameter2" , "Parameter3";'),
				array('Parameter1', 'Parameter2', 'Parameter3')
				);
	}
	public function testRun_echo_parameters_2() {
		$this->assertEquals(
				Runtime::runSource('echo \'This \', \'string \', \'was \', \'made \', \'with multiple parameters.\';'),
				array('This ', 'string ', 'was ', 'made ', 'with multiple parameters.')
				);
	}

	public function testRun_echo_multiline_1() {
		$this->assertEquals(
				Runtime::runSource('echo "This spans
multiple lines. The newlines will be
output as well";'),
				array("This spans\nmultiple lines. The newlines will be\noutput as well")
				);
	}
	public function testRun_echo_multiline_2() {
		$this->assertEquals(
				Runtime::runSource('echo "Again: This spans\nmultiple lines. The newlines will be\noutput as well.";'),
				array("Again: This spans\nmultiple lines. The newlines will be\noutput as well.")
				);
	}

	public function testRun_echo_negative_1() {
		$this->assertEquals(
				Runtime::runSource('echo -7;'),
				array(-7)
				);
	}
	public function testRun_echo_negative_2() {
		$this->assertEquals(
				Runtime::runSource('echo (int)-7;'),
				array(-7)
				);
	}
	public function testRun_echo_negative_3() {
		$this->assertEquals(
				Runtime::runSource('echo (int)-(int)7;'),
				array(-7)
				);
	}

	public function testRun_echo_variables_0() {
		$this->assertEquals(
				Runtime::runSource('$foo=111; echo $foo;'),
				array(111)
				);
	}
	public function testRun_echo_variables_1() {
		$this->assertEquals(
				Runtime::runSource('
$foo = "foobar";
$bar = "barbaz";
echo "foo is $foo"; // foo is foobar'),
				array('foo is foobar')
				);
	}
	public function testRun_echo_variables_2() {
		$this->assertEquals(
				Runtime::runSource('echo "foo is {$foo}";'),
				array('foo is foobar')
				);
	}
	public function testRun_echo_variables_3() {
		$this->assertEquals(
				Runtime::runSource('echo "foo is {$foo}.";'),
				array('foo is foobar.')
				);
	}
	public function testRun_echo_variables_4() {
		$this->assertEquals(
				Runtime::runSource('echo "foo is $foo\n\n";'),
				array("foo is foobar\n\n")
				);
	}
	public function testRun_echo_variables_5() {
		$this->assertEquals(
				Runtime::runSource('echo \'foo is $foo\';'),
				array('foo is $foo')
				);
	}
	public function testRun_echo_variables_6() {
		$this->assertEquals(
				Runtime::runSource('echo $foo,$bar;'),
				array('foobar', 'barbaz')
				);
	}
	public function testRun_echo_variables_7() {
		$this->assertEquals(
				Runtime::runSource('echo "$foo$bar";'),
				array('foobarbarbaz')
				);
	}
	public function testRun_echo_variables_8() {
		$this->assertEquals(
				Runtime::runSource('echo "s{$foo}l{$bar}e";'),
				array('sfoobarlbarbaze')
				);
	}
	public function testRun_echo_variables_9() {
		$this->assertEquals(
				Runtime::runSource('echo "s{$foo}l$bar";'),
				array('sfoobarlbarbaz')
				);
	}
	public function testRun_echo_variables_10() {
		$this->assertEquals(
				Runtime::runSource('echo "start" . $foo . "end";'),
				array('startfoobarend')
				);
	}
	public function testRun_echo_variables_11() {
		$this->assertEquals(
				Runtime::runSource('echo "This ", \'string \', "was $foo ", \'with multiple parameters.\';'),
				array('This ', 'string ', 'was foobar ', 'with multiple parameters.')
				);
	}
	public function testRun_echo_variables_12() {
		$this->assertEquals(
				Runtime::runSource('$foo=-7; echo -$foo;'),
				array(7)
				);
	}
	public function testRun_echo_variables_13() {
		$this->assertEquals(
				Runtime::runSource('$foo=(int)-7; echo -$foo;'),
				array(7)
				);
	}
	public function testRun_echo_variables_14() {
		$this->assertEquals(
				Runtime::runSource('$foo=-7; echo (int)-(int)$foo;'),
				array(7)
				);
	}

	public function testRun_echo_escaping_1() {
		$this->assertEquals(
				Runtime::runSource('echo \'s\\\\\\\'e\';'),	// echo 's\\\'e';
				array('s\\\'e')								// s\'e
				);
	}
	public function testRun_echo_escaping_2() {
		$this->assertEquals(
				Runtime::runSource('echo "s\\\\\\"e";'),	// echo "s\\\"e";
				array('s\\"e')							// s\"e
				);
	}
	public function testRun_echo_escaping_3() {
		$this->assertEquals(
				Runtime::runSource('echo "\\\\\\\\\\\\n";'),	// echo "\\\\\\n";
				array('\\\\\\n')							// \\\n
				);
	}
	public function testRun_echo_escaping_4() {
		$this->assertEquals(
				Runtime::runSource('echo "\\\\\\\\\\\\\\n";'),	// echo "\\\\\\\n";
				array("\\\\\\\n")							// \\\<new line>
				);
	}

	public function testRun_echo_digit_1() {
		$this->assertEquals(
				Runtime::runSource('echo 5;'),
				array(5)
				);
	}

	public function testRun_echo_digit_2() {
		$this->assertEquals(
				Runtime::runSource('echo 5.5;'),
				array('5.5')
				);
	}

	public function testRun_echo_math_1() {
		$this->assertEquals(
				Runtime::runSource('echo \'5 + 5 * 10 = \', 5 + 5 * 10;'),
				array('5 + 5 * 10 = ', '55')
				);
	}
	public function testRun_echo_math_2() {
		$this->assertEquals(
				Runtime::runSource('echo -5 + 5 + 10 + 20 - 50 - 5;'),
				array('-25')
				);
	}
	public function testRun_echo_math_3() {
		$this->assertEquals(
				Runtime::runSource('echo 5 + 5 / 10 + 50/100;'),
				array('6')
				);
	}
	public function testRun_echo_math_4() {
		$this->assertEquals(
				Runtime::runSource('echo 10 * 10 + "20" * \'20\' - 30 * 30 + 40 / 9;'),
				array('-395.55555555556')
				);
	}
	public function testRun_echo_math_5() {
		$this->assertEquals(
				Runtime::runSource('$foo = 5; echo 2 + "$foo$foo" * 10;'),
				array('552')
				);
	}
	public function testRun_echo_math_6() {
		$this->assertEquals(
				Runtime::runSource('$foo = 5; echo 2 + "$foo{$foo}0" * 10;'),
				array('5502')
				);
	}

	public function testRun_echo_math_params() {
		$this->assertEquals(
				Runtime::runSource('echo \'10 + 5 * 5 = \', 10 + 5 * 5, "\n\n";'),
				array('10 + 5 * 5 = ', '35', "\n\n")
				);
	}

	public function testRun_echo_math_variables() {
		$this->assertEquals(
				Runtime::runSource('
$foo = 100;
$bar = \'5\';
echo "\$foo * \$bar = $foo * $bar = ", $foo * $bar, "\n\n";'),
				array('$foo * $bar = 100 * 5 = ', '500', "\n\n")
				);
		$this->assertEquals(
				Runtime::runSource('echo "\$foo / \$bar = $foo / $bar = ", $foo / $bar, "\n\n";'),
				array('$foo / $bar = 100 / 5 = ', '20', "\n\n")
				);
		$this->assertEquals(
				Runtime::runSource('echo "-\$foo / -\$bar = {-$foo} / {-$bar} = ", -$foo / -$bar, "\n\n";'),
				array('-$foo / -$bar = {-100} / {-5} = ', '20', "\n\n")
				);
	}

	public function testRun_echo_math_variables_1() {
		$this->assertEquals(
				Runtime::runSource('$foo = 100; $bar=-50; echo $foo+=$bar; echo $foo;'),
				array('50', '50')
				);
	}
	public function testRun_echo_math_variables_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo;'), // $foo = 40 + 1; echo 41 + 41
				array('82')
				);
	}
	public function testRun_echo_math_variables_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo, $foo;'), // $foo = 40 + 1; echo 41 + 41, 41
				array('82', '41')
				);
	}
	public function testRun_echo_math_variables_4() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo = 400 + $foo, $foo;'), // $foo = 400 + 1; $foo = 40 + 401; echo 441 + 441, 441
				array('882', '441')
				);
	}
	public function testRun_echo_math_variables_4_increment_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo + $foo = 40 + $foo = 400 + $foo++, $foo;'), // $foo = 400 + 1; $foo = 40 + 401; echo 441 + 441, 441
				array('882', '441')
				);
	}/*
	public function testRun_echo_math_variables_4_increment_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo++ + $foo = 40 + $foo = 400 + $foo, $foo;'), // $foo = 400 + 2; $foo = 40 + 402; echo 1 + 442, 442
				array('443', '442')
				);
	}*/
	public function testRun_echo_math_variables_short_circuit_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=10; echo $foo = 400 + $foo or $foo = 10000, $foo;'), // $foo = 400 + 10; echo 441 or ... , 410
				array(true, '410')
				);
	}/*
	public function testRun_echo_math_variables_short_circuit_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=10; echo $foo = 10 - $foo or $foo = 10000, $foo;'), // $foo = 10 - 10; echo 0 or $foo=10000 , 10000
				array(true, '10000')
				);
	}*/
	public function testRun_echo_math_variables_5() {
		$this->assertEquals(
				Runtime::runSource('$foo=10; echo $foo = 400 + $foo | $foo = 10000, $foo;'), // $foo = 400 + 10 | 10000; echo 10138, 10138
				array('10138', '10138')
				);
	}

	public function testRun_echo_math_union_1() {
		$this->assertEquals(
				Runtime::runSource('echo 10 + 5 . 5;'),
				array('155')
				);
	}
	public function testRun_echo_math_union_2() {
		$this->assertEquals(
				Runtime::runSource('echo 10 + 5 . 5  * 9;'),
				array('1545')
				);
	}
	public function testRun_echo_math_union_3() {
		$this->assertEquals(
				Runtime::runSource('echo 10 + 5 . 5  * 9 . 4 - 5 . 8;'),
				array('154498')
				);
	}

	public function testRun_echo_math_Modulus_1() {
		$this->assertEquals(
				Runtime::runSource('echo 123 % 21;'),
				array('18')
				);
	}
	public function testRun_echo_math_Modulus_2() {
		$this->assertEquals(
				Runtime::runSource('echo 123 % 21 + 74 % -5;'),
				array('22')
				);
	}
	public function testRun_echo_math_Modulus_3() {
		$this->assertEquals(
				Runtime::runSource('echo 123 % 21 + 74.5 % -5 * 4 / 2 . 5 + -1;'),
				array('264')
				);
	}

	public function testRun_echo_math_BitwiseAnd_1() {
		$this->assertEquals(
				Runtime::runSource('echo 123 & 21;'),
				array('17')
				);
	}
	public function testRun_echo_math_BitwiseAnd_2() {
		$this->assertEquals(
				Runtime::runSource('echo 123 & 21 + 94 & 54;'),
				array('50')
				);
	}
	public function testRun_echo_math_BitwiseAnd_3() {
		$this->assertEquals(
				Runtime::runSource('echo 123 & 21 + 94 & -54;'),
				array('66')
				);
	}

	public function testRun_echo_math_BitwiseOr_1() {
		$this->assertEquals(
				Runtime::runSource('echo 123 | 21;'),
				array('127')
				);
	}
	public function testRun_echo_math_BitwiseOr_2() {
		$this->assertEquals(
				Runtime::runSource('echo 123 | -21 / 3;'),
				array('-5')
				);
	}

	public function testRun_echo_math_BitwiseXor() {
		$this->assertEquals(
				Runtime::runSource('echo -123 ^ 21;'),
				array('-112')
				);
	}

	public function testRun_echo_math_LeftShift_1() {
		$this->assertEquals(
				Runtime::runSource('echo 123 << 2;'),
				array('492')
				);
	}
	public function testRun_echo_math_LeftShift_2() {
		$this->assertEquals(
				Runtime::runSource('echo 123 << 2 + 4;'),
				array('7872')
				);
	}
	public function testRun_echo_math_LeftShift_3() {
		$this->assertEquals(
				Runtime::runSource('echo 123 << 2 + 4 << 2;'),
				array('31488')
				);
	}
	public function testRun_echo_math_LeftShift_4() {
		$this->assertEquals(
				Runtime::runSource('echo 123 << 2 + 4 << 2 * 8;'),
				array('515899392')
				);
	}

	public function testRun_echo_math_RightShift_1() {
		$this->assertEquals(
				Runtime::runSource('echo 123 >> 2;'),
				array('30')
				);
	}
	public function testRun_echo_math_RightShift_2() {
		$this->assertEquals(
				Runtime::runSource('echo 123 >> 2 + 3;'),
				array('3')
				);
	}
	public function testRun_echo_math_RightShift_3() {
		$this->assertEquals(
				Runtime::runSource('echo -123 >> 2 + 3;'),
				array('-4')
				);
	}

	public function testRun_echo_math_Increment_1() {
		$this->assertEquals(
				Runtime::runSource('$a = 10; echo $a++, $a, ++$a;'),
				array('10', '11', '12')
				);
	}
	public function testRun_echo_math_Increment_2() {
		$this->assertEquals(
				Runtime::runSource('$a = 10; echo $a++ + $a + ++$a;'),
				array('33')
				);
	}
	public function testRun_echo_math_Increment_3() {
		$this->assertEquals(
				Runtime::runSource('
$a = 10;
$a++;
++$a;
echo "$a, ", $a++ + -5, ", " . ++$a, ", $a.";'),
				array('12, ', '7', ', 14', ', 14.')
				);
	}
	public function testRun_echo_math_Increment_4() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; $c=30; echo $a + $b * $a++;'),
				array('23')
				);
	}
	public function testRun_echo_math_Increment_5() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; $c=30; echo $a + $b * ++$a;'),
				array('33')
				);
	}
	public function testRun_echo_math_Increment_6() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; $c=30; echo $a + $b * ++$a;'),
				array('33')
				);
	}
	public function testRun_echo_math_Increment_7() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; $c=30; echo ++$a + $b * $a;'),
				array('33')
				);
	}
	public function testRun_echo_math_Increment_8() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; $c=30; echo $a++ + $b * ++$a;'),
				array('42')
				);
	}
	public function testRun_echo_math_Increment_9() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; $c=30; echo ++$a + $b * ++$a + $b;'),
				array('53')
				);
	}
	public function testRun_echo_math_Increment_10() {
		$this->assertEquals(
				Runtime::runSource('$a=2; $b=10; echo $a + ++$a + $b * ++$a + $b;'),
				array('56')
				);
	}
	public function testRun_echo_math_Decrement_1() {
		$this->assertEquals(
				Runtime::runSource('$a = 10; echo $a--, $a, --$a;'),
				array('10', '9', '8')
				);
	}
	public function testRun_echo_math_Decrement_2() {
		$this->assertEquals(
				Runtime::runSource('
$a = 10;
$a--;
--$a;
echo "$a, ", $a-- + -5, ", " . --$a, ", $a.";'),
				array('8, ', '3', ', 6', ', 6.')
				);
	}

	public function testRun_echo_parentheses_1() {
		$this->assertEquals(
				Runtime::runSource('echo (2+5);'),
				array('7')
				);
	}
	public function testRun_echo_parentheses_1_n() {
		$this->assertEquals(
				Runtime::runSource('echo -(2+5);'),
				array('-7')
				);
	}
	public function testRun_echo_parentheses_2() {
		$this->assertEquals(
				Runtime::runSource('echo ("hello");'),
				array('hello')
				);
	}
	public function testRun_echo_parentheses_3() {
		$this->assertEquals(
				Runtime::runSource('echo (2+5)*10;'),
				array('70')
				);
	}
	public function testRun_echo_parentheses_3_n() {
		$this->assertEquals(
				Runtime::runSource('echo -(2+5)*10;'),
				array('-70')
				);
	}
	public function testRun_echo_parentheses_3_n_n() {
		$this->assertEquals(
				Runtime::runSource('echo -(-2+5)*10;'),
				array('-30')
				);
	}
	public function testRun_echo_parentheses_4() {
		$this->assertEquals(
				Runtime::runSource('$a=5; $a += (3+11); echo $a;'),
				array('19')
				);
	}
	public function testRun_echo_parentheses_4_n() {
		$this->assertEquals(
				Runtime::runSource('$a=5; $a += -(3+11); echo $a;'),
				array('-9')
				);
	}
	public function testRun_echo_parentheses_5() {
		$this->assertEquals(
				Runtime::runSource('$a=5; $a += ++$a-(3+11); echo $a;'),
				array('-2')
				);
	}
	public function testRun_echo_parentheses_5_n() {
		$this->assertEquals(
				Runtime::runSource('$a=5; $a += ++$a- -(3+11)/2; echo $a;'),
				array('19')
				);
	}
	public function testRun_echo_parentheses_6() {
		$this->assertEquals(
				Runtime::runSource('echo (5+8)/4 + (((2+1) * (3+2) + 4)/5 + 7);'),
				array('14.05')
				);
	}
	public function testRun_echo_parentheses_7() {
		$this->assertEquals(
				Runtime::runSource('$foo = "foo"; echo("hello $foo");'),
				array('hello foo')
				);
	}
	public function testRun_echo_parentheses_8() {
		$this->assertEquals(
				Runtime::runSource('echo("hello "), $foo;'),
				array('hello ', 'foo')
				);
	}
	public function testRun_echo_parentheses_9() {
		$this->assertEquals(
				Runtime::runSource('echo ($foo), (" is "), $foo;'),
				array('foo', ' is ', 'foo')
				);
	}
	public function testRun_echo_parentheses_10() {
		$this->assertEquals(
				Runtime::runSource('echo (6)*(2);'),
				array(12)
				);
	}
	public function testRun_echo_parentheses_10_n() {
		$this->assertEquals(
				Runtime::runSource('echo (6)*(-2);'),
				array(-12)
				);
	}
	public function testRun_echo_parentheses_11() {
		$this->assertEquals(
				Runtime::runSource('$foo=3; echo -($foo+5)*10;'),
				array('-80')
				);
	}
	public function testRun_echo_parentheses_12() {
		$this->assertEquals(
				Runtime::runSource('$foo=3; echo -(-$foo+-5)*-10;'),
				array('-80')
				);
	}
	public function testRun_echo_parentheses_13() {
		$this->assertEquals(
				Runtime::runSource('echo (3+10)*$foo=5;'),
				array(65)
				);
	}
	public function testRun_echo_parentheses_14() {
		$this->assertEquals(
				Runtime::runSource('echo (3+10)*$foo=5, $foo;'),
				array(65, 5)
				);
	}
	public function testRun_echo_parentheses_15() {
		$this->assertEquals(
				Runtime::runSource('echo (3+10)*$foo=5*(7+9), $foo;'),
				array(1040, 80)
				);
	}

	public function testRun_echo_inverting_1() {
		$this->assertEquals(
				Runtime::runSource('echo ~10;'),
				array('-11')
				);
	}
	public function testRun_echo_inverting_2() {
		$this->assertEquals(
				Runtime::runSource('echo ~-10;'),
				array('9')
				);
	}
	public function testRun_echo_inverting_3() {
		$this->assertEquals(
				Runtime::runSource('echo -~10;'),
				array('11')
				);
	}

	public function testRun_echo_type_1() {
		$this->assertEquals(
				Runtime::runSource('echo (bool)10;'),
				array('1')
				);
	}
	public function testRun_echo_type_2() {
		$this->assertEquals(
				Runtime::runSource('echo (bool)-10;'),
				array('1')
				);
	}
	public function testRun_echo_type_3() {
		$this->assertEquals(
				Runtime::runSource('echo -(bool)10;'),
				array('-1')
				);
	}
	public function testRun_echo_type_4() {
		$this->assertEquals(
				Runtime::runSource('echo (bool)0;'),
				array('')
				);
	}
	public function testRun_echo_type_5() {
		$this->assertEquals(
				Runtime::runSource('echo -(int)-5.5;'),
				array('5')
				);
	}
	public function testRun_echo_type_6() {
		$this->assertEquals(
				Runtime::runSource('echo -(int)-5.5 + (int)(bool)"2";'),
				array('6')
				);
	}

	public function testRun_echo_true() {
		$this->assertEquals(
				Runtime::runSource('echo true;'),
				array('1')
				);
	}
	public function testRun_echo_false() {
		$this->assertEquals(
				Runtime::runSource('echo false;'),
				array('')
				);
	}

	public function testRun_echo_compare_1() {
		$this->assertEquals(
				Runtime::runSource('echo 5 == 5;'),
				array('1')
				);
	}
	public function testRun_echo_compare_2() {
		$this->assertEquals(
				Runtime::runSource('echo 5 == 3+2;'),
				array('1')
				);
	}
	public function testRun_echo_compare_3() {
		$this->assertEquals(
				Runtime::runSource('echo -3 + 8 == 3 + 2;'),
				array('1')
				);
	}
	public function testRun_echo_compare_4() {
		$this->assertEquals(
				Runtime::runSource('echo -3 * -8 > 3 + 8;'),
				array('1')
				);
	}
	public function testRun_echo_compare_5() {
		$this->assertEquals(
				Runtime::runSource('echo -3 * 8 < 3 + 8;'),
				array('1')
				);
	}
	public function testRun_echo_compare_6() {
		$this->assertEquals(
				Runtime::runSource('echo 3 === (int)"3";'),
				array('1')
				);
	}
	public function testRun_echo_compare_7() {
		$this->assertEquals(
				Runtime::runSource('echo 0 == "a";'),
				array('1')
				);
	}
	public function testRun_echo_compare_8() {
		$this->assertEquals(
				Runtime::runSource('echo "1" == "01";'),
				array('1')
				);
	}
	public function testRun_echo_compare_9() {
		$this->assertEquals(
				Runtime::runSource('echo "10" == "1e1";'),
				array('1')
				);
	}
	public function testRun_echo_compare_10() {
		$this->assertEquals(
				Runtime::runSource('echo 100 == "1e2";'),
				array('1')
				);
	}
	public function testRun_echo_compare_11() {
		$this->assertEquals(
				Runtime::runSource('$foo = 4; echo $foo != $foo*2;'),
				array('1')
				);
	}
	public function testRun_echo_compare_12() {
		$this->assertEquals(
				Runtime::runSource('echo $foo <= $foo*2;'),
				array('1')
				);
	}
	public function testRun_echo_compare_13() {
		$this->assertEquals(
				Runtime::runSource('echo $foo*4 >= $foo*2;'),
				array('1')
				);
	}
	public function testRun_echo_compare_14() {
		$this->assertEquals(
				Runtime::runSource('echo 5 !== (string)5;'),
				array('1')
				);
	}

	public function testRun_echo_compare_false() {
		$this->assertEquals(
				Runtime::runSource('echo ( 5 === (string)5 ) === false;'),
				array('1')
				);
	}
	public function testRun_echo_compare_true() {
		$this->assertEquals(
				Runtime::runSource('echo (100 == "1e2") === true;'),
				array('1')
				);
	}
	public function testRun_echo_compare_false_true() {
		$this->assertEquals(
				Runtime::runSource('echo (false === true) == false;'),
				array('1')
				);
	}
	public function testRun_echo_compare_true_true() {
		$this->assertEquals(
				Runtime::runSource('echo true === true === true;'),
				array('1')
				);
	}

	public function testRun_echo_assignment_1() {
		$this->assertEquals(
				Runtime::runSource('echo $foo = 1;'),
				array('1')
				);
	}
	public function testRun_echo_assignment_2() {
		$this->assertEquals(
				Runtime::runSource('echo $foo = 1 + 2;'),
				array('3')
				);
	}
	public function testRun_echo_assignment_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo += 2;'),
				array('3')
				);
	}
	public function testRun_echo_assignment_4() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; echo $foo += 2 + 3;'),
				array('6')
				);
	}
	public function testRun_echo_assignment_5() {
		$this->assertEquals(
				Runtime::runSource('echo $bar = $foo = 1, $foo, $bar;'),
				array('1', '1', '1')
				);
	}
	public function testRun_echo_assignment_6() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; $bar=2; $foo+=$bar; echo $foo,$bar;'),
				array('3', '2')
				);
	}

	public function testRun_echo_ternary_1() {
		$this->assertEquals(
				Runtime::runSource('echo true?"true":"false";'),
				array('true')
				);
	}
	public function testRun_echo_ternary_2() {
		$this->assertEquals(
				Runtime::runSource('echo false?"true":"false";'),
				array('false')
				);
	}
	public function testRun_echo_ternary_3() {
		$this->assertEquals(
				Runtime::runSource('echo true?"true":false?"t":"f";'),
				array('t')
				);
	}
	public function testRun_echo_ternary_4() {
		$this->assertEquals(
				Runtime::runSource('echo false?"true":false?"t":"f";'),
				array('f')
				);
	}
	public function testRun_echo_ternary_5() {
		$this->assertEquals(
				Runtime::runSource('echo true?true?"true":false:false?"t":"f";'),
				array('t')
				);
	}
	public function testRun_echo_ternary_6() {
		$this->assertEquals(
				Runtime::runSource('echo true?true?false:false:false?"t":"f";'),
				array('f')
				);
	}
	public function testRun_echo_ternary_7() {
		$this->assertEquals(
				Runtime::runSource('echo true?true?"true":false:"false";'),
				array('true')
				);
	}
	public function testRun_echo_ternary_8() {
		$this->assertEquals(
				Runtime::runSource('echo false?true?false:false:"false";'),
				array('false')
				);
	}
	public function testRun_echo_ternary_9() {
		$this->assertEquals(
				Runtime::runSource('echo (true?"true":"false");'),
				array('true')
				);
	}
	public function testRun_echo_ternary_10() {
		$this->assertEquals(
				Runtime::runSource('echo (false?"true":"false");'),
				array('false')
				);
	}
	public function testRun_echo_ternary_11() {
		$this->assertEquals(
				Runtime::runSource('echo ((true)?("tr"."ue"):("fa"."lse"));'),
				array('true')
				);
	}
	public function testRun_echo_ternary_12() {
		$this->assertEquals(
				Runtime::runSource('echo ((false)?("tr"."ue"):("fa"."lse"));'),
				array('false')
				);
	}
	public function testRun_echo_ternary_variable_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=true; echo $foo?"true":"false";'),
				array('true')
				);
	}
	public function testRun_echo_ternary_variable_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=false; echo $foo?"true":"false";'),
				array('false')
				);
	}
	public function testRun_echo_ternary_variable_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=true?"true1":"false0"; echo $foo;'),
				array('true1')
				);
	}
	public function testRun_echo_ternary_variable_4() {
		$this->assertEquals(
				Runtime::runSource('$foo=false?"true1":"false0"; echo $foo;'),
				array('false0')
				);
	}
	public function testRun_echo_ternary_variable_5() {
		$this->assertEquals(
				Runtime::runSource('$foo=true?"true"."1":"false"."0"; echo $foo;'),
				array('true1')
				);
	}
	public function testRun_echo_ternary_variable_6() {
		$this->assertEquals(
				Runtime::runSource('$foo=false?"true"."1":"false"."0"; echo $foo;'),
				array('false0')
				);
	}
	public function testRun_echo_ternary_math_1() {
		$this->assertEquals(
				Runtime::runSource('echo 1-1?"true":"false";'),
				array('false')
				);
	}
	public function testRun_echo_ternary_math_2() {
		$this->assertEquals(
				Runtime::runSource('echo 1+1?"true":"false";'),
				array('true')
				);
	}

	public function testRun_echo_if_simple_1() {
		$this->assertEquals(
				Runtime::runSource('if(true) echo "hello";'),
				array('hello')
				);
	}
	public function testRun_echo_if_simple_2() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) echo "hello";'),
				array()
				);
	}
	public function testRun_echo_if_simple_3() {
		$this->assertEquals(
				Runtime::runSource('if(1+1) echo "hello";'),
				array('hello')
				);
	}
	public function testRun_echo_if_simple_4() {
		$this->assertEquals(
				Runtime::runSource('if(1+1) echo "hello"; echo "world";'),
				array('hello', 'world')
				);
	}
	public function testRun_echo_if_simple_5() {
		$this->assertEquals(
				Runtime::runSource('if(1-1) echo "hello"; echo "world";'),
				array('world')
				);
	}
	public function testRun_echo_if_simple_6() {
		$this->assertEquals(
				Runtime::runSource('if( (1+1)*10 ) echo "true";'),
				array('true')
				);
	}
	public function testRun_echo_if_simple_7() {
		$this->assertEquals(
				Runtime::runSource('
if ( 5+5 ) echo "hello";
if ( 5-5 ) echo " === FALSE === ";
if ( (5+5)/4 ) echo "world";
if ( -5+5 ) echo " === FALSE === ";
if ( ((74+4)*(4+6)+88)*4 ) echo "!!!";'),
				array('hello', 'world', '!!!')
				);
	}
	public function testRun_echo_if_block_1() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) { echo "true"; } echo "BAR";'),
				array('true', 'BAR')
				);
	}
	public function testRun_echo_if_block_2() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) { echo "true";} echo "BAR";'),
				array('BAR')
				);
	}
	public function testRun_echo_if_block_3() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) { echo "true"; echo "BAR"; }'),
				array('true', 'BAR')
				);
	}
	public function testRun_echo_if_block_4() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) { echo "true"; echo "BAR"; }'),
				array()
				);
	}
	public function testRun_echo_if_else_simple_1() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) echo "true"; else echo "false";'),
				array('true')
				);
	}
	public function testRun_echo_if_else_simple_2() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) echo "true"; else echo "false";'),
				array('false')
				);
	}
	public function testRun_echo_if_else_simple_3() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) echo "true"; else echo "false"; echo " always!";'),
				array('true', ' always!')
				);
	}
	public function testRun_echo_if_else_simple_4() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) echo "true"; else echo "false"; echo " always!";'),
				array('false', ' always!')
				);
	}
	public function testRun_echo_if_else_block_1() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; }'),
				array('true1', 'true2')
				);
	}
	public function testRun_echo_if_else_block_2() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; }'),
				array('false1', 'false2')
				);
	}
	public function testRun_echo_if_else_block_3() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; } echo " always!";'),
				array('true1', 'true2', ' always!')
				);
	}
	public function testRun_echo_if_else_block_4() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) { echo "true1"; echo "true2";} else { echo "false1"; echo "false2"; } echo " always!";'),
				array('false1', 'false2', ' always!')
				);
	}
	public function testRun_echo_if_else_block_5() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) echo "true1"; else { echo "false1"; echo "false2"; } echo " always!";'),
				array('true1', ' always!')
				);
	}
	public function testRun_echo_if_else_block_6() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) echo "true1"; else { echo "false1"; echo "false2"; } echo " always!";'),
				array('false1', 'false2', ' always!')
				);
	}
	public function testRun_echo_if_else_block_7() {
		$this->assertEquals(
				Runtime::runSource('if ( true ) { echo "true1"; echo "true2";} else echo "false1"; echo " always!";'),
				array('true1', 'true2', ' always!')
				);
	}
	public function testRun_echo_if_else_block_8() {
		$this->assertEquals(
				Runtime::runSource('if ( false ) { echo "true1"; echo "true2";} else echo "false1"; echo " always!";'),
				array('false1', ' always!')
				);
	}
	public function testRun_echo_if_variable_1() {
		$this->assertEquals(
				Runtime::runSource('$foo = 5; if ( $foo > 4 ) echo "true"; else echo "false";'),
				array('true')
				);
	}
	public function testRun_echo_if_variable_2() {
		$this->assertEquals(
				Runtime::runSource('if( $foo*2 > 4*3 ) echo "true"; else echo "false";'),
				array('false')
				);
	}
	public function testRun_echo_if_variable_3() {
		$this->assertEquals(
				Runtime::runSource('if( $foo === 5 ) echo "true"; else echo "false";'),
				array('true')
				);
	}
	public function testRun_echo_if_variable_4() {
		$this->assertEquals(
				Runtime::runSource('if( $foo++ ==  5 ) echo "true"; else echo "false";'),
				array('true')
				);
	}
	public function testRun_echo_if_variable_5() {
		$this->assertEquals(
				Runtime::runSource('if( ++$foo ==  7 ) echo "true"; else echo "false";'),
				array('true')
				);
	}
	public function testRun_echo_if_variable_6() {
		$this->assertEquals(
				Runtime::runSource('$foo = true;$bar = false;
if ( $foo ) echo $foo;
if ( $bar ) echo $bar;
if ( $foo + $bar ) echo "\$foo + \$bar";'),
				array('1', '$foo + $bar')
				);
	}
	public function testRun_echo_if_double_1() {
		$this->assertEquals(
				Runtime::runSource('if( true ) if( true ) echo "true"; else echo "false";'),
				array('true')
				);
	}
	public function testRun_echo_if_double_2() {
		$this->assertEquals(
				Runtime::runSource('if( true ) if( true ) {echo "true1"; echo "true2";} else echo "falsefalse";'),
				array('true1', 'true2')
				);
	}
	public function testRun_echo_if_double_3() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else echo "falsefalse";'),
				array()
				);
	}
	public function testRun_echo_if_double_4() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else echo "falsefalse"; else echo "false";'),
				array('false')
				);
	}
	public function testRun_echo_if_double_5() {
		$this->assertEquals(
				Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else echo "falsefalse"; }'),
				array()
				);
	}
	public function testRun_echo_if_double_6() {
		$this->assertEquals(
				Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } }'),
				array()
				);
	}
	public function testRun_echo_if_double_7() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; }'),
				array()
				);
	}
	public function testRun_echo_if_double_8() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else echo "false";'),
				array('false')
				);
	}
	public function testRun_echo_if_double_9() {
		$this->assertEquals(
				Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } } else echo "false";'),
				array('false')
				);
	}
	public function testRun_echo_if_double_10() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { echo "false"; }'),
				array('false')
				);
	}
	public function testRun_echo_if_double_11() {
		$this->assertEquals(
				Runtime::runSource('if( false ) { if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } } else { echo "false"; }'),
				array('false')
				);
	}
	public function testRun_echo_if_double_12() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else if(true) echo "false";'),
				array('false')
				);
	}
	public function testRun_echo_if_double_13() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else if(true) { echo "false"; }'),
				array('false')
				);
	}
	public function testRun_echo_if_double_14() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) echo "false second TRUE"; }'),
				array('false second TRUE')
				);
	}
	public function testRun_echo_if_double_15() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) { echo "false second TRUE"; } }'),
				array('false second TRUE')
				);
	}
	public function testRun_echo_if_double_16() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) echo "false second TRUE"; else echo "false second FALSE"; }'),
				array('false second TRUE')
				);
	}
	public function testRun_echo_if_double_17() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) {echo "false second TRUE";} else echo "false second FALSE"; }'),
				array('false second TRUE')
				);
	}
	public function testRun_echo_if_double_18() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) echo "false second TRUE"; else {echo "false second FALSE";} }'),
				array('false second TRUE')
				);
	}
	public function testRun_echo_if_double_19() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(TRUE) {echo "false second TRUE";} else {echo "false second FALSE";} }'),
				array('false second TRUE')
				);
	}
	public function testRun_echo_if_double_20() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) echo "false second TRUE"; else echo "false second FALSE"; }'),
				array('false second FALSE')
				);
	}
	public function testRun_echo_if_double_21() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) {echo "false second TRUE";} else echo "false second FALSE"; }'),
				array('false second FALSE')
				);
	}
	public function testRun_echo_if_double_22() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) echo "false second TRUE"; else {echo "false second FALSE";} }'),
				array('false second FALSE')
				);
	}
	public function testRun_echo_if_double_23() {
		$this->assertEquals(
				Runtime::runSource('if( false ) if( true ) {echo "true1"; echo "true2";} else { echo "falsefalse"; } else { if(FALSE) {echo "false second TRUE";} else {echo "false second FALSE";} }'),
				array('false second FALSE')
				);
	}
	public function testRun_echo_elseif_1() {
		$this->assertEquals(
				Runtime::runSource('if( true ) echo "one"; elseif( true ) echo "two"; else echo "three";'),
				array('one')
				);
	}
	public function testRun_echo_elseif_2() {
		$this->assertEquals(
				Runtime::runSource('if( false ) echo "one"; elseif( true ) echo "two"; else echo "three";'),
				array('two')
				);
	}
	public function testRun_echo_elseif_3() {
		$this->assertEquals(
				Runtime::runSource('if( false ) echo "one"; elseif( false ) echo "two"; else echo "three";'),
				array('three')
				);
	}
	public function testRun_echo_elseif_4() {
		$this->assertEquals(
				Runtime::runSource('if( true ) { echo "*"; echo "one"; } elseif( true ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }'),
				array('*', 'one')
				);
	}
	public function testRun_echo_elseif_5() {
		$this->assertEquals(
				Runtime::runSource('if( false ) { echo "*"; echo "one"; } elseif( true ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }'),
				array('*', 'two')
				);
	}
	public function testRun_echo_elseif_6() {
		$this->assertEquals(
				Runtime::runSource('if( false ) { echo "*"; echo "one"; } elseif( false ) { echo "*"; echo "two"; } else { echo "*"; echo "three"; }'),
				array('*', 'three')
				);
	}
	public function testRun_echo_elseif_variable_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; if($foo++) echo "one $foo"; elseif($foo++) echo "two $foo"; else echo "three $foo";'),
				array('one 2')
				);
	}
	public function testRun_echo_elseif_variable_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=0; if($foo++) echo "one $foo"; elseif($foo++) echo "two $foo"; else echo "three $foo";'),
				array('two 2')
				);
	}
	public function testRun_echo_elseif_variable_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=0; if($foo) echo "one $foo"; elseif($foo++) echo "two $foo"; else echo "three $foo";'),
				array('three 1')
				);
	}
	public function testRun_echo_elseif_variable_4() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; if($foo++) {echo "one $foo";} elseif($foo++) {echo "two $foo";} else {echo "three $foo";}'),
				array('one 2')
				);
	}
	public function testRun_echo_elseif_variable_5() {
		$this->assertEquals(
				Runtime::runSource('$foo=0; if($foo++) {echo "one $foo";} elseif($foo++) {echo "two $foo";} else {echo "three $foo";}'),
				array('two 2')
				);
	}
	public function testRun_echo_elseif_variable_6() {
		$this->assertEquals(
				Runtime::runSource('$foo=0; if($foo) {echo "one $foo";} elseif($foo++) {echo "two $foo";} else {echo "three $foo";}'),
				array('three 1')
				);
	}

	public function testRun_echo_array_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(5); echo $foo[0];'),
				array('5')
				);
	}
	public function testRun_echo_array_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(5,); echo $foo[0];'),
				array('5')
				);
	}
	public function testRun_echo_array_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=array( 5, 6, 7 ); echo $foo[0],$foo[1],$foo[2];'),
				array('5', '6', '7')
				);
	}
	public function testRun_echo_array_math_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(3+2); echo $foo[0];'),
				array('5')
				);
	}
	public function testRun_echo_array_math_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(3+2,6,7); echo $foo[0],$foo[1],$foo[2];'),
				array('5', '6', '7')
				);
	}
	public function testRun_echo_array_variable_1() {
		$this->assertEquals(
				Runtime::runSource('$bar="BAR"; $foo=array( 5, 6, $bar ); echo $foo[0],$foo[1],$foo[2];'),
				array('5', '6', 'BAR')
				);
	}
	public function testRun_echo_array_variable_2() {
		$this->assertEquals(
				Runtime::runSource('$foo="FOO"; $foo=array( 5, 6, $foo ); echo $foo[0],$foo[1],$foo[2];'),
				array('5', '6', 'FOO')
				);
	}
	public function testRun_echo_array_variable_3() {
		$this->assertEquals(
				Runtime::runSource('$foo[$bar="BAR"]="FOO"; echo $foo[$bar], $bar;'),
				array('FOO', 'BAR')
				);
	}
	public function testRun_echo_array_variable_math_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; $foo=array($foo++,$foo,++$foo); echo $foo[0],$foo[1],$foo[2];'),
				array('1', '2', '3')
				);
	}
	public function testRun_echo_array_variable_math_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=1; $foo=array($foo+1,$foo+2,$foo+3); echo $foo[0],$foo[1],$foo[2];'),
				array('2', '3', '4')
				);
	}
	public function testRun_echo_array_variable_increment_1() {
		$this->assertEquals(
				Runtime::runSource('$foo[5]=10; $foo[5]++; echo $foo[5];'),
				array('11')
				);
	}
	public function testRun_echo_array_variable_increment_2() {
		$this->assertEquals(
				Runtime::runSource('$foo[5]=10; echo ++$foo[5];'),
				array('11')
				);
	}
	public function testRun_echo_array_variable_increment_3() {
		$this->assertEquals(
				Runtime::runSource('$foo[5]=10; echo $foo[5]++,$foo[5],++$foo[5];'),
				array('10', '11', '12')
				);
	}
	public function testRun_echo_array_variable_assignment_1() {
		$this->assertEquals(
				Runtime::runSource('$foo[5]=10; $foo[5]+=20; echo $foo[5];'),
				array('30')
				);
	}
	public function testRun_echo_array_set_by_index_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[5]=5; $foo[6]=6; $foo[7]=7; echo $foo[5],$foo[6],$foo[7];'),
				array('5', '6', '7')
				);
	}
	public function testRun_echo_array_set_by_index_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]=5; echo $foo[0];'),
				array('5')
				);
	}
	public function testRun_echo_array_set_by_index_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]=5; $foo[]=6; $foo[]=7; echo $foo[0],$foo[1],$foo[2];'),
				array('5', '6', '7')
				);
	}
	public function testRun_echo_array_set_by_index_4() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[50]=5; $foo[]=6; $foo[]=7; echo $foo[50],$foo[51],$foo[52];'),
				array('5', '6', '7')
				);
	}
	public function testRun_echo_array_10() {
		$this->assertEquals(
				Runtime::runSource('$foo=(array)"this is string"; echo $foo[0];'),
				array('this is string')
				);
	}

	public function testRun_echo_array_double_arrow_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(4=>50); echo $foo[4];'),
				array('50')
				);
	}
	public function testRun_echo_array_double_arrow_2() {
		$this->assertEquals(
				Runtime::runSource('$bar="BAR"; $foo=array( $bar => $bar ); echo $foo[$bar];'),
				array('BAR')
				);
	}
	public function testRun_echo_array_double_arrow_3() {
		$this->assertEquals(
				Runtime::runSource('$bar="BAR"; $foo=array( 5 => 50, $bar => $bar, "string" => "STRING" ); echo $foo[$bar], $foo[5], $foo["string"];'),
				array('BAR', 50, 'STRING')
				);
	}

	public function testRun_echo_empty_array_push_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]+=5; $foo[]-=6; $foo[].=7; echo $foo[0],$foo[1],$foo[2];'),
				array('5', '-6', '7')
				);
	}
	public function testRun_echo_empty_array_push_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]+="5"; $foo[]-="6"; $foo[].="7"; echo $foo[0],$foo[1],$foo[2];'),
				array('5', '-6', '7')
				);
	}
	public function testRun_echo_empty_array_push_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]+="v"; $foo[]-="b"; $foo[].="n"; echo $foo[0],$foo[1],$foo[2];'),
				array('0', '0', 'n')
				);
	}
	public function testRun_echo_empty_array_push_4() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]*=5; $foo[]/=6; $foo[]%=7; $foo[]&=8; echo $foo[0],$foo[1],$foo[2],$foo[3];'),
				array('0', '0', '0', '0')
				);
	}
	public function testRun_echo_empty_array_push_5() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]*="v"; $foo[]+="b"; $foo[]-="n"; $foo[]&="m"; echo $foo[0],$foo[1],$foo[2],$foo[3];'),
				array(0, 0, 0, 0)
				);
	}
	public function testRun_echo_empty_array_push_6() {
		$this->assertEquals(
				Runtime::runSource('$foo=array(); $foo[]|=5; $foo[]^=6; $foo[]<<=7; $foo[]>>=8; echo $foo[0],$foo[1],$foo[2],$foo[3];'),
				array('5', '6', '0', '0')
				);
	}
	public function testRun_echo_array_encapsed_1() {
		$this->assertEquals(
				Runtime::runSource('$foo=(array)5; echo "*$foo[0]*";'),
				array('*5*')
				);
	}
	public function testRun_echo_array_encapsed_2() {
		$this->assertEquals(
				Runtime::runSource('$foo=(array)5; echo "*{$foo[0]}*";'),
				array('*5*')
				);
	}
	public function testRun_echo_array_encapsed_3() {
		$this->assertEquals(
				Runtime::runSource('$foo=(array)5; echo $foo[0], "*".$foo[0]."*", "*$foo[0]*", "*{$foo[0]}*";'),
				array('5', '*5*', '*5*', '*5*')
				);
	}
	public function testRun_echo_array_encapsed_4() {
		$this->assertEquals(
				Runtime::runSource('$bar = "BAR"; $foo=array( 5 => 5, $bar => $bar, "string" => "string" ); echo "*$foo[5]*"; echo "*$foo[$bar]*"; echo "*{$foo["string"]}*";'),
				array('*5*', '*BAR*', '*string*')
				);
	}
	public function testRun_echo_array_encapsed_5() {
		$this->assertEquals(
				Runtime::runSource('$foo["DDD"]="ddd"; echo "-={$foo["DDD"]}=-";'),
				array('-=ddd=-')
				);
	}
	public function testRun_echo_array_encapsed_6() {
		$this->assertEquals(
				Runtime::runSource('$foo[$bar="BAR"]="FOO"; echo "-={$foo[$bar]}=-", $bar;'),
				array('-=FOO=-', 'BAR')
				);
	}

}
