<?php
	$this->install_key='12345'; // run site.com/install.php?install_key=12345
	//$this->admin_ip_access='127.0.0.1, 192.168.1.102';
	$this->admin_user_name='admin';
	$this->admin_password='admin';

	DEFINE ('DEBUG',0);
	DEFINE ('UDP_DEBUG',0);
	
	$this->admin_ip_access=isset($this->admin_ip_access) ? array_map('trim',explode(',',$this->admin_ip_access)) : array();

	$this->gs_connectors=array (
			'mysql'=>array( 
				'db_type'=>'mysql',
				'db_hostname'=>'127.0.0.1',
				'db_port'=>'3306',
				'db_database'=>'test',
				'db_username'=>'root',
				'db_password'=>'',
				'codepage'=>'utf8',
				),
#			'wizard'=>array( 
#				'db_type'=>'sqlite',
#				'db_file'=>$this->var_dir.'wizard.db',
#				),
			'file_public'=>array( 
				'db_type'=>'file',
				'db_root'=>$this->document_root.'files',
				'www_root'=>'/files',
				),
			'handlers_cache'=>array( 
				'db_type'=>'file',
				'db_root'=>$this->var_dir.'handlers_cache/',
				),
			);
    if (!isset($this->gs_connectors['wizard'])) $this->gs_connectors['wizard']=reset($this->gs_connectors);

	if (function_exists('posix_getuid') && posix_getuid()==fileowner(__FILE__)) {
		$this->created_files_perm=0600;
		$this->created_dirs_perm=0700;
	}

	$this->modules_priority='wizard,packagemanager';

	date_default_timezone_set('Europe/Moscow');
	setlocale(LC_ALL,'ru_RU.UTF-8');
	$this->mail_smtp_host='127.0.0.1';
	$this->mail_smtp_port='25';
	$this->mail_smtp_username='';
	$this->mail_smtp_password='';
	$this->mail_smtp_auth=0;
	$this->mail_from='info';
	$this->mail_type='smtp';

	$this->languages=NULL;
	//$this->languages=array('ru'=>'RUS','en'=>'ENG');
	//$this->languages='tw_languages';
	//$this->multilang_default_language_id=2; //default record in languages recordset
	//$this->multilang_default_language='en'; //use prior of handler_multilang_base::setlocale_handler

	$this->widget_MultiPowUpload_upload_thubnail=true;
	$this->widget_MultiPowUpload_thubnail_size=1600;
	$this->widget_MultiPowUpload_license='put your key here';
	//$this->widget_MultiPowUpload_watermark='/i/watermark.png';
	//$this->watermark_filename=$this->document_root.'i/watermark.png';
	//$this->widget_MultiPowUpload_thubnail_quality=90;

    $this->lib_modules_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR;
    $this->tpl_data_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR;
    $this->tpl_var_dir=$this->var_dir.'templates_c'.DIRECTORY_SEPARATOR.basename($this->tpl_data_dir);


	setlocale(LC_NUMERIC,'POSIX');

