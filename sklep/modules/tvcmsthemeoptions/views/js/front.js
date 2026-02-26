/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
var currentThemeName = tvthemename + "_";
var isCallAjax = true;
var storage;
var cssPath;
var layoutPath;
var demo_theme;
var demo_theme_mode;
var cssDataResult;
var demo_theme_layout_Status = false;
var stopRefreshStatus = false;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
$(document).ready(function(e) {
    /******start init*******/
    function getUrlVars() {
        var vars = [],
            hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    } //function getUrlVars()

    storage = $.localStorage;
    cssPath = prestashop.urls.css_url;
    layoutPath = prestashop.urls.theme_assets + "../templates/_partials/";
    demo_theme = getUrlVars()["demo-theme"];
    demo_layout_hl = getUrlVars()["hl"];
    demo_layout_mhl = getUrlVars()["mhl"];
    demo_layout_fl = getUrlVars()["fl"];
    demo_layout_pdl = getUrlVars()["pdl"];
    demo_theme_layout_Status = (demo_layout_hl != undefined || demo_layout_mhl != undefined || demo_layout_fl != undefined || demo_layout_pdl != undefined);
    demo_theme_mode = getUrlVars()["demo-theme-mode"];
    cssDataResult = '';
    if ((demo_theme != '' && demo_theme != undefined) || (demo_theme_mode != '' && demo_theme_mode != undefined) || demo_theme_layout_Status){ //FOR REQUEST
        gettvcmsthemeoptions();
    }

    function storageGet(key) {
        return "" + storage.get(currentThemeName + key);
    }

    function storageSet(key, value) {
        storage.set(currentThemeName + key, value);
    }

    function setRequestTheme(demo_theme) {
        if (demo_theme == "box-width") {
            storageSet("box-layout", "box");
        } else if (demo_theme != '' && demo_theme != undefined) {
            storageSet("theme", demo_theme);
            $('.tvselect-theme #select_theme input[value="' + storageGet("theme") + '"]').attr('checked', 'checked');
            var themeColorVal = $('input:checked', $('#select_theme')).attr('data-color');
            var themeColorVal2 = $('input:checked', $('#select_theme')).attr('data-color-two');
            storageSet("theme_color", themeColorVal); //save localStorage
            storageSet("theme_color2", themeColorVal2); //save localStorage
            setCustomeTheme();
            setTimeout(function() { $('.tvtheme-control-icon').trigger('click') }, 500);
        }
        if(demo_theme_layout_Status){
            setTimeout(function() { $('.tvtheme-layout-icon').trigger('click') }, 500);
        }
        if (demo_theme_mode == 'dark') {
            storageSet("theme-dark-mode", 1);
        }else if(demo_theme_mode == 'light'){
            storageSet("theme-dark-mode", 0);
        } else if (demo_theme != '' && demo_theme != undefined) {
            storageSet("theme-dark-mode", 0);
        }
    }

    /******end init*******/

    function gettvcmsthemeoptions() {
        if (isCallAjax) {
            /*****Load Cache*****/
            var data = storageGet("setting");
            if (data != 'null') {
                $('body').prepend(data);
                $('#themecolor1').minicolors();
                $('#themecolor2').minicolors();
                $('#themebgcolor2').minicolors();
                $('#themebodybgcolor').minicolors();
                $('#themeCustomTitleColor').minicolors();
                loadJs();
                setRequestTheme(demo_theme);
                getCustomSetting();
                getCustomFontSettingOnPageLoad();
            }
            /*****Load Cache*****/
            isCallAjax = false;
            $.ajax({
                type: 'POST',
                url: getThemeOptionsLink,
                success: function(data) {
                    var dataCache = storageGet("setting");
                    storageSet("setting", data);
                    if (dataCache === 'null') {
                        $('body').prepend(data);
                        $('#themecolor1').minicolors();
                        $('#themecolor2').minicolors();
                        $('#themebgcolor2').minicolors();
                        $('#themebodybgcolor').minicolors();
                        $('#themeCustomTitleColor').minicolors();
                        loadJs();
                        setRequestTheme(demo_theme);
                        getCustomSetting();
                        getCustomFontSettingOnPageLoad();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }

    }
    //setTimeout(function(){gettvcmsthemeoptions()},1000);
    themevoltyCallEventsPush(gettvcmsthemeoptions, null);
    /*$(document).on('scroll', function() {
    });*/


    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    function replaceAll(str, find, replace) {
        return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
    }

    function idealTextColor(bgColor) {

        var nThreshold = 105;
        var components = getRGBComponents(bgColor);
        var bgDelta = (components.R * 0.299) + (components.G * 0.587) + (components.B * 0.114);

        return ((255 - bgDelta) < nThreshold) ? "#000000" : "#ffffff";
    }

    function getRGBComponents(color) {

        var r = color.substring(1, 3);
        var g = color.substring(3, 5);
        var b = color.substring(5, 7);

        return {
            R: parseInt(r, 16),
            G: parseInt(g, 16),
            B: parseInt(b, 16)
        };
    }

    function moveDataInMobileView(desktopClass, mobileClass) {
        if ($(desktopClass).html() != undefined && $(mobileClass).html() != undefined) {
            var html = '' + $(desktopClass).html();
            if (html != '') {
                $(mobileClass).html(html);
                //$(desktopClass).html('');
            }
        }
    }

    function moveDataInDesktopView(desktopClass, mobileClass) {
        if ($(desktopClass).html() != undefined && $(mobileClass).html() != undefined) {
            var html = '' + $(mobileClass).html();
            if (html != '') {
                $(desktopClass).html(html);
                //$(mobileClass).html('');
            }
        }
    }

    function showView($find, $header) {
        if (document.body.clientWidth <= mobileViewSize) { //for mobile view
            var findHeaderLayout = ".mh" + $find;
            var headerLayout = ".mh" + $header;
            
            moveDataInMobileView(findHeaderLayout + ' .tvmobileheader-offer-wrapper', headerLayout + ' .tvmobileheader-offer-wrapper');
            moveDataInMobileView(findHeaderLayout + ' #tvmobile-megamenu', headerLayout + ' #tvmobile-megamenu');
            moveDataInMobileView(findHeaderLayout + ' #tvcmsmobile-header-logo', headerLayout + ' #tvcmsmobile-header-logo');
            moveDataInMobileView(findHeaderLayout + ' #tvcmsmobile-search', headerLayout + ' #tvcmsmobile-search');
            moveDataInMobileView(findHeaderLayout + ' #tvmobile-cart', headerLayout + ' #tvmobile-cart');
            moveDataInMobileView(findHeaderLayout + ' #tvmobile-lang', headerLayout + ' #tvmobile-lang');
            moveDataInMobileView(findHeaderLayout + ' #tvmobile-curr', headerLayout + ' #tvmobile-curr');
            moveDataInMobileView(findHeaderLayout + ' #tvcmsmobile-account-button', headerLayout + ' #tvcmsmobile-account-button');
            moveDataInMobileView(findHeaderLayout + ' .tvcmsmobile-contact', headerLayout + ' .tvcmsmobile-contact');

            //moveDataInMobileView('#tvcmsmobile-vertical-menu', headerLayout + ' #tvcmsmobile-vertical-menu');
            moveDataInMobileView(findHeaderLayout + ' .tvmobile-compare', headerLayout + ' .tvmobile-compare');
            moveDataInMobileView(findHeaderLayout + ' .tvmobile-wishlist', headerLayout + ' .tvmobile-wishlist');
            // /moveDataInMobileView('#tvcmsmobile-vertical-menu', headerLayout + ' #tvcmsmobile-vertical-menu');
        } else { //for desktop view
            var findHeaderLayout = ".header-" + $find;
            var headerLayout = ".header-" + $header;
            moveDataInMobileView(findHeaderLayout + ' .tvheader-offer-wrapper', headerLayout + ' .tvheader-offer-wrapper');
            moveDataInMobileView(findHeaderLayout + ' #tvcmsdesktop-logo', headerLayout + ' #tvcmsdesktop-logo');
            moveDataInMobileView(findHeaderLayout + ' #_desktop_cart_manage', headerLayout + ' #_desktop_cart_manage');
            moveDataInMobileView(findHeaderLayout + ' #tvdesktop-megamenu', headerLayout + ' #tvdesktop-megamenu');
            moveDataInMobileView(findHeaderLayout + ' #_desktop_search', headerLayout + ' #_desktop_search');
            moveDataInMobileView(findHeaderLayout + ' #tvcmsdesktop-account-button', headerLayout + ' #tvcmsdesktop-account-button');
            moveDataInMobileView(findHeaderLayout + ' .tvheader-compare', headerLayout + ' .tvheader-compare');
            moveDataInMobileView(findHeaderLayout + ' .ttvcms-wishlist-icon', headerLayout + ' .ttvcms-wishlist-icon');
            moveDataInMobileView(findHeaderLayout + ' .tvcmsdesktop-contact', headerLayout + ' .tvcmsdesktop-contact');
            moveDataInMobileView(findHeaderLayout + ' .tvheader-language', headerLayout + ' .tvheader-language');
            moveDataInMobileView(findHeaderLayout + ' .tvheader-currency', headerLayout + ' .tvheader-currency');
            // moveDataInMobileView('#tvcmsdesktop-vertical-menu', '#tvcmsmobile-vertical-menu');
        }
        $(findHeaderLayout).hide();        
    } //showView
    function setCustomHLayout() {        
        if (document.body.clientWidth > mobileViewSize) { //for mobile view
            var header = storageGet("theme-header-layout");
            var findHeaderLayout = $('.tvcmsdesktop-top-header-wrapper:visible').attr("data-header-layout");
            if ((!$('.tvcmsdesktop-top-header-wrapper').hasClass('header-' + header)) && (header !== undefined) && (header != 'null')){
                if(stopRefreshStatus){
                    $('html, body').animate({
                        scrollTop:  0
                    }, 800);
                }
                $.get(layoutPath + "desk-header-" + header + ".tvtpl", function(dataResult) {
                    dataResult = replaceAll(dataResult, '{*', '<!--');
                    dataResult = replaceAll(dataResult, '*}', '-->');
                    $('#header').append(dataResult);
                    showView(findHeaderLayout, header);
                });
            } else {
                $('.tvcmsdesktop-top-header-wrapper').hide();
                $('.tvcmsdesktop-top-header-wrapper.header-' + header).show();
            }
        }
    }
    function setCustomMHLayout() {
        if (document.body.clientWidth <= mobileViewSize) { //for mobile view
            var header = storageGet("theme-mobile-layout");
            var findHeaderLayout = $('.tvheader-mobile-layout:visible').attr("data-header-mobile-layout");
            if ((!$('.tvheader-mobile-layout').hasClass('mh' + header)) && header !== undefined  && header != 'null') {
                if(stopRefreshStatus){
                    $('html, body').animate({
                        scrollTop:  0
                    }, 800);
                }
                $.get(layoutPath + "mobile-header-" + header + ".tvtpl", function(dataResult) {
                    dataResult = replaceAll(dataResult, '{*', '<!--');
                    dataResult = replaceAll(dataResult, '*}', '-->');
                    $('#header').append(dataResult);
                    showView(findHeaderLayout, header);
                });
            } else {
                $('.tvheader-mobile-layout').hide();
                $('.tvheader-mobile-layout.mh' + header).show();
            }
        }
    }
    $(document).on("change", 'input[name="header-mobile-layout"]', function() {
        storageSet("theme-mobile-layout", $(this).val());
        setCustomMHLayout();
    });
    $(document).on("change", 'input[name="header-layout"]', function() {
        storageSet("theme-header-layout", $(this).val());
        setCustomHLayout();
    });
    function makeLayoutUrl(refresh){        
        var currUrl = window.location.href.split('#')[0];
        currUrl = currUrl.split('?')[0];
        currUrl += ((currUrl.indexOf('?') > 0)?"&":"?");
        var param = '';
        if(storageGet("theme-header-layout") != 'null'){
            param += "hl="+storageGet("theme-header-layout");
        }

        if(storageGet("theme-mobile-layout") != 'null'){
            param += "&mhl="+storageGet("theme-mobile-layout");
        }

        if(storageGet("theme-footer-layout") != 'null'){
            param += "&fl="+storageGet("theme-footer-layout");
        }

        if(storageGet("theme-product-layout") != 'null'){
            if($('body#product').length == 0 && (storageGet("theme-product-layout-redirect") == 'true')){                
                currUrl = $('article a.product-thumbnail').eq(1).attr('href');
                currUrl = currUrl.split('#')[0];
                currUrl = currUrl.split('?')[0];
                currUrl += ((currUrl.indexOf('?') > 0)?"&":"?");
            }
            param += "&pdl="+storageGet("theme-product-layout");
        }

        if(refresh & stopRefreshStatus){
            location.href = currUrl + param;
        }
    }
    $(document).on("change", 'input[name="footer-layout"]', function() {
        storageSet("theme-footer-layout", $(this).val());
        if(stopRefreshStatus){//not run for default setting
            storageSet("theme-footer-layout-scroll", true);
        }
        makeLayoutUrl(true);
    });
    $(document).on("change", 'input[name="product-layout"]', function() {
        storageSet("theme-product-layout", $(this).val());
        if(stopRefreshStatus){//not run for default setting
            storageSet("theme-product-layout-redirect", true);
        }
        makeLayoutUrl(true);
    });    

    function setCustomeTheme() {
        if ((storageGet("theme_color") != undefined && storageGet("theme_color") != '') || (storageGet("theme_color2") != undefined && storageGet("theme_color2") != '')) {
            //$('#themecolor1').val(storageGet("theme_color"));
            //$('#themecolor2').val(storageGet("theme_color2"));
            if (cssDataResult == "") {
                $.get(cssPath + "theme-custom.css", function(dataResult) {
                    cssDataResult = dataResult;
                    setCustomCssValues(cssDataResult);
                });
            } else {
                setCustomCssValues(cssDataResult);
            }
            setBodyTextClass();
        }
    }

    function setBodyTextClass() {
        var TextColor1 = idealTextColor(storageGet("theme_color"));
        var TextColor2 = idealTextColor(storageGet("theme_color2"));
        $('body').removeClass("text1-light");
        $('body').removeClass("text1-dark");
        $('body').removeClass("text2-light");
        $('body').removeClass("text2-dark");
        if (TextColor1 == "#ffffff") {
            $('body').addClass("text1-light");
        } else {
            $('body').addClass("text1-dark");
        }
        if (TextColor2 == "#ffffff") {
            $('body').addClass("text2-light");
        } else {
            $('body').addClass("text2-dark");
        }
    }

    function setCustomCssValues(dataResult) {
        var TextColor1 = idealTextColor(storageGet("theme_color"));
        var TextColor2 = idealTextColor(storageGet("theme_color2"));
        setBodyTextClass();

        dataResult = replaceAll(dataResult, '#maincolor1', storageGet("theme_color"));
        dataResult = replaceAll(dataResult, '#maincolor2', storageGet("theme_color2"));
        dataResult = replaceAll(dataResult, '#maincolortext1', TextColor1);
        dataResult = replaceAll(dataResult, '#maincolortext2', TextColor2);

        var AltTextColor1 = (TextColor1 == '#000000') ? '#ffffff' : "#000000";
        var AltTextColor2 = (TextColor2 == '#000000') ? '#ffffff' : "#000000";
        dataResult = replaceAll(dataResult, '#altcolortext1', AltTextColor1);
        dataResult = replaceAll(dataResult, '#altcolortext2', AltTextColor2);

        $('.tvcms-custom-theme .custom-css').remove('');
        $(".tvcms-custom-theme").append("<div class='custom-css'><style>" + dataResult + "</style><div>");
    }
    $(document).on("change", 'input[name="TVCMSCUSTOMSETTING_LIGHT_DARK_MODE_INPUT"]', function() {
        $('.tvcheck-popup').hide();
        $(this).parent().find('.tvcheck-popup').show();
        storageSet("theme-dark-mode", $(this).val());
        setDarkMode();
    });

    

    function manmamanageDefaultVal() {
        $(document).on('click', '.lightModeWrapper', function() {
            if ($('.dark-theme-css-r').find()) {
                $('.dark-theme-css-r').remove();
            }
        });

        if (TVCMSCUSTOMSETTING_DARK_MODE_INPUT == 1) {
            storageSet("theme-dark-mode", 1);
        } else if (TVCMSCUSTOMSETTING_DARK_MODE_INPUT == 0) {
            storageSet("theme-dark-mode", 0);
        } else if (TVCMSCUSTOMSETTING_DARK_MODE_INPUT != '' && TVCMSCUSTOMSETTING_DARK_MODE_INPUT != undefined) {
            storageSet("theme-dark-mode", 0);
        }
    }
    // manmamanageDefaultVal();

    function setDarkMode() {
        if (storageGet("theme-dark-mode") == 0) {
            if($('body').hasClass('dark-mode-active')){
                $('body').removeClass('dark-mode-active');
            }
            $('.tvtheme-theme-mode .lightModeWrapper .tvcheck-popup').show();
            $('.tvtheme-theme-mode .lightModeWrapper input').prop('checked', true);
            $('.tvcms-custom-theme .dark-mode-css').remove('');
        } else {
            $('.tvtheme-theme-mode .darkModeWrapper .tvcheck-popup').show();
            if(!$('body').hasClass('dark-mode-active')){
                $('body').addClass('dark-mode-active');    
            }
            $('.tvtheme-theme-mode .darkModeWrapper input').prop('checked', true);
            $.get(cssPath + "dark-theme.css", function(data) {
                $('.tvcms-custom-theme .dark-mode-css').remove('');
                data = replaceAll(data, 'url(..\/img\/', 'url(' + prestashop.urls.img_url);
                $(".tvcms-custom-theme").append("<div class='dark-mode-css'><style>" + data + "</style><div>");
            });
        }
    }

    if(TVCMSCUSTOMSETTING_DARK_MODE_INPUT == 1){
        $('body').addClass('dark-mode-active');
    }

    function setBoxLayoutTheme() {
        if (storageGet("theme-bg-status") == "pattern") {
            $('body').css('background-image', storageGet("theme-bg-pattern"));
            $('body').css('background-color', '');

            $('.tvtheme-all-pattern-wrapper').addClass('open');
            $('.tvtheme-bgcolor-box').removeClass('open');
        } else {
            $('body').css('background-color', storageGet("theme-bg-color"));
            $('body').css('background-image', "");
            $('#themebgcolor2').val(storageGet("theme-bg-color"));
            $('#themebgcolor2').parent().find('.minicolors-swatch-color').css('background-color', storageGet("theme-bg-color"))

            $('.tvtheme-bgcolor-box').addClass('open');
            $('.tvtheme-all-pattern-wrapper').removeClass('open');
        }
    }

    function setBoxLayout(obj) {
        // if($(obj).find('.toggle.btn.btn-default').hasClass('off')){            
        if ($(obj).val() == "box") {
            $('.tv-main-div').addClass('tv-box-layout container');
            $('.tv-main-div').removeClass('tv-full-layout');
            $('.box-block').addClass('open');
            storageSet("box-layout", "box"); //save localStorage
            setBoxLayoutTheme();
        } else {
            $('.tv-main-div').addClass('tvcms-full-layout');
            $('.tv-main-div').removeClass('tv-box-layout container');
            $('.box-block').removeClass('open');
            storageSet("box-layout", "wide"); //save localStorage
        }
    }

    function setBodyLayoutTheme() {
        if (storageGet("theme-body-bgcolor-effect") == 'color') {
            $('.tv-main-div').css('background-color', storageGet("theme-body-bgcolor"));
            $('.tv-main-div').css('background-image', "");
            $('#themebodybgcolor').val(storageGet("theme-body-bgcolor"));
            $('#themebodybgcolor').parent().find('.minicolors-swatch-color').css('background-color', storageGet("theme-body-bgcolor"));

            $('.tvbody-bgcolor-wrapper.tvtheme-body-bgcolor').addClass('open');
            $('.tvtheme-body-background-patten').removeClass('open');
        } else {
            $('.tv-main-div').css('background-image', storageGet('theme-body-bgimage'));

            $('.tvtheme-body-background-patten').addClass('open');
            $('.tvbody-bgcolor-wrapper.tvtheme-body-bgcolor').removeClass('open');
        }
    }

    function setBodyLayout(obj) {
        if ($(obj).val() == "default") {
            $('.bg-block').removeClass('open');
            $('.tv-main-div').removeAttr('style');
            storageSet("theme-body-bgcolor-status", "default"); //save localStorage
        } else {
            setBodyLayoutTheme();
            $('.bg-block').addClass('open');
            storageSet("theme-body-bgcolor-status", "custom"); //save localStorage
        }
    }
    $(document).on('change', '.tvtheme-box-layout input[name="box-themes"]', function() {
        storageSet("theme-bg-status", $(this).val());
        setBoxLayoutTheme();
    });

    $(document).on('click', '.tvtheme-background-layout input[name="body-themes"]', function() {
        storageSet("theme-body-bgcolor-effect", $(this).val());
        setBodyLayoutTheme();
    });

    function getPattenSetting(obj) {
        $(obj).addClass('active');
        $('.tvtheme-pattern-image').removeClass("active");
        $('.tvtheme-pattern-image').each(function() {
            if ("url(" + $(this).attr('data-background-image-url') + ")" == storageGet("theme-bg-pattern")) {
                $(this).addClass('active');
            }
        });
        $('body').css('background-image', "url(" + storageGet("theme-bg-pattern") + ")");
        $('body').css('background-color', '');
    }

    function getBgColorSetting(obj) {
        $('body').css('background-color', storageGet("theme-bg-color"));
        $('body').css('background-image', "");
        obj.parent().find('.minicolors-swatch-color').css('background-color', storageGet("theme-bg-color"));
    }

    function getCustomSetting() {
        if (storageGet("themeControl") == 'null') {
            resetCustomSetting();
        }
        if (storageGet("theme").match(/theme_custom/g)) {
            $('.tvselect-theme #select_theme input[value="' + storageGet("theme") + '"]').attr('data-color', storageGet("theme_color"));
            $('.tvselect-theme #select_theme input[value="' + storageGet("theme") + '"]').attr('data-color-two', storageGet("theme_color2"));
            $('.tvtheme-color-one').show();
            $('.tvtheme-color-two').show();
            $('#themecolor1').val(storageGet("theme_color"));
            $('#themecolor2').val(storageGet("theme_color2"));
            $('#themecolor1').parent().find('.minicolors-swatch-color').css('background-color', storageGet("theme_color"));
            $('#themecolor2').parent().find('.minicolors-swatch-color').css('background-color', storageGet("theme_color2"));
        } else {
            $('.tvtheme-color-one').hide();
            $('.tvtheme-color-two').hide();
        }
        $('.tvselect-theme #select_theme input[value="' + storageGet("theme") + '"]').trigger('click');
        /******* selection default layout *******/
        storageSet("theme-product-layout-redirect", false);
        if($("#checkout").length == 0) {
              $('#headerLayout input[value="' + storageGet("theme-header-layout") + '"]').trigger('click');
              $('#headerMobileLayout input[value="' + storageGet("header-mobile-layout") + '"]').trigger('click');
              $('#footerLayout input[value="' + storageGet("theme-footer-layout") + '"]').trigger('click');
              $('#productLayout input[value="' + storageGet("theme-product-layout") + '"]').trigger('click');
            }
        stopRefreshStatus = true;// start change layout to refresh.
        if(storageGet("theme-footer-layout-scroll") == 'true'){
            $('html, body').animate({
                scrollTop:  $(document).height()
            }, 800);
        }
        setCustomeTheme();
        setDarkMode();
        if (storageGet("box-layout") == "box") {
            var obj = $(".tvtheme-box-layout-option");
            setBoxLayout(obj);
            if (storageGet("theme-bg-status") == "pattern") {
                getPattenSetting($('.tvtheme-pattern-image'));
            } else {
                getBgColorSetting($('#themebgcolor2'));
            }
            $('.box-block input[value="' + storageGet("theme-bg-status") + '"]').trigger('click');
            setBoxLayoutTheme();
            obj.trigger('click');
        } else {
            $('.box-block').removeClass('open');
        }
        if (storageGet("theme-body-bgcolor-status") == "custom") {
            $('input[value="' + storageGet("theme-body-bgcolor-status") + '"]').trigger('click');
            $('.bg-block').addClass('open');
            $('.bg-block input[value="' + storageGet("theme-body-bgcolor-effect") + '"]').trigger('click');
            setBodyLayoutTheme();
        }

        if (!storageGet("menu-sticky")) {
            $('.tvtheme-menu-sticky-option').trigger('click');
        }
    }

    $(document).on('click', '#tvtheme-reset-layout svg', function() {
        storageSet("theme-bg-color", '#000');
        $('body').css('background-color', '#000');
        $('#themebgcolor2').val('#000');
        $('.tvtheme-bgcolor-box .minicolors-swatch-color').css('background-color', '#000');
    });


    $(document).on('click', '#tvtheme-reset-bgcolor svg', function() {
        storageSet("theme-body-bgcolor", "#f5f5f5");
        $('.tv-main-div').css('background-color', '#f5f5f5');
        $('.tvbody-bgcolor-wrapper .minicolors-swatch-color').removeAttr('style');
        $('#themebodybgcolor').val('');
    });

    $(document).on('click', '.tvselect-title-font-1 svg', function() {
        storageSet("theme-title-color", "#333");
        if (storageGet("theme-title-color") != undefined && storageGet("theme-title-color") != '') {
            $.get(cssPath + "theme-custom-title-color.css", function(data) {
                data = replaceAll(data, '#customTitleColor', storageGet("theme-title-color"));
                returnData = data;
                $('#themeCustomTitleColor').val('');
                $('#themeCustomTitleColor').parent().find('.minicolors-swatch-color').removeAttr('style');
                // $(".tvcms-custom-font").html($(".tvcms-custom-font").html()+"<style>"+returnData+"</style>");
                $('.tvcms-custom-color').html('<style>' + returnData + '</style>');
            });
        }
    });

    function resetCustomSetting() {
        storage.removeAll();
        storageSet("themeControl", true);
        storageSet("theme", "");
        storageSet("theme_color", "");
        storageSet("theme_color2", "");
        storageSet("box-layout", "wide");
        storageSet("menu-sticky", true);
        storageSet("themeColor1", '#fff'); //default color1
        storageSet("themeColor2", '#ededed'); //default color1
        storageSet("theme-bg-pattern", "url(" + prestashop.urls.img_url + "pattern/pattern14.png)"); //save storage
        storageSet("theme-bg-color", '#000'); //default color2
        storageSet("theme-bg-status", "color"); //patten bg dafault on box layout on*/
        storageSet("theme-body-bgcolor-status", "default");
        storageSet("theme-body-bgcolor-effect", 'color');
        storageSet("theme-body-bgcolor", "");
        storageSet("theme-body-bgimage", "");
        storageSet("theme-custom-font-1", "");
        storageSet("theme-custom-font-link-1", "");
        storageSet("theme-custom-font-2", "");
        storageSet("theme-custom-font-link-2", "");
        storageSet("theme-title-color", "");
        storageSet("theme-dark-mode", 0);

        storageSet("theme-header-layout", null);
        storageSet("header-mobile-layout", null);
        storageSet("theme-footer-layout", null);
        storageSet("theme-product-layout", null);
        storageSet("theme-footer-layout-scroll", false);
        storageSet("theme-product-layout-redirect", false);
    }

    function getCustomFontSettingOnPageLoad() {
        setCustomeFontTheme(1); //title font 1
        setCustomeFontTheme(2); //body font 2
        setCustomeFontTheme(3); //title color 3
    }

    function setCustomeFontTheme($dataCall) {
        var returnData = "";
        /******************Title Font********************/
        if (storageGet("theme-custom-font-1") != undefined && storageGet("theme-custom-font-1") != '' && $dataCall == 1) {
            $('.tvselect-title-font-1-select').val(storageGet("theme-custom-font-1"));
            $.get(cssPath + "theme-custom-title-font.css", function(data) {
                var link_1 = '';
                data = replaceAll(data, '#fontFamily1', storageGet("theme-custom-font-1"));
                link_1 = '@import url(\'' + storageGet("theme-custom-font-link-1") + '\');';
                returnData += link_1 + ' ' + data;
                // $(".tvcms-custom-font").html($(".tvcms-custom-font").html()+"<style>"+returnData+"</style>");
                $('.tvcms-custom-font-1').html("<style>" + returnData + "</style>");
            });
        } else if (storageGet("theme-custom-font-1") == '') {
            $('.tvcms-custom-font-1').html('');
        }
        /******************title color********************/
        if (storageGet("theme-title-color") != undefined && storageGet("theme-title-color") != '' && $dataCall == 3) {
            $.get(cssPath + "theme-custom-title-color.css", function(data) {
                data = replaceAll(data, '#customTitleColor', storageGet("theme-title-color"));
                returnData = data;
                $('#themeCustomTitleColor').val(storageGet("theme-title-color"));
                $('#themeCustomTitleColor').parent().find('.minicolors-swatch-color').css('background-color', storageGet("theme-title-color"));
                // $(".tvcms-custom-font").html($(".tvcms-custom-font").html()+"<style>"+returnData+"</style>");
                $('.tvcms-custom-color').html('<style>' + returnData + '</style>');
            });
        } else if (storageGet("theme-title-color") == '') {
            $('.tvcms-custom-color').html('');
        }
        /******************Body Font********************/
        if (storageGet("theme-custom-font-2") != undefined && storageGet("theme-custom-font-2") != '' && $dataCall == 2) {
            $('.tvselect-title-font-2-select').val(storageGet("theme-custom-font-2"));
            $.get(cssPath + "theme-custom-body-font.css", function(data) {
                var link_2 = '';
                data = replaceAll(data, '#fontFamily2', storageGet("theme-custom-font-2"));
                link_2 = '@import url(\'' + storageGet("theme-custom-font-link-2") + '\');';
                returnData = link_2 + ' ' + data;
                // $(".tvcms-custom-font").html($(".tvcms-custom-font").html()+"<style>"+returnData+"</style>");
                $('.tvcms-custom-font-2').html('<style>' + returnData + '</style>');
            });
        } else if (storageGet("theme-custom-font-2") == '') {
            $('.tvcms-custom-font-2').html('');
        }
    }

    function loadJs() {
        $(".tvcms-custom-font-1").html('');
        $(".tvcms-custom-font-2").html('');
        $(".tvcms-custom-color").html('');

        $(document).on('change', '.tvtheme-menu-sticky-option', function(e) {
            if ($(this).val() == "sticky") {
                storageSet("menu-sticky", true); //save localStorage
                $('.tvcms-header-menu-offer-wrapper').addClass('tvcmsheader-sticky');
            } else {
                storageSet("menu-sticky", false); //save localStorage  
                $('.tvcms-header-menu-offer-wrapper').removeClass('tvcmsheader-sticky');
            }
        });

        $(document).on('click', '.tvcmstheme-control .tvtheme-control-icon', function(e) {
            var themeOptionWrapper = $('.tvcmstheme-control .tvtheme-control-wrapper');
            if (themeOptionWrapper.hasClass('open')) {
                themeOptionWrapper.removeClass('open');
                $('.tvcmstheme-control').removeClass('open');
                // $('body').removeClass('cart-open');
            } else {
                themeOptionWrapper.addClass('open');
                $('.tvcmstheme-control').addClass('open');
                $('.tvcmstheme-layout').removeClass('open');
                $('.tvcmstheme-layout .tvtheme-layout-wrapper').removeClass('open');
                // $('body').addClass('cart-open');
            }
        });

        $(document).on('click', '.tvcmstheme-layout .tvtheme-layout-icon', function(e) {
            var themeOptionWrapper = $('.tvcmstheme-layout .tvtheme-layout-wrapper');
            if (themeOptionWrapper.hasClass('open')) {
                themeOptionWrapper.removeClass('open');
                $('.tvcmstheme-layout').removeClass('open');
                // $('body').removeClass('layout-open');
            } else {
                themeOptionWrapper.addClass('open');
                $('.tvcmstheme-layout').addClass('open');
                $('.tvcmstheme-control').removeClass('open');
                $('.tvcmstheme-control .tvtheme-control-wrapper').removeClass('open');

                if (document.body.clientWidth <= mobileViewSize) { //for mobile view
                    if($('.tvcmstheme-layout button[data-target=#headerMobileLayout]').hasClass('collapsed')){
                        $('.tvcmstheme-layout button[data-target=#headerMobileLayout]').trigger('click');
                    }
                }else{
                    if($('body#product .tvcmstheme-layout button[data-target=#productLayout]').hasClass('collapsed') && (storageGet("theme-footer-layout-scroll") == 'false')){
                        $('body#product .tvcmstheme-layout button[data-target=#productLayout]').trigger('click');
                    }else if(storageGet("theme-footer-layout-scroll") == 'true'){
                        $('.tvcmstheme-layout button[data-target=#footerLayout]').trigger('click');
                        storageSet("theme-footer-layout-scroll", false);//reset footer scroll event.
                    }
                }
                // $('body').addClass('layout-open');
            }
        });

        $(document).on('change', '.tvselect-theme #select_theme input', function(e) {
            e.preventDefault();
            var themeVal = $(this).val();
            var themeColorVal = $(this).attr('data-color');
            var themeColorVal2 = $(this).attr('data-color-two');
            storageSet("theme", themeVal); //save localStorage
            storageSet("theme_color", themeColorVal); //save localStorage
            storageSet("theme_color2", themeColorVal2); //save localStorage

            $('.tvtheme-color-one').hide().animate({ height: 0, opacity: 0 }, 200);
            $('.tvtheme-color-two').hide().animate({ height: 0, opacity: 0 }, 200);
            if (themeVal == "") {
                $('.tvcms-custom-theme .custom-css').remove();
                $('.minicolors .themecolor1').hide();
                $('.minicolors .themecolor2').hide();
            } else if (themeVal.match(/theme_custom/g)) {
                $('.tvtheme-color-one').show().animate({ height: 73, opacity: 1 }, 200);
                $('.tvtheme-color-two').show().animate({ height: 73, opacity: 1 }, 200);
                var theme_color = $('#themecolor1').val();
                var theme_color2 = $('#themecolor2').val();
                storageSet("theme_color", theme_color);
                storageSet("theme_color2", theme_color2);
                setCustomeTheme();
            } else {
                setCustomeTheme();
            }
        });

        $(document).on('change', '#themecolor1', function(e) {
            var theme_color = $(this).val();
            storageSet("theme_color", theme_color);
            setCustomeTheme();
        });

        $(document).on('change', '#themecolor2', function(e) {
            var theme_color2 = $(this).val();
            storageSet("theme_color2", theme_color2);
            setCustomeTheme();
        });

        $(document).on('change', 'input.box-layout-option', function(e) {
            setBoxLayout(this);
        });
        $(document).on('change', 'input.body-layout-option', function(e) {
            setBodyLayout(this);
        });

        $(document).on('click', '.tvtheme-pattern-image', function(e) {
            $('.tvtheme-pattern-image').removeClass('active');
            $(this).addClass('active');
            $('body').css('background-image', "url(" + $(this).attr('data-background-image-url') + ")");
            storageSet("theme-bg-pattern", "url(" + $(this).attr('data-background-image-url') + ")"); //save localStorage
            $('body').css('background-color', '');
            storageSet("theme-bg-status", "pattern");
        });
        $(document).on('change', '#themebgcolor2', function(e) {
            $('body').css('background-color', $(this).val());
            $('body').css('background-image', "");
            storageSet("theme-bg-status", "color");
            storageSet("theme-bg-color", $(this).val());
        });

        $(document).on('change', '#themebodybgcolor', function(e) {
            $('.tv-main-div').removeAttr('style');
            $('.tv-main-div').css('background-color', $(this).val());
            storageSet("theme-body-bgcolor", $(this).val());
            storageSet("theme-body-bgcolor-effect", 'color');
        });

        $(document).on('click', '.tvtheme-body-pattern-image', function(e) {
            $('.tvtheme-body-pattern-image').removeClass('active');
            $('.tv-main-div').removeAttr('style');
            $(this).addClass('active');
            // $(this).css('background-image')
            var tmp = $(this).attr('data-background-image-url');
            $('.tv-main-div').css('background-image', 'url(' + tmp + ')');
            storageSet("theme-body-bgimage", 'url(' + tmp + ')'); //save localStorage
            storageSet("theme-body-bgcolor-effect", 'pattern');
        });

        $(document).on('change', '.tvselect-title-font-1 #select_title_font_1', function(e) {
            var font_title = $(this).val();
            var font_link = $(this).find('option:selected').attr('data-font-link');
            storageSet("theme-custom-font-1", font_title);
            storageSet("theme-custom-font-link-1", font_link);
            setCustomeFontTheme(1);
        });

        $(document).on('change', '.tvselect-title-font-2 #select_title_font_2', function(e) {
            var font_title = $(this).val();
            var font_link = $(this).find('option:selected').attr('data-font-link');
            storageSet("theme-custom-font-2", font_title);
            storageSet("theme-custom-font-link-2", font_link);
            setCustomeFontTheme(2);
        });

        $(document).on('change', '#themeCustomTitleColor', function(e) {
            storageSet("theme-title-color", $(this).val());
            setCustomeFontTheme(3);
        });

        $(document).on('click', '.tvtheme-control-reset, .tvtheme-layout-reset', function(e) {
            e.preventDefault();
            resetCustomSetting();
            var currUrl = window.location.href.split('#')[0];
            currUrl = currUrl.split('?')[0];
            currUrl += ((currUrl.indexOf('?') > 0)?"&":"?");
            location.href = currUrl;
        });
    }
});
//==========================================