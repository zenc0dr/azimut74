var FilterDATA = {};
var OptionTown = 0;

function shipVideoPopup() {
	$('.shipVideoButton').magnificPopup({
		type: 'inline',
	});
}

$(document).ready(function(){
    if(!$('#Selector').length) return;
    getFilterData();
    var checkin_id = $('checkin-id').attr('id');
    if(checkin_id != ''){
        ion.cmd("val='checkins':'"+checkin_id+"';ajax=/rivercrs/api/v1/selector/htmlresult;html=#Result;");
        //$(document).on('click', '#FilterGo', function(){        });
    }
    $('#modalBooking .phone').inputmask('+7(999)999-99-99', { showMaskOnHover: false });
});

function getFilterOptions(){
    if($('filter-options').length > 0){
        var options = {};
        options.town = $('filter-options').attr('town');
        options.dest = $('filter-options').attr('dest');
        options.ship = $('filter-options').attr('ship');
        options.date = $('filter-options').attr('date-1');
        options.dateb = $('filter-options').attr('date-2');
        options.days = $('filter-options').attr('days');
        options.items = $('filter-options').attr('items');
        options.shipfix = $('filter-options').attr('shipfix');
        return options;
    }
}

/* Get Start DATA */
function getFilterData(){
    $.ajax({
        url: location.origin + '/rivercrs/api/v1/selector/filter',
        success: function (data)
        {
            FilterDATA = JSON.parse(data);
            sendEvents();
            filterDropdown('#Selector [name=town]',softRemove(FilterDATA.towns), FilterDATA.start_id);
            datePickersTuning();
            getCheckins(getFilterOptions());
        },
        complete: function ()
        {
        	$('.filterloader').hide();
        },
    });
}

/* RESULT */
$(document).on('click', '#FilterGo', function(){
    getCheckins();
    $('.filterloader').show();
});

// Удаляем soft-связанные города
function softRemove (arr) {
    if(FilterDATA.soft === undefined) return arr;
    for(key in arr){
        for(ii in FilterDATA.soft) {
            var soft_arr = FilterDATA.soft[ii].split(':');
            if(key == soft_arr[0]) {
                delete arr[key];
            }
        }
    }
    return arr;
}

function getSoftOriginal (town_id) {
    for(i in FilterDATA.soft){
        var soft_arr = FilterDATA.soft[i].split(':');
        if(soft_arr[0] == town_id) {
            return parseInt(soft_arr[1]);
        }
    }
    return false;
}

