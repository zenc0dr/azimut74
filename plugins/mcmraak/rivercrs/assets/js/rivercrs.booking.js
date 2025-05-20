let BEX = new Vue({
    el: '#BookingExistVueWrap',
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
        modal_cabin: null,
        modal_cabin_info: null,
        info_block: false,
        anchor_assistant: false,
        yandex_id: null,

        exist_preloader: false,
    },
    mounted: function () {
        console.log('mounted: plugins/mcmraak/rivercrs/assets/js/rivercrs.booking.js')

        this.checkin_id = $('#BookingExist[checkin-id]').attr('checkin-id')
        this.checkin_data = JSON.parse($('meta[name="checkin-data"]').attr('content'))
        // {"town_id":46}

        this.loadDataFromJson() // Парсинг с meta@name='checkin-prices'
        // Всё попадает в data

        let $this = this
        $('.order.phone.orderBook').inputmask('+7(999) 999-99-99', {
            showMaskOnHover: true,
            "onincomplete": function () {
                $this.form.phone = this.value
            },
            "oncomplete": function () {
                $this.form.phone = this.value
            }
        })

    },
    computed: {
        selected() {
            let $return = []
            for (let i in this.rooms) {
                if (this.rooms[i].check) {
                    $return.push(this.getSelected(this.rooms[i], i))
                }
            }
            this.showAnchorAssistant(!!$return.length)
            return $return;
        },
    },
    filters: {
        priceFormat(value) {
            value += ''
            return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, " $1")
        }
    },
    methods: {
        loadData() {

            console.log('HALLO')

            let url = '/rivercrs/api/v2/exist/' + this.checkin_id
            url += this.cached ? '?cached=1' : '?cached=0'

            console.log('Запрос', url)

            this.sync(null, url, 'get', data => {
                console.log('Загружаются данные', data)
                this.decks = data.decks
                if (data.tariff_price1_title) {
                    this.tariff_price1_title = data.tariff_price1_title
                }
                if (data.tariff_price2) {
                    this.tariff_price2 = data.tariff_price2
                }
                if (data.tariff_price2_title) {
                    this.tariff_price2_title = data.tariff_price2_title
                }
                this.collectRooms(data.rooms)

                $('.booking-exist-preloader').hide()

                if (!this.cached && !data.cached) {
                    this.cached = true
                    this.loadData()
                }

                if (data.cached) {
                    this.cached = false
                }
            })
        },
        loadDataFromJson() {
            let data = JSON.parse($('meta[name="checkin-prices"]').attr('content'))
            this.decks = data.decks
            if (data.tariff_price1_title) {
                this.tariff_price1_title = data.tariff_price1_title
            }
            if (data.tariff_price2) {
                this.tariff_price2 = data.tariff_price2
            }
            if (data.tariff_price2_title) {
                this.tariff_price2_title = data.tariff_price2_title
            }
            this.collectRooms(data.rooms)
            this.exist_preloader = true
            this.loadExistData()
        },
        loadExistData() {
            let url = '/rivercrs/api/v2/exist/' + this.checkin_id + '?cached=1'

            console.log('url=' + url)

            this.sync(null, url, 'get', data => {
                console.log('Загружаются exist данные', data)
                this.decks = data.decks
                if (data.tariff_price1_title) {
                    this.tariff_price1_title = data.tariff_price1_title
                }
                if (data.tariff_price2) {
                    this.tariff_price2 = data.tariff_price2
                }
                if (data.tariff_price2_title) {
                    this.tariff_price2_title = data.tariff_price2_title
                }
                this.collectRooms(data.rooms)

                this.exist_preloader = false
            })
        },
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

            options = Object.assign(defaults, options)

            if (!options.async) {
                if (this.process) {
                    return
                }
            }

            $.ajax({
                type: type,
                url: location.origin + url,
                data: data,
                beforeSend: () => {
                    this.process = true
                },
                success: (data) => {
                    this.process = false
                    if (data) {
                        if (!options.html) {
                            data = JSON.parse(data)
                        }
                        callback(data)
                    } else {
                        callback()
                    }
                },
                error: function (x) {
                    console.log(x.responseText);
                },
            });
        },
        silentSync(url, data, callback) {
            $.ajax({
                url: location.origin + url,
                data,
                success: (response) => {
                    if (response) {
                        response = JSON.parse(response)
                        callback(response)
                    } else {
                        callback()
                    }
                },
                error: function (x) {
                    console.log(x.responseText);
                },
            })
        },
        uncheckRoom(item) {
            this.rooms[item.key].check = false
        },
        collectRooms(rooms) {
            for (let i in rooms) {
                rooms[i].check = false
                this.rooms.push(rooms[i])
            }
            console.log('this.rooms', this.rooms)
        },
        getSelectedCabins() {
            let $return = []
            for (let i in this.selected) {
                let item = this.rooms[this.selected[i].key]
                $return.push({
                    cabin_id: item.c,
                    deck_id: item.d,
                    num: item.n,
                })
            }
            return $return;
        },
        scrollBooking() {
            let pos = $('.booking-tab').offset().top - 100;
            $('body, html').animate({ scrollTop: pos }, 300);
        },
        scrollTable() {
            let pos = $('.tab-booking-table').offset().top - 100;
            $('body, html').animate({ scrollTop: pos }, 300);
        },
        sendBooking() {
            if (typeof ym === 'undefined') {
                this.send()
            } else {
                ym(13605125, 'getClientID', (clientID) => {
                    this.yandex_id = clientID

                    this.sync(
                        {yandex_id:clientID},
                        '/master.api.Log.addYandexId',
                        'get',
                        () => {
                            this.send()
                        }
                    )
                })
            }
        },
        send() {
            if (this.process) {
                return
            }

            //this.form.peoples = $('input[name="peoples"]').val()
            this.form.cabins = this.getSelectedCabins()
            this.form.checkin_id = this.checkin_id
            this.form.refer = location.href
            this.form.yandex_id = this.yandex_id

            console.log('Отправка формы', this.form)

            this.sync(this.form, '/rivercrs/api/booking', 'post', (data) => {
                if (data.alerts !== undefined) {
                    this.alerts = data.alerts;
                    if (data.success === true) {
                        this.form = {
                            confirm: false,
                            name: '',
                            phone: '',
                            email: '',
                            desc: '',
                            checkin_id: 0,
                            cabins: '',
                            peoples: 1,
                        }
                        this.aimCapture()
                    }
                }
            });
        },
        aimCapture() {
            let town_id = +this.checkin_data.town_id;
            if (town_id === 63) {
                this.yandexAim('booking_teplohod_64');
            }
            // Бронирование Круиза из Самары
            if (town_id === 61) {
                this.yandexAim('booking_teplohod_63');
            }
            // Бронирование Круиза из Волгограда
            if (town_id === 14) {
                this.yandexAim('booking_teplohod_34');
            }
            // Бронирование Круиза из Астрахани
            if (town_id === 2) {
                this.yandexAim('booking_teplohod_30');
            }
            // Бронирование Круиза из Казани
            if (town_id === 27) {
                this.yandexAim('booking_teplohod_16');
            }
            // Бронирование Круиза из Перми
            if (town_id === 53) {
                this.yandexAim('booking_teplohod_59');
            }
            // Бронирование Круиза из Н.Н
            if (town_id === 88) {
                this.yandexAim('booking_teplohod_52');
            }
            // Бронирование Круиза из Москвы
            if (town_id === 46) {
                this.yandexAim('booking_teplohod_199');
            }
            // Бронирование Круиза из Санкт-Петербурга
            if (town_id === 60) {
                this.yandexAim('booking_teplohod_78');
            }
            this.yandexAim('booking_teplohod_all_cities');
        },
        isFunction(functionToCheck) {
            functionToCheck = window[functionToCheck]
            let getType = {}
            return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]'
        },
        // Яндекс-цель:
        yandexAim: function (target) {
            if (!this.isFunction('ym')) {
                return
            }
            ym(13605125, 'reachGoal', target);
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
            /* Открытие модального окна с описание каюты */
            let cabin_id = cabin.id
            this.silentSync('/rivercrs/api/cabinInfo', { cabin_id }, (response) => {
                this.modal_cabin_info = response.html
            })
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

            this.silentSync('/rivercrs/api/openCabin', send_data, (response) => {
                this.modal_cabin = response.html
            })
        },
        markCabin() {
            for (let i in this.rooms) {
                if (this.rooms[i].n === this.selected_cabin.n) {
                    if (this.rooms[i].check) {
                        this.rooms[i].check = false
                    } else {
                        this.rooms[i].check = true
                    }
                }
            }
        },
        goToBooking() {
            this.modals.scheme = false
            this.modal_cabin = null
            this.scrollBooking()
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
        testForAA(show) {
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
            if (test) {
                return false
            }
            return true
        },
    }
});
