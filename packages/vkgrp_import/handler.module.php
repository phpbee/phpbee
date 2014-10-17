<?php
class vkgrp_import_handler extends gs_base_handler {
	function get_token($ret) {
		$cfg=record_by_id($this->va(0),'vkgrp_import_cfg');
		$oauth = new oauth2_vk($cfg);
		$token=$oauth->token($this->data);
		$cfg->TOKEN=serialize($token);
		$cfg->commit();
		return $this->redirect_up();
	}

	function auth($cfg) {
		$oauth = new oauth2_vk($cfg);
		if (!$cfg->TOKEN) {
			$url=$oauth->authorize(current_url().'/get_token/'.$cfg->get_id());
			header('Location: '.$url);
			return null;
		}
		$oauth->token=unserialize($cfg->TOKEN);
		return $oauth;
	}

	function execute($ret) {
		$rs=new vkgrp_import_cfg();
		$options=array('disabled'=>0);
		if ($this->va(0)) $options['id']=$this->va(0);
		//$rec=record_by_id($this->data['gspgid_va'][0],'vkgrp_import_cfg');
		foreach ($rs->find_records($options) as $rec) {

			md($rec->get_values(),1);
			$oauth=$this->auth($rec);
			if (!$oauth) return;

			$group=reset(reset($oauth->exec('groups.getById',array('gid'=>$rec->group_id))));
			$groups=$oauth->exec('wall.get',array(
							'owner_id'=>'-'.$group->gid,
							'offset'=>0,
							'count'=>100,
							'filter'=>'owner',
							'extended'=>1,
							));
			$messages=$groups->response->wall;

			$wz_rset=record_by_id($rec->recordset_id,'wz_recordsets');
			$target_rs=new $wz_rset->name;
			$title_fieldname=record_by_id($rec->title_fieldname_id,'wz_recordset_fields')->name;
			$description_fieldname=record_by_id($rec->description_fieldname_id,'wz_recordset_fields')->name;
			$link_fieldname=record_by_id($rec->link_fieldname_id,'wz_recordset_fields')->name;
			$images_linkname=record_by_id($rec->images_linkname_id,'wz_recordset_links')->name;


			$count=0;
			foreach ($messages as $a) {
				if ($rec->max_count && $count>=$rec->max_count) break;
				if (!$a->id) continue;
				$link=$rec->group_id.'_'.$a->id;
				if ($link_fieldname) {
					$rs=new $wz_rset->name;
					$rs->find_records(array($link_fieldname=>$link));
					if ($rs->count()) {
						$count++;
						continue;
					}
				}
				$text=str_replace('<br>',"\n",$a->text);
				$text=preg_split('/[\r\n]+/',$text,2);
				$r=$target_rs->new_record();
				if($title_fieldname) $r->$title_fieldname=trim($text[0]);
				if($description_fieldname) $r->$description_fieldname=trim($text[1]);
				if($link_fieldname) $r->$link_fieldname=$link;
				if ($rec->rec_default_values) {
					$r->fill_values(string_to_params($rec->rec_default_values));
				}
				if ($images_linkname && $a->attachments) {
					foreach ($a->attachments as $enc) {
						if ($enc->type!='photo') continue;
						$enc=$enc->photo;
						$url=$enc->src_xbig;
						if (!$url) $url=$enc->src;
						if (!$url) continue;
						$img=$r->$images_linkname->new_record();
						$file=$img->File->new_record($img->File->fetch_image($url));
					}
				}
				if (
					($description_fieldname && $rec->only_with_body && !$r->$description_fieldname) 
					|| ($description_fieldname && $rec->min_body_length && strlen($r->$description_fieldname<$rec->min_body_length)) 
					|| ($images_linkname && $rec->only_with_images && !$r->$images_linkname->count()) 
					) {
					$r->delete();
					continue;
				}
				$count++;
				$r->commit();
			}
			$target_rs->commit();
			md($target_rs->get_values(),1);
		}
	}
}
