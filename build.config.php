<?php
	$this->install_key='12345'; // run site.com/install.php?install_key=12345
	$this->admin_ip_access='127.0.0.1, 192.168.1.102';
	$this->admin_user_name='admin';
	$this->admin_password='admin';

	DEFINE ('DEBUG',0);
	DEFINE ('UDP_DEBUG',0);
	
	$this->admin_ip_access=isset($this->admin_ip_access) ? array_map('trim',explode(',',$this->admin_ip_access)) : array();

	$this->gs_connectors=array (
			'mysql'=>array( 
				'db_type'=>'mysql',
				'db_hostname'=>'',
				'db_port'=>'3306',
				'db_username'=>'phpbee_build',
				'db_password'=>'',
				'db_database'=>'phpbee_build',
				'codepage'=>'utf8',
				),
			'wizard'=>array( 
				'db_type'=>'sqlite',
				'db_file'=>$this->var_dir.'wizard.db',
				),
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

	if (function_exists('posix_getuid') && posix_getuid()==fileowner(__FILE__)) {
		$this->created_files_perm=0600;
		$this->created_dirs_perm=0700;
	}

	$this->modules_priority='wizard,packagemanager';

	date_default_timezone_set('Europe/Moscow');
	setlocale(LC_ALL,'ru_RU.UTF-8');
	setlocale(LC_NUMERIC,'en_US.UTF-8');
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

	$this->widget_MultiPowUpload_license='put your key here';
	$this->widget_MultiPowUpload_watermark='/libs/widgets/MultiPowUpload/watermark.png';


?>
