$('.tabs .tab').on('click', function () {
   $('.tabs .tab').removeClass('active');
   $('.tabs .tab.no-border').removeClass('no-border');
   $(this).addClass('active');
   let idTab = $(this).attr('tab');
   showTabContainer(idTab);
   if ($('.tabs .tab:last-child').hasClass('active')) {
      $('.tabs .tab:first-child').addClass('no-border');
   }
});


function showTabContainer(idTab) {
   $('.tab-container').removeClass('active')
   $('.tab-container[tab="' + idTab + '"]').addClass('active')
}

$('.tab-content').on('click', function (e) {
   e.stopPropagation();
});

$('.tab-item').on('click', function (e) {
   if (document.documentElement.clientWidth < 992) {
      $(e.currentTarget).toggleClass('close-mobile');
   } else {
      $(e.currentTarget).toggleClass('open');
   }
});
