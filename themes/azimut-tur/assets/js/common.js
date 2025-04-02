/* Встречающее окно */

/// Раз в день
/*
$(document).ready(function(){
    let now_date = dateFormated()
    if(localStorage.getItem('hallo') != now_date) {
        let domain = location.origin
        ion.cmd('type=get;ajax='+domain+'/meeting-window;'+'html=meetmodal meetmodalbody;');
        setTimeout(function(){
            $('html').addClass('locked');
            $('meetmodal').addClass('active');
        }, 1000);
        localStorage.setItem('hallo', now_date);
    }
});
*/

/*
$(document).ready(function(){
    let now_time = Math.floor(Date.now() / 1000)
    let show_time = parseInt(localStorage.getItem('meeting_window_time'))

    if (!show_time || now_time - show_time > 300) {
        let domain = location.origin
        ion.cmd('type=get;ajax='+domain+'/meeting-window;'+'html=meetmodal meetmodalbody;')
        setTimeout(function(){
            $('html').addClass('locked')
            $('meetmodal').addClass('active')
        }, 1000)
        localStorage.setItem('meeting_window_time', now_time);
    }
})
*/

/*
$(document).ready(function(){
    let domain = location.origin
    ion.cmd('type=get;ajax='+domain+'/meeting-window;'+'html=meetmodal meetmodalbody;');
    setTimeout(function(){
        $('html').addClass('locked');
        $('meetmodal').addClass('active');
    }, 1000);
});
*/



function dateFormated(date) {
    if(!date) date = new Date
    var dd = date.getDate();
    if (dd < 10) dd = '0' + dd;
    var mm = date.getMonth() + 1;
    if (mm < 10) mm = '0' + mm;
    var yy = date.getFullYear();
    if (yy < 10) yy = '0' + yy;
    return dd + '.' + mm + '.' + yy;
}


$(document).on('click', 'meetmodal .close', function(){
	$('html').removeClass('locked');
    $(this).closest('meetmodal').removeClass('active').addClass('closed');
});
$(document).mouseup(function(e){
	var cont = $('meetmodal > div');
	if (cont.parent().hasClass('active') && !cont.is(e.target) && cont.has(e.target).length === 0) {
		$('html').removeClass('locked');
		$('meetmodal').removeClass('active').addClass('closed');
	}
});

$(document).ready(function(){
	$('.sliderItems').show();
    heightsMoreItems();
    RiverCRSmoreItems();
	cardOwl(); // появление карусели в карточке теплохода, ранее запускалась при клике на свитчер, но клиент пожелал иначе, поэтому изменили
});

$(document).ready(function(){
    var mainsl = $('.sliderItems');
    mainsl.owlCarousel({
    loop: true,
    items: 1,
    margin: 0,
    nav: false,
    autowidth: true,
    autoHeight:true,
    autoplay: true,
    autoplayTimeout: 4000,
    autoplayHoverPause: true,
    autoplaySpeed: 500
    });

    $('.sliderNav .next').click(function (){
        mainsl.trigger('next.owl.carousel');
    });
    $('.sliderNav .prev').click(function (){
        mainsl.trigger('prev.owl.carousel');
    });
});

$(window).scroll(function(){
    if($(this).scrollTop()>100){
        $('.fixedNav').addClass('active');
    }
    else if ($(this).scrollTop()<100){
        $('.fixedNav').removeClass('active');
    }
});

$(document).on('click', '.readMore .rbtn', function(){
    var par = $('.articleData .data');
    par.toggleClass('active');
    $(this).parent().toggleClass('active');
    // $(this).hide();
});

// HoverMenu
$(document).ready(function(){
    var hmenu = $('.servicesMenu > ul > li > ul');
    $('.servicesMenu > ul > li > .servLink').on('mouseenter', function(){
        $(this).parent().addClass('active')
    });
    $('.servicesMenu > ul > li > .servLink').on('mouseleave', function(){
        $(this).parent().removeClass('active')
    });
    hmenu.on('mouseenter',function(){
        $(this).parent().addClass('active')
    });
    hmenu.on('mouseleave',function(){
        $(this).parent().removeClass('active')
    });
});

$(document).on('click', '.servicesMenu > ul > li > .servLink', function(event){
	var hlist = $(this).parent().next('ul');
	if(hlist.hasClass('active')) {
		hlist.slideToggle(200).toggleClass('active');
	} else {
		$('.servicesMenu > ul > ul').slideUp(200).removeClass('active');
		hlist.slideToggle(200).toggleClass('active');
	}
    // event.preventDefault();
});

$(document).ready(function(){
	var link = window.location;
	$('.servicesMenu ul li a[href="'+link+'"]').addClass('active');
});

