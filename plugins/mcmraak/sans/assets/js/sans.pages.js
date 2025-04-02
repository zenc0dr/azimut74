/*
    откулючил данную функцию для того, чтобы показывались вообще все курорты, а не только основные группы!
    @darkrogua
*/

/*var resort_id = $('#Form-field-Page-resort_id').val();

function fillResortsOptions(root_group_id)
{
    $.ajax({
        url: location.origin + '/sans/api/v1/groups/'+root_group_id,
        success: function (json)
        {
            var groups = JSON.parse(json);
            var options = '<option value="0" selected="selected">- -</option>';
            for(id in groups){
                options += '<option value="'+groups[id].id+'">'+groups[id].name+'</option>';
            }
            $('#Form-field-Page-resort_id').html(options);
            $('#Form-field-Page-resort_id option').removeAttr('selected');
            $('#Form-field-Page-resort_id option[value="'+resort_id+'"]').attr('selected', 'selected');
        }
    });

}

$('#Form-field-Page-root_id').change(function() {
    fillResortsOptions($(this).val());
});

fillResortsOptions($('#Form-field-Page-root_id').val());*/
