function genPassword(passlength) {
    var vowels = "aeiou";
    var consonants = "bcdghjklmnpqrstvwx";
    var password = "";
    var numLen = 2;
    var numProgress = false;
    var stamp = new Date();
    var alt = stamp.getSeconds() % 2;

    for (var i = 0; i < passlength; i++) {
        if (alt == 1 && !(numProgress && numLen)) {
            password += consonants.charAt(((Math.random() * 100000000) % consonants.length));
            alt = 0;
        }
        else if (alt == 0 && Math.round(Math.random()) && numLen && password || numProgress && numLen) {
            numProgress = true;
            numLen --;
            password += Math.round(Math.random() * 9);
            alt = 0;
        }
        else {
            password += vowels.charAt(((Math.random() * 100000000) % vowels.length));
            alt = 1;
        }
    }
    return password;
}