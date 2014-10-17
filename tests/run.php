<?php


require_once 'libs_suite.php';


class AllTests

{

	    public static function suite()

	        {

			        $suite = new PHPUnit_Framework_TestSuite('PhpBee');

				        // добавляем набор тестов

					        $suite->addTest(LibsTests::suite());

						        return $suite; 

							    }

}
