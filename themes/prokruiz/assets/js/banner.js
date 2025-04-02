new class BannerSlider {
    constructor() {
        this.sliderContainerSelector = '.banner .swiper-container';
        this.slideSelector = '.banner .swiper-slide';
        this.navigation = {
            nextEl: '.banner .swiper-button-next',
            prevEl: '.banner .swiper-button-prev',
            disabledClass: 'slider__btn_disable',
            hiddenClass: 'slider__btn_hidden',
        };

        this.handler();
    }

    handler() {
        document.addEventListener('DOMContentLoaded', () => {
            this.init();
        });
    }

    init() {
        const slider = document.querySelector(`${this.sliderContainerSelector}`);

        if (!slider) return;
        const slides = slider.querySelectorAll(`${this.slideSelector}`).length;
        const { navigation } =  this ;

        this.HeroSlider = new Swiper(`${this.sliderContainerSelector}`, {
            slidesPerView: 1,
            speed: 700,
            lazy: true,
            spaceBetween: 30,
            autoplay: slides > 1,
            watchOverflow: true,
            watchSlidesVisibility: true,
            watchSlidesProgress: true,
            observer: true,
            navigation,
            loop: slides > 1,
            on: {
                init: () => {
                 /*   LazyLoad.update();*/
                },
            },
        });
    }
}();