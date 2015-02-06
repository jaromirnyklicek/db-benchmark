//--------------------------------------------------------------------------------------------------
// Funkce pro nastavovani hodnot formularovych prvku, krome multiselectu
//
// [void]
function setInputByID(name, value, frame){
	if(frame == undefined) frame = this;
	if (typeof(name) == 'object'){
		element = name;
	}else{
		element = frame.document.getElementById(name);
	}
	if(element){
		//alert(name+' '+element.type+' '+element.text+' '+value);
		switch (element.type){
			case 'password':
			case 'text':
			case 'hidden':
			case 'textarea':
				element.value=value;
			break;
			case 'checkbox':
				element.checked = (value == 1 || value === true || value == element.value);
			break;
			case 'select-one':
				//alert("select-one");
				/*var messageCont = "";
				for(var j=0;j<element.length;j++){
					messageCont += "value:"+element.options[j].value+" default:"+value+"\n";
				}
				alert(messageCont);*/
				for (var i=0;i<element.length;i++){
					//alert("value:"+element.options[i].value+" default:"+value);
				  	element.options[i].selected = (element.options[i].value == value);
				}
			break;
			case 'radio':
				if(!element.form){
					alert('setInputByID(): INPUT radio ' + element.name + ' musí být součástí formuláře.');
				}
				radioArr = eval('element.form.' + name);
				for (i = 0; i < radioArr.length; i++){
					if (radioArr[i].value == value){
						radioArr[i].checked = true;
					}
				}
			break;
			case 'select-muliple':
				setMultiSelectByID(element, value);
			break;
			default: //zkusí, jestli má metodu value, href, src nebo innerHTML a zapíše obsah
				if (element.src !== undefined){
					element.src = value;
				}else if (element.href !== undefined){
					element.href = value;
				}else if (element.value !== undefined){
					element.value = value;
				}else{
					element.innerHTML = value;
				}
			break;
		}

		return true;

	}else{
		//alert('not found');
		return false;
	}
}// setInputByID() ---------------------------------------------------------------------------------


//--------------------------------------------------------------------------------------------------
// Funkce pro nastavovani hodnot multiselectu. Funkce prijima dvourozmerne pole s touto syntaxi:
//   valueArr = new Array(
//     new Array(<hodnota option>, <selected>),
//     ...
//   );
//
// [void]
function setMultiSelectByID(element, valueArr){
	valueArrLen = valueArr.length;
	for (i=valueArrLen-1; i>=0; i--){
		for (j=0; j<element.length; j++){
			if (element.options[j].value == valueArr[i][0]){
				element.options[j].selected = valueArr[i][1];
			}
		}
	}
}// setMultiSelectByID() ---------------------------------------------------------------------------


