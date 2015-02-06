/**
 * Příklad inicializace
 *  var formUnsave = new form_unsave("test");
 *  window.onload = function(){formUnsave.init()};
 *  window.onbeforeunload = function(){return formUnsave.onunload()};
 */

/**
 * 
 * @param string formName - název formuláře
 * @param string formSendVarName - NÁZEV globální proměnné, která určuje, jestli je formulář korektně odeslán
 * 		a nesmí být tudíž kontrolován na neuložené změny
 * @return void
 */ 
 form_unsave = function(formName, formSendVarName){
	this.inputsBackupArr = new Array;
	this.checked = false;
	this.name = formName;
	this.formSendVarName = formSendVarName;
	//registrace onUnload a onLoad
}


/**
 * 
 * @return void
 */
form_unsave.prototype.check = function(){
	var inputsArr = this.getInputs();
	return this.compare(inputsArr, this.inputsBackupArr);
}

form_unsave.prototype.compare = function($multiArr1, $multiArr2){
	for(var i in $multiArr1){
		if(typeof($multiArr2[i]) == 'undefined'){
			return false;
		}else if(typeof($multiArr1[i]) == 'object'){
			if(typeof($multiArr2[i]) == 'object'){
				res = this.compare($multiArr1[i], $multiArr2[i]);
				if(!res) return false;
			}else{
				return false;
			}
		}else if($multiArr1[i] != $multiArr2[i]){
			return false;
		}
	}
	return true;
}


form_unsave.prototype.init = function(){
	this.obj = document.forms[this.name];
	if (!this.obj){
		alert('JavaSriptová třída form_unsave nemůže ve stránce nalézt formulář s názvem "' + this.name + '".');
		return;
	}
	this.inputsBackupArr = this.getInputs();
}


form_unsave.prototype.inputValue = function(inputObj){
	switch (inputObj.type){
		case 'text':
		case 'textarea':
		case 'hidden':
		case 'password':
		case 'file':
			return inputObj.value;
		break;

		case 'checkbox':
		case 'radio':
			return inputObj.checked;
		break;

		case 'select-one':
		case 'select-multiple':
			returnArr = new Array();
			for (var opt=0; opt<inputObj.length; opt++){
				 returnArr[opt] = inputObj.options[opt].selected;
			}
			return returnArr;
		break;
		
		
		case 'submit':
		case 'reset':
		case 'button':
		case 'application/x-oleobject':

			return '';
		break;
		default : alert("Neznámý typ:" + inputObj.type);
	}
}


form_unsave.prototype.getInputs = function(){
	var inputIdArr = new Array()
	for (var i = 0; i < this.obj.length; i++){
		if (this.obj[i].name != undefined && this.obj[i].name != ''){
			//alert("object:"+this.obj[i]+"\nid:"+this.obj[i].id+"\nname:"+this.obj[i].name+"\nvalue:"+this.inputValue(this.obj[i]));
			inputIdArr[this.obj[i].name] = this.inputValue(this.obj[i]);
		}
	}
	return inputIdArr;
}

form_unsave.prototype.onunload = function(){
	formSend = eval('typeof(' + this.formSendVarName + ')');
	if (formSend != 'undefined'){
		formSend = eval(this.formSendVarName);
	}else{
		//alert('Třídě form_unsave nebyl v 2.parametru konstruktoru předán název existující globální proměnné (formunsave.js).');
        formSend = false;
		//return;
	}
	//alert(formSend);
	if(!formSend && !this.check()){
		var confirmText = 'Na stánce byly provedeny změny bez uložení.\n' +
			'Přejete si pokračovat bez uložení změn?';
		return (confirmText);
	}
}

