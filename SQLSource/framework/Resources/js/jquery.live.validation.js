/*  Live validace formcontrolu
    Rozsurije vsechny html elementy o funkce
    invalid() - zmeni tridu na invalidInput a pripoji DIV s chybovou hlaskou
    valid() - odstrani class invalidInput a chybovy DIV
    Autor: Ondrej Novak
*/

jQuery.fn.extend({
    
  invalid: function(res) {
    err = '#'+this.attr('id')+' ~ .controlerror';
    $(err).remove();
    this.addClass('invalidInput');
    if($('#'+this.attr('id')+'_lv').length == 0) {
        lv=document.createElement("div");
        $(lv).attr('className', 'live');
        $(lv).attr('id', this.attr('id')+'_lv');
        $(lv).text(res.message);
        this.parent().append(lv);     
    }
    else {
        $('#'+this.attr('id')+'_lv').text(res.message);
    }
  },
  
  valid: function(res) {
    err = '#'+this.attr('id')+' ~ .controlerror';
    $(err).remove();
    this.removeClass('invalidInput');
    $('#'+this.attr('id')+'_lv').remove();
  }
});

