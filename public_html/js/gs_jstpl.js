function gs_tpl(filename) {
	var html='';
	var filename=filename;

	this.parse=function() {
		var str=['html=\'\';'];
		var parts=[];
		var tpl=this.tpl;
		var j=i=0;
		var js='';

		do {
			j=tpl.indexOf('<%');
			if(j>=0) {
				str.push('html+=\''+addslashes(tpl.substring(0,j))+'\';');
				tpl=tpl.substring(j+2);

				i=tpl.indexOf('%>');
				if(i>=0){
					js=tpl.substring(0,i);
					if(js.substr(0,1)=='=') str.push('html+=(typeof '+js.substring(1)+' == "undefined") ? "" :'+js.substring(1)+';');
						 else str.push(js);

					tpl=tpl.substring(i+2);
				}
			}
		}while(j>=0);

		str.push('html+=\''+addslashes(tpl)+'\';');

		this.html=str.join('\n');
	}
	this.fetch=function(data){
		for(key in data) self[key]=data[key];
		return eval(this.html);
	}
	this.load=function(filename){
		$.ajax({
			type: "GET",
			url: filename,
			dataType: "html",
			async: false,
			success: function(ans){
				this.tpl=ans;
				this.parse();
			}.bind(this)
		});

	}
	this.load(filename);
}
function addslashes(str) {
	str=str.replace(/\\/g,'\\\\');
	str=str.replace(/\'/g,'\\\'');
	str=str.replace(/\"/g,'\\"');
	str=str.replace(/\n/g,'\\n');
	str=str.replace(/\0/g,'\\0');
	str=str.replace(/\</g,'\\<');
	str=str.replace(/\>/g,'\\>');
	return str;
}

Function.prototype.bind = function(object) {
	    var method = this
	        return function() {
			        return method.apply(object, arguments)
				    }
}

function debug (v) {
	var str='';
	for (key in v) {
		str+=key+': '+v[key]+'\n';
	}
	alert (str);
}

function count(v) {
	var cnt=0;
	for (key in v) cnt++;
	return cnt;
}

if (String.prototype.addslashes) String.prototype.addslashes=function(){ 
	return addslashes(this);
}
if (String.prototype.wrap==null) String.prototype.wrap=function(m, b, c){ 
  b= b ? b : "\n";
  var i, j, s, r = this.split("\n");
    if(m > 0) for(i in r){
        for(s = r[i], r[i] = ""; s.length > m;
            j = c ? m : (j = s.substr(0, m).match(/\S*$/)).input.length - j[0].length
            || m,
            r[i] += s.substr(0, j) + ((s = s.substr(j)).length ? b : "")
        );
        r[i] += s;
    }
    return r.join("\n");
}

if (String.prototype.nl2br==null) String.prototype.nl2br=function(){ 
	return (this + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />$2');
}


