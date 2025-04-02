$(document).ready(function () {
    $('.price-range').slider({
        range: true,
        min: 0,
        max: 500,
        values: [0, 500],
        slide: function (event, ui) {
            $('.price-range-input_one').val(ui.values[0]);
            $('.price-range-input_two').val(ui.values[1]);
        }
    });

    $('.price-range-input_one').val($('.price-range').slider("values", 0));
    $('.price-range-input_two').val($('.price-range').slider("values", 1));

    $(document).on('keyup', '.price-range-input_one', function () {
        var val1 = $(this).val();
        $('.price-range').slider("values", 0, val1);
    })
    $(document).on('keyup', '.price-range-input_two', function () {
        var val2 = $(this).val();
        $('.price-range').slider("values", 1, val2);
    })

});
