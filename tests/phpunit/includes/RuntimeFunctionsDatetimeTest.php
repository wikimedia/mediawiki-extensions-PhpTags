<?php
namespace PhpTags;

class RuntimeFunctionsDatetime extends \PHPUnit_Framework_TestCase {

	public function testRun_checkdate_1() {
		$this->assertEquals(
				Runtime::runSource('echo checkdate(12, 31, 2000) === true ? "true" : "false";'),
				array('true')
				);
	}
	public function testRun_checkdate_2() {
		$this->assertEquals(
				Runtime::runSource('echo checkdate(2, 29, 2001) === true ? "true" : "false";'),
				array('false')
				);
	}

	public function testRun_date_parse_from_format_1() {
		$this->assertEquals(
				Runtime::runSource('$date = "6.1.2009 13:00+01:00"; echo print_r(date_parse_from_format("j.n.Y H:iP", $date), true);'),
				array('Array
(
    [year] => 2009
    [month] => 1
    [day] => 6
    [hour] => 13
    [minute] => 0
    [second] => 0
    [fraction] => '.'
    [warning_count] => 0
    [warnings] => Array
        (
        )

    [error_count] => 0
    [errors] => Array
        (
        )

    [is_localtime] => 1
    [zone_type] => 1
    [zone] => -60
    [is_dst] => '.'
)
'					)
				);
	}

	public function testRun_date_parse_1() {
		$return = Runtime::runSource('echo print_r( date_parse("2006-12-12 10:00:00.5"), true );');
		$this->assertRegExp(
				'/Array\s+\(\s+\[year\] => 2006\s+\[month\] => 12\s+\[day\] => 12\s+\[hour\] => 10\s+\[minute\] => 0\s+\[second\] => 0\s+\[fraction\] => 0.5\s+\[warning_count\] => 0\s+\[warnings\] => Array\s+\(\s*\)\s+\[error_count\] => 0\s+\[errors\] => Array\s+\(\s*\)\s+\[is_localtime\] =>\s+\)\s+/',
				$return[0]
				);
	}

	public function testRun_date_1() {
		$return = Runtime::runSource('echo date("l");');
		$this->assertRegExp(
				'/\S+/',
				$return[0]
				);
	}
	public function testRun_date_2() {
		$this->assertEquals(
				Runtime::runSource('echo "July 1, 2000 is on a " . date("l", mktime(0, 0, 0, 7, 1, 2000));'),
				array('July 1, 2000 is on a Saturday')
				);
	}

	public function testRun_getdate_1() {
		$return = Runtime::runSource('$today = getdate(); echo print_r($today,true);');
		$this->assertRegExp(
				'/Array\s+\(\s+\[seconds\]\s+=>\s+\S+\s+\[minutes\]\s+=>\s+\S+\s+\[hours\]\s+=>\s+\S+\s+\[mday\]\s+=>\s+\S+\s+\[wday\]\s+=>\s+\S+\s+\[mon\]\s+=>\s+\S+\s+\[year\]\s+=>\s+\S+\s+\[yday\]\s+=>\s+\S+\s+\[weekday\]\s+=>\s+\S+\s+\[month\]\s+=>\s+\S+\s+\[0]\s+=>\s+\S+\s+\)\s+/',
				$return[0]
				);
	}

	public function testRun_idate_1() {
		$this->assertEquals(
				Runtime::runSource('$timestamp = strtotime("1st January 2004"); echo idate("y", $timestamp);'),
				array('4')
				);
	}

}
