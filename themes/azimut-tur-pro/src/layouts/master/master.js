/*libs*/
import '@src/js/zen-modal.js'
import '@src/components/call-back/call-back.js' // Модуль обратной связи откл: https://8ber.kaiten.ru/space/45609/boards/card/48106455
//import '@fancyapps/fancybox/dist/jquery.fancybox.min'


//import '@src/components/lightgallery/lightgallery-integration.js'


import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle';


/*components*/
import '@src/components/header/_.js'
//import '@src/js/injector.js'



// Инициализация всплывающих окон в шаблоне
window.PopoverInit = function ()
{
    $('[data-bs-toggle="popover"]:not([initialized])').get().map(function (popoverTriggerEl) {
        $(popoverTriggerEl).attr('initialized', true)
        return new bootstrap.Popover(popoverTriggerEl, {
            html: true,
        })
    })
}

window.onload = () => PopoverInit()

$(document).ready(function () {
    if (location.href.includes('russia-river-cruises')) {
        $('.only-river-crs').show()
    }
})
