function ShowStatus() {
    this.init()
    this.el = null
    this.status_name = null
    this.status_desc = null
    this.memory = {}
}

ShowStatus.prototype = {
    init() {
        $(document).on('click', 'span.ship-type', (event) => {
            this.status_name = $(event.target).text()
            this.el = $(event.target)
            this.getStatusDesc()
        })

        $(document).on('click', '.click-out', () => {
            $('.status_desc').remove()
        })
    },
    getStatusDesc() {

        if(this.memory[this.status_name]) {
            this.status_desc = this.memory[this.status_name]
            this.showDesc()
            return
        }

        $.ajax({
            url: location.origin + '/prok/api/getShipStatusDesc?name=' + this.status_name,
            success: (response) => {
                if(!response) return
                response = JSON.parse(response)
                if(!response.desc) return
                this.status_desc = response.desc
                this.memory[this.status_name] = this.status_desc
                this.showDesc()
            }
        })
    },
    showDesc() {
        console.log(this.status_desc)
        this.el.append('<div class="status_desc">'+this.status_desc+'<div class="click-out"></div></div>')
    }
}

new ShowStatus

/* modal */

$(document).on('click', '[modal]', function(event) {
    event.preventDefault()

    let modal_selector = $(this).attr('modal')
    let modal = $(this).next(modal_selector)[0]
    let modal_content =  $(modal).html()
    let modal_window = '<div class="zen-modal">' +
                           '<div class="zen-modal__body">' +
                               '<div class="zen-modal__header"><div class="zen-modal__header__close"><i class="fa fa-close"></i></div>' +
                               '<div class="zen-modal__content">'+ modal_content +'</div>' +
                           '</div>' +
                       '</div>'
    $('body').append(modal_window)
})

$(document).on('click', '.zen-modal', function(event) {
    if(event.target != this) return
    $(this).remove()
})

$(document).on('click', '.zen-modal__header__close i', function() {
    $(this).closest('.zen-modal').remove()
})

/* popup hide */
$(document).mouseup(function (event) {
    let div = $('.popup-window')
    if (!div.is(event.target) && div.has(event.target).length === 0) $('.popup-window').hide()
});
