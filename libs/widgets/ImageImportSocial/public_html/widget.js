function ImageImportSocial_click() {
	$(this).toggleClass("ImageImportSocialCheckedElement");
	var $widget=$(this).closest('.ImageImportSocial');
	var $uplbtn=$('#ImageImportSocialGalleryBtnUpload',$widget);
	$uplbtn.hide();
	if ($('.ImageImportSocialCheckedElement',$widget).size()>0) $uplbtn.show();
}
function ImageImportSocial_upload(obj) {
	var $widget=$(obj).closest('.ImageImportSocial');
	$('.ImageImportSocialCheckedElement',$widget).each(function() {
		log(this);
		jQuery.ajax(this.url, {
					context: this,
					dataType: "html" ,
					data: {src: this.imgurl},
					success : function(ret) {
						$("#gallery_"+hash).append(ret);
						$(this).click();
					}
				}
		   );
	});
	return false;
}
function ImageImportSocial_search_completed(results,$widget) {
		var $d=$('.ImageImportSocialGallery',$widget);
		if (!results || results.length==0) return false;
		$d.html('');
	
		for (var i = 0; i < results.length; i++) {
			var result = results[i];
			log(result);
			if (result.src) {
				var img=$('<div class="ImageImportSocialGalleryItem"><a href="#" onClick="return false;" class="preview"><img src="'+result.src+'"></a></div>').get(0);

				img.url=$d.attr('rel');
				img.imgurl=result.src_big;
				if (typeof result.src_xxbig != "undefined" ) img.imgurl=result.src_xxbig;
				img.onclick=ImageImportSocial_click;
				log(img);
				$d.append(img);
			}
		}
		return true;
	}

var vk_offset=0;
var vk_count=16;

function ImageImportSocial_search(obj,step) {
		 var $widget=$(obj).closest('.ImageImportSocial');


		 $('#ImageImportSocialGalleryBtnPrev,#ImageImportSocialGalleryBtnNext,#ImageImportSocialGalleryBtnUpload',$widget).hide();
		
		 if (step!=0) vk_offset+=vk_count*(Math.abs(step)/step);
		 if (vk_offset<0) $vk_offset=0;

		 VK.Api.call("photos.getAll", {count:vk_count,offset:vk_offset}, function(data) { 
			 if (ImageImportSocial_search_completed(data.response,$widget)) {
				 $('#ImageImportSocialGalleryBtnNext,#ImageImportSocialGalleryBtnPrev',$widget).show().prop('disabled',false);
				 if (vk_offset==0) $('#ImageImportSocialGalleryBtnPrev',$widget).prop('disabled',true);
			 }
		 });
		 return false;
}



$(document).ready(function() {
        $(".ImageImportSocial_string").each(function() {
            this.value=$(this).closest('form').find('input[type=text]').get(0).value;
        });


		$("#gallery_"+hash+" .ImageImportSocialGalleryItem").live("click",function(){
			var li=$(this);
			var inp=$("input",li);
			inp.attr('checked',!(inp.attr('checked')));
			li.removeClass('ImageImportSocialCheckedElement');
			if(inp.attr('checked')) li.addClass('ImageImportSocialCheckedElement');
		});



		$("#ImageImportSocial_checked_items_submit").click(function(){
			var form=$(this).closest('form');
			//var action=$(':radio[name=checked_items_action]',form).filter(":checked").val();
			var gspgid="widgets/ImageImportSocial/action";
			$('input[name=gspgid_form]',form).val(gspgid);
			form.get(0).submit();
		});

});

function ImageImportSocial($obj) {
    VK.Auth.login(function(response){ 
		 if (!response.session) {
			 alert('Необходимо войти с помощью ВКонтакте.');
			 return false;
		 }
		 $(".ImageImportSocialGallery",$obj).each(function() { ImageImportSocial_search(this,0);});
	});
	return false;
}






