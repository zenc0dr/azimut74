function injectReady() {
    $('.swiper-container').css('width', $('.injector-body .content-wrap').width() + 'px')

    $('.inject-reviews').show()

    let mySwiper = new Swiper('.swiper-container', {
        loop: true,
        pagination: {
            el: '.swiper-pagination',
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
}