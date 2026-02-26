/**
 * 2007-2025 PrestaShop
 * ThemeVolty Slider JS - Fixed Speed & Stability
 */
jQuery(document).ready(function($) {
    
    // --- VIDEO CONTROLS ---
    var videoPlayStatus = true;
    $('.tvslider-video-play').click(function() {
        var video = $('#tvmain-slider .owl-item.active .tvslider-video');
        if ($('.tvslider-video-play').hasClass("active")) {            
            videoPlayStatus = false;
            video.trigger('pause');
            $('.tvslider-video-play').html("<i class='material-icons'>play_arrow</i>");
            $('.tvslider-video-play').removeClass("active");
        } else {
            videoPlayStatus = true;
            video.trigger('play');
            $('.tvslider-video-play').html("<i class='material-icons'>pause</i>");
            $('.tvslider-video-play').addClass("active");
        }
    });
    $('.tvslider-video-mute').click(function() {
        var video = $('#tvmain-slider .owl-item .tvslider-video');
        if ($('.tvslider-video-mute').hasClass("active")) {            
            video.prop('muted', true);
            $('.tvslider-video-mute').html("<i class='material-icons'>volume_off</i>");
            $('.tvslider-video-mute').removeClass("active");
        } else {            
            video.prop('muted', false);
            $('.tvslider-video-mute').html("<i class='material-icons'>volume_up</i>");
            $('.tvslider-video-mute').addClass("active");
        }
    });

    // --- CONFIGURATION ---
    // Czas trwania slajdu (pobrany z panelu)
    var tvMainSliderSpeed = jQuery('.tvcmsmain-slider-wrapper').attr('data-speed');
    
    // ZABEZPIECZENIE: Jeśli puste lub za małe, ustaw 5000ms
    if (!tvMainSliderSpeed || tvMainSliderSpeed < 1000) {
        tvMainSliderSpeed = 5000;
    }

    var tvMainSliderPause = '';
    if (jQuery('.tvcmsmain-slider-wrapper').attr('data-pause-hover') == 'true') {
        tvMainSliderPause = true;
    }
    
    // SZTYWNA PRĘDKOŚĆ PRZEJŚCIA (ANIMACJI) - To naprawia drganie
    var tvMainSmartSpeed = 800; 

    var mainSliderHomePage = jQuery('.tv-main-slider #tvmain-slider');
    
    // --- OWL CAROUSEL INIT ---
    mainSliderHomePage.owlCarousel({
        loop: true,
        dots: true,
        nav: true,
        margin: 0, // Zero marginesu = brak paska po lewej
        navText: ["<i class='material-icons'>&#xe5cb;</i>","<i class='material-icons'>&#xe5cc;</i>"], 
        smartSpeed: tvMainSmartSpeed, // Używamy sztywnej prędkości animacji
        autoplay: true,
        autoplayTimeout: tvMainSliderSpeed, // Czas wyświetlania slajdu
        autoplayHoverPause: tvMainSliderPause,
        autoHeight: false, 
        responsive: {
            0: { items: 1, nav: false, dots: true },
            768: { items: 1, slideBy: 1, nav: true, dots: true },
            1024: { items: 1, slideBy: 1, nav: true, dots: true },
            1399: { items: 1, slideBy: 1, nav: true, dots: true }
        },
    });

    // Custom buttons mapping
    jQuery('.tvmain-slider-next-pre-btn .tvcmsmain-prev').click(function(e) {
        e.preventDefault();
        jQuery('.tv-main-slider .owl-nav .owl-prev').trigger('click');
    });
    jQuery('.tvmain-slider-next-pre-btn .tvcmsmain-next').click(function(e) {
        e.preventDefault();
        jQuery('.tv-main-slider .owl-nav .owl-next').trigger('click');
    });

    $('#tvmain-slider').removeClass('tvcms-hide-owl');
    
    // Video logic
    mainSliderHomePage.on('translated.owl.carousel', function(event) {
        if($('#tvmain-slider .owl-item.active').find('video').length > 0){            
            var video = $('#tvmain-slider .owl-item.active .tvslider-video');
            var src = ""+video.attr('src');
            if(src.indexOf("undefined") == 0){
                var dataSrc = video.find('source').attr('data-src');
                video.removeAttr('data-src');
                video.attr('src',dataSrc);
            }
            var videoAll = $('#tvmain-slider .tvslider-video');
            videoAll.trigger('pause');          
            if(videoPlayStatus){
                setTimeout(function() {                    
                    video.trigger('play');
                }, 100);
            }
        } else {
           var video = $('#tvmain-slider .tvslider-video');
           setTimeout(function() {                    
                video.trigger('pause');
            }, 100);
        }
    });
});