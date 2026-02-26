jQuery(document).ready(function($) {
    var catSlider = $('#tv-category-slider');
    if (catSlider.length) {
        catSlider.owlCarousel({
            loop: false, rewind: true, dots: false, nav: true, margin: 15,
            navText: ["<i class='material-icons'>chevron_left</i>","<i class='material-icons'>chevron_right</i>"], 
            autoplay: false,
            responsive: {
                0: { items: 2, slideBy: 2, margin: 10 },
                576: { items: 3, slideBy: 3 },
                768: { items: 4, slideBy: 4 },
                992: { items: 5, slideBy: 1 },
                1200: { items: 6, slideBy: 1 },
                1400: { items: 7, slideBy: 1 }
            }
        });
    }
});