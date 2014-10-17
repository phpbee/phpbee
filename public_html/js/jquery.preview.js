(function($) {

	var options = {};
	var timeout_id=false;
	var frame=false;
	
	$.fn.preview= function(params) {
		options = $.extend({}, $.fn.preview.defaults, params);

		return this.each(function() {
			$(this).hover(function(e){
				var target=this;
				timeout_id=setTimeout(function() {
					target.t = target.title;
					target.title = "";	
					var c = (target.t != "") ? "<br/>" + target.t : "";
					frame=$("<p class='admin_img_preview_frame'><img src='"+ target.href +"' alt='preview' />"+ c +"</p>");								 
					$("body").append(frame);								 
					/*$(target).css("position","relative");*/
					frame.css("position","absolute")
						.css("top",(e.pageY + options.yOffset)+"px")
						.css("left",(e.pageX + options.xOffset) +"px")
						.fadeIn("fast");

				}, options.delay);
			    }, 
			    function(){
				this.title = this.t;	
				if(timeout_id) {
					clearTimeout(timeout_id);
					timeout_id=false;
				}
				if( frame) {
					frame.remove();
					frame=false;
				}
			    }
			);	
			$(this).mousemove(function(e){
				if (frame) {
					frame.css("top",(e.pageY + options.yOffset) + "px")
					.css("left",(e.pageX + options.xOffset) + "px");
				}
			});			
		});
	};
	
	$.fn.preview.defaults = {
		'xOffset' : 10,
		'yOffset' : 10,
		'delay'	: 1500
	};
	
})(jQuery);