function getCheckins(options){
	//$('.filterloader').addClass('active'); // Добавляет прелоудер, когда идем 1 загрузка контента (возникает прелоудер в карточке теплохода)
    if(!options){
        var options = {};
    }

    if(options.town) {
        selectDropdownItem('#Selector [name=town]',options.town);
    }

    if(options.dest) {
        selectDropdownItem('#Selector [name=dest]',options.dest);
    }

    if(options.date) {
        $('#Selector [name=date-1]').val(options.date);
    }

    if(options.dateb) {
        $('#Selector [name=date-2]').val(options.dateb);
    }

    if(options.ship) {
        //console.log(options.shipfix)
        if(!options.shipfix){
            selectDropdownItem('#Selector [name=ship]',options.ship);
        }
    }

    if(options.days) {
        selectDropdownItem('#Selector [name=days]',options.days);
    }

    if(options.items && options.items == 'none') {
        return;
    }

    if(options.items && options.items == 'all') {
        ion.cmd("val='checkins':'all','page':1;ajax=/rivercrs/api/v1/selector/htmlresult;html=#Result;");
        return;
    }

    options.town  =  (options.town) ? options.town : $('#Selector [name=town]').val();
    options.dest  =  (options.dest) ? options.dest : $('#Selector [name=dest]').val();
    options.ship  =  (options.ship) ? options.ship : $('#Selector [name=ship]').val();
    options.date  =  (options.date) ? options.date : $('#Selector [name=date-1]').val();
    options.dateb =  (options.dateb) ? options.dateb : $('#Selector [name=date-2]').val();
    options.days  =  (options.days) ? options.days : $('#Selector [name=days]').val();


    var ids = [];
    for(key in FilterDATA.dests){
        var pretindent = true;
        var checkin_id = FilterDATA.dests[key].checkin_id;
        var checkin_date = FilterDATA.checkins[checkin_id].date;

        if(FilterDATA.soft!== undefined){
            if(options.town != 0) {
                if (
                    FilterDATA.dests[key].parent != options.town
                    &&
                    getSoftOriginal(FilterDATA.dests[key].parent) != options.town
                ) pretindent = false;
            }

            if(options.dest != 0) {
                if (
                    FilterDATA.dests[key].id != options.dest
                    &&
                    getSoftOriginal(FilterDATA.dests[key].id) != options.dest
                ) pretindent = false;
            }
        } else {
            if(options.town != 0) {
                if (FilterDATA.dests[key].parent != options.town) pretindent = false;
            }

            if(options.dest != 0) {
                if (FilterDATA.dests[key].id != options.dest) pretindent = false;
            }
        }

        if(options.date != '' && options.dateb != '') {
            var data1test = strDateToMin(options.date);
            var data2test = strDateToMin(options.dateb);
            if(checkin_date<data1test || checkin_date>data2test) {
                pretindent = false;
            }

        } else if(options.date != '') {
            var data1test = strDateToMin(options.date);
            if(data1test > checkin_date) pretindent = false;
        }

        if(options.days != 0) {
            if (FilterDATA.checkins[checkin_id].days != options.days) pretindent = false;
        }

        if(options.ship !=0) {
            if (FilterDATA.checkins[checkin_id].ship_id != options.ship) pretindent = false;
        }

        if(pretindent) {
            if(!ids[checkin_id]) {
                ids[checkin_id] = checkin_id;
            }
        }
    }
    var ids = ids.filter(function (item) { return item != undefined }).join('+');
    ion.cmd("val='checkins':'"+ids+"','page':'"+options.page+"';ajax=/rivercrs/api/v1/selector/htmlresult;html=#Result;");
}

function strDateToMin(date){
    date = date.split('.');
    var y = parseInt(date[2]);
    var m = parseInt(date[1]) - 1;
    var d = parseInt(date[0]);
    return new Date(y,m,d) / 1000;
}

function sendEvents(){
    var eventDates = [];
    for(id in FilterDATA.checkins){
        var currentDate = formatDate(FilterDATA.checkins[id].date * 1000);
        if(eventDates.indexOf(currentDate) == -1){
            eventDates.push(currentDate); // Add to events
        }
    }
    $('[name=date-1],[name=date-2]').datepicker({
    	autoClose: true,
    	//toggleSelected: true,
        onRenderCell: function (date, cellType) {
            var currentDate = date.getTime();
            currentDate = formatDate(currentDate);
            var currentDay = date.getDate();
            if (cellType == 'day' && eventDates.indexOf(currentDate) != -1) {
                return {
                    html: currentDay + '<span class="dp-note"></span>'
                }
            }
        },
        onSelect: function(){
            var date1val = $('[name=date-1]').val();
            //console.log(date1val);
            var date2val = $('[name=date-2]').val();
            if(strDateToMin(date1val) > strDateToMin(date2val)) {
                $('[name=date-2]').val(date1val);
            }
        }
    }).data('datepicker');
    //$.noConflict();
    $(document).on('click', '.dateSelect', function(){
    	var datepicker = $(this).children('input').datepicker().data('datepicker');
    	datepicker.show();
    	//show() - функция самого плагина, описано в документации
	});
}

function sortOptions(options){
    options.sort(function(a,b) {
        if (a.title > b.title) return 1;
        if (a.title < b.title) return -1;
        return 0
    })
    return options;
}

function filterDropdown(selector, data, start_id)
{
    var selector = $(selector);
    var options = [];
    for(id in data){
        options.push({'id':id,'title':data[id]});
    }
    
    /* Options sorting */
    if(selector.selector != '#Selector [name=days]') sortOptions(options);

    /* Add first option */
    options.unshift({'id':0,'title':'Любой(ая)'});

    selector.selectize()[0].selectize.destroy();
    var $dropdown = selector.selectize({
        valueField: 'id',
        labelField: 'title',
        options: options,
        openOnFocus: false
    });
    if(!start_id) start_id = 0;
    var $dropdown_options = $dropdown[0].selectize;
        $dropdown_options.setValue(start_id, false);
}

