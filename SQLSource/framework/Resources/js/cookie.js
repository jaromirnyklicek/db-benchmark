function setCookie( name, value, path, domain) {
        var today = new Date();
        today.setTime( today.getTime() );
        expires = 300 * 1000 * 60 * 60 * 24;
        var expires_date = new Date( today.getTime() + (expires) );                            
        document.cookie = name + "=" +escape( value ) +
            ( ";expires=" + expires_date.toGMTString()) + 
            ( ( path ) ? ";path=" + path : "" ) + 
            ( ( domain ) ? ";domain=" + domain : "" ) 
}