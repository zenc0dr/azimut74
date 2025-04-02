/* EVENTS */

/* Page load */
$(document).ready(function(){
  getRecords();
});

/* Mark changed cells */
$(document).on('input propertychange', '.pricingTable input', function(){
    $(this).css('background','#ffdbfa');
});

/* Get data from ajax */
function getRecords() {
    var motorship_id = $('meta[motorship_id]').attr('motorship_id');
    if(!motorship_id) motorship_id = 0;
     $.ajax({
         url: location.origin + '/rivercrs/api/v1/pricing/motorship/'+motorship_id,
         beforeSend: function (){
             $('#RecordsPreloader').show();
             $('#Records').hide();
         },
         success: function (data)
         {
             if(data == 'null'){
                 $('#RecordsPreloader').html('У данного теплохода отсутствуют заезды.');
                 return;
             }
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
                    $('[name="Motorships[price]"]').val(JSON.stringify(records));
                } changed = true;
              },
              deep: true
          }
     }
});
