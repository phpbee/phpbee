function toggle(target,def) {
	$(target+' .toggle_head').click(function() {
		window['last_opened_'+target]=$(this).attr('id');
		$(this).next().toggle();
		return false;
	}).next().hide();
	$(def).show();
	$("#"+window['last_opened_'+target]).next().show();
	//$($(target).get(0).getAttribute('last_opened')).next().show();
}


function toggle_complex(target,items) {
	$(target+' .toggle_head').click(function() {
		var id=$(this).attr('id');
		var t_id='toggle_last_opened_'+$(target).attr('id');
		$(target+' .toggle_head').css('font-weight','normal');
		if(window[t_id]!=id) {
			$(items+"#toggle_"+window[t_id]).toggle();
		}
		$(items+' #toggle_'+id).toggle();
		if ($(items+' #toggle_'+id).is(":visible")) $(target+' #'+id).css('font-weight','bold');
		window[t_id]=id;
		return false;
	});
	$(items+' .toggle_item').hide();
	return $(target);
}
