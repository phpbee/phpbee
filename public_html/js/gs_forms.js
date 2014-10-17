


function setCal(id) {
	obj=document.getElementById(id);
    if (typeof obj._gs_calendar == 'undefined') {
	obj._gs_calendar = Calendar.setup({
	trigger    : id,
	inputField : id,
	showTime : true,
	minuteStep : 15,
	dateFormat : "%Y-%m-%d %H-%M",
	});
    }
}
function _gs_cancel_form() {
	  if (window._old_tr==false) return;
          var id = window._gs_new_id;
          var o_tr=document.getElementById(id);
	  if (o_tr) o_tr.parentNode.replaceChild(window._old_tr,o_tr);
	  window._old_tr=false;
}
function _gs_request_form(id,formid,gspgid,backurl) {
	  _gs_cancel_form(); 
          window._gs_new_id=id;
          window._formid=formid;
          window._gspgid=gspgid;
          window._backurl=backurl;

          var o_tr=document.getElementById(id);
	  window._old_tr=o_tr.cloneNode(true);

          document.getElementById('_iframe_edit').src=gspgid;
}
function _gs_show_form() {
          if (typeof window._gs_new_id == 'undefined') return false;
          var id = window._gs_new_id;
          var formid = window._formid;
          var o_tr=document.getElementById(id);
          var o_ifr=document.getElementById('_iframe_edit').contentWindow.document;
          var o_ifr_tr=o_ifr.body.getElementsByTagName('tr')[0];
	  var o_ifr_erase_cancel=o_ifr.getElementById('_update_done');

	  if (o_ifr_erase_cancel) window._old_tr=false;
          //for (var i=0; i<o_tr.cells.length; i++) {
                  //  o_tr.cells[i].innerHTML=o_ifr_tr.cells[i].innerHTML;
		  //o_tr.replaceChild(o_ifr_tr.cells[i].cloneNode(true),o_tr.cells[i]);
          //}
	  if (typeof ActiveXObject=='function') {
		       xmlDoc = new ActiveXObject ("Microsoft.XMLDOM"); 
		       	    var html=o_ifr_tr.parentNode.innerHTML;
			    //html=' <tr id="news_28"> <td >28</td> </tr> ';
			    html=html+' ';
			    alert(html);
		            xmlDoc.loadXML(html);
			  alert (xmlDoc.firstChild);
	  } else {
		  xmlDoc=o_ifr_tr.cloneNode(true);
	  }

	  o_tr.parentNode.replaceChild(xmlDoc,o_tr);
	  //o_ifr_tr.parentNode.removeChild(o_ifr_tr);
	  //o_tr.parentNode.addChild(o_tr.cloneNode(true));
          document.forms[formid].elements['gspgid'].value=window._gspgid;
          document.forms[formid].elements['backurl'].value=window._backurl;

	  if(o_ifr.getElementById('_update_reload')) window.document.location.reload();
	  if(o_ifr.getElementById('_tinymce')) setTinyMCE();
          //o_tr.innerHTML=document.getElementById('_iframe_edit').contentWindow.document.body.innerHTML;
}
function _gs_change_answ(o_cnt) {
	var cnt=o_cnt.value;
	var divs=document.getElementById('ans').getElementsByTagName('div');
	for(var i=0, k=1; i<divs.length; i++) {
		if (divs[i].id=='ans_'+k) {
			divs[i].style.display=(k<=cnt) ? 'block' : 'none';
			k++;
		}
	}
}
function _gs_tab(id) {
	var divs=document.getElementById(id).parentNode.getElementsByTagName('div');
	for(var i=0, k=1; i<divs.length; i++) {
			divs[i].style.display='none';
	}
	document.getElementById(id).style.display='block';
	return false;
}
