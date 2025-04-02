import api from '../vue-components/api';

$('.print-wrapper button').on('click', function () {
    let prefix_url = $(this).attr('url')
    let get_params = window.location.href.split('?d=')

    // Подготавливаем данные для печати
    let widget_data = ExtOffersWidget._data
    let allowed_dates = widget_data.allowed_dates
    let result_items = widget_data.result_items

    let json_test = get_params[1].replace(/%22/g, '"');
    let data = {
        result_items,
        allowed_dates,
        get_params: JSON.parse(json_test)
    }

    api.sync({
        url: '/ex-tours/ext/print',
        data,
        then: response => {
            const popupWin = window.open(null,null,'location,width=800,height=800,top=0')
            popupWin.document.write(response)
            popupWin.history.pushState(null, 'Печать', '/print-page')
        },
        error: error => {
            console.log(error)
        }
    })
});
