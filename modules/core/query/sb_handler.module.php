<?php


class sb_handler extends gs_handler {



	function subscribe($d) {
		$rec=$d['last'];
		$emfield=isset($this->params['field']) ? $this->params['field'] : 'email';
		if (!$rec->$emfield) {
			//return $this->unsubscribe($d);
			query_handler::stop($rec,$this->params['name']);
			return $rec;
		}

		query_handler::add($rec,$this->params['name'], $rec->$emfield);


		return $rec;
	}


	function email4record($d) {
		$rec=$d['last'];
		$q=$d['sb_query'];
		$cfg=$q->Config->first();
		$smarty=gs_tpl::get_instance();
		$smarty->assign('rec',$rec);

		$from=$smarty->fetch('string:'.$cfg->tpl_from);
		if(!$from) $from=$cfg('support_email_address');
		$subj=$smarty->fetch('string:'.$cfg->tpl_subject);
		$txt=$smarty->fetch('string:'.nl2br($cfg->tpl_text));
		$to=$q->email;
		//return('mail disabled');
        $ret=bee_mail($to,$subj,$txt,$from);
		return $ret;
	}
}
