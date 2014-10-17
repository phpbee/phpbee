<?php
class wz_link {}

class wz_link_list extends wz_link {
	function lMany2One($rec,$l121=false) { 

		$name_rs_images=$rec->Recordset->first().'_'.strtolower($rec->name);


		$module=$rec->Recordset->first()->Module->first();


		$wz_rs=new wz_recordsets();

		$rec_images=$wz_rs->find_records(array('name'=>$name_rs_images))->first(true);

		$rec_images->fill_values(array(
			'name'=>$name_rs_images,
			'title'=>$rec->verbose_name,
			'Module_id'=>$module->get_id(),
			'no_urlkey'=>1,
		));

		$rec_images->Fields->new_record(array(
				'name'=>'name',
				'verbose_name'=>'name',
				'type'=>'fString',
				));

		$rec_images->Links->new_record(array(
				 'name'=>'Parent',
				 'type'=>'lOne2One',
				 'classname'=>$rec->Recordset->first()->name,
				 'linkname'=>'',
				 'fkey_on_delete'=>'CASCADE',
				 'fkey_on_update'=>'CASCADE',
				 'fkey_name'=>$rec->Recordset->first()->name.'.'.$rec->name,
				 ));
		$arr=array('type'=>'handler','gspgid_value'=>'/admin/form/'.$name_rs_images);
		$url=$module->urls->find_records($arr)->first(true);
		$url->fill_values($arr);
		$url->Handlers->new_record(array(
			'cnt'=>1,
			'handler_keyname'=>0,
			'handler_value'=>'gs_base_handler.post:{name:admin_form.html:classname:'.$name_rs_images.':form_class:g_forms_table:return:gs_record}',
			));
		$url->Handlers->new_record(array(
			'cnt'=>2,
			'handler_keyname'=>0,
			'handler_value'=>'gs_base_handler.redirect',
			));
		//$url->Handlers->delete();

		$rec->fill_values(array(
			'classname'=>$name_rs_images,
			'linkname'=>$l121? '' : 'Parent',
			));

		$rec_images->commit();
	}
	function lOne2One($rec) { 
		return $this->lMany2One($rec,true); 
	}
	function lMany2Many($rec) { return; }
}

class wz_link_images extends wz_link {

	function lMany2Many($rec) { return; }

	function lOne2One($rec) { 
		return $this->lMany2One($rec,true); 
	}

	function lMany2One($rec,$l121=false) { 


		//$name_rs_images=$rec->Recordset->first().'_images';
		$name_rs_images=$rec->Recordset->first().'_'.strtolower($rec->name);
		$name_rs_images_files=$name_rs_images.'_files';


		$module=$rec->Recordset->first()->Module->first();
		
		$wz_rs=new wz_recordsets();

		$rec_images=$wz_rs->find_records(array('name'=>$name_rs_images))->first(true);
		$rec_images_files=$wz_rs->find_records(array('name'=>$name_rs_images_files))->first(true);

		$rec_images->fill_values(array(
			'name'=>$name_rs_images,
			'title'=>$rec->verbose_name,
			'Module_id'=>$module->get_id(),
			'extends'=>'tw_images',
			'no_urlkey'=>1,
		));

		$rec_images_files->fill_values(array(
			'name'=>$name_rs_images_files,
			'title'=>'Image',
			'Module_id'=>$module->get_id(),
			'extends'=>'tw_file_images',
			'no_urlkey'=>1,
			));

		$rec_images->Fields->new_record(array(
				'name'=>'file_uid',
				'type'=>'fString',
				'options'=>64,
				'make_index'=>true,
				));

		$rec_images->Fields->new_record(array(
				'name'=>'group_key',
				'type'=>'fString',
				'options'=>32,
				'make_index'=>true,
				));

		$rec_images->Links->new_record(array(
				 'name'=>'Parent',
				 'type'=>'lOne2One',
				 'classname'=>$rec->Recordset->first()->name,
				 'linkname'=>'',
				 'extra_options'=>'mode=link',
				 'fkey_on_delete'=>'CASCADE',
				 'fkey_on_update'=>'CASCADE',
				 'fkey_name'=>$rec->Recordset->first()->name.'.'.$rec->name,
				 ));
		$rec_images->Links->new_record(array(
				 'name'=>'File',
				 'type'=>'lOne2One',
				 'classname'=>$name_rs_images_files,
				 'verbose_name'=>'File',
				 'widget'=>'include_form',
				 'extra_options'=>'hidden=false',
				 'fkey_on_delete'=>'NONE',
				 'fkey_on_update'=>'NONE',
				 ));
		$rec_images_files->Links->new_record(array(
				 'name'=>'Parent',
				 'type'=>'lOne2One',
				 'classname'=>$name_rs_images,
				 'fkey_on_delete'=>'CASCADE',
				 'fkey_on_update'=>'CASCADE',
				 'fkey_name'=>"$name_rs_images.File",
				 ));

		$arr=array('type'=>'handler','gspgid_value'=>'/admin/form/'.$name_rs_images);
		$url=$module->urls->find_records($arr)->first(true);
		$url->fill_values($arr);
		$url->Handlers->new_record(array(
			'cnt'=>1,
			'handler_keyname'=>0,
			'handler_value'=>'gs_base_handler.post:{name:admin_form.html:classname:'.$name_rs_images.':form_class:g_forms_table:return:gs_record}',
			));
		$url->Handlers->new_record(array(
			'cnt'=>2,
			'handler_keyname'=>0,
			'handler_value'=>'gs_base_handler.redirect',
			));
		//$url->Handlers->delete();

		$rec->fill_values(array(
			'classname'=>$name_rs_images,
			'linkname'=>$l121? '' : 'Parent',
			));

		$rec_images->commit();
		$rec_images_files->commit();
	}


}

?>
