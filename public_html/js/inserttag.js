var JS_HELPOFF = false;
/* indentify the browser */
var DOM = (document.getElementById) ? 1 : 0;
var NS4 = (document.layers) ? 1 : 0;
var IE4 = (document.all) ? 1 : 0;
var OPERA = navigator.userAgent.indexOf("Opera") > -1 ? 1 : 0;
var MAC = navigator.userAgent.indexOf("Mac") > -1 ? 1 : 0;

/* edit box stuff */
function insertTag(obj, stag, etag)
{
    if (navigator.userAgent.indexOf("MSIE") > -1 && !OPERA) {
        insertTagIE(obj, stag, etag);
    } else {
            insertTagMoz(obj, stag, etag);
    }
        /*
    } else if (window.getSelection && navigator.userAgent.indexOf("Safari") == -1) {
        insertTagMoz(obj, stag, etag);
    } else {
        insertTagNS(obj, stag, etag);
    }
    */
    obj.focus();
}

function insertTagNS(obj, stag, etag)
{
    obj.value = obj.value+stag+etag;
}

function insertTagMoz(obj, stag, etag)
{
    var txt = window.getSelection();

    if (!txt || txt == '') {
        var t=obj;
        //var t = document.getElementById('answer_body');
        //var h = document.getElementsByTagName('textarea')[0];
        if (t.selectionStart == t.selectionEnd) {
            t.value = t.value.substring(0, t.selectionStart) + stag + etag +  t.value.substring(t.selectionEnd, t.value.length);
            return;
        }
        txt = t.value.substring(t.selectionStart, t.selectionEnd);
        if (txt) {
            t.value = t.value.substring(0, t.selectionStart) + stag + txt + etag +  t.value.substring(t.selectionEnd, t.value.length);
            return;
        }
    }
    obj.value = obj.value+stag+etag;
}

function insertTagIE(obj, stag, etag)
{
    var r = document.selection.createRange();
    if( document.selection.type == 'Text' && (obj.value.indexOf(r.text) != -1) ) {
        a = r.text;
        r.text = stag+r.text+etag;
        if ( obj.value.indexOf(document.selection.createRange().text) == -1 ) {
            document.selection.createRange().text = a;
        }
    }
    else insertAtCaret(obj, stag+etag); 
}

function storeCaret(textEl)
{
     if (textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
}

function insertAtCaret(textEl, text)
{

    if (textEl.createTextRange && textEl.caretPos)
    {
        var caretPos = textEl.caretPos;
        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
    }
    else 
        textEl.value  =  textEl.value + text;
}

function setCaretPosition(ctrl, del) {
    pos=ctrl.value.indexOf(del);
    if (pos==-1) return;
    //ctrl.value=ctrl.value.replace(del,'');
    if(ctrl.setSelectionRange) {
        ctrl.focus();
        ctrl.setSelectionRange(pos,pos);
    } else if (ctrl.createTextRange) {
        var range = ctrl.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}
function setCaretPosition2(ctrl, del) {
    pos=ctrl.value.indexOf(del);
    if (pos==-1) return;
    ctrl.value=ctrl.value.replace(del,'');
    if(ctrl.setSelectionRange) {
        ctrl.focus();
        ctrl.setSelectionRange(pos,pos);
    } else if (ctrl.createTextRange) {
        var range = ctrl.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}