$(document).on('click', '.toggleMain', function(){
    $('.hiddenMain').slideToggle(200);
});
$(document).on('click', '.toggleFixed', function(){
    $('.hiddenFixed').slideToggle(200);
});

/* Not work with ajax*/
$(document).ready(function(){
    $('.mbtn').magnificPopup({
        type: 'inline',
        preloader: false,
        callbacks: {
            afterClose: function() {
                $('ofmodal').fadeOut(300);
            },
            open: function() {
		    	$('.modalWrap').addClass('active');
		  	},
        }
    });
});

var formFocus = null;
$(document).on('click', '[href="#modalTour"]', function () {
    //console.log('Открыта форма - Подобрать тур');
    //ym(13605125, 'reachGoal', 'tour_searching');
    formFocus = 'tour_searching';
});
$(document).on('click', '[href="#modalCall"]', function () {
    //console.log('Открыта форма - Заказать звонок');
    //ym(13605125, 'reachGoal', 'call_back');
    formFocus = 'call_back';
});

$(document).ready(function(){
    // $('.images').magnificPopup({
    //     type: 'image',
    //     delegate: 'a',
    //     gallery: {
    //         enabled: true,
    //         navigateByImgClick: true,
    //         // preload: [0,1]
    //     },
    // });
    // $('.plusbtn').magnificPopup({
    //     type: 'image',
    //     gallery: {
    //         enabled: false,
    //         navigateByImgClick: true,
    //         // preload: [0,1]
    //     },
    // });
});

$(document).on('click', '.smallImages .imageItem', function(){
    $('.smallImages .imageItem').removeClass('active');
    $(this).addClass('active');
});
$(document).on('click', '.imagePlusButton .ibtn', function(){
	$('.smallImages > :nth-child(n+10)').slideToggle(200);
	$(this).parent().toggleClass('active');
});
$(document).on('click', '.shipDescAboutButton .ibtn', function(){
	$('.shipDescAbout .data').toggleClass('visible');
	$(this).parent().toggleClass('active');
	//$('.shipDescAbout .data p:nth-child(-n+3)').slideToggle(200);
});
$(document).on('click', '.cabinItemLine .cabinplus', function(){
	$(this).parent().children('a:nth-of-type(n+2)').slideToggle(200).parent().addClass('active');
	$(this).toggleClass('active');
});


$(document).ready(function(){
    var cardimgs = $('.images');
    cardimgs.owlCarousel({
        loop: false,
        margin: 0,
        items: 1,
        onDragged: callback,
        onInitialized: callback,
        onTranslated: callback,
    });

	function callback() {
		var hash = $('.cardImages .mainImages .images .owl-item.active .bigItem').attr('data-hash');
		$('.cardImages .smallImages a .imageItem').removeClass('active');
		$('.cardImages .smallImages a[href="#'+hash+'"]').find('.imageItem').addClass('active');
	}

    $('.sliderNav .next').click(function (){
        cardimgs.trigger('next.owl.carousel');
    });
    $('.sliderNav .prev').click(function (){
        cardimgs.trigger('prev.owl.carousel');
    });

    /* Not work with ajax */
    // var cabin = $('.modalCabin .bigImages');
    // cabin.owlCarousel({
    //     loop: false,
    //     margin: 0,
    //     items: 1
    // });

    /*var shipdesc = $('.shipDescImages .bigImages');
    shipdesc.owlCarousel({
        loop: false,
        margin: 0,
        items: 1,
        onDragged: shipdesco,
        onInitialized: shipdesco,
    });
    // $('.smallImages a:first-child div').addClass('active');

    function shipdesco() {
    	var hash = $('.shipDescImages .bigImages .owl-item.active .imageItem').attr('data-hash');
		$('.shipDescImages .smallImages a .imageItem').removeClass('active');
		$('.shipDescImages .smallImages a[href="#'+hash+'"]').find('.imageItem').addClass('active');
    }*/
});

$(document).on('click', '.cardButton button', function(){
	var tourid = $(this).attr('tourid');
	$('#modalCallTour #tourid').val(tourid);
});

$(document).ready(function(){
    $('.fLine .phone, .dLine .phone').inputmask('+7(999)999-99-99', { showMaskOnHover: false });

    $('.fLine .phone, .dLine .phone').on('input', function () {
        if($(this).val().indexOf('+7(8') !== -1) {
            $(this).val('+7(')
        }
    })

    var timersCount = $('.countdown').length;
    for(var i=0;i<timersCount;i++){
        var timeEnd = $('.countdown').eq(i).attr('endtime');
        $('.countdown').eq(i).downCount({
                date: timeEnd,
                offset: +4
            });
    }
});

