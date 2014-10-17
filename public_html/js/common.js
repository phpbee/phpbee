$(document).ready(function () {
	$('a.login_link').click(function (){
		$('.overlay_login').toggleClass('show');
		return false;
	});
});

ymaps.ready(init_map);

function init_map() {
	window.myMap = new ymaps.Map ("coords_map", {center: [ymaps.geolocation.latitude, ymaps.geolocation.longitude], zoom: 10});
	window.myMap.controls.add(
		new ymaps.control.ZoomControl()
	);
	window.placemark=new ymaps.Placemark([ymaps.geolocation.latitude, ymaps.geolocation.longitude]);
	window.myMap.geoObjects.add(window.placemark);
	//window.placemark.geometry.Point([txt[0]+10,txt[1]+10]);
	
	window.myMap.events.add("click",
		function(e) {
			var txt=e.get("coordPosition");
			$('#coord_x').val(txt[0]);
			$('#coord_y').val(txt[1]);
			window.placemark.geometry.setCoordinates(txt);
		}
	);
}