function selectDropdownItem(selector,value){
    var $dropdown = $(selector).selectize();
    var $dropdown_options = $dropdown[0].selectize;
    $dropdown_options.setValue(value, false);
}

function datePickersTuning(){
    var date = new Date();
    var year = date.getFullYear();
    var nextYear = new Date(year+1,0,1) / 1000;
    var showNexYear = false;

    for(key in FilterDATA.checkins){
        var checkin_date =FilterDATA.checkins[key].date;
        if(checkin_date > nextYear) {showNexYear = true;}
    }

    setYearsRanges(year);
    $('.tsYearSwitcher .tsYear').eq(0).attr('year', year).find('span').text(year);
    $('.tsYearSwitcher .tsYear').eq(0).css('opacity',1);

    if(showNexYear){
        $('.tsYearSwitcher .tsYear').eq(1).attr('year', year+1).find('span').text(year+1);
        $('.tsYearSwitcher .tsYear').eq(1).css('opacity',1);
    } else {
        $('.tsYearSwitcher .tsYear').eq(1).hide();
    }
}

// @input: (int) miliseconds
// @output: (string) formated date ex: 27.11.2017 ()
function formatDate(date) {
    var date = new Date(date);
    var dd = date.getDate();
    if (dd < 10) dd = '0' + dd;
    var mm = date.getMonth() + 1;
    if (mm < 10) mm = '0' + mm;
    var yy = date.getFullYear();
    if (yy < 10) yy = '0' + yy;
    return dd + '.' + mm + '.' + yy;
}

function setYearsRanges(year){
    var date_1 = $('[name=date-1]').datepicker().data('datepicker');
    var date_2 = $('[name=date-2]').datepicker().data('datepicker');
    var date = new Date();
    var yearnow = date.getFullYear();
    if(year == yearnow) {
        var now_sec = date.getTime();
        var next_point = now_sec+31556926000;
        for(id in FilterDATA.checkins){
            var sec = FilterDATA.checkins[id].date;
            sec = sec * 1000;
            if(sec > now_sec && sec < next_point){
                next_point = sec;
            }
        }
        $('[name=date-1]').val(formatDate(next_point));
        var next_point = new Date(next_point);
        date_1.date = next_point;
        date_2.date = next_point;
    } else {
        var next_year = new Date(year,0,1);
        date_1.date = next_year;
        date_2.date = next_year;
        $('[name=date-1]').val('01.01.'+year);
        $('[name=date-2]').val('');
    }

}

$(document).on('click', '.tsItem .tsYearSwitcher .tsYear', function(){
    var year = $(this).attr('year');
    setYearsRanges(year);
    $('.tsItem .tsYearSwitcher .tsYear').removeClass('active');
    $(this).addClass('active')
});


/* Work with JSON */

/* Город отправления */
$(document).on('change', '#Selector [name=town]', function(){
    OptionTown = $(this).val();
    onChangeFilter();
});

function onChangeFilter()
{
    var dests = FilterDATA.dests;

    var dest_arr = [];
    var ship_arr = [];
    var days_arr = [];

    for(id in FilterDATA.dests)
    {
        if(dests[id].parent == OptionTown || OptionTown == 0){

            var checkin_id = dests[id].checkin_id;
            var ship_id = FilterDATA.checkins[checkin_id].ship_id;
            var ship_name = FilterDATA.ships[ship_id];
            var days = FilterDATA.checkins[checkin_id].days;
            dest_arr[dests[id].id] = dests[id].name;
            days_arr[days] = days;
            ship_arr[ship_id] = ship_name;
        }
    }

    filterDropdown('#Selector [name=dest]', softRemove(dest_arr));
    filterDropdown('#Selector [name=days]', days_arr);
    if($('filter-options').attr('shipfix')!='true') {
        filterDropdown('#Selector [name=ship]', ship_arr);
    }
}

/* Pagination */
$(document).on('click', '#Result .pagination-wrap a', function(event){
    event.preventDefault();
    var page = $(this).attr('href');
    page = page.replace(/\D+/g,'')
    if(!page) page = 1;
    getCheckins({page:page});
    var pos = $('.cruiseData').offset().top - 100;
    $('body, html').animate({scrollTop: pos}, 300);
});

