$(document).on('click', '[booking-key]', function(){
    var booking_key = $(this).attr('booking-key');
    $.ajax({
        type: 'post',
        data: {booking_key:booking_key},
        url: location.origin + '/sans/api/v1/booking/getModal',
        success: function (html)
        {
            $('#SansBookingModalContent').html(html);
        }
    });
});