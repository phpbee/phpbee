$(document).ready(function(){
	$(".action_modal_close, .modal_close_action").live('click',function(){
		return modal_close(this);
	});
	/*
	$(".register_show").click(function(){
			return modal_popup("#modal_popup_registration");
	});
	*/
	$(".modal_popup_btn").click(function(event){
			var obj=$(event.target).closest('.modal_popup_btn');
			return modal_popup("#"+obj.attr('popup_id'));
	});

});
function modal_close(obj) {
		$(obj).closest(".modal_container").css("display","none");
		return false;
}
function modal_popup(uid) {
	var t=$(uid);//.clone();
	var c=$(".modal_container");
	c.html('');
	c.append(t);
	t.show();
	c.show();
	/*
	$(".modal_container").css("display","block");
	$(".modal_container div.modal").css("display","none");
	$(".modal_container "+uid).css("display","block");
	*/
	return false;
}

