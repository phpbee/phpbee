	if ( typeof left_heightWid == 'undefined' ) {
		function left_heightWid() {};
	}

	var params = {  
		BGColor: "#FFFFFF",
		wmode: "opaque"
	};
	
	var attributes = {  
		id: "MultiPowUpload",  
		name: "MultiPowUpload"
	};



	function MultiPowUpload_Start() {
		$(".MultiPowUpload_instructions").show();
		var so=swfobject.embedSWF("/libs/widgets/MultiPowUpload/ElementITMultiPowUpload.swf", "MultiPowUpload_holder", "900", "450", "10.0.0", "/widgets/MultiPowUpload/Extra/expressInstall.swf", flashvars, params, attributes);

	}



	var path_to_file = "";
	
	function MultiPowUpload_onThumbnailUploadComplete(li, response)
	{
			addThumbnail(response);
	}
	
	function addThumbnail(response)
	{
		$("#gallery_"+hash).append(response);
	}



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


		/*
		$(".MultiPowUploadGalleryGroup").dblclick(function(){
			$(".MultiPowUploadGalleryItem",this).click();
			return false;
		});
		$(".MultiPowUploadGallery").dblclick(function(){
			$(".MultiPowUploadGalleryItem",this).click();
			return false;
		});
		*/

		$("#checked_items_submit").click(function(){
			var form=$(this).closest('form');
			//var action=$(':radio[name=checked_items_action]',form).filter(":checked").val();
			var gspgid="widgets/MultiPowUpload/action";
			$('input[name=gspgid_form]',form).val(gspgid);
			form.get(0).submit();
		});

	});

this.imagePreview = function(){	
	/* CONFIG */
		
		xOffset = 10;
		yOffset = 30;
		
		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result
		
	/* END CONFIG */
	$("a.preview").hover(function(e){
		this.t = this.title;
		this.title = "";	
		var c = (this.t != "") ? "<br/>" + this.t : "";
		$("body").append("<p id='preview'><img src='"+ this.href +"' alt='Image preview' />"+ c +"</p>");								 
		$("#preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");						
    },
	function(){
		this.title = this.t;	
		$("#preview").remove();
    });	
	$("a.preview").mousemove(function(e){
		$("#preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});			
};


// starting the script on page load
$(document).ready(function(){
	imagePreview();
});



