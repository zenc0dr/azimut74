function z(m){console.log(m)}

var listParsers = [
    'parseCountries',
    'parseResorts',
    'parseHotelCategories',
    'parseHotels',
    'parseMeals',
];

function parse(steep){

    var method = listParsers[steep];
    var indicator = '<img src="/plugins/mcmraak/sans/assets/images/preloader.gif">';

    $.ajax({
        type: 'get',
        url: location.origin + '/sans/api/v1/parser/lists/'+method,
        beforeSend: function ()
        {
            //console.log('parse '+listParsers[steep]);
            $('#parsersResult').append('<div method="'+method+'">Парсинг метод :'+method+'<result>'+indicator+'</result></div>');
        },
        success: function (data)
        {
            $('#parsersResult [method="'+method+'"] result').html(data);
                steep++;
            if(steep < listParsers.length) {
                parse(steep);
            } else {
                $('#hotelsCacheRun').removeAttr('disabled');
                saveTime('parsers');
            }
        },
        error: function (x)
        {
            z(x);
        },
    });
}

var hotelsList = [];
function parseHotelDescriptions()
{
    var indicator = '<img src="/plugins/mcmraak/sans/assets/images/preloader.gif">';
    $.ajax({
        type: 'get',
        url: location.origin + '/sans/api/v1/parser/hotels_list',
        beforeSend: function ()
        {
            //console.log('parse '+listParsers[steep]);
            $('#hotelsCacheResult').append('<div method="hotels">Получение списков отелей :<result>'+indicator+'</result></div>');
        },
        success: function (data)
        {
            $('#hotelsCacheResult [method="hotels"] result').html('ok');
            hotelsList = JSON.parse(data);
            hotelsCache(0);
        },
        error: function (x)
        {
            z(x);
        },
    });
}

//parse(0);
$(document).on('click', '#parsersRun', function(){
    $('#parsersResult').html('');
    parse(0);
});

$(document).on('click', '#hotelsCacheRun', function(){
    $('#hotelsCacheResult').html('');
    parseHotelDescriptions();
});

function hotelsCache(steep){
    var indicator = '<img src="/plugins/mcmraak/sans/assets/images/preloader.gif">';
    var hotel_id = hotelsList[steep];
    var all = hotelsList.length;
    if(steep < all) {
        getHotelDescription(hotel_id, steep);

        var proc = Math.round((100*steep) / all);

        $('#hotelsCacheResult').html('Отелей обработано: '+steep+' из '+all+ '('+proc+'%) '+indicator);
    } else {
        $('#hotelsCacheResult').html('Кеш подготовлен');
        saveTime('cache');
    }
}

function getHotelDescription(hotel_id, steep) {
    //z('run parser hotel_id='+hotel_id+' steep='+steep);
    $.ajax({
        type: 'get',
        url: location.origin + '/sans/api/v1/parser/hotel_profile/'+hotel_id,
        success: function (data)
        {
            if(data!=='ok'){
                $('#hotelsCacheErrors').append('<div class="error">(hotel_id:'+hotel_id+') Ошибка: ['+data+']</div>');
            }
            steep++;
            //z('recursive run? steep: '+steep);
            hotelsCache(steep);
        },
        error: function (x)
        {
            z(x);
        },
    });
}


function saveTime(prefix)
{
    $.ajax({
        type: 'get',
        url: location.origin + '/sans/api/v1/parser/savetime/'+prefix,
        success: function (data)
        {
            $('#parserInfo span.'+prefix).text(data);
        },
        error: function (x)
        {
            z(x);
        },
    });
}