function cardOwl() {
	var shipdesc = $('.shipDescImages .bigImages');
    shipdesc.owlCarousel({
        loop: false,
        margin: 0,
        items: 1,
        onDragged: shipdesco,
        onInitialized: shipdesco,
    });
    // $('.smallImages a:first-child div').addClass('active');

    function shipdesco() {
    	var hash = $('.shipDescImages .bigImages .owl-item.active .imageItem').attr('data-hash');
		$('.shipDescImages .smallImages a .imageItem').removeClass('active');
		$('.shipDescImages .smallImages a[href="#'+hash+'"]').find('.imageItem').addClass('active');
    }
}

/*$(document).ready(function(e){
	var hash = $('.images .owl-item.active a').children().attr('data-hash');
	$('.smallImages a[href="#'+hash+'"]').children().addClass('active')
});*/

//Табуляция
$(document).on('click', '.departure .switch', function(){
	var number = $(this).index();
	var swcontent = $('.departure .switchContent');
	$('.departure .switch').removeClass('active');
	$(this).addClass('active');

	swcontent.removeClass('active').eq(number).addClass('active');
});

$(document).ready(function(){
	$('.tsSelect, .wrSelect').selectize({
	    sortField: 'text'
	});
	// $('.s-sel__select_childrens, .s-sel__select').selectize({
	//     sortField: 'text',
	//     //onChange: valChecker,
	//     // onChange(function(){
	//     // 	valChecker();
	//     // })
	// });
	// var date = new Date();
	// var currentDate = date.getDay();
	// console.log(currentDate);
	// function valChecker() {
	// 	var sc =  $('.s-sel__select_childrens').val();
	// 	console.log(sc);
	// 	var inputHtml = $('<input class="s-sel__input s-sel__input_childer-age" class="children-age" v-for="item in childrens_ages">');
	// 	var parentNode = $('.s-sel__label_children-age');
	// 	for (var i=0;i<sc;i++) {
	// 		$('<input class="s-sel__input s-sel__input_children-age" class="children-age" v-for="item in childrens_ages">').eq(i).insertAfter(parentNode);
	// 		//console.log(line1.length);
	// 		// if (line1.length > sc) {
	// 		// 	line1.eq(sc++).remove();
	// 		// }
	// 	}
	// 	//if ($('.s-sel__select_childrens').val())
	// }
	// $(document).on('click', '.selectize-dropdown-content .option', function(){

	// })

});

$(document).on('click', '.rightBlock .crSwitcher', function(){
	var thclass = $(this).data('id');
	var num = $(this).index();
	var crswcontent = $('.rightBlock .cruiseCardContent .crSwitchContent');

	$('.rightBlock .crSwitcher').removeClass('active');
	$(this).addClass('active');

	crswcontent.removeClass('active');
	crswcontent.eq(num).addClass('active');
	var top = $('.cruiseCardContent').offset().top - 166;
	$('body,html').animate({scrollTop: top}, 300);
	$('[data-id='+thclass+']').addClass('active');
	//cardOwl();
});

// Deprocated
// $(document).on('click', '.tsItem .tsYearSwitcher .tsYear', function(){
// 	$('.tsItem .tsYearSwitcher .tsYear').removeClass('active');
// 	$(this).addClass('active')
// });

$(document).on('click', '.writerSwitchers .wrSwitcher', function(){
	$('.writerSwitchers .wrSwitcher').removeClass('active');
	$(this).addClass('active')
});

$(document).on('click', '#revBut1, #revBut2', function(){
	$('.revBlockWriter, .s-reviews-writer').slideToggle(300).toggleClass('active');
	var id = $(this).attr('href');
    var top = $(id).offset().top - 100;
    if($('.revBlockWriter, .s-reviews-writer').hasClass('active')){
    	$('body,html').animate({scrollTop: top}, 300);
    } else {}
});

$(document).on('click', '.shipButtonToggle .tbtn', function(){
	var spanCount = $('.shipButtonToggle .tbtn span').length;
	var span = $('.shipButtonToggle .tbtn span');
	var arrow = $('.shipButtonToggle .tbtn i');
    for(var i=0;i<spanCount;i++){
        if (span.eq(i).hasClass('active')){
        	span.eq(i).removeClass('active');
        	arrow.eq(i).removeClass('active');
        	$('.shipBlockTop, .shipBlockBottom').show();
        } else {
        	span.eq(i).addClass('active');
        	arrow.eq(i).addClass('active');
        	$('.shipBlockTop, .shipBlockBottom').hide();
        }
    }
});
$(document).on('click', '.revBlockWriter .offerItem .check, .s-reviews-writer .offerItem .check', function(){
	var number = $('[rev-send="name"]').val().length;
	if($(this).hasClass('checked')){
		$(this).removeClass('checked');
		$('.writerButton').addClass('disabled');
	} else {
		$(this).addClass('checked');
		if (($('[rev-send="town"]').val() !== '') && ($('[rev-send="name"]').val() !== '') && ($('.offerItem .check').hasClass('checked')) && number > 2) {
			$('.writerButton').removeClass('disabled');
		}
	}
});


