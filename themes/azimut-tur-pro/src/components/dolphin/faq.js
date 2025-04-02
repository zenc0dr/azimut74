$(document).on('click', '.faq__item__question', function () {
    let next = $(this).next('.faq__item__answer')
    next.slideToggle(200, () => {
        $('i', this).attr('class', next.is(":hidden") ? 'bi bi-plus-lg' : 'bi bi-dash')
    })
})
