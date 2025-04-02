let leftMenu = {
    last_width: 0,
    changeWidth(width)
    {
        if (this.last_width) {
            let diff = Math.abs(this.last_width - width)
            this.last_width = width
            if (diff < 20) {
                return
            }
        }

        this.last_width = width

        if (width < 992) {
            this.closeAllChapters()
        }
        else {
            this.openAllChapters()
        }
    },
    closeAllChapters()
    {
        $('.left-menu__chapter__items').hide()
        $('.left-menu__chapter__title').addClass('closed')

    },
    openAllChapters()
    {
        $('.left-menu__chapter__items').show()
        $('.left-menu__chapter__title').removeClass('closed')
    }
}

$(document).ready(function () {
    leftMenu.changeWidth($('body').width())
    $('.left-menu').show()
})

$(window).resize(function() {
    leftMenu.changeWidth($('body').width())
})

$(document).on('click', '.left-menu__chapter__title',  function () {

    if ($(this).next('.left-menu__chapter__items').css('display') === 'block') {
        $(this).next().slideUp(100)
        $(this).addClass('closed')
    }
    else {
        $(this).next().slideDown(100)
        $(this).removeClass('closed')
    }
})
