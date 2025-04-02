let BEX = new Vue({
    el: '#BookingExist',
    delimiters: ['${', '}'],
    data: {
        process: false,
        checkin_id: false,
        checkin_data: null,
        selected_category_id: 0,
        selected_cabin: [],
        decks: [],
        tariff_price1_title: false,
        tariff_price2: false,
        tariff_price2_title: false,
        rooms: [],
        form: {
            confirm: false,
            name: '',
            phone: '',
            email: '',
            desc: '',
            checkin_id: 0,
            cabins: '',
            peoples: 1,
        },
        alerts: [],
        modals: {
            scheme: false,
            offer: false,
        },
        info_block: false,
        anchor_assistant: false,
    },
    mounted() {
        this.checkin_id = $('#BookingExist[checkin-id]').attr('checkin-id')
        this.checkin_data = JSON.parse($('meta[name="checkin-data"]').attr('content'))
        this.yandexAim('open_kruiz_form')
        let $this = this

        this.sync(null, '/rivercrs/api/v2/exist/' + this.checkin_id, 'get', function (data) {
            $this.decks = data.decks
            if (data.tariff_price1_title) {
                $this.tariff_price1_title = data.tariff_price1_title
            }
            if (data.tariff_price2) {
                $this.tariff_price2 = data.tariff_price2
            }
            if (data.tariff_price2_title) {
                $this.tariff_price2_title = data.tariff_price2_title
            }
            $this.collectRooms(data.rooms)
            $('.booking-exist-preloader').hide()
        });

        $('.order.phone.orderBook').inputmask(
            '+7(999) 999-99-99',
            {
                showMaskOnHover: true,
                "onincomplete": function () {
                    $this.form.phone = this.value;
                },
                "oncomplete": function () {
                    $this.form.phone = this.value;
                }
            })
    },
    computed: {
        selected: function () {
            let $return = [];
            for (let i in this.rooms) {
                if (this.rooms[i].check) {
                    $return.push(this.getSelected(this.rooms[i], i));
                }
            }
            this.showAnchorAssistant(!!$return.length);
            return $return;
        },
    },
    filters: {
        priceFormat(value) {
            value += '';
            return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, " $1");
        }
    },
    methods: {
        getSelected(item, key) {
            for (let i in this.decks) {
                for (let ii in this.decks[i].cabins) {
                    if (this.decks[i].cabins[ii].id === item.c) {
                        return {
                            num: item.n,
                            name: this.decks[i].cabins[ii].name,
                            deck: this.decks[i].name,
                            key: key,
                        }
                    }
                }
            }
        },
        sync(data, url, type, callback, options) {

            let defaults = {
                html: false,
                async: false,
            }

            options = Object.assign(defaults, options);

            if (!options.async) {
                if (this.process) {
                    return
                }
            }

            let $this = this
            $.ajax({
                type: type,
                url: location.origin + url,
                data: data,
                beforeSend: function () {
                    $this.process = true;
                },
                success: function (data) {
                    $this.process = false;
                    if (data) {
                        if (!options.html) {
                            data = JSON.parse(data);
                        }
                        callback(data);
                    } else {
                        callback();
                    }
                },
                error: function (x) {
                    console.log(x.responseText);
                },
            });
        },
        uncheckRoom(item) {
            this.rooms[item.key].check = false
        },
        collectRooms(rooms) {
            for (let i in rooms) {
                rooms[i].check = false;
                this.rooms.push(rooms[i]);
            }
        },
        getSelectedCabins() {
            let $return = []
            for (let i in this.selected) {
                let item = this.rooms[this.selected[i].key]
                $return.push({
                    cabin_id: item.c,
                    deck_id: item.d,
                    num: item.n,
                });
            }
            return $return
        },
        sendBooking: function () {
            if (this.process) {
                return
            }

            this.form.peoples = $('input[name="peoples"]').val()
            this.form.cabins = this.getSelectedCabins()
            this.form.checkin_id = this.checkin_id

            var $this = this;
            this.sync(this.form, '/rivercrs/api/v2/booking/send', 'post', function (data) {
                if (data.alerts !== undefined) {
                    $this.alerts = data.alerts
                    if (data.success === true) {
                        $this.form = {
                            confirm: false,
                            name: '',
                            phone: '',
                            email: '',
                            desc: '',
                            checkin_id: 0,
                            cabins: '',
                            peoples: 1,
                        }
                        $this.aimCapture()
                    }
                }
            });
        },
        aimCapture() {
            let town_id = +this.checkin_data.town_id
            if (town_id === 63) {
                this.yandexAim('booking_teplohod_64')
            }
            // Бронирование Круиза из Самары
            if (town_id === 61) {
                this.yandexAim('booking_teplohod_63')
            }
            // Бронирование Круиза из Волгограда
            if (town_id === 14) {
                this.yandexAim('booking_teplohod_34')
            }
            // Бронирование Круиза из Астрахани
            if (town_id === 2) {
                this.yandexAim('booking_teplohod_30')
            }
            // Бронирование Круиза из Казани
            if (town_id === 27) {
                this.yandexAim('booking_teplohod_16')
            }
            // Бронирование Круиза из Перми
            if (town_id === 53) {
                this.yandexAim('booking_teplohod_59')
            }
            // Бронирование Круиза из Н.Н
            if (town_id === 88) {
                this.yandexAim('booking_teplohod_52')
            }
            // Бронирование Круиза из Москвы
            if (town_id === 46) {
                this.yandexAim('booking_teplohod_199')
            }
            // Бронирование Круиза из Санкт-Петербурга
            if (town_id === 60) {
                this.yandexAim('booking_teplohod_78')
            }
            this.yandexAim('booking_kruiz')
        },
        isFunction(functionToCheck) {
            functionToCheck = window[functionToCheck];
            var getType = {};
            return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';
        },
        // Яндекс-цель:
        yandexAim(target) {
            if (!this.isFunction('ym')) {
                return
            }
            ym(13605125, 'reachGoal', target)
        },
        wrong(wrong_field_name) {
            if (!this.alerts.length) {
                return
            }
            for (let i in this.alerts) {
                if (this.alerts[i].field === wrong_field_name) {
                    return true
                }
            }
        },
        showScheme(cabin_id) {
            this.selected_category_id = cabin_id
            this.modals.scheme = true
        },
        getPointClass(point) {
            let cl = ''
            if (point.check) {
                cl = 'is-selected'
            } else {
                cl = (point.f) ? 'is-actual' : 'on-request'
            }
            if (this.selected_category_id === point.c) {
                cl += ' selected-category'
            }
            if (point.n.length > 5) {
                cl += ' small-text'
            }
            return cl
        },
        openInfo(cabin) {
            let cabin_id = cabin.id
            this.sync(null, '/rivercrs/api/v1/cabin/modal/' + cabin_id, 'get', function (html) {
                $('topmodal modalbody').html(html)
                owlGalleryBind()
                $('topmodal').fadeIn(300)
            }, {html: true})
        },
        openCabin(cabin) {
            this.selected_cabin = cabin
            let send_data = {
                'checkin_id': this.checkin_id,
                'check': cabin.check,
                'c': cabin.c,
                'n': cabin.n,
                'f': cabin.f,
                'd': cabin.d,
            }

            this.sync(send_data, '/rivercrs/api/v2/cabin/open', 'post', function (data) {
                $('topmodal modalbody').html(data.html)
                owlGalleryBind()
                $('topmodal').fadeIn(300)
            })
        },
        markCabin: function () {
            for (let i in this.rooms) {
                if (this.rooms[i].n === this.selected_cabin.n) {
                    this.rooms[i].check = !this.rooms[i].check
                }
            }
        },
        showAnchorAssistant(show) {
            let test = this.testForAA(show)
            if (!this.anchor_assistant && test) {
                $('#AnchorAssistant').addClass('show')
                this.anchor_assistant = true
            }
            if (this.anchor_assistant && !test) {
                $('#AnchorAssistant').removeClass('show')
                this.anchor_assistant = false
            }
        },
        testForAA: function (show) {
            if (show === false) {
                return false
            }
            if (show === undefined && !$('.bex-booking-line').length) {
                return false
            }
            if (this.modals.scheme) {
                return true
            }
            let modal_height = $('rivercrs_booking>div').height()
            let modal_scroll = $('rivercrs_booking').scrollTop()
            let test = modal_scroll > modal_height - 1200
            return !test
        },
    }
});

$(document).on('click', 'rivercrs_booking .bex-modal-close', function () {
    $(this).closest('.bex-modal').remove()
});

$(document).on('click', '.bex-hint i', function () {
    $(this).next().addClass('show');
});

$(document).mouseup(function (e) {
    var container = $('.bex-scheme-info .bex-hint>div.show');
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.removeClass('show');
    }
});

$('rivercrs_booking').scroll(function () {
    BEX.showAnchorAssistant();
});

$(document).on('click', '#AnchorAssistant>div', function () {
    BEX.modals.scheme = false;
    var modal_height = $('rivercrs_booking>div').height();
    $('rivercrs_booking').animate({scrollTop: modal_height}, 800);
});

$(document).on('click', '.tariff-name-name.desc', function () {
    $(this).next('.tariff-name-desc').show()
})

$(document).mouseup(function (e) {
    var container = $('.tariff-name-desc');
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.hide();
    }
});
