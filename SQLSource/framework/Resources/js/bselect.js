function SetListboxOptions(arr, dest, idDestSel) 
{
 var j=0
 for (var i=0; i<arr.length; i++) {  
   dest.options[j++] = new Option(arr[i][1], arr[i][0]);
   if(idDestSel == arr[i][0]) dest.options[j-1].selected = true;  
 }
 dest.options.length = j
}

