$(document).on('click', '.item-drop', function () {
    if($(this).hasClass('showed')) {
        $(this).removeClass('showed')
    } else {
        $(this).addClass('showed')
    }
})

$(document).on('click', '.extra-items-close', function () {
    let extra_block = $(this).prev()
    if(extra_block.hasClass('showed')) {
        extra_block.hide()
        extra_block.removeClass('showed')
    } else {
        extra_block.show()
        extra_block.addClass('showed')
    }
})

$(document).on('mouseenter', '.atm-result-box', function () {
    let operator = $(this).attr('op')
    if(!operator) return

    $('.manager-data__operator-name').remove()
    $('body').append('<div class="manager-data__operator-name">'+operator+'</div>')
})

$(document).on('mouseleave', '.atm-result-box', function () {
    $('.manager-data__operator-name').remove()
})
