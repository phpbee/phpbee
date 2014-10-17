$(document).ready(function () {
	var sclx=1;
	var scly=1;
	var sw=1;
	var sh=1;
	var $img=$('img#imgAreaSelect');
	var img=$img.get(0);

	var $vars=$(".jsedit_crop#"+fieldname);


	var imgAreaSelect2Canvas=function(img, selection) {
		if (selection.y2>0) {
			$('[id=x1]',$vars).val(100*selection.x1/img.width);
			$('[id=y1]',$vars).val(100*selection.y1/img.height);
			$('[id=x2]',$vars).val(100*selection.x2/img.width);
			$('[id=y2]',$vars).val(100*selection.y2/img.height);

			$('[id=width]',$vars).val(100*selection.width/img.width);
			$('[id=height]',$vars).val(100*selection.height/img.height);
		}

	}


	var simg = new Image();
	simg.onload=function() {
		sclx=simg.width/$img.get(0).width;
		scly=simg.height/$img.get(0).height;
		sw=$img.get(0).width/100;
		sh=$img.get(0).height/100;

		var ias=$img.imgAreaSelect({
			instance: true,
			handles: true,
			onSelectEnd: imgAreaSelect2Canvas,
			onInit: imgAreaSelect2Canvas,
		});
		if (crop_y2>0) {
			ias.setSelection(crop_x1*sw,crop_y1*sh,crop_x2*sw,crop_y2*sh,true);
			ias.setOptions({ show: true });
			ias.update();
		}
	}
	simg.src=$img.attr('srcbig');


});
