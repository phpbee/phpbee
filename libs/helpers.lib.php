<?php
class helper_clone {
	function show($label,$txt,$errors=array(),$field=array()) {
		return sprintf('<div class="helper_clone">
		%s</div>
		<input type="button" value="+" class="button_helper_clone">

		<script type="text/javascript">
		$(".button_helper_clone").click(function() {
			var fs=$($(this).siblings(".helper_clone").get(-1));
			var newfs=fs.clone();
			var id=parseInt($("input,textarea,select",newfs).get(0).name.match(/%s:(-?\d+):/)[1])-1;
			$("input,textarea,select",newfs).each(function() {
				this.name=this.name.replace(/%s:-?\d+:/,"%s:"+id+":");
			});
			fs.after(newfs);
		});
		</script>
		',$txt,$label,$label,$label);
	}
}
class helper_fieldset {
	function show($label,$txt,$errors=array(),$field=array()) {
		return sprintf('<fieldset><legend>%s</legend>%s</fieldset>',$label,$txt);
	}
}
class helper_label{
	function show($label,$txt,$errors=array(),$field=array()) {
		//$e = $errors ? '<span class="error">'.gs_dict::get('FORM_VALIDATE_ERROR').': '.implode(',',gs_dict::get($errors)).'</span>' : '';
		$e = $errors ? '<span class="error">*</span>' : '';
		return sprintf('<label class="interact_box">%s&nbsp;%s%s</label>',$label,$e,$txt);
	}
}
class helper_label_br{
	function show($label,$txt,$errors=array(),$field=array()) {
		//$e = $errors ? '<span class="error">*</span>' : '';
		$e = $errors ? '<div class="error">'.implode(',',gs_dict::get($errors)).'</div>' : '';
		$req= ($field['validate']=='dummyValid'  ) ? '' : '<i>*</i>';
		return sprintf('<span class="interact_box"><label>%s%s</label>%s%s</span>',$label,$req,$txt,$e);
	}
}
class helper_dl {
	function show($label,$txt,$errors=array(),$field=array()) {
		return sprintf('<dl class="interact_box">%s</dl>',$txt);
	}
}
class helper_dt {
	function show($label,$txt,$errors=array(),$field=array()) {
		$e = $errors ? '<div class="error">Error: '.implode(',',$errors).'</div>' : '';
		return sprintf('<dt class="interact_box">%s</dt><dd>%s%s</dd>',$label,$txt,$e);
	}
}


class helper_table {
	function show($label,$txt,$errors=array(),$field=array()) {
		return sprintf('<table class="helper_table">%s</table>',$txt);
	}
}


class helper_empty {
	function show($label,$txt,$errors=array(),$field=array()) {
		$e = $errors ? '<span class="error">*</span>' : '';
		return sprintf('%s%s',$txt,$e);
	}
}
class helper_divbox{
	function show($label,$txt,$errors=array(),$field=array()) {
		$e = $errors ? '<span class="error">*</span>' : '';
		return sprintf('<div class="divbox interact_box"><label>%s%s<br>%s</label></div>',$label,$e,$txt);
	}
}

class helper_inline {
	function show($label,$txt,$errors=array(),$field=array()) {
		$e = $errors ? '<span class="error">*</span>' : '';
		return sprintf('<span class="inline interact_box"><label>%s%s%s</label></span>',$label,$txt,$e);
	}
}

class helper_table_admin {
	function show($label,$txt,$errors=array(),$field=array()) {
		return sprintf('<table class="helper_table"><tr><td class="helper_table_submit_l"><input type="submit" value="%s">
		</td><td class="helper_table_submit_r"><input type="submit" value="%s"></td></tr>%s<tr><td class="helper_table_submit_l">
		<input type="submit" value="%s"></td><td class="helper_table_submit_r"><input type="submit" value="%s"></td></tr></table>',
		gs_dict::get('SUBMIT_FORM'),
		gs_dict::get('SUBMIT_FORM'),
		$txt,
		gs_dict::get('SUBMIT_FORM'),
		gs_dict::get('SUBMIT_FORM'));
	}
}
class helper_table_submit{
    function show($label,$txt,$errors=array(),$field=array()) {
         return sprintf('<table class="helper_table"> %s <tr> <td class="helper_table_submit_r" colspan="2"><input type="submit" value="%s"></td></tr> </table>',
			 $txt, gs_dict::get('SUBMIT_FORM'));
    }
}
class helper_submit{
    function show($label,$txt,$errors=array(),$field=array()) {
         return sprintf('%s <input type="submit" value="%s">',
			 $txt, gs_dict::get('SUBMIT_FORM'));
    }
}


class helper_tr {
	function show($label,$txt,$errors=array(),$field=array()) {
		$e = $errors ? '<div class="error">'.gs_dict::get('FORM_VALIDATE_ERROR').': '.implode(',',gs_dict::get($errors)).'</div>' : '';
		return sprintf('<tr class="helper_tr interact_box"><td class="helper_tr_title">%s</td><td class="helper_tr_field">%s%s</td></tr>',$label,$txt,$e);
	}
}
?>
