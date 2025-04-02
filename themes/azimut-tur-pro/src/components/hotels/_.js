$(document).on('click', '.tour-hotel__item-top', function () {
   $('.tour-hotel__arrow').toggleClass('rotate')
   if ($(this).next('.tour-hotel__item-bot').css('display') === 'block') {
      $(this).next().slideUp(100)
      $(this).addClass('closed')
   }
   else {
      $(this).next().slideDown(100)
      $(this).removeClass('closed')
   }
})

