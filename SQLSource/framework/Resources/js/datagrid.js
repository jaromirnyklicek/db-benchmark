function getTextAreaPosition (ctrl) {
    var CaretPos = 0;
    // IE Support
    if (document.selection) {
        ctrl.focus ();
        var Sel = document.selection.createRange ();
        Sel.moveStart ('character', -ctrl.value.length);
        CaretPos = Sel.text.length;
    }
    // Firefox support
    else if (ctrl.selectionStart || ctrl.selectionStart == '0')
    CaretPos = ctrl.selectionStart;
    return (CaretPos);
}

function getLastWord(ctrl) {
    var pos = getTextAreaPosition(ctrl);
    var value = ctrl.value;
    var i = 0;
    s = value.substr(i, 100);     
}

function strpos( haystack, needle, offset){
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Onno Marsman    
    // +   bugfixed by: Daniel Esteban
    // *     example 1: strpos('Kevin van Zonneveld', 'e', 5);
    // *     returns 1: 14
 
    var i = (haystack+'').indexOf(needle, (offset ? offset : 0));
    return i === -1 ? false : i;
}


IdArrToUrl = function(arr, param){
    s = '';
    for(i=0; i< arr.length;i++) {
            s += param + '%5B%5D=' + arr[i] + '&';
    }
    return s;
}

MultiLink = function (link, datagrid)
{
    fnc = datagrid + '_selectedId()';
    arr = eval(fnc);
    params = IdArrToUrl(arr, 'sel_id');
    if(link.indexOf('?') > 0) url = link + '&' + params;
    else url = link + '?' + params;
    document.location = url;
}

/**
    Nastaveni voleb listboxu
    arr - vstupni pole polozek
    dest - cilovy listbox, ktery se bude plnit
    idDestSel - id vybrane hodnoty
*/
function SetListboxOptions(arr, dest, idDestSel) 
{       
    if(idDestSel == undefined) idDestSel = dest.value;
    var j=0
    for (var i=0; i<arr.length; i++) {  
       dest.options[j++] = new Option(arr[i][1], arr[i][0]);
       if(idDestSel == arr[i][0]) dest.options[j-1].selected = true;  
    }
    dest.options.length = j
}