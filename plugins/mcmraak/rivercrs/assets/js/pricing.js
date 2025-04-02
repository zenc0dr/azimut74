/* EVENTS */

/* Page load */
$(document).ready(function(){
  getRecords();
  defaultTimes();
});

function defaultTimes(){
    if($('#DatePicker-formDate-time-date').val() == '')
        $('#DatePicker-formDate-time-date').val('00:00');
    if($('#DatePicker-formDateb-time-dateb').val() == '')
        $('#DatePicker-formDateb-time-dateb').val('00:00');
}

/* Change motorship_id field */
$('#Form-field-Checkins-motorship').change(function() {
    getRecords();
});


/* Mark changed cells */
$(document).on('input propertychange', '.pricingTable input', function(){
    $(this).css('background','#ffdbfa');
});

/* Get data from ajax */
function getRecords() {
    var motorship_id = $('#Form-field-Checkins-motorship').val();
    var checkin_id = $('meta[checkin_id]').attr('checkin_id');
     $.ajax({
         type: 'post',
         url: location.origin + '/rivercrs/api/v1/pricing/get',
         data: {'motorship_id':motorship_id, 'checkin_id':checkin_id},
         beforeSend: function (){
             $('#RecordsPreloader').show();
             $('#Records').hide();
         },
         success: function (data)
         {
           Pricing.records = JSON.parse(data);
           $('#RecordsPreloader').hide();
           $('#Records').fadeIn(300);
         }
     });
}

/* VUE */

var changed = false; // Change data trigger
var Pricing = new Vue({
	 el: '#Records',
	 data: {
        records: []
     },
     watch: {
         'records': {
              handler: function(records) {
                  if(changed){
                      console.log('data changed!');
                      $('[name="Checkins[pricing]"]').val(JSON.stringify(records));
                  } changed = true;
              },
              deep: true
          }
     }
});
