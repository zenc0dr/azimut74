let ProkSearch = new Vue({
    el: '#ProkSearch',
    delimiters: ['${', '}'],
    data: {
        outer_result_container: 0,
        ajaxQueryProcess: false,
        empty_answer: 'Поиск не дал результатов, измените условия поиска и попробуйте снова...',
        loaded: false,
        process: false,
        message: null,
        available_dates: [],
        s_table: [],
        DB: {
            checkins: {},
            ways: {},
            towns: {},
            ships: {},
            soft: {}
        },
        soft_towns: [],
        years: [],
        form: {
            town: null,
            dest: null,
            date_1: null,
            date_1_cahged: false,
            date_2: null,
            date_2_cahged: false,
            date_range: null,
            days: null,
            ship: null,
        },
        fixed_ship: false, // Фиксированный теплоход
        ids: [],
        pages_count: 0,
        page: 0,
        per_page: 15,
        result: '',
        date_range: false, // Не используется
        find_count: 0,
        alert: null,
        checkin_id: null,
        queries_count: 0,
        search_query: null // Запрос с главной
    },

    mounted: function () {
        //this.outer_result_container = parseInt($('meta[name="outer_result_container"]').attr('content'));
        this.fixed_ship = parseInt($('meta[name="fixed_ship"]').attr('content'));
        this.getSearchQuery(() => {
            //this.addBookingLinkEvent() // Не нужно
        })
        this.startCheckin()

        let local_db = this.getLocalDb()

        if (local_db) {
            this.bootstrap(local_db)
        } else {
            this.sync(null, 'mounted', (data) => {
                console.log('Запрос выпонен')
                this.bootstrap(data)
                this.setLocalDb(data)
            });
        }
    },

    watch: {
        form: {
            handler: function () {
                if (this.form.date_1 && this.form.date_2) {
                    if (this.dateToSec(this.form.date_1) > this.dateToSec(this.form.date_2)) {
                        this.form.date_1 = this.form.date_2
                    }
                }
            },
            deep: true,
        },
        pages_count: function (val) {
            if (this.outer_result_container) {
                RiverResults.pages_count = val
            }
        },
        page: function (val) {
            if (this.outer_result_container) {
                RiverResults.page = val
            }
        },
        result: function (val) {
            if (this.outer_result_container) {
                RiverResults.result = val
            }
        }
    },

    methods: {
        getLocalDb() {
            let local_db_time = localStorage.getItem('prok_db_time')
            if (!local_db_time) {
                return
            }
            let time_to_live_sec = 3600
            let time_now_sec = Math.floor(new Date().getTime() / 1000)
            if (time_now_sec - local_db_time < time_to_live_sec) {
                let let_local_db = localStorage.getItem('prok_db')
                let_local_db = JSON.parse(let_local_db)
                return let_local_db
            }
            console.log('Время вышло')
            return false
        },
        setLocalDb(data) {
            let time_now_sec = Math.floor(new Date().getTime() / 1000)
            localStorage.setItem('prok_db_time', time_now_sec)
            data = JSON.stringify(data)
            localStorage.setItem('prok_db', data)
        },
        sync: function (data, action, callback) {
            if (this.ajaxQueryProcess) {
                return
            }

            let url = '/prok/api/' + action

            console.log(url, data)

            $.ajax({
                type: 'post',
                url: location.origin + url,
                data,
                beforeSend: () => {
                    this.ajaxQueryProcess = true
                    if (action !== 'mounted') {
                        setTimeout(() => {
                            if (this.ajaxQueryProcess) {
                                this.process = true
                            }
                        }, 300)
                    }
                },
                success: (data) => {
                    this.queries_count++
                    this.ajaxQueryProcess = false
                    this.process = false
                    if (data) {
                        data = JSON.parse(data)
                        if (data.alerts) {
                            this.message = data.message
                        }
                        callback(data)
                    } else {
                        callback()
                    }
                },
                error: (x) => {
                    this.ajaxQueryProcess = false
                    this.process = false
                    this.message = x.responseText
                },
            });
        },

        bootstrap(data) {
            this.DB = data
            this.formFill(() => {
                this.loaded = true;
                this.selectActualDate()
                this.getFilterOptions(() => {
                    this.search()
                })
                if (this.fixed_ship) {
                    this.form.ship.value = [parseInt(this.fixed_ship)]
                    this.search()
                }
            })
        },

        addBookingLinkEvent() {
            let $this = this
            $(document).on('click', 'a.booking-btn', function (event) {
                event.preventDefault()
                location.href = this.href + '?search_query=' + JSON.stringify($this.getSearchObject())
            })
        },

        getSearchQuery(callback) {
            let search_query = $('meta[name="query_from_start"]').attr('content')
            if (search_query) {
                this.search_query = search_query
                //let href = location.href.split('?')
                // TODO: Расскоментировать
                //window.history.pushState(null, null, href[0]);
                callback()
            }
        },

        // Получить метку конкретного заезда
        startCheckin() {
            if ($('checkin-id').length) {
                this.checkin_id = $('checkin-id').attr('id')
            }
        },

        // Добавить запись в таблицу поиска
        toTable: function (way) {
            if (!this.s_table[way.checkin_id]) {
                this.s_table[way.checkin_id] = {t: [], d: []}
            }

            let town_id = (this.DB.soft[way.town_id]) ? this.DB.soft[way.town_id] : way.town_id

            if (way.start) {
                this.s_table[way.checkin_id].t[town_id] = true
            } else {
                this.s_table[way.checkin_id].d[town_id] = true
            }
        },

        // Заполнить элементы управления
        formFill: function (callback) {

            /* Dropdown options in objects (for collectiong) */
            let town_obj = {},
                dest_obj = {},
                days_obj = {},
                ship_obj = {}

            // Collect town and desc
            for (let i in this.DB.ways) {
                let town_id = this.DB.ways[i].town_id
                if (!this.DB.soft[town_id]) {
                    if (this.DB.ways[i].start) {
                        // Add to town options
                        if (this.DB.towns[town_id]) {
                            town_obj[town_id] = this.DB.towns[town_id]
                        }
                    } else {
                        // Add to dest options
                        if (this.DB.towns[town_id]) {
                            dest_obj[town_id] = this.DB.towns[town_id]
                        }
                    }
                }
                this.toTable(this.DB.ways[i])
            }

            // Collect days
            for (let i in this.DB.checkins) {
                days_obj[this.DB.checkins[i].days] = this.DB.checkins[i].days + ''
                this.available_dates.push(this.secToDate(this.DB.checkins[i].date))
            }

            // Оставить уникальные даты
            this.available_dates = this.uniqueArr(this.available_dates)

            // Collect ships
            for (let i in this.DB.ships) {
                ship_obj[i] = this.DB.ships[i]
            }

            // Add to model town
            this.form.town = {
                value: 0,
                options: this.objToArray(town_obj, 1)
            }

            // Add to model dest
            this.form.dest = {
                value: 0,
                options: this.objToArray(dest_obj, 1)
            }

            // Add to model days
            this.form.days = {
                value: 0,
                options: this.objToArray(days_obj)
            }

            // Add to model ship
            this.form.ship = {
                value: 0,
                options: this.objToArray(ship_obj, 1)
            }

            callback()
        },

        // Сортировать опции dropdown
        sortOptions(options) {
            options.sort(function (a, b) {
                if (a.name > b.name) {
                    return 1
                }
                if (a.name < b.name) {
                    return -1
                }
                return 0
            })
            return options
        },

        // Выставить кнопки быстрого выбора года
        yearButtons: function () {
            let date = new Date()
            let year = date.getFullYear()
            let nextYear = new Date(year + 1, 0, 1) / 1000
            let showNexYear = false
            for (let i in this.DB.checkins) {
                let checkin_date = this.DB.checkins[i].date
                if (checkin_date > nextYear) {
                    showNexYear = true
                }
            }
            this.years.push({value: year, active: true})
            if (showNexYear) {
                this.years.push({value: year + 1, active: false})
            }
        },

        // Выбрать активный год
        setActiveYear: function (item) {
            for (let i in this.years) {
                if (this.years[i].value === item.value) {
                    this.years[i].active = true
                } else {
                    this.years[i].active = false
                }
            }
            this.selectActualDate()
        },

        // Полное кланирование объекта без связей
        newObject: function (obj) {
            return JSON.parse(JSON.stringify(obj))
        },

        // Преобразовать объект в массив
        objToArray: function (obj, sort) {
            let $return = []
            for (let i in obj) {
                $return.push({id: i, name: obj[i]})
            }

            if (sort) {
                $return = this.sortOptions($return)
            }

            return $return
        },

        // Оставить только уникальные записи массива
        uniqueArr: function (arr) {
            let obj = {}
            for (let i = 0; i < arr.length; i++) {
                let str = arr[i]
                obj[str] = true
            }
            return Object.keys(obj)
        },

        // Выбрать актуальные даты в календарях
        selectActualDate: function () {
            let now = this.nowInSeconds()
            let date_1 = null
            let date_2 = null

            // Если есть следующий год
            let nextYear = (this.years.length > 1) ? this.years[1].value : 0
            let nextYearActive = !!(nextYear && this.years[1].active)

            if (nextYear) {
                nextYear = new Date(nextYear, 0, 1, 0, 0, 0, 0)
                nextYear = parseInt(nextYear.getTime() / 1000)
            }

            for (let i in this.DB.checkins) {
                if (nextYearActive) {
                    if (date_1 == null && this.DB.checkins[i].date > nextYear) {
                        date_1 = this.secToDate(this.DB.checkins[i].date)
                    }
                } else {
                    if (date_1 == null && this.DB.checkins[i].date > now) {
                        date_1 = this.secToDate(this.DB.checkins[i].date)
                    }
                }

                if (nextYearActive) {
                    date_2 = this.secToDate(this.DB.checkins[i].date)
                } else {
                    if (nextYear) {
                        if (date_2 == null && this.DB.checkins[i].date > nextYear) {
                            date_2 = this.secToDate(this.DB.checkins[i].date)
                        }
                    }
                }

                if (!nextYearActive && date_1 && date_2) {
                    break
                }
            }

            this.form.date_1 = date_1
            this.form.date_2 = date_2
        },

        // Открыть календарь (Air Datepicker)
        openDP: function (date_num) {
            let $this = this;
            let datepicker = $('[name="date-' + date_num + '"]').datepicker({
                //language: $this.L,
                autoClose: true,
                onRenderCell: function (date, cellType) {
                    if (!$this.available_dates.length) {
                        return
                    }
                    let currentDate = date.getTime();
                    currentDate = $this.secToDate(currentDate / 1000)
                    let currentDay = date.getDate();
                    if (cellType === 'day' && $this.available_dates.indexOf(currentDate) !== -1) {
                        return {
                            html: '<span class="calendar-note">' + currentDay + '</span>'
                        }
                    }
                    if (cellType === 'day' && $this.available_dates.indexOf(currentDate) === -1) {
                        return {
                            disabled: true
                        }
                    }
                },
                onSelect: function (selected_date) {
                    $this.form['date_' + date_num] = selected_date;
                    $this.form['date_' + date_num + '_cahged'] = true; // Пометка об изменении даты
                }
            }).data('datepicker');


            if (date_num === 1 && this.form.date_1) {
                let ad = new Date(this.dateToSec(this.form.date_1) * 1000)
                datepicker.selectDate(ad);
            }


            if (date_num === 2) {
                if (this.form.date_1_cahged && !this.form.date_2_cahged) {
                    this.form.date_2 = this.form.date_1
                }
            }

            if (date_num === 2 && this.form.date_2) {
                let ad = new Date(this.dateToSec(this.form.date_2) * 1000)
                datepicker.selectDate(ad)
            }
            datepicker.show()
        },


        // Открыть календарь (Air Datepicker с диапазоном дат) - Отключено
        openAirRange: function () {
            let $this = this
            let datepicker = $('[name="date-range').datepicker({
                autoClose: true,
                onRenderCell: function (date, cellType) {
                    if (!$this.available_dates.length) {
                        return
                    }
                    let currentDate = date.getTime()
                    currentDate = $this.secToDate(currentDate / 1000)
                    let currentDay = date.getDate()
                    if (cellType === 'day' && $this.available_dates.indexOf(currentDate) !== -1) {
                        return {
                            html: '<span class="calendar-note">' + currentDay + '</span>'
                        }
                    }
                    if (cellType === 'day' && $this.available_dates.indexOf(currentDate) === -1) {
                        return {
                            disabled: true
                        }
                    }
                },
                onSelect: function (selected_date) {
                    $this.form['date_' + date_num] = selected_date;
                }
            }).data('datepicker')
            datepicker.show()
        },

        // Открыть календарь (DateRangePicker) - Отключено
        openDR: function () {
            if (this.date_range) {
                return
            }
            this.date_range = true;
            let calendar = $('input[name="date-range"]')
            calendar.daterangepicker({
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(32, 'hour'),
                locale: {
                    format: 'M/DD hh:mm A'
                },
            });
            calendar.on('apply.daterangepicker', function (ev, picker) {
                console.log(picker.startDate.format('YYYY-MM-DD'))
                console.log(picker.endDate.format('YYYY-MM-DD'))
            });
            calendar.click()
        },

        // @input: (int) seconds ex: 1567022400
        // @output: (string) formated date ex: 27.11.2017
        secToDate: function (date) {
            date *= 1000
            date = new Date(date)
            let dd = date.getDate()
            if (dd < 10) {
                dd = '0' + dd
            }
            let mm = date.getMonth() + 1
            if (mm < 10) {
                mm = '0' + mm
            }
            let yy = date.getFullYear()
            if (yy < 10) {
                yy = '0' + yy
            }
            return dd + '.' + mm + '.' + yy
        },

        // @input: (string) formated date ex: 27.11.2017
        // @output: (int) seconds ex: 1567022400
        dateToSec: function (date) {
            date = date.split('.')
            let y = parseInt(date[2])
            let m = parseInt(date[1]) - 1
            let d = parseInt(date[0])
            return new Date(y, m, d) / 1000
        },

        // @output: (int) seconds ex: 1567022400
        nowInSeconds: function () {
            let now = new Date()
            return parseInt(now.getTime() / 1000)
        },


        // Подбор checkin_id для колеекции this.ids
        searchFilter: function (callback) {
            if (this.checkin_id) {
                callback([this.checkin_id])
                this.checkin_id = null;
                return
            }

            let ids = [], he = true;
            for (let i in this.DB.checkins) {
                let checkin = this.DB.checkins[i]

                // Days test
                he = this.searchTest(checkin.days, this.form.days.value)
                if (!he) {
                    continue
                }

                // Ship test
                he = this.searchTest(checkin.ship_id, this.form.ship.value)
                if (!he) {
                    continue
                }

                // Town test
                he = this.fastTownTest(checkin.id)
                if (!he) {
                    continue
                }

                // Dest test
                he = this.fastTownTest(checkin.id, 1)
                if (!he) {
                    continue
                }

                // Date OF test
                if (this.form.date_1) {
                    let date_1 = this.dateToSec(this.form.date_1)
                    he = checkin.date >= date_1
                    if (!he) {
                        continue
                    }
                }

                // Date TO test
                if (this.form.date_2) {
                    let date_2 = this.dateToSec(this.form.date_2)
                    he = checkin.date <= date_2
                    if (!he) {
                        continue
                    }
                }
                ids.push(checkin.id)
            }

            callback(ids);
        },

        aimCapture() {
            if (this.queries_count < 2) {
                return
            }
            this.form.town.value.map(item => {
                let town_id = +item
                if (town_id === 63) {
                    this.yandexAim('pro-kruiz_booking_64') // Саратов
                }
                else if (town_id === 61) {
                    this.yandexAim('pro-kruiz_booking_63') // Самара
                }
                else if (town_id === 14) {
                    this.yandexAim('pro-kruiz_booking_34') // Волгоград
                }
                else if (town_id === 2) {
                    this.yandexAim('pro-kruiz_booking_30') // Астрахань
                }
                else if (town_id === 27) {
                    this.yandexAim('pro-kruiz_booking_16') // Казань
                }
                else if (town_id === 53) {
                    this.yandexAim('pro-kruiz_booking_59') // Пермь
                }
                else if (town_id === 88) {
                    this.yandexAim('pro-kruiz_booking_52') // Нижний Новогрод
                }
                else if (town_id === 46) {
                    this.yandexAim('pro-kruiz_booking_199') // Москва
                }
                else if (town_id === 60) {
                    this.yandexAim('pro-kruiz_booking_78') // Санкт-Петербург
                }
                else {
                    this.yandexAim('pro-kruiz_booking_all_cities') // Любые другие города
                }
            })
        },

        // Яндекс-цель:
        yandexAim(target) {
            ym(87436963, 'reachGoal', target)
        },

        // Нажатие на кнопку поиск
        search() {
            if (this.ajaxQueryProcess) {
                return
            }

            this.searchFilter((ids) => {
                this.idsBuild(ids, () => {
                    this.showAlert()
                    if (!this.ids.length) {
                        this.result = this.empty_answer
                        return
                    }
                    this.query()
                })
            })
        },

        // Проверка соответствия значения из итема и элемента формы
        searchTest: function (item_value, form_value) {
            if (!form_value) {
                return true
            }
            item_value = parseInt(item_value)

            if (Array.isArray(form_value)) {
                if (form_value.length === 0) {
                    return true
                }
                if (form_value.indexOf(item_value) !== -1) {
                    return true
                }
            } else {
                if (item_value === form_value) {
                    return true
                }
            }
            return false
        },

        // Быстрый поиск по городу
        fastTownTest(checkin_id, table_num) {
            if (!table_num) {
                if (!this.form.town.value) {
                    return true
                }
                if (!this.form.town.value.length) {
                    return true
                }
                for (let i in this.form.town.value) {
                    let town_id = this.form.town.value[i]
                    if (this.s_table[checkin_id].t[town_id]) {
                        return true
                    }
                }
            } else {
                if (!this.form.dest.value) {
                    return true
                }
                if (!this.form.dest.value.length) {
                    return true
                }
                for (let i in this.form.dest.value) {
                    let town_id = this.form.dest.value[i]
                    if (this.s_table[checkin_id].d[town_id]) {
                        return true
                    }
                }
            }
            return false
        },

        // Собирает id-шники и сортирует их по пакетам запросов (по 15 на страницу)
        idsBuild(ids, callback) {
            this.ids = []
            let len = ids.length
            this.find_count = len
            let page = 0
            let item_cnt = 0;
            for (let i = 0; i < len; i++) {
                if (item_cnt < this.per_page - 1) {
                    if (!this.ids[page]) {
                        this.ids[page] = []
                    }
                    this.ids[page].push(ids[i])
                    item_cnt++
                } else {
                    page++
                    item_cnt = 0;
                    this.ids[page] = []
                    this.ids[page].push(ids[i])
                }
            }
            this.pages_count = this.ids.length
            callback()
        },

        // Сам запрос отправляющий 15 id-шников
        query: function (page) {
            if (page === undefined) {
                page = 0
            }
            this.page = page
            this.sync({ids: this.ids[page]}, 'ksearch', (data) => {
                console.log(data)
                if (!data) {
                    this.result = this.empty_answer
                    $('#Result').html(this.empty_answer)
                    return
                }
                this.result = data.html
            });
        },

        // При нажатии на кнопки пагинации
        goPage(page) {
            console.log('goPage', page)
            if (this.ajaxQueryProcess) {
                return
            }
            if (page > this.pages_count - 1) {
                return
            }
            if (page < 0) {
                return
            }
            this.query(page)
            this.scrollTop()
        },

        // После пагинации двигать вверх
        scrollTop() {
            let pos = $('.search-widget').offset().top - 100
            $('body, html').animate({scrollTop: pos}, 300)
        },

        presetAttr(name, bool) {
            if (bool) {
                return parseInt($('filter-options').attr(name))
            } else {
                return $('filter-options').attr(name)
            }
        },

        // Получить опции из пресета
        getFilterOptions(callback) {
            if (this.search_query) {
                let search_query = JSON.parse(this.search_query)
                if (search_query.d1) {
                    this.form.date_1 = search_query.d1
                }
                if (search_query.d2) {
                    this.form.date_2 = search_query.d2
                }
                if (search_query.t1) {
                    this.form.town.value = search_query.t1
                }
                if (search_query.t2) {
                    this.form.dest.value = search_query.t2
                }
                if (search_query.ds) {
                    this.form.days.value = search_query.ds
                }
                if (search_query.sp) {
                    this.form.ship.value = search_query.sp
                }
                callback()
            }
        },

        showAlert() {
            this.alert = null
            setTimeout(() => {
                this.alert = 'Круизов найдено: ' + this.find_count
            }, 100)
        },

        searchFromStart() {
            location.href = '/cruises?search_query=' + JSON.stringify(this.getSearchObject())
        },

        getSearchObject() {
            let output = {}
            if (this.form.date_1) {
                output.d1 = this.form.date_1
            }
            if (this.form.date_2) {
                output.d2 = this.form.date_2
            }
            if (this.form.town.value.length) {
                output.t1 = this.form.town.value
            }
            if (this.form.dest.value.length) {
                output.t2 = this.form.dest.value
            }
            if (this.form.days.value.length) {
                output.ds = this.form.days.value
            }
            if (this.form.ship.value.length) {
                output.sp = this.form.ship.value
            }
            return output
        }
    }
});
