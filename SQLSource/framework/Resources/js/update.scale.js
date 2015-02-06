function update_scale_string(item) {
    document.getElementById(item.id + "_scale_text").innerHTML = item.value.length + " / " + item.maxLength + " znaků";
    document.getElementById(item.id + "_scale_bar").style.width = Math.round((item.value.length / item.maxLength) * 100) + "%";
    return true;
}
