(function($) {

	var options = {};

	
	$.fn.sel_filter = function(params) {
		var options = $.extend({}, $.fn.sel_filter.defaults, params);

		return this.each(function() {
			if (this.tagName.toUpperCase()!='SELECT') return;
			if(options.min_options>this.options.length) return;



			var sel=this;

			sel.params=options;

			sel.construct=function() {
				//alert('f construct '+this.name+' '+md(this.params));
				$(this).sel_filter(this.params);
			}
			sel.destruct=function(newobj) {
				//alert('f destruct '+this.name+' '+md(this.params));
				newobj.params=this.params;
				$(this.tl_filter.button_add).remove();
				$(this.tl_filter).remove();
			}


			var sw=sel.offsetWidth;
			if (sw==0) sw=parseInt($(sel).css("width").replace('px',''));
			var tl_filter=$('<input type="text" class="sel_filter">').get(0);
			sel.tl_filter=tl_filter;
			tl_filter['sel']=sel;
			tl_filter.onkeyup=function (e) { sel_filter(this,e); return false;}
			tl_filter['osel']=sel.cloneNode(true);
			tl_filter.onkeydown=function (e) {
											this.norefresh=false;	
											if (e.keyCode==13) {
													this.norefresh=true;	
													sel.focus();
													return false;
											}

											var si=sel.selectedIndex;
											if (e.keyCode==38) {
													this.norefresh=true;	
													if (si>0) sel.options[si-1].selected=true;
													return false;
											}
											if (e.keyCode==40) {
													this.norefresh=true;	
													if (si<sel.options.length-1) sel.selectedIndex=si+1;
													return false;
											}

										}
			sel.parentNode.insertBefore(tl_filter,sel);

			var tw=tl_filter.offsetWidth;
			if (options.crop && sw>0) {
					sw=sw-tw;
					sel.style.width=sw+'px';
			}

			tl_filter.onfocus=function() {
						sw = sw ? sw : sel.offsetWidth;	
						tw = tw ? tw : tl_filter.offsetWidth;	
						this.style.width=(tw+options.slide_width)+'px'; 
						if(sw>options.slide_width+tw) sel.style.width=(sw-options.slide_width)+'px' 
						}
			tl_filter.onblur=function() { if (this.escaped) {
											this.value=''; 
											sel_filter(this,null); 
										}
									      this.style.width=tw+'px'; 
										  sel.style.width=sw+'px' 
										}
			hide_add_button=function(sel,inp) {
				if (typeof inp.button_add != 'undefined') {
					inp.button_add.remove();
					inp.button_add=false;
				}
			}
			show_add_button=function(sel,inp) {
				var value=inp.value;
				if (!inp.button_add) {
					inp.button_add=$('<input type="button" class="ParentListWithInsertButton button" value="Insert">');
					inp.button_add.attr('url',sel.getAttribute('ParentListWithInsertURL'));

					$(sel).after(inp.button_add);
					inp.button_add.click(function(){
						var url=this.getAttribute('url');
						var val=this.getAttribute('val');
						jQuery.ajax(url,{
									context: this,
									dataType: "json" ,
									data: {'value': val, 'interact_request': sel.interact_request},
									success : function(ret) {
										var new_option=new Option(ret.value,ret.id,false,true);
										sel.appendChild(new_option);
										inp['osel'].appendChild(new_option);
										sel_filter(inp,null); 
									}
								}
								);

					});
				}
				inp.button_add.attr('val',value);
			}

			sel_filter=function (inp,e){
					var sel=inp.sel;
					if (inp.norefresh) return false;
					if (typeof window.event !='undefined') e=window.event;
					inp["escaped"]=false;
					if (e!=null) {
							if (e.keyCode==27) {
									inp["escaped"]=true;
									inp.blur();
							}
					}
				os=inp['osel'].getElementsByTagName('option');
				sel.innerHTML='';
				for (var key=0;key<os.length;key++) {
					if (!inp.value || os[key].value.toLowerCase().indexOf(inp.value.toLowerCase())>=0 || os[key].innerHTML.toLowerCase().indexOf(inp.value.toLowerCase())>=0) {
						sel.appendChild(os[key].cloneNode(true));
					}
				}
				if (inp.value && sel.getAttribute('ParentListWithInsertURL')) {
					if (sel.options.length==0) {
						show_add_button(sel,inp);
					} else {
						hide_add_button(sel,inp);
					}
				}

				$(sel).change();

			}
		});
	};
	
	$.fn.sel_filter.defaults = {
			slide_width : 50,
			min_options: 10 ,
			crop: true
	};
	
})(jQuery);

