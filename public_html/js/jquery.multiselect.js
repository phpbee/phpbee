(function($) {

	var options = {};
	
	$.fn.gs_multiselect = function(params) {
		options = $.extend({}, $.fn.gs_multiselect.defaults, params);

		return this.each(function() {
			var list_options={};
			var list_names={};
			var idx={};
			var rw=Math.min(400,this.offsetWidth);
			var rh=this.offsetHeight;
			var t=document.createElement('table');
			var to_sel=this.parentNode.replaceChild(t,this);
			var from_sel=$('<select multiple="multiple"></select>').get(0);
			var r=t.insertRow(0);
			if (options.buttons) {
				var c1=r.insertCell(0);
				var c2=r.insertCell(1);
				var c3=r.insertCell(2);
				var btl_all=$('<input type="button" value="+">').get(0);
				//var tl_filter=$('<input type="text">').get(0);
				//c1.appendChild(tl_filter);
				c1.appendChild(btl_all);
				var btr_all=btl_all.cloneNode(true);
				c3.appendChild(btr_all);
				btl_all.onclick=function () {
					$('option',from_sel).each(function() {this.selected=!this.selected;});
					$('option',to_sel).attr('selected',false);
				}
				btr_all.onclick=function () {
					$('option',from_sel).attr('selected',false);
					$('option',to_sel).each(function() {this.selected=!this.selected;});
				}
				r=t.insertRow(1);
			}
			var fc=r.insertCell(0);
			var mc=r.insertCell(1);
			var lc=r.insertCell(2);
			var o_lc=lc;
			
			lc.appendChild(to_sel);
			$(fc).append($(from_sel));
			//var from_sel=fc.firstChild;
			var sel_name=to_sel.getAttribute('name');
			to_sel.setAttribute('name','');
			$(from_sel).html($(to_sel).html());
			$(this).html('');
			from_sel.style.height=to_sel.style.height=rh+'px';
			from_sel.style.width=to_sel.style.width=rw+'px';
			var fb=$('<input type="button" value="&raquo;">');
			var bb=$('<input type="button" value="&laquo;">');
			mc.appendChild(fb.get(0));
			mc.appendChild(document.createElement('br'));
			mc.appendChild(bb.get(0));
			var list=from_sel.cloneNode(true);
			//if (list.innerHTML!='') $(list).filter('option').each(function (i) {
			if (list.innerHTML!='') $('option',list).each(function (i) {
				list_options[this.value]=i;
				list_names[i]=this.innerHTML;
			})
			// start of functions
			fbutton=function (){
				var ol=[];
				var os=from_sel.getElementsByTagName('option');
				for (var key=0;key<os.length;key++) {
					if (os[key].selected) {
						ol.push(os[key]);
						idx[os[key].value]=list_options[os[key].value];
					}
				}
				for (var key=0;key<ol.length;key++) {
					var n=from_sel.removeChild(ol[key]);
				}
				
				to_sel.innerHTML='';
				$('input',o_lc).remove();
				for (key in list_options) {
					var add=false;
					for (i in idx) {
						if (list_options[key]==idx[i]) {
							add=true;
							break;
						}
					}
					if (add) {
						to_sel.appendChild($('<option value="'+key+'">'+list_names[list_options[key]]+'</option>').get(0));
						o_lc.appendChild($('<input type="hidden" name="'+sel_name+'" value="'+key+'">').get(0));
					}
				}
			}
		
	
			bbutton=function (){
				var ol=[];
				var os=to_sel.getElementsByTagName('option');
				for (var key=os.length-1;key>=0;key--) {
					if (os[key].selected) {
						ol.push(os[key]);
						to_sel.removeChild(os[key]);
					}
				}
				idx={};
				var os=to_sel.getElementsByTagName('option');
				for (var key=0;key<os.length;key++) {
					idx[os[key].value]=list_options[os[key].value];
				}
				
				from_sel.innerHTML='';
				$('input',o_lc).remove();
				for (key in list_options) {
					var add=true;
					for (i in idx) {
						if (list_options[key]==idx[i]) {
							add=false;
							break;
						}
					}
					if (add) {
						from_sel.appendChild($('<option value="'+key+'">'+list_names[list_options[key]]+'</option>').get(0));
					} else {
						o_lc.appendChild($('<input type="hidden" name="'+sel_name+'" value="'+key+'">').get(0));
					}
				}
			}
			// end of functions
			fb.click(fbutton);
			bb.click(bbutton);
			$(from_sel).dblclick(fbutton);
			$(to_sel).dblclick(bbutton);
			fbutton();
		});
	};
	
	
	
	$.fn.gs_multiselect.defaults = {
		
	};
	
})(jQuery);