$(document).on('click', '.modalWrapStandart .offerItem .check', function(){
    //var dataion = $(this).closest('.modalWrap').find('.modalButton').children('button').attr('data-ion');
    if($(this).hasClass('checked')){
        $(this).removeClass('checked');
        $(this).closest('.modalWrap').find('.checkButton, .modalButton').addClass('disabled');
        //$(this).closest('.modalWrap').find('.modalButton').children('button').attr('ion', '');
    } else {
        $(this).addClass('checked');
        $(this).closest('.modalWrap').find('.checkButton, .modalButton').removeClass('disabled');
        //$(this).closest('.modalWrap').find('.modalButton').children('button').attr('ion', dataion);
    }
});

$(document).on('click', '.modalButton:not(.disabled) button', function(){

    var formData = new FormData();
    formData.append('name',$(this).closest('.modalContent').find('[name=name]').val());
    formData.append('email',$(this).closest('.modalContent').find('[name=email]').val());
    formData.append('phone',$(this).closest('.modalContent').find('[name=phone]').val());
    formData.append('message',$(this).closest('.modalContent').find('[name=message]').val());
    formData.append('ide',$(this).closest('.modalContent').find('[name=ide]').val());
    formData.append('tour',$(this).closest('.modalContent').find('[name=tour]').val());

    //console.log(formData);

    $.ajax({
        url: location.origin + '/callback/send',
        beforeSend: function ()
        {
            $('.modal-loader').show();
            //$('#SystemMessages').prepend(data);
        },
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (formData)
        {
            $('#SystemMessages').prepend(formData);
            var errors = $('#SystemMessages .error').length;
            if(!errors) {
                ym(13605125, 'reachGoal', formFocus);
            }
            $('.modal-loader').hide();
        },
        error: function (x)
        {
            $('html').html(x.responseText);
        }
    });

});

