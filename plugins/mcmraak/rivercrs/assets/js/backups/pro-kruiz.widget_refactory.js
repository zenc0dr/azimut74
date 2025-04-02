Vue.component('dropdown', {
    props: [ 'id', 'opt'],
    data: function () {
        return {
            any: 'Любой(ая)',
            valid: true,
            popup: false,
            value: null,
            options: [],
            last_volue: null,
        }
    },
    mounted: function () {
        this.last_volue = this.any;
        this.reactor();
        this.closeEvent();
        this.start_fill();
    },
    watch: {
        'opt.value': function () {
            this.value = this.getValue(this.opt.value);
        },
        'opt.options': function () {
            this.reactor();
        },
    },
    methods: {
        start_fill: function () {
            if(this.value == this.any && this.opt.value) {
                this.value = this.last_volue = this.getValue(this.opt.value);
            }
        },
        getValue: function (key) {
            key = parseInt(key);
            for(var i in this.options){
                if(this.options[i].id == key) {
                    return this.options[i].name;
                }
            }
        },
        reactor: function (key) {
            try {
                if(key!==undefined) {
                    this.popup = false;
                    this.setModel(key);
                } else {
                    this.fillOptions();
                    this.value = this.options[0].name;
                }
            } catch (e) {
                this.value = 'Нет данных';
                this.options = [{id:0,name:'Нет данных'}]
                this.valid = false;
            }
        },
        filter: function () {
            this.fillOptions();
            var filted_options = [];
            for(var i in this.options) {
                if(this.options[i].name == this.value) {
                    this.setModel(i);
                    this.popup = false;
                    return;
                }
                if(this.options[i].name.indexOf(this.value) != -1) {
                    filted_options.push(this.options[i]);
                }
            }
            this.popup = true;
            this.options = filted_options;
        },
        fillOptions: function () {
            var options = [{id:0,name:this.any}];
            this.options = options.concat(this.opt.options);
        },
        setModel: function (key) {
            key = parseInt(key);
            this.last_volue = this.options[key].name;
            this.opt.value = this.options[key].id;
        },
        focus: function () {
            if(this.value == this.any) this.value = '';
            this.fillOptions();
            this.popup = true;
        },
        lastValue: function () {
            this.popup = false;
            if(!this.valid) return;
            this.value = this.last_volue;
        },
        closeEvent: function () {
            var $this = this;
            $(document).mouseup(function (e) {
                var obj = $('#'+$this.id);
                if(!obj.is(e.target) && obj.has(e.target).length === 0) {
                    $this.lastValue();
                }
            });
        },
    },
    template: '<div :id="id" class="zendd">' +

        '<div v-if="popup && options.length" class="options">'+
        '<div class="option"' +
        'v-for="(item, key) in options"' +
        '@click="reactor(key)">{{ item.name }}</div>' +
        '</div>'+

        '<div class="value">' +
        '<input @focus="focus()" @input="filter()" v-model="value" :style="(!options.length)?\'color:red\':\'\'">' +
        '<i @click="popup=!popup;popup && fillOptions()" class="fa fa-angle-down"></i>' +
        '</div>'+

        '</div>'
});

