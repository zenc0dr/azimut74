
$(document).on('click', '[modal]', function (event) {
   event.preventDefault()

   let modal_selector = $(this).attr('modal')
   let modal = $(this).next(modal_selector)[0]
   console.log(modal_selector);
   let modal_content = $(modal).html()
   let modal_window = '<div class="zen-modal">' +
      '<div class="zen-modal__body">' +
      '<div class="zen-modal__header"><div class="zen-modal__header__close"><i class="bi bi-x-circle"></i></div>' +
      '<div class="zen-modal__content">' + modal_content + '</div>' +
      '</div>' +
      '</div>'
   $('body').append(modal_window)
})

$(document).on('click', '.zen-modal', function (event) {
   if (event.target != this) return
   $(this).remove()
})

$(document).on('click', '.zen-modal__header__close i', function () {
   $(this).closest('.zen-modal').remove()
})

/* popup hide */
$(document).mouseup(function (event) {
   let div = $('.popup-window')
   if (!div.is(event.target) && div.has(event.target).length === 0) $('.popup-window').hide()
});