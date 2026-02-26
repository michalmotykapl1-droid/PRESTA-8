/**
  * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
 */
$(document).ready(function(){
    Etsblog_runowl();

    // --- POCZĄTEK KODU DO KLIKALNEJ IKONY WYSZUKIWANIA ---
    $(document).on('click', '.ets_block_search .icon_search', function(e){
        e.preventDefault();
        $(this).closest('form').submit();
    });
    // --- KONIEC KODU ---
});
function Etsblog_runowl()
{
    if ( $('.ets_blog_rtl_mode').length > 0 ){
        var rtl_blog = true;

    } else {
        var rtl_blog = false;
    }
    if ($('.page_home.ets_block_slider ul').length > 0) {
        $('.page_home.ets_block_slider ul').etsowlCarousel({
    items: 3,
    responsive: {
        0:    { items: 1 },
        480:  { items: 1 },
        768:  { items: 3 },
        992:  { items: 3 },
        1199: { items: 3 }
    },
    rtl: rtl_blog,
    nav: true,
    dots: false,
loop: true,
slideBy: 1,    navText: ['', ''],
    callbacks: true
});
    }
 }
