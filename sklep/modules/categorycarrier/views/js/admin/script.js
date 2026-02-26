/* views/js/admin/script.js */

$(document).ready(function() {
    // Kosmetyka: Podświetl ramkę drzewa po załadowaniu, by widać było, że działa
    setTimeout(function(){
        if ($('#categories-tree').length) {
            $('#categories-tree').css('border-left', '5px solid #25b9d7');
        }
    }, 1000);
});