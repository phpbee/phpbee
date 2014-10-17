<?php

require_once 'compat.php';
require_once 'gs_recordset_short.php';

class LibsTests

{

    public static function suite()

    {

        $suite = new PHPUnit_Framework_TestSuite('Libs');

        // добавляем тест в набор

        $suite->addTestSuite('Compat_Test'); 
        $suite->addTestSuite('gs_recordset_short_Test'); 

        return $suite; 

    }

}
