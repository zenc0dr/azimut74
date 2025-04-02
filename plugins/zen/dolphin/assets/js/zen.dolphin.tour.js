
function dolphinGalleryFull() {
    $('.dgf-big-images').owlCarousel({
        loop: false,
        margin: 0,
        items: 1,
        onDragged: imageActions,
        onInitialized: imageActions,
    });
    // $('.smallImages a:first-child div').addClass('active');

    // Это функция для того чтобы помечать мелкие картинки активными
    function imageActions() {
        let hash = $('.dgf-big-images .owl-item.active .dgf-preview').attr('data-hash');
        $('.dgf-small-images .dgf-image').removeClass('active');
        $('.dgf-small-images a[href="#'+hash+'"]').find('.dgf-image').addClass('active');
    }
}


// Для маленьких только наоборот
$(document).on('click', '.dgf-small-images .dgf-image', function() {
    $('.dgf-small-images .dgf-image').removeClass('active');
    $(this).addClass('active');
});

$(document).on('click', '.dgf-more-images', function () {
    let wrap = $(this).prev();

    if(wrap.css('display') == 'none') {
        $(this).text('- меньше фото')
    } else {
        $(this).text('+ ещё фото')
    }

    wrap.slideToggle()
})


dolphinGalleryFull()

/* Раскрывашка для инфоблоков отеля */
$(document).on('click', '.dhc-block', function () {
    let ct = $(this).next();
    if(ct.css('display') == 'none') {
        $(this).find('.dhc-block-dropdown div').css('transform', 'rotate(-90deg)')
    } else {
        $(this).find('.dhc-block-dropdown div').css('transform', 'rotate(90deg)')
    }
    ct.slideToggle()
})
