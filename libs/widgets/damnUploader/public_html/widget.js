$(document).ready(function() {
	$("#gallery_"+hash+" .MultiPowUploadGalleryItem").live("click",function(){
		var li=$(this);
		var inp=$("input",li);
		inp.attr('checked',!(inp.attr('checked')));
		li.removeClass('MultiPowUploadCheckedElement');
		if(inp.attr('checked')) li.addClass('MultiPowUploadCheckedElement');


		$("#images_manager_"+hash).hide();
		if($("#gallery_"+hash+" .MultiPowUploadGalleryItem input:checked").size()) {
			$("#images_manager_"+hash).show();
		}
	});

	$("#checked_items_submit").click(function(){
		var form=$(this).closest('form');
		var gspgid="widgets/damnUploader/action";
		$('input[name=gspgid_form]',form).val(gspgid);
		form.get(0).submit();
	});

});