Vue.component('mdropdown', {
    props: [ 'id', 'opt'],
    data: function() {
        return {
            any: 'Любой(ая)',
            popup: false,
            options: [],
            filter: '',
        }
    },
    mounted: function () {
        if(!Array.isArray(this.opt.value)) {
            if(!this.opt.value) {
                this.opt.value = [];
            } else {
                this.opt.value = this.opt.value.split(',');
            }
        }
        this.closeEvent();
    },
    computed: {
        title: function () {
            let ln = this.opt.value.length;
            if(!ln) return this.any;
            if(ln==1) return this.getValueById(this.opt.value[0]);
            return 'Выбрано: '+ln;
        }
    },
    methods: {
        isChecked: function (index) {
            var id = parseInt(this.opt.options[index].id);
            if(this.opt.value.indexOf(id) != -1) {
                return true;
            }
            return false;
        },
        getValueById: function (id) {
            for(var i in this.opt.options){
                if(this.opt.options[i].id == id) {
                    return this.opt.options[i].name;
                }
            }
        },
        check: function (index) {
            var id = parseInt(this.opt.options[index].id);
            var key = this.opt.value.indexOf(id);
            if(key == -1) {
                this.opt.value.push(id);
            }
            else {
                this.opt.value.splice(key, 1);
            }
        },
        closeEvent: function () {
            var $this = this;
            $(document).mouseup(function (e) {
                var obj = $('#'+$this.id+'.multi-drop');
                if(!obj.is(e.target) && obj.has(e.target).length === 0) {
                    $this.popup = false;
                    $this.filter = '';
                    //RiverCrs.search();
                }
            });
        },
        filterTest: function (need) {
            if(!this.filter) return true;
            var filter = this.filter.toLowerCase();
            need = need.toLowerCase();
            if(need.indexOf(filter) !=-1 ) return true;
        }
    },
    template: '<div :id="id" class="multi-drop">' +
        '<template v-if="popup && opt.options.length">' +
        '<div class="filter"><input v-model="filter" placeholder="Фильтр"></div>'+
        '<div @click="opt.value=[]" :class="{active:opt.value.length}" class="clean">Очистить</div>'+
        '<div class="options">'+
        '<div :class="{checked:isChecked(index)}" class="option"' +
        'v-if="filterTest(item.name)"'+
        'v-for="(item, index) in opt.options"' +
        '@click="check(index)">{{ item.name }}' +
        '<i class="fa fa-check"></i>' +
        '</div>' +
        '</div>'+
        '</template>'+

        '<div @click="popup=!popup" class="value">' +
        '<span>{{ title }}</span>'+
        '<i class="fa fa-angle-down"></i>' +
        '</div>'+
        '</div>'
});

Vue.component('pagination', {
    props: ['page', 'pages_count'],
    methods: {
        goPage:function (page) {
            RiverCrs.goPage(page);
        }
    },
    template:   '<div v-if="pages_count > 1" class="pagination-wrap pagination">' +
        '<ul class="pagination">' +
        '<template v-if="pages_count < 11">' +
        '<li v-for="n in pages_count">' +
        '<span class="pagination-item" :class="{active:(n == page+1)}" v-if="n == page+1">{{ n }}</span>' +
        '<span @click="goPage(n-1)" class="pagination-item" v-else href="#">{{ n }}</span>' +
        '</li>'+
        '</template>'+
        '<template v-else>' +
        '<li @click="goPage(page-1)"><span class="pagination-control" :class="{active:(page>0)}">«</span></li>'+
        '<li @click="goPage(0)" v-if="page > 3"><span class="pagination-item">1</span></li>'+
        '<li v-if="page > 3"><span>...</span></li>' +
        '<li class="test_f"><span class="pagination-item active">{{ page+1 }}</span></li>' +
        '<li class="test_x" @click="goPage(page+1)" v-if="(page+1) < pages_count && (page+2) != pages_count"><span class="pagination-item">{{ page+2 }}</span></li>' +
        '<li class="test_y" @click="goPage(page+2)" v-if="(page+2) < pages_count && (page+3) != pages_count"><span class="pagination-item">{{ page+3 }}</span></li>' +
        '<li v-if="(page+2) < pages_count"><span>...</span></li>' +
        '<li class="test_z" v-if="(page+1) != pages_count" @click="goPage(pages_count-1)"><span class="pagination-item">{{ pages_count }}</span></li>' +
        '<li @click="goPage(page+1)"><span class="pagination-control" :class="{active:(page!=pages_count-1)}">»</span></li>'+
        '</template>'+
        '</ul>'+
        '</div>'
});

Vue.component('paginator', {
    props: ['page', 'pages_count'],
    methods: {

    },
    template:   '<div class="pagination-wrap pagination">' +
        '<ul class="pagination">' +
        '<template v-if="pages_count < 6">' +
        '<li v-for="n in pages_count">' +
        '<span class="pagination-item" :class="{active:(n == page)}" v-if="n == page">{{ n }}</span>' +
        '<span @click="goPage(n)" class="pagination-item" v-else href="#">{{ n }}</span>' +
        '</li>'+
        '</template>'+
        '</ul>'+
        '</div>'
});

