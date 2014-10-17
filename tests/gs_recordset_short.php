<?
require_once(dirname(__FILE__).'/../libs/config.lib.php');

$init=new gs_init();
$init->init(LOAD_CORE);


class tst_recordset1 extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'string'=> "fString",
		'int'=> "fInt",
		'float'=> "fFloat",
		'text'=> "fText",
		),$init_opts);
	}
}


class gs_recordset_short_Test extends PHPUnit_Framework_TestCase {

	
	function testDroptable() {
		$rs=new tst_recordset1;
		try {
			$rs->droptable();
		} catch (gs_dbd_exception $e) {
			$this->fail ('tst_recordset1 droptable failed'); 
		}
	}

	function testInstall() {

		$rs=new tst_recordset1;
		try {
			$rs->install();
		} catch (gs_dbd_exception $e) {
			$this->fail ('tst_recordset1 install failed'); 
		}
	}

	function testInsertSelect() {
		$rs=new tst_recordset1;
		$data=array(
			'string'=>md5(rand()),
			'int'=>rand(0,1000),
			'float'=>pi(),
			//'text'=>str_repeat(md5(rand()),1024),
			'text'=>str_repeat(md5(rand()),1),
		);
		$rec=$rs->new_record($data);
		$rs->commit();
		var_dump($rec->get_id());

		$nrec=record_by_id($rec->get_id(),'tst_recordset1');

		$this->assertNotNull($nrec);
		$this->assertEquals($rec->get_id(),$nrec->get_id());
		foreach ($data as $k=>$v) {
			if ($k=='float') {
				$this->assertEquals(round($nrec->$k,5),round($v,5));
				continue;
			}
			$this->assertEquals($nrec->$k,$v);
		}

	}




	function testImportExport() {
		$rs=new tst_recordset1;
		$rs->find_records(array());
		$x=$rs->xml_export();
		$xml=$x->asXML();

		$rs2=xml_import($xml);
		$x2=$rs2->xml_export();
		$xml2=$x2->asXML();

		$this->assertXmlStringEqualsXmlString($xml,$xml2);

		

	}

}





?>
