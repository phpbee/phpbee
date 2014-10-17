<?php 
class Compat_Test extends PHPUnit_Framework_TestCase {
	function testDecimalPoint() {

		$lconv=localeconv();
		$this->assertEquals('.',$lconv['decimal_point']);
		$this->assertEquals('3.1416',sprintf("%.4f",pi()));
	}
}
