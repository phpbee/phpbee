
function ImageWebSearch_search(obj) {
	/*var inp=$(obj).closest('form').find('input[type=text]').get(0);*/
	var inp=$(obj).closest('form').find('input.ImageWebSearch_string').get(0);
	ImageWebSearch_search_completed=function(imageSearch) {
		if (!imageSearch.results || imageSearch.results.length==0) return false;
		var d=$(obj).siblings('div').first();
		d.html('');
	
		var results = imageSearch.results;
		for (var i = 0; i < results.length; i++) {
			var result = results[i];
			//var img=new Image();
			var img=$('<div class="ImageWebSearchGalleryItem"><a href="#" onClick="return false;" class="preview"><img src="'+result.tbUrl+'"></a></div>').get(0);
			img.url=$(obj).attr('rel');
			img.imgurl=result.url;
			img.onclick=ImageWebSearch_click;
			d.append(img);
		}
	}
    jQuery.ajax('/widgets/ImageWebSearch/search', {
				context: this,
				dataType: "json" ,
				data: {search: inp.value},
				success : ImageWebSearch_search_completed
			});
    return false;

}

function ImageWebSearch_click() {
	$(this).addClass("ImageWebSearchCheckedElement");
    jQuery.ajax(this.url, {
				context: this,
				dataType: "html" ,
				data: {src: this.imgurl},
				success : function(ret) {
					$("#gallery_"+hash).append(ret);
				}
			}
               );
    return false;

}


	$(document).ready(function() {
        $(".ImageWebSearch_string").each(function() {
            this.value=$(this).closest('form').find('input[type=text]').get(0).value;
        });


		$("#gallery_"+hash+" div.ImageWebSearchGalleryItem").live("click",function(){
			var li=$(this);
			var inp=$("input",li);
			inp.attr('checked',!(inp.attr('checked')));
			li.removeClass('ImageWebSearchCheckedElement');
			if(inp.attr('checked')) li.addClass('ImageWebSearchCheckedElement');
		});


		$(".ImageWebSearchGalleryGroup").dblclick(function(){
			$("div.ImageWebSearchGalleryItem",this).click();
			return false;
		});
		$(".ImageWebSearchGallery").dblclick(function(){
			$("div.ImageWebSearchGalleryItem",this).click();
			return false;
		});

		$("#ImageWebSearch_checked_items_submit").click(function(){
			var form=$(this).closest('form');
			//var action=$(':radio[name=checked_items_action]',form).filter(":checked").val();
			var gspgid="widgets/ImageWebSearch/action";
			$('input[name=gspgid_form]',form).val(gspgid);
			form.get(0).submit();
		});
	});
