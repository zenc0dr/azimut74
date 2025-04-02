import lightGallery from 'lightgallery';
import lgThumbnail from 'lightgallery/plugins/thumbnail'
import lgZoom from 'lightgallery/plugins/zoom'

/*
*  Это заготовок интеграции lightgallery который как оказалось не работает с динамически
* добавляемым html
*
* */

function LightGalleryActivator()
{
    this.wrap = null
}

LightGalleryActivator.prototype = {
    scan(options)
    {
        //console.log($('.cabin-modal__gallery').length)
        this.wrap = options.wrap
        this.bing()
    },
    bing()
    {

        // this.handleBefore(() => {
        //     $(this.wrap).map((index, node) => {
        //         lightGallery(node, {
        //             plugins: [lgZoom, lgThumbnail],
        //             speed: 500,
        //             licenseKey: '0000-0000-000-0000'
        //         })
        //     })
        // })

    },
    handleBefore(fn) {
        this.options.handleBefore()
        fn()
    }
}

window.LG = new LightGalleryActivator()