/* Modals */

/* Modal Cabin */
// $(document).on('click', '[href="#modalCabin"]', function(){
//     var cabin_id = $(this).attr('cabin-id');
//     ion.cmd('type=get;ajax=/rivercrs/api/v1/cabin/modal/'+cabin_id+';'
//         +'html=#modalCabin .modalLine;afterajax=owlGalleryBind();');
//     $(this).magnificPopup({
//         type: 'inline',
//         preloader: false,
//         callbacks: {
//             afterClose: function() {
//                 $('#modalCabin .modalLine').html('<span style="font-size: 20px">Загрузка...</span>');
//             }
//         }
//     }).magnificPopup('open');
// });

/* Second modal above booking modal */
$(document).on('click', '[href="#modalCabinTop"]', function(){
    var cabin_id = $(this).attr('cabin-id');
    ion.cmd('type=get;ajax=/rivercrs/api/v1/cabin/modal/'+cabin_id+';'
        +'html=topmodal modalbody;afterajax=owlGalleryBind();');
    $('topmodal').fadeIn(300);
});

$(document).on('click', 'topmodal .close', function(){
    $('topmodal').hide();
    $('topmodal modalbody').html('<span style="font-size: 20px">Загрузка...</span>');
});

function owlGalleryBind()
{
    $('.modalCabin .bigImages, .modalSecond .bigImages').owlCarousel({
        loop: false,
        margin: 0,
        items: 1,
        onDragged: modalcabin,
        onInitialized: modalcabin,
    });
    function modalcabin() {
		var hash = $('.modalCabin .bigImages .owl-item.active .imageItem, .modalSecond .bigImages .owl-item.active .imageItem').attr('data-hash');
		$('.modalCabin .smallImages a .imageItem, .modalSecond .smallImages a .imageItem').removeClass('active');
		$('.modalCabin .smallImages a[href="#'+hash+'"], .modalSecond .smallImages a[href="#'+hash+'"]').find('.imageItem').addClass('active');
	}
}

/* Modal Graphic */
$(document).on('click', '[href="#modalGraphic"]', function(){
    var checkin_id = $(this).attr('checkin-id');
    ion.cmd('type=get;ajax=/rivercrs/api/v1/checkin/modalgraphic/'+checkin_id+';'
        +'html=#modalGraphic .modalContent;');
    $(this).magnificPopup({
        type: 'inline',
        preloader: false,
        callbacks: {
            afterClose: function() {
                $('#modalGraphic .modalContent').html('<span style="font-size: 20px">Загрузка...</span>');
            }
        }
    }).magnificPopup('open');
});

/* Second modal above graphic modal */
$(document).on('click', '[href="#modalGraphicTop"]', function(){
    var checkin_id = $(this).attr('checkin-id');
    ion.cmd('type=get;ajax=/rivercrs/api/v1/checkin/modalgraphic/'+checkin_id+';'
        +'html=topmodal modalbody;');
    $('topmodal').fadeIn(300);
});

/* ModalBooking */
var CabinLine = '';

$(document).on('click', '[href="#modalBooking"]', function(){

    var checkin_id = $(this).attr('checkin-id');

    $.ajax({
        url: location.origin + '/rivercrs/api/v1/checkin/modalbooking/'+checkin_id,
        beforeSend: function ()
        {
            $('body').append('<rivercrs_booking class="bex-modal"><div><img src="/themes/azimut-tur/assets/images/preloader_spin.gif"> Загрузка ...</div></rivercrs_booking>');
        },
        success: function (html)
        {
            $('rivercrs_booking>div').html(html);
        }
    });

});

// function bookingTuning()
// {
//     CabinLine = $('.bookingCategoryLine').html();
//     selectizeHandler();
// }

// $(document).on('click', '.catAddBtn', function(){
//     var line = $(this).closest('.bookingCategoryLine');
//     $('<div class="bookingCategoryLine">'+CabinLine+'</div>').insertAfter(line);
//     selectizeHandler();
// });
//
// function selectizeHandler()
// {
//     $('[name=bookingcabin]:not([handler])')
//         .selectize({sortField:'text'})
//         .attr('handler','');
// }