let ProkSearch = new Vue({
    el: '#ProkSearch',
    delimiters: ['${','}'],
    data: {
        outer_result_container: 0,
        ajaxQueryProcess: false,
        empty_answer: 'Поиск не дал результатов, измените условия поиска и попробуйте снова...',
        loaded: false,
        process: false,
        message: null,
        available_dates:[],
        s_table: [],
        DB:{
            checkins: {},
            ways: {},
            towns: {},
            ships: {},
            soft: {}
        },
        soft_towns: [],
        years:[],
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
        ship_fix: false, // Фиксированный теплоход
        ids: [],
        pages_count: 0,
        page: 0,
        per_page: 15,
        result: '',
        date_range: false, // Не используется
        find_count: 0,
        alert: null,
        checkin_id:null,
        queries_count: 0,
    },

    mounted: function () {
        this.outer_result_container = parseInt($('meta[name="outer_result_container"]').attr('content'));
        this.startCheckin()
        this.sync(null, 'mounted', (data) => {
            this.DB = data
            this.formFill(() => {
                this.loaded = true;
                this.selectActualDate()
                this.getFilterOptions()
            })
        });
    },

    watch: {
        form: {
            handler: function () {
                if(this.form.date_1 && this.form.date_2)
                    if(this.dateToSec(this.form.date_1) > this.dateToSec(this.form.date_2)) {
                        this.form.date_1 = this.form.date_2
                    }
            },
            deep: true,
        },
        pages_count: function (val) {
            if(this.outer_result_container) {
                RiverResults.pages_count = val
            }
        },
        page: function (val) {
            if(this.outer_result_container) {
                RiverResults.page = val
            }
        },
        result: function (val) {
            if(this.outer_result_container) {
                RiverResults.result = val
            }
        }
    },

    methods: {
        sync: function (data, action, callback) {
            if(this.ajaxQueryProcess) return

            let url = '/rivercrs/api/v2/search/'+action

            $.ajax({
                type: 'post',
                url: location.origin + url,
                data:data,
                beforeSend: () => {
                    this.ajaxQueryProcess = true
                    if(action != 'mounted') {
                        setTimeout(function () {
                            if(this.ajaxQueryProcess) {
                                this.process = true
                            }
                        }, 300)
                    }
                },
                success: (data) =>
                {
                    this.queries_count++
                    this.ajaxQueryProcess = false
                    this.process = false
                    if(data) {
                        data = JSON.parse(data)
                        if(data.alerts) {
                            this.message = data.message
                        }
                        callback(data)
                    } else {
                        callback()
                    }
                },
                error: (x) =>
                {
                    this.ajaxQueryProcess = false
                    this.process = false
                    this.message = x.responseText
                },
            });
        },

        // Получить метку конкретного заезда
        startCheckin: function () {
            if($('checkin-id').length){
                this.checkin_id = $('checkin-id').attr('id')
            }
        },

        // Добавить запись в таблицу поиска
        toTable: function (way) {
            if(!this.s_table[way.checkin_id]) {
                this.s_table[way.checkin_id] = {t:[],d:[]}
            }

            let town_id = (this.DB.soft[way.town_id])?this.DB.soft[way.town_id]:way.town_id

            if(way.start) {
                this.s_table[way.checkin_id].t[town_id] = true
            } else {
                this.s_table[way.checkin_id].d[town_id] = true
            }
        },

        // Заполнить элементы управления
        formFill: function (callback) {

            /* Dropdown options in objects (for collectiong) */
            let town_obj={},
                dest_obj={},
                days_obj={},
                ship_obj={}

            // Collect town and desc
            for(let i in this.DB.ways) {
                let town_id = this.DB.ways[i].town_id
                if(!this.DB.soft[town_id]) {
                    if(this.DB.ways[i].start) {
                        // Add to town options
                        if(this.DB.towns[town_id]) {
                            town_obj[town_id] = this.DB.towns[town_id]
                        }
                    } else {
                        // Add to dest options
                        if(this.DB.towns[town_id]){
                            dest_obj[town_id] = this.DB.towns[town_id]
                        }

                    }
                }

                this.toTable(this.DB.ways[i])
            }

            // Collect days
            for(let i in this.DB.checkins) {
                days_obj[this.DB.checkins[i].days] = this.DB.checkins[i].days+''
                this.available_dates.push(this.secToDate(this.DB.checkins[i].date))
            }

            // Оставить уникальные даты
            this.available_dates = this.uniqueArr(this.available_dates)

            // Collect ships
            for(let i in this.DB.ships) {
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
        sortOptions: function (options) {
            options.sort(function(a,b) {
                if (a.name > b.name) return 1
                if (a.name < b.name) return -1
                return 0
            })
            return options
        },

        // Выставить кнопки быстрого выбора года
        yearButtons: function () {
            let date = new Date()
            let year = date.getFullYear()
            let nextYear = new Date(year+1,0,1) / 1000
            let showNexYear = false
            for(let i in this.DB.checkins) {
                let checkin_date = this.DB.checkins[i].date
                if(checkin_date > nextYear) showNexYear = true
            }
            this.years.push({value:year,active:true})
            if(showNexYear) this.years.push({value:year+1,active:false})
        },

        // Выбрать активный год
        setActiveYear: function (item) {
            for(let i in this.years) {
                if(this.years[i].value == item.value) {
                    this.years[i].active = true
                } else {
                    this.years[i].active = false
                }
            }
            this.selectActualDate()
        },

        // Полное кланирование объекта без связей
        newObject:function (obj) {
            return JSON.parse(JSON.stringify(obj))
        },

        // Преобразовать объект в массив
        objToArray: function (obj, sort) {
            let $return = []
            for(let i in obj) {
                $return.push({id:i,name:obj[i]})
            }

            if(sort) {
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
            let nextYear = (this.years.length>1)?this.years[1].value:0
            let nextYearActive = (nextYear && this.years[1].active)?true:false

            if(nextYear) {
                nextYear = new Date(nextYear, 0, 1, 0, 0, 0, 0)
                nextYear = parseInt(nextYear.getTime() / 1000)
            }

            for(let i in this.DB.checkins) {

                if(nextYearActive) {
                    if(date_1 == null && this.DB.checkins[i].date > nextYear) {
                        date_1 = this.secToDate(this.DB.checkins[i].date)
                    }
                } else {
                    if(date_1 == null && this.DB.checkins[i].date > now) {
                        date_1 = this.secToDate(this.DB.checkins[i].date)
                    }
                }

                if(nextYearActive) {
                    date_2 = this.secToDate(this.DB.checkins[i].date)
                } else {
                    if(nextYear) {
                        if(date_2 == null && this.DB.checkins[i].date > nextYear) {
                            date_2 = this.secToDate(this.DB.checkins[i].date)
                        }
                    }
                }

                if(!nextYearActive && date_1 && date_2) break
            }

            this.form.date_1 = date_1
            this.form.date_2 = date_2
        },

        // Открыть календарь (Air Datepicker)
        openDP: function (date_num) {
            let $this = this;
            let datepicker = $('[name="date-'+date_num+'"]').datepicker({
                //language: $this.L,
                autoClose: true,
                onRenderCell: function (date, cellType) {
                    if(!$this.available_dates.length) return;
                    let currentDate = date.getTime();
                    currentDate = $this.secToDate(currentDate/1000);
                    let currentDay = date.getDate();
                    if (cellType == 'day' && $this.available_dates.indexOf(currentDate) != -1) {
                        return {
                            html:  '<span class="calendar-note">'+ currentDay +'</span>'
                        }
                    }
                    if (cellType == 'day' && $this.available_dates.indexOf(currentDate) == -1) {
                        return {
                            disabled: true
                        }
                    }
                },
                onSelect: function(selected_date){
                    $this.form['date_'+date_num] = selected_date;
                    $this.form['date_'+date_num+'_cahged'] = true; // Пометка об изменении даты
                }
            }).data('datepicker');


            if(date_num == 1 && this.form.date_1) {
                let ad = new Date(this.dateToSec(this.form.date_1)*1000)
                datepicker.selectDate(ad);
            }


            if(date_num == 2) {
                if(this.form.date_1_cahged && !this.form.date_2_cahged) {
                    this.form.date_2 = this.form.date_1
                }
            }

            if(date_num == 2 && this.form.date_2) {
                let ad = new Date(this.dateToSec(this.form.date_2)*1000)
                datepicker.selectDate(ad)
            }
            datepicker.show()
        },


        // Открыть календарь (Air Datepicker с диапазоном дат) - Отключено
        openAirRange:function () {
            let $this = this
            let datepicker = $('[name="date-range').datepicker({
                autoClose: true,
                onRenderCell: function (date, cellType) {
                    if(!$this.available_dates.length) return
                    let currentDate = date.getTime()
                    currentDate = $this.secToDate(currentDate/1000)
                    let currentDay = date.getDate()
                    if (cellType == 'day' && $this.available_dates.indexOf(currentDate) != -1) {
                        return {
                            html:  '<span class="calendar-note">'+ currentDay +'</span>'
                        }
                    }
                    if (cellType == 'day' && $this.available_dates.indexOf(currentDate) == -1) {
                        return {
                            disabled: true
                        }
                    }
                },
                onSelect: function(selected_date){
                    $this.form['date_'+date_num] = selected_date;
                }
            }).data('datepicker')
            datepicker.show()
        },

        // Открыть календарь (DateRangePicker) - Отключено
        openDR: function () {
            if(this.date_range) return;
            this.date_range = true;
            let calendar = $('input[name="date-range"]')
            calendar.daterangepicker({
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(32, 'hour'),
                locale: {
                    format: 'M/DD hh:mm A'
                },
            });
            calendar.on('apply.daterangepicker', function(ev, picker) {
                console.log(picker.startDate.format('YYYY-MM-DD'))
                console.log(picker.endDate.format('YYYY-MM-DD'))
            });
            calendar.click()
        },

        // @input: (int) seconds ex: 1567022400
        // @output: (string) formated date ex: 27.11.2017
        secToDate: function(date) {
            date *= 1000
            var date = new Date(date)
            let dd = date.getDate()
            if (dd < 10) dd = '0' + dd
            let mm = date.getMonth() + 1
            if (mm < 10) mm = '0' + mm
            let yy = date.getFullYear()
            if (yy < 10) yy = '0' + yy
            return dd + '.' + mm + '.' + yy
        },

        // @input: (string) formated date ex: 27.11.2017
        // @output: (int) seconds ex: 1567022400
        dateToSec: function (date) {
            date = date.split('.')
            let y = parseInt(date[2])
            let m = parseInt(date[1]) - 1
            let d = parseInt(date[0])
            return new Date(y,m,d) / 1000
        },

        // @output: (int) seconds ex: 1567022400
        nowInSeconds: function () {
            let now = new Date()
            return parseInt(now.getTime() / 1000)
        },


        // Подбор checkin_id для колеекции this.ids
        searchFilter: function (callback) {
            if(this.checkin_id) {
                callback([this.checkin_id])
                this.checkin_id = null;
                return
            }

            let ids = [], he = true;
            for(let i in this.DB.checkins)
            {
                let checkin = this.DB.checkins[i]

                // Days test
                he = this.searchTest(checkin.days, this.form.days.value)
                if(!he) {
                    continue
                }

                // Ship test
                he = this.searchTest(checkin.ship_id, this.form.ship.value)
                if(!he) {
                    continue
                }

                // Town test
                he = this.fastTownTest(checkin.id)
                if(!he) {
                    continue
                }


                // Dest test
                he = this.fastTownTest(checkin.id, 1)
                if(!he) {
                    continue
                }

                // Date OF test
                if(this.form.date_1) {
                    let date_1 = this.dateToSec(this.form.date_1)
                    he = (checkin.date >= date_1)?true:false
                    if(!he) {
                        continue
                    }
                }

                // Date TO test
                if(this.form.date_2) {
                    let date_2 = this.dateToSec(this.form.date_2)
                    he = (checkin.date <= date_2)?true:false
                    if(!he) {
                        continue
                    }
                }
                ids.push(checkin.id)
            }

            callback(ids);
        },

        aimCapture: function () {
            if(this.queries_count < 2) return
            let $this = this
            this.form.town.value.map(function (item) {
                let town_id = +item
                if(town_id == 63) $this.yandexAim('booking_teplohod_64')
                if(town_id == 61) $this.yandexAim('booking_teplohod_63')
                if(town_id == 14) $this.yandexAim('booking_teplohod_34')
                if(town_id == 2) $this.yandexAim('booking_teplohod_30')
            });
        },

        // Яндекс-цель:
        yandexAim: function (target) {
            ym(13605125, 'reachGoal', target)
        },

        // Нажатие на кнопку поиск
        search: function () {
            if(this.ajaxQueryProcess) return
            let $this = this
            this.searchFilter(function (ids) {
                $this.idsBuild(ids, function () {
                    $this.showAlert()
                    if(!$this.ids.length) {
                        $this.result = $this.empty_answer
                        return;
                    }
                    $this.query()
                });
            });
        },

        // Проверка соответствия значения из итема и элемента формы
        searchTest: function (item_value, form_value) {
            if(!form_value) return true
            item_value = parseInt(item_value)

            if(Array.isArray(form_value)) {
                if(form_value.length == 0) return true
                if(form_value.indexOf(item_value) != -1) return true
            }
            else {
                if(item_value == form_value) return true
            }
            return false
        },

        // Быстрый поиск по городу
        fastTownTest: function (checkin_id, table_num) {
            if(!table_num) {
                if(!this.form.town.value) return true
                if(!this.form.town.value.length) return true
                for(let i in this.form.town.value) {
                    let town_id = this.form.town.value[i]
                    if(this.s_table[checkin_id].t[town_id]) return true
                }
            } else {
                if(!this.form.dest.value) return true
                if(!this.form.dest.value.length) return true
                for(let i in this.form.dest.value) {
                    let town_id = this.form.dest.value[i]
                    if(this.s_table[checkin_id].d[town_id]) return true
                }
            }
            return false
        },

        // Собирает id-шники и сортирует их по пакетам запросов (по 15 на страницу)
        idsBuild: function (ids, callback) {
            this.ids = []
            let len = ids.length
            this.find_count = len
            let page = 0
            let item_cnt = 0;
            for(let i=0;i<len;i++){
                if(item_cnt < this.per_page - 1) {
                    if(!this.ids[page]) {
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
            if(page == undefined) page = 0
            this.page = page
            let $this = this
            this.sync({ids:this.ids[page]}, 'search', function (data) {
                if(!data) {
                    $this.result = $this.empty_answer
                    $('#Result').html($this.empty_answer)
                    return
                }
                $this.result = data.html
            });
        },

        // При нажатии на кнопки пагинации
        goPage: function (page) {
            if(this.ajaxQueryProcess) return
            if(page > this.pages_count - 1) return
            if(page < 0) return
            this.query(page)
            this.scrollTop()
        },

        // После пагинации двигать вверх
        scrollTop: function () {
            let pos = $('.cruiseData').offset().top - 100
            $('body, html').animate({scrollTop: pos}, 300)
        },

        presetAttr: function (name, bool) {
            if(bool) {
                return parseInt($('filter-options').attr(name))
            } else {
                return $('filter-options').attr(name)
            }
        },

        // Получить опции из пресета
        getFilterOptions: function (){
            let options = {}
            if($('filter-options').length > 0) {
                options.town = this.presetAttr('town', 1)
                options.dest = this.presetAttr('dest', 1)
                options.ship = this.presetAttr('ship', 1)
                options.date = this.presetAttr('date-1')
                options.dateb = this.presetAttr('date-2')
                options.days = this.presetAttr('days', 1)
                options.items = this.presetAttr('items'); // ids ex: 1+2+3 OR 0;
                if(options.items == 'none') options.items = false
                options.shipfix = this.presetAttr('shipfix', 1) // Фиксированный теплоход
            }

            // Если ids явно указаны
            if(options.items) {
                this.idsBuild(options.items.split('+'), function () {
                    if(!this.ids.length) {
                        this.result = $this.empty_answer
                        return
                    }
                    this.query(0)
                });
            }
            else {
                if(options.town) this.form.town.value = [parseInt(options.town)]
                if(options.dest) this.form.dest.value = [parseInt(options.dest)]
                if(options.shipfix) this.ship_fix = true
                if(options.ship) this.form.ship.value = [parseInt(options.ship)]
                if(options.date) this.form.date_1 = options.date
                if(options.dateb) this.form.date_2 = options.dateb
                if(options.days) this.form.days.value = [parseInt(options.days)]

                this.search()
            }
        },

        showAlert: function () {
            this.alert = null
            let $this = this
            setTimeout(function () {
                $this.alert = 'Круизов найдено: '+$this.find_count
            }, 100)
        }
    }
});
