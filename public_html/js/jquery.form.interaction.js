(function($) {

	var options = {
		'start_field':false,
		'start_pattern':false
		};

	var events = {
		'.fRadio':'change',	
		'.fCheckbox':'change',	
		'.fSelect':'change',	
		'.lOne2One':'change',	
		};
	
	$.fn.interaction= function(params) {
		options = $.extend({}, $.fn.interaction.defaults, params);

		error= function(obj,d) {
			if(d.debug) alert(d.message);
		}

		show = function(obj,d) {
			obj.show();
		}
		hide= function(obj,d) {
			obj.hide();
		}
		set_value= function(obj,d,request) {
			var new_el=$('[name="'+d.field+'"]',obj);
			new_el.val(d.value);
			new_el.get(0).interact_request=request;
		}

		replace_element = function (obj,d,request) {

			var new_el=$(d.html);
			var old_el=$('[name="'+d.field+'"]',obj);
			for (ev in events) {
				$(new_el).filter(ev).bind(events[ev],postform);
			}
			if (typeof old_el.get(0).construct=="function") new_el.get(0).construct=old_el.get(0).construct;
			if (typeof old_el.get(0).destruct=="function") old_el.get(0).destruct(new_el.get(0));
			new_el.get(0).interact_request=request;

			old_el.replaceWith(new_el);

			if (typeof new_el.get(0).construct=="function") new_el.get(0).construct();
		}
		replace_options = function (obj,d,request) {
			var new_el=$(d.html);
			var old_el=$('[name="'+d.field+'"]',obj);
			old_el.html(new_el.html());

			old_el.trigger("liszt:updated");

		}
				
		postform=function() {
			clearTimeout($(this).data('timeout'));
			var e = $(this).data('timeout', setTimeout(function() 
				{
				var form = e.closest('form');
				var data = {} ;
				data['gsform_interact'] = e.attr('name');
				data['gspgid_form']=$('[name=gspgid_form]',form).val();
				$('.fInteract',form).each(function() {
					if ($(this).hasClass('fCheckbox')) {
						data[this.name]=this.checked ? 1 : 0;
					} else if ($(this).hasClass('fRadio')) {
						if (this.checked) data[this.name]=this.value;
					} else {
						data[this.name]=this.value;
					}
				});

				var request=data;
				answer = function(data) {
					for ( k in data) {
						var d=data[k];
						var obj=$('[name="'+d.field+'"]');
						var i_box=obj.closest('.interact_box');
						if (i_box.size()) obj=i_box;
						self[d.action](obj,d,request);
					}
				}

				$.ajax({
					url:document.location.href,
					data: data,
					type: 'POST',
					dataType: 'JSON',
					success : answer
				});
			},1));


		}

		return this.each(function() {

			$('.fInteract',this).each(function() {
				if (this.interact==1) {
				} else {
					for (ev in events) {
						$(this).filter(ev).bind(events[ev],postform);
					}
					this.interact=1;
				}

			});
	
			if (options.start_field) {
				$('[name="'+options.start_field+'"]',this).each(postform);
			}
			if (options.start_pattern) {
				$(options.start_pattern,this).each(postform);
			}

		});

	};


	
	
	
	$.fn.interaction.defaults = {
		
	};
	
})(jQuery);
