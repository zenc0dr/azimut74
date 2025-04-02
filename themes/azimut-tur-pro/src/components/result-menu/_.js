$(`.result-menu__category-title`).on('click', function (e) {
   let menu = $(this).siblings('.result-menu__items').first();
   menu.toggleClass('open');
   $(this).toggleClass('close');
   $(this).children('.result-menu__arrow-icon').first().toggleClass('open');
});