/*
$(document).on('click', '#modalBooking .offerItem .check', function(){
	var number = $('[booking-name="name"]').val().length;
	//console.log(number);
	if($(this).hasClass('checked')){
		$(this).removeClass('checked');
		$('.bookingButton').addClass('disabled');
	} else {
		$(this).addClass('checked');
		if (($('[booking-name="phone"]').val() !== '') && ($('[booking-name="email"]').val() !== '') && ($('.offerItem .check').hasClass('checked')) && number > 2) {
			$('.bookingButton').removeClass('disabled');
		}
	}
});

$(document).on('keyup', '#modalBooking .order', function(){
	var number = $('[booking-name="name"]').val().length;
	if (($('[booking-name="phone"]').val() !== '') && ($('[booking-name="email"]').val() !== '') && ($('.offerItem .check').hasClass('checked')) && number > 2) {
		$('.bookingButton').removeClass('disabled');
	} else {
		$('.bookingButton').addClass('disabled');
	}
})

$(document).on('keyup', '.revBlockWriter .wLine input, .s-reviews-writer__input', function(){
	var number = $('[rev-send="name"]').val().length;
	if (($('[rev-send="town"]').val() !== '') && ($('[rev-send="name"]').val() !== '') && ($('.offerItem .check').hasClass('checked')) && number > 2) {
		$('.writerButton').removeClass('disabled');
	} else {
		$('.writerButton').addClass('disabled');
	}
})
/*$(document).on('mouseenter', '.checkButton', function(){
	if (($('[rev-send="town"]').val() !== '') && ($('[rev-send="name"]').val() !== '') && ($('.offerItem .check').hasClass('checked'))) {
		$('.checkButton, .modalButton').removeClass('disabled');
	}
});*/
$(document).on('click', '.cruiseButtons .crbtn', function (event) {
    event.preventDefault();
    var id  = $(this).attr('href');
    var top = $(id).offset().top - 72;
    $('body,html').animate({scrollTop: top}, 300);
});
$(document).on('click', '.catLineItem .countPlus', function() {
	var $input = $(this).parent().find('input');
	var count = parseInt($input.val()) + 1;
	count = count < 1 ? 1 : count;
    $input.val(count);
    $input.change();
    return false;
});
$(document).on('click', '.catLineItem .countMinus', function() {
	var $input = $(this).parent().find('input');
    var count = parseInt($input.val()) - 1;
    count = count < 1 ? 1 : count;
    $input.val(count);
    $input.change();
    return false;
});
$(document).ready(function(){
	$('.gal').nivoLightbox();
});
$(document).on('click', '[href="#modalOffer"]', function(){
	$('html').addClass('locked');
    ion.cmd('type=get;ajax=/offer;'+'html=ofmodal ofmodalbody;');
	setTimeout(function(){
		$('ofmodal').fadeIn(300).addClass('active');
	}, 100);
    if ($('.modalWrap').hasClass('active')){
    	setTimeout(function(){
			$('ofmodal').fadeIn(300);
		}, 100);
    } else {
    	setTimeout(function(){
			$('.reviewsSecondModal').fadeIn(300);
		}, 100);
    }
});
$(document).on('click', 'ofmodal .close', function(){
    $(this).closest('ofmodal').fadeOut(300).removeClass('active');
    setTimeout(function(){
		$('ofmodal ofmodalbody').html('');
		$('html').removeClass('locked');
	}, 300);
    // $('ofmodal').fadeOut(300);
});
$(document).on('click', '.leftBlock .leftBlockItem .itemHead', function(){
	var w = $(document).width();
	if (!$(this).children().is('a')) {
		$(this).next().slideToggle(250);
		if (w>=992) {
			$(this).toggleClass('closed');
		}
	} else {
		if (w<992) {
			$(this).next().slideToggle(250);
			$(this).children('a').removeAttr('href');
		}
	}
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


function currentYPosition() {
    // Firefox, Chrome, Opera, Safari
    if (self.pageYOffset) return self.pageYOffset;
    // Internet Explorer 6 - standards mode
    if (document.documentElement && document.documentElement.scrollTop)
        return document.documentElement.scrollTop;
    // Internet Explorer 6, 7 and 8
    if (document.body.scrollTop) return document.body.scrollTop;
    return 0;
}


function elmYPosition(eID) {

    var elm = document.getElementById(eID);

    var y = elm.offsetTop;
    var node = elm;
    while (node.offsetParent && node.offsetParent != document.body) {
        node = node.offsetParent;
        y += node.offsetTop;
    }

    return y;

}


function smoothScroll(eID) {

    var startY = currentYPosition();
    var stopY = elmYPosition(eID)-100;

    var distance = stopY > startY ? stopY - startY : startY - stopY;

    if (distance < 100) {
        scrollTo(0, stopY); return;
    }
    var speed = Math.round(distance / 100);

    if (speed >= 20) speed = 20;
    var step = Math.round(distance / 25);

    var speed = 15;
    var leapY = stopY > startY ? startY + step : startY - step;
    var timer = 200;

    $('html, body').animate({scrollTop:stopY}, 400);

    return;

}

function scrollTo(element, to, duration) {
    if (duration <= 0) return;
    var difference = to - element.scrollTop;
    var perTick = difference / duration * 10;

    setTimeout(function() {
        element.scrollTop = element.scrollTop + perTick;
        if (element.scrollTop === to) return;
        scrollTo(element, to, duration - 10);
    }, 10);
}

function customScroll(blet) {
  // проверка на докрутку до определенного элемента
	var scroll_picca = $('#procedurs').offset().top;
	$('body, html').scrollTop(scroll_picca);

}

function heightsMoreItems() {
    var els = $('.leftBlock .more-items').length;
    if(!els) return;
    for(var i=0;i<els;i++) {
        var height = $('.leftBlock .more-items').eq(i).prev('ul').height();
        $('.leftBlock .more-items').eq(i).attr('h', height);
    }
}

function RiverCRSmoreItems(trigger, h) {
    if(!h) h = 'auto'
    if(trigger) {
        $('.leftBlock .more-items').prev('ul').css({
            height: h
        });
    } else {
        $('.leftBlock .more-items').prev('ul').css({
            height: '215px',
            overflow: 'hidden'
        });
    }
}

/* Смотреть больше в меню RiverCRS */
$(document).on('click', '.more-items', function () {
    if($(this).hasClass('show')) {
        RiverCRSmoreItems(false);
        $(this).removeClass('show');
        $(this).html('Смотреть все теплоходы <i class="fa fa-chevron-down"></i>');
    } else {
        var h = $(this).attr('h');
        RiverCRSmoreItems(true, h);
        $(this).addClass('show');
        $(this).html('Скрыть список <i class="fa fa-chevron-up"></i>');
    }
});
