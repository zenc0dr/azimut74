let menuName = '';

function switchMenu(type) {
    menuName = type;
    $('.header-open[type="' + type + '"]').toggleClass("show");
    closeOtherMenu(type);
    if (document.body.clientWidth <= 992) hideOtherMenu(type);
    $('.header-menuItem[type="' + type + '"]').toggleClass('open');
    $('.header-menuItem[type="' + type + '"] .wrapper-menuItem').toggleClass("open");
}

function closeOtherMenu(type) {
    if (!isOpenMenu()) return false;
    let menuListOpen = document.querySelectorAll('.header-open.show');
    menuListOpen.forEach(function(item, i, menuListOpen) {
        if (item.getAttribute('type') != type) {
            item.classList.remove('show');
            $('.header-menuItem[type="' + item.getAttribute('type') + '"]').removeClass('open');
            $('.header-menuItem[type="' + item.getAttribute('type') + '"] .wrapper-menuItem').removeClass('open');
        }
    });
}

function hideOtherMenu(selectType) {
    if (!isOpenMenu()) return false;
    $(".header-menuItem.hidden").removeClass('hidden');
    if (!$(".header-menuItem[type][type ='" + selectType + "' ]").hasClass('open')) {
        $(".header-menuItem[type][type !='" + selectType + "' ]").addClass('hidden');
    }
}

function isOpenMenu() {
    let menuList = $('.header-open').hasClass('show');
    let mobileMenuList = $('.mobile-header').hasClass('active');
    return (!menuList && !mobileMenuList) ? false : true;
}

$(document).on('click tap tab', function (e) {
    let target = e.target;
    let parentTarget = $(target).parent();
    if (parentTarget != null && $(parentTarget).hasClass('wrapper-menuItem')) {

        switchMenu($(parentTarget).parent().attr('type'));
       return;
    }
    if (isOpenMenu()) {

        let menu = $('.header-open[type="'+menuName+'"]');
        let menuParent = $('.header-menuItem[type="'+menuName+'"] .wrapper-menuItem');
        let hamburger = $('#menu-icon-trigger2');
        let its_header = target == $('header') || $('header').find(target).length;
        let its_mask = $(target).hasClass('h-m-menu-mask')

        if (its_mask || !its_header || target == null) {
            if (menu) menu.removeClass('show');
            if (menuParent) menuParent.removeClass('open');
            if (document.body.clientWidth <= 992) hamburger.click();
        }

    }
    return;

});

// $('.header-childTitleWrapper').on('click', function () {
//     if(document.body.clientWidth > 992) return;
//     $(this).parent().toggleClass('open');
//     if ($('.header-childWrapper').hasClass('open')) {
//         let menus = $('.header-childWrapper.open');
//         if (menus.length < 2) return;
//         let thisMenu = $(this).parent()[0];
//         menus.each(function(index, item) {
//            if (thisMenu != item) $(item).removeClass('open');
//         })
//         /*scrollAnimateTo('header', '.header-childWrapper.open',  1000, -30);*/
//     }
//
// });


function scrollAnimateTo(selector, target, time, offset) {
    time = (time == undefined) ? 1000: time;
    offset =  (offset == undefined) ? 50: offset;
    $('selector').stop().animate({ scrollTop: $(target).offset().top - offset }, 1000);
}

$('.h-m-menu__drop').on('click', function (e) {
    e.preventDefault();
    let parent = $(this).parents('div').first();
    parent.toggleClass('active');
    parent.siblings('div').fadeToggle('fast');
    parent.children('div').fadeToggle('fast');
})
