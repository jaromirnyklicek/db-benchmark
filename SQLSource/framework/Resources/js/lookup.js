LookUpControlLang = {
	Loading : 'Načítám data...',
	EnterQuery : 'Zadejte hledaný výraz.'
};



var LookUpControl = 
{
    called : false,    
    element : null,  
    content : null,  
    control : null,  
    showAll : false,
    url : false,
    onSelect : function (id) {},
    maxHeight : 200,
    
    Show: function(params) {
        x = params.x;
        y = params.y;
        control = params.control;
        this.onSelect = params.onSelect;
        this.control = control;
        this.showAll = params.showAll;        
        this.maxHeight = params.height;        
        if(x == 'undefined') x=0;
        if(y == 'undefined') y=0;
        if(!this.called) this.createIframe(0,0); 
        this.setHeight(this.maxHeight);  
        //this.element.style.display="block";    
        this.Reset();
        this.setPosition(x, y);
        $(this.element).slideDown('normal', function() {
            LookUpControl.iframeobj.style.display = 'block';
            if(LookUpControl.showAll) LookUpControl.Search('');
            $('#LookUpControl_input').focus();
        });
        
        
        //$('#LookUpControl_input').focus();
    },
    
    Init: function() {
        if(!this.called) this.createIframe(0,0);         
    },
    
    createIframe : function(x, y) {
            w = 270;
            h = 180;
            name = "LookUpControl";
            element =document.createElement("div");
            element.id = name;
            element.className = "lookup";
            element.style.height = h + "px";
            element.style.width =  w + "px";
            element.style.display="none";
            element.style.left = x + "px";
            element.style.top = y + "px";
            
            var oDiv2=document.createElement("div");
            oDiv2.className = "lookup_up";
            oDiv2.id = name + "_up";
            oDiv2.style.width = w + "px";                
            oDiv2.innerHTML = '<img style="float:right" src="/img/core/ico/close.gif" onClick="LookUpControl.Close();" /><input id="LookUpControl_input" type="text" style="border: 1px solid #555555; width: ' + (w - 20) + 'px; margin-top: 1px; margin-left: 1px" onKeyUp="LookUpControl.Search(this.value)"/>';
            
            var oDiv3=document.createElement("div");
            oDiv3.id = name + '2';
            hup = 23;  // vyska lookup_up
            oDiv3.style.height = (h - hup) + "px";
            oDiv3.style.width = w + "px";
            oDiv3.className = "lookup_content";
            
            var oDiv4=document.createElement("div");
            oDiv4.id = name + '2';
            hup = 23;  // vyska lookup_up
            oDiv4.style.height = (h - hup) + "px";
            oDiv4.style.width = w + "px";
            oDiv4.style.position = 'absolute';            
            oDiv4.style.display = 'none'; 
            oDiv4.style.top = (y + hup) + 'px';
            oDiv4.style.left = x + 'px';
            oDiv4.className = "lookup_loader";
            oDiv4.innerHTML = '<div style="width: 50px; margin: auto; top: 15%; position:relative"><img src="/img/core/ani-wait.gif"/></div>';
            
            var iframex=document.createElement("iframe");
            iframex.style.height = (h - hup - 8) + "px";
            iframex.style.width = (w - 4) + "px";
            iframex.style.position = 'absolute';            
            iframex.style.top = (y + hup) + 'px';
            iframex.style.left = x + 'px';
            iframex.style.zindex = 1000;
            iframex.style.display="none";  
            //iframex.style.border = '0px';
            iframex.frameborder = 0;
            //oDiv3.innerHTML = '<iframe id="ListBoxSearch_iframe" name="ListBoxSearch_iframe" src="/framework/vaadmin/core/input.select.dialog.php" name="ListBoxSearch_iframe" src="" width="'+ w +'" height="'+ (h - 20) +'" marginwidth="0" marginheight="0" hspace="0" vspace="0" frameborder="0" scrolling="yes"></iframe>';                
            
            element.appendChild(oDiv2);
            element.appendChild(oDiv3);
            element.appendChild(oDiv4);
             document.body.appendChild(iframex);
            this.element = element;
            this.content = oDiv3;  
            this.loader = oDiv4;  
            this.iframeobj = iframex;  
            document.body.appendChild(element);                                
            this.called = true;                
    },
    
    Search : function(text)
    {
        this.text = text;
        if(text != '' || this.showAll)
        {
            this.Loading();
            //this.writeHTML('<div id="wait" style="text-align: center"><br/><b>' + LookUpControlLang.Loading + '</b><br/><br/><img src="/img/ani-wait.gif"></div>');
            encFunc = encodeURIComponent ?  encodeURIComponent : escape;
            if ((this.url).indexOf('?') == -1) ch = '?';
            else ch = '&';
            //listbox_send_xmlhttprequest(this.url + ch + 'text='+encFunc(text)+'&parent='+this.parent);
            //alert(this.url);
            url = this.url + ch + 'text='+encFunc(text);
            $.getJSON(url,
                function(data){
                    //alert(data.text);
                    LookUpControl.loader.style.display = 'none';
                    if(LookUpControl.text == data.text) 
                    {                            
                        LookUpControl.content.innerHTML = data.html; 
                        js_evalScripts(data.html);
                        height = $('#lookuplist').height();
                        LookUpControl.setHeight(height+25);
                    }
                });

        }
        else this.Reset();
    },
     
    Loading : function()
    {
           this.loader.style.display = 'block';
    },    
    
    Close : function()
    {
        if(this.called)
        {
            //this.element.style.display="none";
            $(this.iframeobj).hide();
            $(this.element).hide('fast'); 
            if(document.getElementById(this.control+'_preview')) {
                document.getElementById(this.control+'_preview').focus();
            }
            else {
                document.getElementById(this.control).focus();
            }
 
        }
    },
    
    Update : function(res)
    {  
       this.Close();
       document.getElementById(this.control).value = res.id;
       if(res.autosubmit) {
       		document.getElementById(this.control).form.onsubmit();
	   }
       if(document.getElementById(this.control+'_preview')) {
            document.getElementById(this.control+'_preview').value = res.value;
       }
       if(res.href != undefined) {
           document.getElementById(this.control+'_edit').href = res.href;
           document.getElementById(this.control+'_eimg').src = '/img/core/ico/pencil.gif';
       }
       this.onSelect(res.id);
    },
    
    Reset : function()
    {
        document.getElementById('LookUpControl_input').value = '';            
        this.writeHTML('<div style="text-align: center; margin-top: 5px"><b>' + LookUpControlLang.EnterQuery+ '</b></div>');
    },
    
    writeHTML : function(text)
    {
       this.content.innerHTML = text;
    },
    
    setDimension : function(w, h)
    {
        name = 'LookUpControl';
        document.getElementById(name + '_input').style.width = (w - 20) + 'px';
        document.getElementById(name + '_up').style.width = w + 'px';
        this.element.style.height = h + "px";
        this.element.style.width =  w + "px";
        this.content.style.height = (h - 23) + "px";
        this.content.style.width = w + "px";
        this.iframeobj.style.height = (h - 23) + "px";
        this.iframeobj.style.width = w + "px";
    },  
    
    setHeight : function(h)
    {
        if(h < 40) h = 40;
        if(h > this.maxHeight) h = this.maxHeight;
        name = 'LookUpControl';
        this.element.style.height = h + "px";
        this.content.style.height = (h - 23) + "px";
        this.loader.style.height = (h - 23) + "px";
        this.iframeobj.style.height = (h - 27) + "px";
    },  
    
    setPosition : function(x, y)
    {
        this.element.style.left =  x + "px";
        this.element.style.top = y + "px";
        this.iframeobj.style.left = x + "px";
        this.iframeobj.style.top = (y + 23) + "px";
    },   
    
    findPosByObj : function(obj) 
    {
        var curleft = curtop = 0;
        if (obj.offsetParent) {
            curleft = obj.offsetLeft
            curtop = obj.offsetTop
            while (obj = obj.offsetParent) {
                curleft += obj.offsetLeft
                curtop += obj.offsetTop
            }
        }
        return [curleft,curtop]; 
    }
}

$(document).ready(function(){
    LookUpControl.Init();
});