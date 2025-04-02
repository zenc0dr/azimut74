$('.h-m-menu__drop').on('click', function (e) {
    e.preventDefault();
    let parent = $(this).parents('div').first();
    parent.toggleClass('active');
    parent.children('div').fadeToggle();
})