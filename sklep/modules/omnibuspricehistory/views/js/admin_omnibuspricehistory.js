/**
 * 2007-2023 PrestaShop
 *
 * Plik JS dla panelu administracyjnego modułu Historia Ceny Omnibus.
 */

$(document).ready(function() {
    // --- Zakomentuj lub usuń całą sekcję Select2, jeśli przechodzisz na prosty <select> ---
    // var productSearchElement = $('#omnibus-product-search');
    // if (productSearchElement.length) {
    //     productSearchElement.select2({
    //         placeholder: "Zacznij wpisywać nazwę, referencję lub ID produktu...",
    //         minimumInputLength: 2,
    //         width: '100%',
    //         dropdownCssClass: "bootstrap",
    //         ajax: {
    //             url: currentIndex + '&token=' + token + '&ajax=1&action=searchProducts&controller=AdminOmnibusPriceHistoryDebug',
    //             dataType: 'json',
    //             delay: 250,
    //             data: function (params) {
    //                 return { q: params.term };
    //             },
    //             processResults: function (data) { return { results: data.results }; },
    //             cache: true
    //         }
    //     });
    //     productSearchElement.on('select2:select', function (e) {
    //         var data = e.params.data;
    //         if (data.id) {
    //             window.location.href = currentIndex + '&token=' + token + '&controller=AdminOmnibusPriceHistoryDebug&id_product=' + data.id;
    //         }
    //     });
    //     var urlParams = new URLSearchParams(window.location.search);
    //     var id_product_from_url = urlParams.get('id_product');
    //     if (id_product_from_url) {
    //         $.ajax({
    //             url: currentIndex + '&token=' + token + '&ajax=1&action=searchProducts&controller=AdminOmnibusPriceHistoryDebug',
    //             dataType: 'json',
    //             data: { q: id_product_from_url },
    //             success: function(data) {
    //                 if (data.results && data.results.length > 0) {
    //                     var productData = data.results[0];
    //                     var option = new Option(productData.text, productData.id, true, true);
    //                     productSearchElement.append(option).trigger('change');
    //                     productSearchElement.trigger({ type: 'select2:select', params: { data: productData } });
    //                 }
    //             }
    //         });
    //     }
    // }
    // --- Koniec sekcji Select2 ---

    // UWAGA: Logika dla $('#simple-product-select') jest teraz w szablonie Smarty,
    // więc ten plik JS może być pusty, jeśli nie masz innych skryptów specyficznych dla panelu admina.
    // Jeśli chcesz, możesz ją przenieść tutaj, ale w Smarty jest prostsza, bo ma dostęp do {$current_url}
});