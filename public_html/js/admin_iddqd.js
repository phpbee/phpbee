$(document).ready( function() {
	$("beeblock,body").dblclick(function() {
		var url=window.document.location.href.replace("/admin/wizard/iddqd","/admin/wizard/iddqdblock");
		url+="/"+this.id.replace("bee_block_","");
		window.document.location.href=url;
		return false;
	});
	
});