// $(document).on('click', '.bookingCategoryLine .book-close', function(){
//     var line = $(this).closest('.bookingCategoryLine');
//     if(line.prev().attr('class') !== undefined) line.remove();
// });


//checkButton
// $(document).on('click', '#BookingSend:not(.disabled) button', function(){
//
//     var cabins_count = $('[booking-name=cabin]').length;
//     var cabins = [];
//     for(var i=0;i<cabins_count;i++)
//     {
//         cabins[i] = {
//             'cabin_id':$('[booking-name=cabin]').eq(i).val(),
//             'cabin_count':$('[booking-name=cabincount]').eq(i).val()
//         };
//     }
//
//     var data = {'data':{
//         'checkin_id':$('[booking-name=checkin_id]').val(),
//         'name':  $('[booking-name=name]').val(),
//         'phone': $('[booking-name=phone]').val(),
//         'email': $('[booking-name=email]').val(),
//         'desc': $('[booking-name=desc]').val(),
//         'peoples': $('[booking-name=peoples]').val(),
//         'cabins': cabins,
//     }}
//
//     $.ajax({
//         url: location.origin + '/rivercrs/api/v1/booking/send',
//         beforeSend: function ()
//         {
//             //
//         },
//         type: 'post',
//         data: data,
//         success: function (data)
//         {
//
//            // console.log(data);
//         	//$('#SystemMessages').prepend(data);
//
// 			$('.modalDone').show();
// 			$('.modalDone').parent().addClass('blued');
// 			$('.modalContent').hide();
// 			setTimeout(function(){
// 				$.magnificPopup.close();
// 				$('.modalDone').hide();
// 				$('.modalDone').parent().removeClass('blued');
// 				$('.modalContent').show();
// 			}, 3000);
//
//         },
//         error: function (x)
//         {
//             $('html').html(x.responseText);
//         }
//     });
//
// });
//
// function cleanBooking(){
//     $('#modalBooking .mfp-close').click();
// }

if($('.reviewSendImage').length){
    var imageBlock = $('.reviewSendImage')[0].outerHTML;
}

$(document).on('change', '[rev-send=image]', function(){
    $('#imagesWrap').append(imageBlock);
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        var $this = this;
        reader.onload = function (e) {
            var parentEl = $($this).closest('.reviewSendImage');
            parentEl.find('span').css('opacity', 0);
            parentEl.css('background-image','url('+e.target.result+')');
            parentEl.addClass('added');
        }
        reader.readAsDataURL(this.files[0]);
    }
    
});

$(document).on('click', '.addPhotoClose', function(){
	$(this).closest('.added').remove();
});

$(document).on('click', '#ReviewSend:not(.disabled) button', function(){

    var like = $('[rev-send=recommended].active').attr('value');
    var data = new FormData();
    data.append('motorship_id',$('[rev-send=motorship_id]').val());
    data.append('comment',$('[rev-send=comment]').val());
    data.append('liked',$('[rev-send=liked]').val());
    data.append('notliked',$('[rev-send=notliked]').val());
    data.append('startdoing',$('[rev-send=startdoing]').val());
    data.append('stopdoing',$('[rev-send=stopdoing]').val());
    data.append('doing',$('[rev-send=doing]').val());
    data.append('name',$('[rev-send=name]').val());
    data.append('email',$('[rev-send=email]').val());
    data.append('town',$('[rev-send=town]').val());
    data.append('recommended',like);
    //console.log(data);

    var images_count = $('[rev-send=image]').length;
    for(var i=0;i<images_count - 1;i++){
        data.append('image_'+i, $('[rev-send=image]')[i].files[0]);
    }

    $.ajax({
        url: location.origin + '/rivercrs/api/v1/review/send',
        beforeSend: function ()
        {
            //$('#SystemMessages').prepend(data);
            $('.revloader').show();
        },
        type: 'post',
        data: data,
        processData: false,
        contentType: false,
        success: function (data)
        {
            $('#SystemMessages').prepend(data);
        },
        complete: function ()
        {
        	$('.revloader').hide();
        },
        error: function (x)
        {
            $('html').html(x.responseText);
        }
    });

});

