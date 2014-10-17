<?php


if (!class_exists('gs_base_handler',0)) {
	date_default_timezone_set('GMT');
	/*
	$fname='/../../../../../config.lib.php';
	require_once(dirname(__FILE__).'/../../../../../config.lib.php');
	*/
	$dname=dirname(__FILE__);
	while($dname) {
		$fname=$dname.DIRECTORY_SEPARATOR.'libs/config.lib.php';
		if (file_exists($fname)) {
			require($fname);
			break;
		}
		$dname=realpath($dname.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
	}
	$cfg=gs_config::get_instance();
	$init=new gs_init('auto');
	$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
}


if(isset($_FILES['Filedata'])) {
	/*
	$params=gs_session::load('MultiPowUpload_params');
	*/

	$params=$_REQUEST;

	/*
	$params=array_merge($params,$_REQUEST);
	md($params,1);
	md($_REQUEST,1);
	md($_GET,1);
	md($_POST,1);
	die();
	*/
	$rs_name=$params['recordset'];
	$f_name=$params['foreign_field_name'];
	$f_hash_name=$f_name.'_hash';
	

	$f=new $rs_name;
	$f=$f->new_record();

	$f->$f_hash_name=$params['hash'];
	$f->$f_name=$params['rid'];

	$values=$_FILES['Filedata'];


	$ret=array(
			'File_data'=>file_get_contents($values['tmp_name']),
			'File_filename'=>$values['name'],
			'File_mimetype'=>$values['type'],
			'File_size'=>$values['size'],
			'File_width'=>max($_REQUEST['thumbnailWidth'],$_REQUEST['imageWidth']),
			'File_height'=>max($_REQUEST['thumbnailHeight'],$_REQUEST['imageHeight']),
		 );
	
	$ff=$f->File->new_record($ret);

	$f->commit();

	$tpl=gs_tpl::get_instance();
	//$tpl->template_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.'../../../templates';
	$tpl->template_dir=cfg('lib_dir').DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'MultiPowUpload'.DIRECTORY_SEPARATOR.'templates';
	$tpl->assign('i',$f);
	echo $tpl->fetch('li_image.html');
	die();
	
	echo $f->src1('admin');
}
