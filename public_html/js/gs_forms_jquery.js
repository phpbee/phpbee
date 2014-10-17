$('form input[type=button],input[type=submit],a').live('click',function(e) { 
	if (!this.getAttribute('_gsf_binded')) { 
		gsf_events.bind_myforms(this,e); 
		$(this).click();
	} else {
	    e.preventDefault(); 
	}
});
function Obj2JSON(obj) {
    if(typeof JSON=='undefined') {
        var arr=[];
        for (key in obj) {
            arr.push('"'+key+'":"'+obj[key]+'"');
        }
        str='{'+arr.join(',')+'}';
        return str;
        
    } else {
        return JSON.stringify(obj);
    }
}

function gs_forms() {
    this.show_id;
    this.show=function (selector,obj) {
        this.show_id=obj.show_id;
        var res=escape(Obj2JSON(obj));
        jQuery.ajax({
           url: "gs_forms/show",
           data: 'json='+res,
           success: function(msg) {
            $(selector).append(msg);
            $(selector+' .gsf_inline').each(function() {
                gsf_events.bind(this);   
            });
            
            if(obj._bind_selector) {
                $(obj._bind_selector).each(function() {
                    gsf_events.bind(this);   
                });
            }
           }
        });
    }
}

gsf_events={
    myforms_gsf_suffix:function(obj) {
            var gsf_suffix_action=obj.getAttribute('gsf_suffix_action');
            var gsf_suffix_params=obj.getAttribute('gsf_suffix_params');
	if (gsf_suffix_action=='redirect') {
		return window.location.href=gsf_suffix_params;
	} 

    },
    myforms_reload_selector:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
	    var cont=gsf_selector ? $(gsf_selector) : $(this).parents('.gsf_inline');
	    //alert(cont.size());
	    gsf_events.myforms_show_inline.bind(cont.get(0))();
    },
    myforms_popup_close:function() {
	    $(".ui-dialog-titlebar-close").click();
    },
    myforms_popup:function() {
	    //alert('myforms_popup:function');
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_reload_img=this.getAttribute('gsf_reload_img');
            var gsf_message=this.getAttribute('gsf_message');
            var gsf_template=this.getAttribute('gsf_template');
            var gsf_template_type=this.getAttribute('gsf_template_type');
            var gsf_classname=this.getAttribute('gsf_classname');


            obj={_id:gsf_id,_action:gsf_action,_message:gsf_message,_template:gsf_template,_template_type:gsf_template_type,_classname:gsf_classname,_selector:gsf_selector};


	    $('.gsf_ext_vars').each(function() { obj[this.name]=this.value; });
            jQuery.ajax({
                url: "gs_forms/myforms/",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
		    if (gsf_reload_img) {
			$('img',res).each(function() {
				this.setAttribute('src',this.getAttribute('src')+'?'+Math.random());
			});
		    }
		    res.dialog({modal: true} );

                }
            });
    },
    myforms_show_inline:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_reload_img=this.getAttribute('gsf_reload_img');
            var gsf_message=this.getAttribute('gsf_message');
            var gsf_template=this.getAttribute('gsf_template');
            var gsf_template_type=this.getAttribute('gsf_template_type');
            var gsf_classname=this.getAttribute('gsf_classname');

	    if (gsf_selector) {
		    var cont=$(gsf_selector);
	    } else {
		    //var cont=$(this).children(":first").parents('.gsf_inline');
		    var cont=$(this).parents('.gsf_inline');
		    cont.addClass('gsf_load');
	    }
            obj={_id:gsf_id,_action:gsf_action,_message:gsf_message,_template:gsf_template,_template_type:gsf_template_type,_classname:gsf_classname};
	    $('.gsf_ext_vars').each(function() { obj[this.name]=this.value; });
            jQuery.ajax({
                url: "gs_forms/myforms/",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
		    if (gsf_reload_img) {
			$('img',res).each(function() {
				this.setAttribute('src',this.getAttribute('src')+'?'+Math.random());
			});
		    }

		    if(cont.get(0).parentNode.tagName.toLowerCase()=='form') {
			    $(cont.get(0).parentNode).replaceWith(res);
		    } else {
                    cont.replaceWith(res);
		    }
                }
            });
    },
    myforms_close_inline:function() {
            var cont=$(this).parents('.gsf_inline');
		if (!gsf_events.myforms_gsf_suffix(this)) 
            cont.remove();
    },
    myforms_add_inline:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_template=this.getAttribute('gsf_template');
            var gsf_template_type=this.getAttribute('gsf_template_type');
            var gsf_classname=this.getAttribute('gsf_classname');
	    if (gsf_selector) {
		    var cont=$(gsf_selector);
	    } else {
		    var cont=$(this).parents('.gsf_table');
		    var cont=$('tbody',cont);
		    cont.addClass('gsf_load');
	    }
            obj={_id:gsf_id,_action:gsf_action,_template:gsf_template,_template_type:gsf_template_type,_classname:gsf_classname};
	    $('.gsf_ext_vars').each(function() { obj[this.name]=this.value; });
            jQuery.ajax({
                url: "gs_forms/myforms/",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.prepend(res);
                }
            });
    },
    myforms_post_form:function(ev) {

	    if (this.getAttribute('gsf_st')) return true;

	    ev.stopImmediatePropagation();

            var gsf_id=this.getAttribute('gsf_id');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_classname');
            var gsf_ext_vars=this.getAttribute('gsf_ext_vars');
	    var gsf_handler=this.getAttribute('gsf_handler') ? this.getAttribute('gsf_handler') : '/gsforms_admin/gs_forms/post/';
            var cont=$(this).parents('.gsf_inline');
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_action:gsf_action,_class:gsf_class,_ext_vars:gsf_ext_vars};
            var owner=this;
            var options = {
                url: "/index.php",
                type: "POST",
		forceSync: true,
                dataType:  'json',
                semantic: true,
		//iframe: true,
                success: function(res) {
			//alert('--');
                    if (res.status) {
                        obj._id=res.id;
                        owner.setAttribute('gsf_action','show');
                        owner.setAttribute('gsf_id',obj._id);
                        owner.setAttribute('gsf_reload_img',1);

			if (!gsf_events.myforms_gsf_suffix(owner)) {
				owner.setAttribute('gsf_st',1);
				$(owner).click();
				return true;
			}
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    $(".gsf_validate_message",cont).text('');
                    $("#gsf_validate_message",cont).text('');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"'],textarea[name='"+key+"']",cont).addClass('gsf_error_field');
                        $("#gsf_validate_message_"+key,cont).text(res.error_fields.MESSAGES[key]);
                    }
                    if(res.error_message) {
			    $("#gsf_error_message",cont).text(res.error_message);
		    }
                    if(res.exception) {
                        alert(res.exception_message);
                        owner.setAttribute('gsf_action','exception');
                        owner.setAttribute('gsf_message',res.exception_message);
                    }
		    //ev.stopImmediatePropagation();
                    //return false;
                }
            };


	    $("input,textarea",cont).removeClass('gsf_error_field');



	    $('textarea',cont).each(function(){ this.innerHTML=this.value});
	    if($(cont).parents(".ui-dialog").size()>0) {
	        var cont2=cont;
	    } else {
	    	var cont2=cont.clone();
	    }


	    $('.gsf_ext_vars').clone().appendTo(cont2);

	    $("input[type=password]",cont).each( function() { 
		    cont2.append('<input type="hidden" name="'+this.name+'" value="'+this.value+'">\n');
		    });

            cont2.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont2.append('<input type="hidden" name="gspgid" value="'+gsf_handler+'">\n');

	    if (cont.parents('tbody').size()>0) { 
		    cont2=cont2.appendTo(cont.parents('tbody')); 
	    }
	    cont2.wrap(document.createElement("form"));

	    //alert(cont2.parents('form').html());


	    cont2.parents('form').ajaxSubmit(options);

	    return false;


    },



    show_inline_form:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_message=this.getAttribute('gsf_message');
	    if (gsf_selector) {
		    var cont=$(gsf_selector);
	    } else {
		    var cont=$(this).parents('.gsf_inline');
		    cont.addClass('gsf_load');
	    }
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_message:gsf_message};
            
            jQuery.ajax({
                url: "gs_forms/show",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.replaceWith(res);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    show_content_by_value:function() {
		this.setAttribute('gsf_id',this.value);
		var gsf_id=this.getAttribute('gsf_id');
		var gsf_set_field=this.getAttribute('gsf_set_field');
		$(gsf_set_field).each(function() {
			this.value=gsf_id;
			});
		return gsf_events.show_inline_form.bind(this)();
    },
    show_content:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var cont=$(gsf_selector);
            
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action};
            
            jQuery.ajax({
                url: "gs_forms/show",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.html(res);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    post_form_content:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_gspgid= this.getAttribute('gsf_gspgid') ? this.getAttribute('gsf_gspgid') : '/gsforms_admin/gs_forms/post';
            var cont=$(gsf_selector);
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/gsforms_admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        //gsf_events.show_content.bind(owner)();
                        document.location.reload();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                        $("select[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    //debug(res.error_fields.MESSAGES);
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="'+gsf_gspgid+'">\n');
            cont.ajaxSubmit(options);
    },
    
    show_new_form:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var cont=$(gsf_selector);
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action};
            jQuery.ajax({
                url: "gs_forms/show",
                data: 'json='+escape(Obj2JSON(obj)),
                success: function(msg) {
                    var res=$(msg);
                    cont.prepend(res);
                    gsf_events.bind(res.get(0));
                }
            });
    },
    close_inline_form:function() {
            var cont=$(this).parents('.gsf_inline');
            cont.remove();
    },
    remove_content:function() {
            var gsf_selector=this.getAttribute('gsf_selector');
            var cont=$(gsf_selector);
            cont.empty();
    },
    post_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var cont=$(this).parents('.gsf_inline');
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/gsforms_admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        obj._id=res.id;
                        owner.setAttribute('gsf_action','show');
                        owner.setAttribute('gsf_id',obj._id);
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    if(res.exception) {
                        alert('ex!');
                        owner.setAttribute('gsf_action','exception');
                        owner.setAttribute('gsf_message',res.exception_message);
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    //debug(res.error_fields.MESSAGES);
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="/gsforms_admin/gs_forms/post">\n');
            cont.ajaxSubmit(options);
    },
    post_close_form:function() {
            var gsf_id=this.getAttribute('gsf_id');
            var gsf_dir=this.getAttribute('gsf_dir');
            var gsf_action=this.getAttribute('gsf_action');
            var gsf_class=this.getAttribute('gsf_class');
            var cont=$(this).parents('.gsf_inline');
            cont.addClass('gsf_load');
            obj={_id:gsf_id,_dir:gsf_dir,_action:gsf_action,_class:gsf_class};
            var owner=this;
            var options = {
                url: "/gsforms_admin/",
                type: "POST",
                dataType:  'json',
                semantic: true,
                success: function(res) {
                    if (res.status) {
                        gsf_events.close_inline_form.bind(owner)();
                        return true;
                    }
                    cont.removeClass('gsf_load');
                    $("input",cont).removeClass('gsf_error_field');
                    for (key in res.error_fields.MESSAGES) {
                        $("input[name='"+key+"']",cont).addClass('gsf_error_field');
                    }
                    if(res.exception) {
                        owner.setAttribute('gsf_action','exception');
                        owner.setAttribute('gsf_message',res.exception_message);
                        gsf_events.show_inline_form.bind(owner)();
                        return true;
                    }
                    //debug(res.error_fields.MESSAGES);
                }
            };
            cont.append('<input type="hidden" name="json" value=\''+Obj2JSON(obj)+'\'>\n');
            cont.append('<input type="hidden" name="gspgid" value="/gsforms_admin/gs_forms/post">\n');
            cont.ajaxSubmit(options);
    },
    demo: function () { alert ('demo'); },
    
    bind:function (obj) {
        for (attr in gsf_bindings) {
            if ($(obj).hasClass(attr)) {
                var e=gsf_bindings[attr];
                for (ev in e) {
                    $('.'+ev,obj).unbind('click',e[ev]);
                    $('.'+ev,obj).bind('click',e[ev]);
		    //$('.'+ev,obj).each(function() { this.value=this.value+'*'; });
                }
            }
        }
    },
    /*
    bind_myforms:function (obj,e_prev) {
	    	//alert('bind_myforms:function');
                var e=gsf_bindings['gsf_myforms'];
                for (ev in e) {
		    if ($(obj).hasClass(ev)) {
			    e_prev.preventDefault(); 
			    $(obj).unbind('click',e[ev]);
			    $(obj).bind('click',e[ev]);
		    }
                }
	        //obj.value=obj.value+'*';
	        obj.setAttribute('_gsf_binded',1);
    }
    */
    bind_myforms:function (obj,e_prev) {
		var arrList = obj.className.split(' ');
                var e=gsf_bindings['gsf_myforms'];
		for (i in arrList) {
			var ev=arrList[i];
			if (e[ev]) {
				e_prev.preventDefault(); 
				$(obj).unbind('click',e[ev]);
				$(obj).bind('click',e[ev]);
			}
		}
		//obj.value=obj.value+'*';
	        obj.setAttribute('_gsf_binded',1);
    }
}

