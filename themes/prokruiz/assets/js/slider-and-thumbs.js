// let swiper = new Swiper(".thumbSlider", {
//     loop: true,
//     spaceBetween: 10,
//     slidesPerView: 4,
//     freeMode: true,
//     watchSlidesVisibility: true,
//     watchSlidesProgress: true,
//     breakpoints: {
//         0: {
//             slidesPerView: 3,
//         },
//         568:  {
//             slidesPerView: 4,
//         }
//     }
// });
// var swiper2 = new Swiper(".shipSlide", {
//     loop: true,
//     spaceBetween: 10,
//     navigation: {
//         nextEl: ".swiper-button-next",
//         prevEl: ".swiper-button-prev",
//     },
//     thumbs: {
//         swiper: swiper,
//     },
// });



$('[data-fancybox]').fancybox({
    protect: true
});

function acceptSwipers()
{
    if (!window.jQuery && !window.Swiper) {
        setTimeout(() => {
            acceptSwipers()
        }, 100)
        return
    }

    let swipers = $('section[swiper]:not([loaded])')
    if(!swipers) return

    for(let i = 0; i < swipers.length; i++) {
        let swiper = swipers.eq(i)
        let swiper_class =  swiper.attr('swiper')
        // console.log('Инициирую слайдер '+swiper_class)
        window['swiper_'+swiper_class] = new Swiper("."+swiper_class+" .thumbSlider", {
            loop: true,
            spaceBetween: 10,
            slidesPerView: 4,
            freeMode: true,
            watchSlidesVisibility: true,
            watchSlidesProgress: true,
            breakpoints: {
                0: {
                    slidesPerView: 3,
                },
                568:  {
                    slidesPerView: 4,
                }
            }
        })

        new Swiper("."+swiper_class+" .shipSlide", {
            loop: true,
            spaceBetween: 10,
            navigation: {
                nextEl: "."+swiper_class+" .swiper-button-next",
                prevEl: "."+swiper_class+" .swiper-button-prev",
            },
            thumbs: {
                swiper: window['swiper_'+swiper_class],
            },
        })

        let parent = $('.'+swiper_class).parents('.tab-item.close-mobile');
        if (parent) {
            parent.on('click' ,function() {
                window['swiper_'+swiper_class].update();
            });
        }
        swiper.attr('loaded', true)
    }
}
