<?php
namespace Foxway;

class RuntimeFunctionsPcreTest extends \PHPUnit_Framework_TestCase {

	public function testRun_function_pcre_preg_filter_1() {
		$this->assertEquals(
				Runtime::runSource('
$subject = array("1", "a", "2", "b", "3", "A", "B", "4");
$pattern = array("/\d/", "/[a-z]/", "/[1a]/");
$replace = array("A:$0", "B:$0", "C:$0");

echo print_r(preg_filter($pattern, $replace, $subject), true);'),
				array('Array
(
    [0] => A:C:1
    [1] => B:C:a
    [2] => A:2
    [3] => B:b
    [4] => A:3
    [7] => A:4
)
'					)
				);
	}
	public function testRun_function_pcre_preg_replace_1() {
		$this->assertEquals(
				Runtime::runSource('echo print_r(preg_replace($pattern, $replace, $subject), true);'),
				array('Array
(
    [0] => A:C:1
    [1] => B:C:a
    [2] => A:2
    [3] => B:b
    [4] => A:3
    [5] => A
    [6] => B
    [7] => A:4
)
'					)
				);
	}
	public function testRun_function_pcre_preg_replace_2() {
		$this->assertEquals(
				Runtime::runSource('$string = "April 15, 2003";
$pattern = "/(\w+) (\d+), (\d+)/i";
$replacement = \'${1}1,$3\';
echo preg_replace($pattern, $replacement, $string);'),
				array('April1,2003')
				);
	}
	public function testRun_function_pcre_preg_replace_3() {
		$this->assertEquals(
				Runtime::runSource('$string = "The quick brown fox jumped over the lazy dog.";
$patterns = array();
$patterns[0] = "/quick/";
$patterns[1] = "/brown/";
$patterns[2] = "/fox/";
$replacements = array();
$replacements[2] = "bear";
$replacements[1] = "black";
$replacements[0] = "slow";
echo preg_replace($patterns, $replacements, $string);'),
				array('The bear black slow jumped over the lazy dog.')
				);
	}
	public function testRun_function_pcre_preg_grep_1() {
		$this->assertEquals(
				Runtime::runSource('$array = array("foo", 5, 4.78, "bar", "7.89", "1.234foo"); echo print_r(preg_grep("/^(\d+)?\.\d+$/", $array), true);'),
				array('Array
(
    [2] => 4.78
    [4] => 7.89
)
'					)
				);
	}
	public function testRun_function_pcre_preg_last_error_1() {
		$this->assertEquals(
				Runtime::runSource('preg_match("/(?:\D+|<\d+>)*[!?]/", "foobar foobar foobar");
if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
    print "Backtrack limit was exhausted!";
}'),
				array('Backtrack limit was exhausted!')
				);
	}
	public function testRun_function_pcre_preg_match_all_1() {
		$this->assertEquals(
				Runtime::runSource('preg_match_all("|<[^>]+>(.*)</[^>]+>|U",
    "<b>example: </b><div align=left>this is a test</div>",
    $out, PREG_PATTERN_ORDER);
echo $out[0][0] . ", " . $out[0][1];
echo $out[1][0] . ", " . $out[1][1];'),
				array('<b>example: </b>, <div align=left>this is a test</div>', 'example: , this is a test')
				);
	}
	public function testRun_function_pcre_preg_match_all_2() {
		$this->assertEquals(
				Runtime::runSource('preg_match_all("|<[^>]+>(.*)</[^>]+>|U",
    "<b>example: </b><div align=\"left\">this is a test</div>",
    $out, PREG_SET_ORDER);
echo $out[0][0] . ", " . $out[0][1];
echo $out[1][0] . ", " . $out[1][1];'),
				array('<b>example: </b>, example: ', '<div align="left">this is a test</div>, this is a test')
				);
	}
	public function testRun_function_pcre_preg_match_1() {
		$this->assertEquals(
				Runtime::runSource('// get host name from URL
preg_match("@^(?:http://)?([^/]+)@i",
    "http://www.php.net/index.html", $matches);
$host = $matches[1];

// get last two segments of host name
preg_match("/[^.]+\.[^.]+$/", $host, $matches);
echo "domain name is: {$matches[0]}";'),
				array('domain name is: php.net')
				);
	}
	public function testRun_function_pcre_preg_match_2() {
		$this->assertEquals(
				Runtime::runSource('$str = "foobar: 2008";
preg_match("/(?P<name>\w+): (?P<digit>\d+)/", $str, $matches);
echo print_r($matches, true);'),
				array('Array
(
    [0] => foobar: 2008
    [name] => foobar
    [1] => foobar
    [digit] => 2008
    [2] => 2008
)
'					)
				);
	}
	public function testRun_function_pcre_preg_quote_1() {
		$this->assertEquals(
				Runtime::runSource('$keywords = "$40 for a g3/400"; $keywords = preg_quote($keywords, "/"); echo $keywords;'),
				array('\$40 for a g3\/400')
				);
	}
	public function testRun_function_pcre_preg_split_1() {
		$this->assertEquals(
				Runtime::runSource('$keywords = preg_split("/[\s,]+/", "hypertext language, programming"); echo print_r($keywords,true);'),
				array('Array
(
    [0] => hypertext
    [1] => language
    [2] => programming
)
'					)
				);
	}
	public function testRun_function_pcre_preg_split_2() {
		$this->assertEquals(
				Runtime::runSource('$str = "string";
$chars = preg_split("//", $str, -1, PREG_SPLIT_NO_EMPTY);
echo print_r($chars,true);'),
				array('Array
(
    [0] => s
    [1] => t
    [2] => r
    [3] => i
    [4] => n
    [5] => g
)
'					)
				);
	}
	public function testRun_function_pcre_preg_split_3() {
		$this->assertEquals(
				Runtime::runSource('$str = "hypertext language programming";
$chars = preg_split("/ /", $str, -1, PREG_SPLIT_OFFSET_CAPTURE);
echo print_r($chars, true);'),
				array('Array
(
    [0] => Array
        (
            [0] => hypertext
            [1] => 0
        )

    [1] => Array
        (
            [0] => language
            [1] => 10
        )

    [2] => Array
        (
            [0] => programming
            [1] => 19
        )

)
'					)
				);
	}

}
