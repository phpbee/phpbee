function addToQueryString(url, key, value) {
    var query = url.indexOf('?');
    var anchor = url.indexOf('#');
    if (query == url.length - 1) {
        // Strip any ? on the end of the URL
        url = url.substring(0, query);
        query = -1;
    }
    return (anchor > 0 ? url.substring(0, anchor) : url)
         + (query > 0 ? "&" + key + "=" + value : "?" + key + "=" + value)
         + (anchor > 0 ? url.substring(anchor) : "");
}

(function($) {
	$.fn.modalform= function(params) {
		return this.each(function() {
			$(this).click(function() {
				var $target=$(params.target);
				var href=$(this).attr('href');
				var $frame=$('iframe',$target);
				href=addToQueryString(href,'modal','1');
				$target.modal('show');
				$target.hide();
				$frame.get(0).height=0;
				$frame.attr('src',href);
				$frame.load(function(){
					var frm= this.contentWindow;
					$target.fadeIn(100);
					this.height=frm.document.body.scrollHeight+'px';
					this.width='100%';
					this.style.border=0;
					if (!frm.keep_modal) {
						$target.modal('hide');
					}
				});
				return false;
			});
		});
	}
})(jQuery);

