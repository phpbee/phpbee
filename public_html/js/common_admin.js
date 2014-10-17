$(document).ready (function () {
	
	//map.setCenter(new YMaps.GeoPoint(37.64, 55.76), 10);
	
	$("form").interaction({startfield:'Make_id',start_pattern:'.lOne2One,.interact_start_field [name]'});
	$(".lMany2Many").gs_multiselect();
	$(".fMultiSelect").gs_multiselect();
	$(".chosen_multiselect").chosen();
	$(".chosen").chosen();
	$(".lOne2One").sel_filter( {slide_width: 150, min_options: 1, crop: false});
	$(".fSelect").sel_filter();
	$(".fDateTime").datepicker();
	 $(".sortable").sortable();
	 $(".sortkey-table > tbody").sortable({
             axis: 'y',
             revert: true,
             cancel: 'span',
		     helper: function(e, ui) {
			    ui.children().each(function() {
				    $(this).width($(this).width());
				});
		        return ui;
			},
		update: function(event, ui) {
			var rec_id=ui.item.attr('record_id');
			var dir='after';
			var dir_rec_id=ui.item.prev().attr('record_id');
			if (!dir_rec_id) {
				var dir='before';
				var dir_rec_id=ui.item.next().attr('record_id');

			}
			var sortkey_id=ui.item.closest('table').attr('sortkey_id');
			if (rec_id && dir_rec_id && sortkey_id) {
				var data = {};
				data['sortkey_id']=sortkey_id;
				data['rec_id']=rec_id;
				data['dir_rec_id']=dir_rec_id;
				data['dir']=dir;
				$.ajax({
					url:document.location.href,
					data: data,
					type: 'GET',
					dataType: 'JSON',
				});
			}
			
		}


	 }).disableSelection();

	$("[-data-href]").dblclick(function() {
		window.document.location.href=$(this).attr('-data-href');
		return false;
	});
	$("[-data-href]").each(function() {
		$(this).attr('title',$(this).attr('-data-href'));
	});

	$("table.tb").children("tbody").children("tr").mouseover(function() {
		$(this).addClass("over");
	});

	$("table.tb tr").mouseout(function() {
		$(this).removeClass("over");
	});

	$('#tpl_content').each(function () {
		//window['tpl_codemirror'] = CodeMirror.fromTextArea(this, { mode:"text/html", tabMode:"indent",lineNumbers: true });

		window['tpl_codemirror'] = CodeMirror.fromTextArea(this,
		{
lineNumbers: true,
matchBrackets: true,
mode: "application/x-httpd-php",
			indentUnit: 8,
indentWithTabs: true,
enterMode: "keep",
tabMode: "shift"
		});
	});

	$('.ch_all').click(
	function() {
		$('.ch1').attr('checked',this.checked);
	}
	);
	$('.fDateTimeFilter').each(function() {
		$(this).daterangepicker(
		{
			dateFormat: $.datepicker.ATOM,
			onOpen: function() {
				$('.ui-daterangepicker:visible .ui-daterangepicker-specificDate').trigger('click');
			}
		}
		);
	});

	$('.form_help_over').mouseover(function() {
		var spoiler=$(this).closest('.form_help_container').find('.form_help');
		spoiler.delay(1500).show();
	});
	$('.form_help_over').mouseout(function() {
		var spoiler=$(this).closest('.form_help_container').find('.form_help');
		spoiler.hide();
	});

	
	$('.admin_img_preview').preview();

	

});

function md(obj) {
	var str="";
	for (key in obj) {
		str +="\n"+key+"="+obj[key];
	}
	return str;
}

ymaps.ready(init_map);

function init_map() {
	window.myMap = new ymaps.Map ("coords_map", {center: [ymaps.geolocation.latitude, ymaps.geolocation.longitude], zoom: 10});
	window.myMap.controls.add(
		new ymaps.control.ZoomControl()
	);
	window.placemark=new ymaps.Placemark([ymaps.geolocation.latitude, ymaps.geolocation.longitude]);
	window.myMap.geoObjects.add(window.placemark);
	
	window.myMap.events.add("click",
		function(e) {
			var txt=e.get("coordPosition");
			$('#coord_x').val(txt[0]);
			$('#coord_y').val(txt[1]);
			window.placemark.geometry.setCoordinates(txt);
		}
	);
}