gsf_bindings={
        gsf_edit:{
            gsf_b_cancel:gsf_events.show_inline_form,
            gsf_b_save:gsf_events.post_form
        },
        gsf_myforms:{
	    gsf_b_show_inline:gsf_events.myforms_show_inline,
	    gsf_b_add_inline:gsf_events.myforms_add_inline,
	    gsf_b_remove_inline:gsf_events.myforms_close_inline,
	    gsf_b_popup:gsf_events.myforms_popup,
	    gsf_b_popup_close:gsf_events.myforms_popup_close,
	    gsf_b_reload_selector:gsf_events.myforms_reload_selector,
	    gsf_b_post:gsf_events.myforms_post_form
        },
        gsf_show:{
            gsf_b_edit:gsf_events.show_inline_form,
            gsf_b_delete:gsf_events.show_inline_form
        },
        gsf_insert:{
            gsf_b_cancel:gsf_events.close_inline_form,
            gsf_b_save:gsf_events.post_form
        },
        gsf_delete:{
            gsf_b_cancel:gsf_events.show_inline_form,
            gsf_b_save:gsf_events.post_close_form
        },
        gsf_form:{
            gsf_b_add:gsf_events.show_new_form
        },
        gsf_show_content:{
            gsf_b_add:gsf_events.show_content,
            gsf_b_save:gsf_events.post_form_content,
            gsf_b_cancel:gsf_events.show_content,
        }
}


function debug (v) {
    var str='';
    for (key in v) {
        str+=key+': '+v[key]+'\n';
    }
    alert (str);
}


Function.prototype.bind = function(object) {
    var method = this
    return function() {
        return method.apply(object, arguments) 
    }
}
