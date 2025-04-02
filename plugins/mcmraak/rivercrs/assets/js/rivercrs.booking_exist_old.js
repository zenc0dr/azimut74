var BokingExist = new Vue({
    el: '#BookingExist',
    delimiters: ['${', '}'],
    data: {
        checkin_id: false,
        selected_category_id: 0,
        selected_cabin: [],
        booking: [],
        decks: [],
        form: {
            confirm: false,
            name:'',
            phone:'',
            email:'',
            desc: '',
            checkin_id: 0,
            cabins: '',
            peoples: 1,
        },
        alerts: [],
        eds_rooms: [],
        rooms: [],
        modals: {
            scheme: false,
        },
    },
    mounted: function () {
        this.checkin_id = $('#BookingExist[checkin-id]').attr('checkin-id');
        var $this = this;
        this.sync(null, '/rivercrs/api/v2/exist/'+this.checkin_id, 'get', function (data) {
            console.log(data);
            $this.booking = data.booking;
            $this.decks = data.decks;
            $this.eds_rooms = data.rooms;
            $this.roomsFitter();
            $('.booking-exist-preloader').hide();
        });
    },
    filters: {
        priceFormat: function (value) {
            value += '';
            return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, " $1");
        }
    },
    methods: {
        sync: function (data, url, type, callback) {
            var $this = this;
            $.ajax({
                type: type,
                url: location.origin + url,
                data:data,
                success: function (data)
                {
                    if(data) {
                        data = JSON.parse(data);
                        callback(data);
                    } else {
                        callback();
                    }
                },
                error: function(x)
                {
                    console.log(x.responseText);
                },
            });
        },
        deckHasCabin: function (deck, cabin) {
            for(var i in deck.cabins){
                if(deck.cabins[i] == cabin.category.id) {
                    return true;
                }
            }
        },
        sendBooking: function () {

            this.form.peoples = $('input[name="peoples"]').val();
            this.form.cabins = this.getCabins();
            this.form.checkin_id = this.checkin_id;

            var $this = this;
            this.sync(this.form, '/rivercrs/api/v2/booking/send', 'post', function (data) {
                if(data.alerts !== undefined) {
                    $this.alerts = data.alerts;
                    if(data.success==true) {
                        $this.form = {
                            confirm: false,
                            name:'',
                            phone:'',
                            email:'',
                            desc: '',
                            checkin_id: 0,
                            cabins: '',
                            peoples: 1,
                        }
                    }
                }
            });
        },
        getCabins: function () {
            var cabins = [];
            for(var i in this.booking){
                for(var ii in this.booking[i].rooms) {
                    var status = this.booking[i].rooms[ii].status;
                    if(status) {
                        cabins.push({
                            'cabin_id':this.booking[i].category.id,
                            'number':this.booking[i].rooms[ii].num,
                        });
                    }
                }
            }
            return cabins;
        },
        wrong: function (wrong_field_name) {
            if(!this.alerts.length) return;
            for(i in this.alerts) {
                if(this.alerts[i].field === wrong_field_name) return true;
            }
        },
        showScheme: function(item){
            this.selected_category_id = item.category.id;
            this.modals.scheme = true;
        },

        // Собирает точки на схеме
        roomsFitter: function () {
            for(var i in this.eds_rooms) {
                this.rooms.push(this.addPoint(this.eds_rooms[i]));
            }
        },

        // Оформляет одну точку для схемы
        addPoint: function (room) {
            for(var i in this.booking){
                for(var ii in this.booking[i].rooms) {
                    var num = this.booking[i].rooms[ii].num;
                    if(num != 'Под запрос') {
                        if(room.n == num) {
                            return {
                                n:room.n,
                                c:this.booking[i].category.id,
                                x:room.x,
                                y:room.y,
                                w:room.w,
                                h:room.h,
                                s:this.booking[i].rooms[ii].status,
                                e:true, // free
                                addr: {i:i,ii:ii}
                            }
                        }
                    }
                }
            }
            return {
                n:room.n,
                c:room.c,
                x:room.x,
                y:room.y,
                w:room.w,
                h:room.h,
                s:false,
                e:false // free
            }
        },
        openCabin: function (cabin) {
            this.selected_cabin = cabin;
            var $this = this;
            this.sync(null, '/rivercrs/api/v2/cabin/'+cabin.c, 'get', function (data) {
                $('topmodal modalbody').html(data.html);
                owlGalleryBind();
                $('topmodal').fadeIn(300);
            })
        },
        markCabin: function () {
            if(this.selected_cabin.addr !== undefined) {
                var i = this.selected_cabin.addr.i;
                var ii = this.selected_cabin.addr.ii;
                this.booking[i].rooms[ii].status = true;

            } else {
                //console.log(this.selected_cabin.c)
                var category_id = this.selected_cabin.c;
                for(var i in this.booking){
                    if(this.booking[i].category.id == category_id) {
                        this.booking[i].rooms[0].status = true;
                    }
                }
            }
            this.roomsFitter();
        }
    }
});

$(document).on('click', 'rivercrs_booking .bex-modal-close', function () {
    $(this).closest('.bex-modal').remove()
});