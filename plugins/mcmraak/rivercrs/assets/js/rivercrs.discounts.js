$(document).ready(function () {
    let input = '<input class="form-control" placeholder="Фильтр" oninput="searchFilter(this)" name="ship-name" />'
    let ship_list = '<div class="checkbox custom-checkbox">' +
        '<input oninput="showChecked(this)" id="ship-list" type="checkbox">' +
        '<label for="ship-list">Показать отмеченные</label>' +
        '</div>'

    $('#Form-field-Discount-motorships-group .field-checkboxlist.is-scrollable')
        .prepend(ship_list)
        .prepend(input)
})

function searchFilter(event)
{
    $('#Form-field-Discount-motorships-group input[type="checkbox"]').map(function (index, node) {
        node = $(node)
        let label = node.next('label').text().trim()
        let checkbox = node.closest('.checkbox')
        if (label.toLowerCase().indexOf(event.value.toLowerCase()) !== -1) {
            checkbox.show()
        } else {
            checkbox.hide()
        }
    })
}

function showChecked(show_checked)
{
    $('#Form-field-Discount-motorships-group input[type="checkbox"][name="Discount[motorships][]"]').map(function (index, node) {
        node = $(node)
        let checkbox = node.closest('.checkbox')

        if (!show_checked.checked) {
            checkbox.show()
        } else {
            if (node[0].checked) {
                checkbox.show()
            } else {
                checkbox.hide()
            }
        }
    })
}
