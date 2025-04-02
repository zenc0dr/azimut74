
function fillTowns(motorship_id)
{
    $.ajax({
        url: location.origin + '/rivercrs/api/v1/pricing/towns/'+motorship_id,
        success: function (json)
        {
            var towns = JSON.parse(json);
            var options = '<option value="0" selected="selected">Все города</option>';
            for(id in towns){
                options += '<option value="'+towns[id].id+'">'+towns[id].name+'</option>';
            }
            $('#selectedTown_id').html(options);
            $('#priceDowloadLink').attr('href','/rivercrs/api/v1/pricing/price/'+motorship_id+'/0');
        }
    });
}

$('#selectedMotorship_id').change(function() {
    fillTowns($(this).val());
});

$('#selectedTown_id').change(function() {
    var motorship_id = $('#selectedMotorship_id').val();
    $('#priceDowloadLink').attr('href','/rivercrs/api/v1/pricing/price/'+motorship_id+'/'+$(this).val());
});

fillTowns($('#selectedMotorship_id').val());