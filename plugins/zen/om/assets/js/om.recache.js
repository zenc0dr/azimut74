function z(message){
    console.log(message);
}

$(document).on('mouseup', 'a.move', function(){
    $('#RecacheDialog').fadeIn(300);
});

$(document).on('click','#RecacheStart', function(){
    $.ajax({
        type: 'post',
        url: location.origin + '/zen/om/recache/categoriescount',
        beforeSend: function (){
            $('#RecacheStart').attr('disabled','disabled');
        },
        success: function (count)
        {
            z('categoriescount='+count);
            updateUrls(0,count,'categories');
        }
    });
});

function updateUrls(start,count,target){
    var steep = 100;
    $('#RecacheLog').text('['+target+'] Records processed: '+start);
    z('start='+start+' count='+count+' target='+target);
    if(start < count) {
        var int_parts = Math.floor(count / steep);
        var int_progress = start / steep;
        var progress = (100*int_progress)/int_parts;
        $('#RecacheProgress').css('width',progress+'%');

        $.ajax({
            type: 'post',
            url: location.origin + '/zen/om/recache/update'+target,
            data: {'page': int_progress,'steep':steep},
            success: function (processed_count) {
                z('processed='+processed_count);
                start += steep;
                updateUrls(start,count,target);
            }
        });
    } else {

        if(target == 'categories'){
            updateItems();
        } else {
            $('#RecacheDialog').hide();
            $('#RecacheStart').removeAttr('disabled');
            $('#RecacheProgress').css('width', '0');
        }
    }
}

function updateItems(){
    $.ajax({
        type: 'post',
        url: location.origin + '/zen/om/recache/itemscount',
        success: function (count)
        {
            updateUrls(0,count,'items');
        }
    });
}