function closeReview(){
    $('[rev-send=comment]').val('');
    $('[rev-send=liked]').val('');
    $('[rev-send=notliked]').val('');
    $('[rev-send=startdoing]').val('');
    $('[rev-send=stopdoing]').val('');
    $('[rev-send=doing]').val('');
    $('[rev-send=name]').val('');
    $('[rev-send=email]').val('');
    $('[rev-send=town]').val('');
    $('#revBut').click();
}

/* Выбор корабля для отзывов */
$(document).on('change', '#SelectShipReviews', function(){
    var motorship_id = $(this).val();
    ion.cmd("ajax=/russia-river-cruises/cruises-reviews-list/"+motorship_id+";html=#ReviewsContainer;");
});

/* Пагинация отзывов */
$(document).on('click', '.revBlockPagination a', function(event){
    event.preventDefault();
    var page = $(this).attr('href').match( /page=(\d+)/i )[1];
    var motorship_id = $('#SelectShipReviews').val();
    ion.cmd("val='page':"+page+";ajax=/russia-river-cruises/cruises-reviews-list/"+motorship_id+";html=#ReviewsContainer;");
});

// Вызов модальной формы Оферты
$(document).on('click', '[href="#modalOffer"]', function(){
	$('html').addClass('locked');
    ion.cmd('type=get;ajax=/offer;'+'html=ofmodal ofmodalbody;');
    setTimeout(function(){
		$('ofmodal').fadeIn(300).addClass('active');
	}, 100);
});
$(document).on('click', 'ofmodal .close', function(){
    $(this).closest('ofmodal').fadeOut(300).removeClass('active');
	$('ofmodal').fadeOut(300).removeClass('active');
	setTimeout(function(){
		$('ofmodal ofmodalbody').html('');
		$('html').removeClass('locked');
	}, 300);
});
$(document).on('click', '#revMore', function(){
	$(this).prev().children(':nth-child(n+2)').slideToggle(300);
	$(this).toggleClass('active');
});
$(document).on('click', '[href="#modalOtherCity"]', function(){
	$('html').addClass('locked');
    ion.cmd('type=get;ajax=/othercity;'+'html=ofmodal ofmodalbody;');
    setTimeout(function(){
		$('ofmodal').fadeIn(300).addClass('active');
	}, 100);
});
$(document).mouseup(function(e){
	var cont = $('ofmodal > div');
	if (cont.parent().hasClass('active') && !cont.is(e.target) && cont.has(e.target).length === 0) {
		$('ofmodal').fadeOut(300).removeClass('active');
		setTimeout(function(){
			$('ofmodal ofmodalbody').html('');
			$('html').removeClass('locked');
		}, 300);
	}
});
$(document).ready(function(){
	var link = window.location.hash;
	if (link == '#c-description') {
		$('.c-description').parent().children('div').removeClass('active');
		$('.c-description').addClass('active');
	}
	if (link == '#c-schedule') {
		$('.c-schedule').parent().children('div').removeClass('active');
		$('.c-schedule').addClass('active');
	}
	if (link == '#c-reviews') {
		$('.c-reviews').parent().children('div').removeClass('active');
		$('.c-reviews').addClass('active');
	}

});

function owlModal() {
	var shvid = $('.shipVideoModalSlider');
	shvid.owlCarousel({
	    loop: false,
	    margin: 0,
	    items: 1,
	    mouseDrag: false
	});
	$('.shipVideoWrap .slider-nav .prev').click(function (){
        shvid.trigger('prev.owl.carousel');
    });
	$('.shipVideoWrap .slider-nav .next').click(function (){
        shvid.trigger('next.owl.carousel');
    });
}

$(document).on('click', '.shipVideoButton:not(#shipVideoModal .mfp-close)', function(){
    $(this).magnificPopup({
        type: 'inline',
        preloader: false,
        closeOnBgClick: false,
    }).magnificPopup('open');
    owlModal();
});

$(document).on('click', '.discountsPopups-buttons>div', function() {

    var win = '.discountsPopups-windows>div';

    if($(this).hasClass('active')){
        $(this).parent().find('.active').removeClass('active');
        $(this).closest('.shipBlock').find(win).hide();
        return;
    }

    var index = $(this).index();
    $(this).parent().find('.active').removeClass('active');
    $(this).addClass('active');
    $(this).closest('.shipBlock').find(win).hide();
    $(this).closest('.shipBlock').find(win).eq(index).fadeIn(300);
});