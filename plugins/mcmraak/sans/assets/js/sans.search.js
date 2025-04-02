function z(m) {
    console.log(m);
}

let Sans = new Vue({
    el: '#Sans',
    delimiters: ['${', '}'],
    data: {
        preloaderStartInterval: 700,
        preloaderSelector: '#Sanspreloader',
        resort_showed: '',
        resorts_options: false,
        rna: [], // resorts nested array
        restest: false,
        level_showed: '',
        levels_options: false,
        type_showed: '',
        types_options: false,
        meal_showed: '',
        meals_options: false,
        medicals_options: false,
        resorts: {
            selected: 0,
            options: [],
            options_html: '', // html nested tree with filter
        },
        parents_count: 2,
        childrens_count: 0,
        wi_childrens_ages: null,
        childrens_ages: [],
        wait_childrens_ages: false,
        date: '',
        date_delta_days: 0,
        days_from: 10,
        days_to: 10,
        old_days_from: 10,
        old_days_to: 10,
        search_by_hotel_name: '',
        results: {},
        page: 1,
        second_filter_enabled: true,
        second_panel: false,
        second_filter: {
            price_from: 0,
            price_to: 0,
            hotel_levels: {
                selected: 0,
                options: [],
            },
            hotel_types: {
                selected: 0,
                options: [],
            },
            meals: {
                selected: 0,
                options: [],
            },
            pool: false,
            pool_exist: false,
            medical: false,
            medical_options: [],
            medical_selected: [],
        },
    },
    created() {
        document.addEventListener('mouseup', this.clickOver);
        window.SearchApi = {
            onSearch: (preset) => {},
            search: (preset) => {
                preset = JSON.parse(preset)
                this.sendData(preset)
            }
        }
    },
    mounted: function () {
        this.date = this.dateNow();
        $('.dateSelect input').val(this.date);
        $.ajax({
            url: location.origin + '/sans/api/v1/search/resorts_list',
            success: (data) => {
                data = JSON.parse(data)
                this.resorts.selected = data.selected
                this.resorts.options = data.options
                this.resorts.options_html = data.options_html
                //this.resort_showed = data.resort_showed
                this.mountPreset()
                this.resortsNestedBehavior()
            },
            error: function (x) {
                $('html').html(x.responseText)
            },
        });

    },
    watch: {
        resorts_options() {
            if (this.resorts_options) {
                setTimeout(function () {
                    $('.sans-resors-html input').focus()
                }, 300);
            }
        },
        childrens_count(val) {
            if (val > 2) {
                this.childrens_count = 2
            }
            if (val < 0) {
                this.childrens_count = 0
            }
            this.fillChildrensAges()
            this.resetSecondPanel()
        },
        date_delta_days(val) {
            if (val > 2) {
                this.date_delta_days = 2
            }
            if (val < 0) {
                this.date_delta_days = 0
            }
            this.resetSecondPanel()
        },
        resorts: {
            handler: function () {
                this.feelResortsInput()
                this.resetSecondPanel()
            },
            deep: true
        },
        second_filter: {
            handler: function () {
                this.page = 1
            },
            deep: true
        },

        days_from: function (val) {
            this.daysBalance()
            this.resetSecondPanel()
        },

        days_to: function (val) {
            this.daysBalance()
            this.resetSecondPanel()
        },

        parents_count(val) {
            if (val > 4) {
                this.parents_count = 4
            }
            if (val < 0) {
                this.parents_count = 0
            }
            this.resetSecondPanel()
        },
        date() {
            this.resetSecondPanel()
        },
        search_by_hotel_name() {
            this.resetSecondPanel()
        },
    },
    computed: {
        dateFrom() {
            let da = this.date.split('.')
            let date = new Date(da[2], da[1] - 1, da[0])
            return this.formatDate(this.removeDays(date, parseInt(this.date_delta_days)))
        },
        dateTo() {
            let da = this.date.split('.')
            let date = new Date(da[2], da[1] - 1, da[0])
            let add_days = parseInt(this.date_delta_days)
            return this.formatDate(this.addDays(date, add_days))
        },
        medicalSelected() {
            return 'Выбрано позиций: ' + this.second_filter.medical_selected.length;
        }
    },
    methods: {
        fillChildrensAges() {
            let ages = null;
            if (this.wi_childrens_ages) {
                ages = this.wi_childrens_ages.split(',')
                this.wi_childrens_ages = null
            }
            let childrens_ages_memory = this.childrens_ages;
            this.childrens_ages = []
            for (let i = 0; i < parseInt(this.childrens_count); i++) {
                if (ages) {
                    this.childrens_ages.push({value: ages[i]})
                } else {
                    if (childrens_ages_memory[i] === undefined) {
                        this.childrens_ages.push({value: 1});
                    } else {
                        this.childrens_ages.push(childrens_ages_memory[i])
                    }
                }
            }

            if (this.wait_childrens_ages) {
                this.wait_childrens_ages = false
                this.sendData()
            }
        },
        resortsNestedBehavior() {
            let $this = this
            $(document).on('click', '.sans-resors-html .resort', function () {
                $this.resorts.selected = $(this).attr('value')
                $this.resorts_options = false
                $this.resort_showed = $(this).text() // Добавил это, но раньше работало и без этого
            });

            $(document).on('click', '.sans-resors-html [class|="deep"]', function () {
                $(this).next().slideToggle(300);
            });

            $(document).on('input', '.sans-resors-html input', function () {
                let text = $(this).val()
                if (text.length > 1) {
                    let resort = $('.sans-resors-html .resort')
                    resort.hide()
                    let resorts_cnt = resort.length

                    for (let i = 0; i < resorts_cnt; i++) {
                        let resort_name = resort.eq(i).text()
                        resort_name = resort_name.toLowerCase()
                        if (resort_name.indexOf(text.toLowerCase()) > -1) {
                            resort.eq(i).show()
                            resort.eq(i).parent().show()
                        }
                    }

                    let group = $('[class|="deep"]')
                    let group_cnt = group.length
                    for (let i = 0; i < group_cnt; i++) {
                        let actives = group.eq(i).next('ul').find('.resort[style="display: list-item;"]').length;
                        if (actives) {
                            group.eq(i).show()
                        } else {
                            group.eq(i).hide()
                        }
                    }
                } else {
                    $('[class|="deep"]').show()
                    $('.sans-resors-html .resort').show()
                    $('.sans-resors-html [class|="deep"]:not(.deep-0)').next('ul').hide()
                }
            });

            $(document).mouseup(function (e) {
                let container = $('.sans-resors-html')
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    container.hide()
                }
            })

        },
        clickOver(e) {
            let cont = document.querySelector('.s-sel__select-list-item')
            let cont2 = document.querySelector('.s-sel__select-output')
            if (cont && e.target.className !== cont2.className && e.target.className !== cont.className) {
                this.resorts_options = false
                this.levels_options = false
                this.types_options = false
                this.meals_options = false
                this.medicals_options = false
            }
        },
        feelResortsInput() {
            this.feelInputName(
                this.resorts.options,
                this.resorts.selected,
                'resort_showed'
            )
        },
        feelLevelsInput() {
            this.feelInputName(
                this.second_filter.hotel_levels.options,
                this.second_filter.hotel_levels.selected,
                'level_showed'
            );
        },
        feelTypesInput() {
            this.feelInputName(
                this.second_filter.hotel_types.options,
                this.second_filter.hotel_types.selected,
                'type_showed'
            );
        },
        feelMealsInput() {
            this.feelInputName(
                this.second_filter.meals.options,
                this.second_filter.meals.selected,
                'meal_showed'
            );
        },
        feelInputName(options, selected, showed) {
            for (let i in options) {
                if (options[i].value === selected) {
                    this[showed] = options[i].name
                    return
                }
            }
        },
        checkItem(item_name) {
            let item = $('[option="medical-value"][name="' + item_name + '"]')
            let ckecked = item.attr('checked')
            if (ckecked === undefined) {
                item.attr('checked', 'checked')
                item.attr('class', 'fa fa-check-square')
            } else {
                item.removeAttr('checked')
                item.attr('class', 'fa fa-square-o')
            }
            this.getMedicals()
        },
        openDP() {
            let datepicker = $('.dateSelect input').datepicker({
                autoClose: true,
            }).data('datepicker')
            datepicker.show()
        },
        dateNow() {
            let date = new Date()
            /* date.setMonth(date.getMonth()+1); */
            return this.formatDate(date)
        },
        formatDate(in_date) {
            let nf_date = new Date(in_date)
            let dd = nf_date.getDate();
            if (dd < 10) {
                dd = '0' + dd
            }
            let mm = nf_date.getMonth() + 1
            if (mm < 10) {
                mm = '0' + mm
            }
            let yy = nf_date.getFullYear()
            if (yy < 10) {
                yy = '0' + yy
            }
            return dd + '.' + mm + '.' + yy;
        },
        parseDate(string) {
            let date = string.split('.')
            let y = parseInt(date[2])
            let m = parseInt(date[1]) - 1
            let d = parseInt(date[0])
            return new Date(y, m, d) / 1000
        },
        addDays(date, days) {
            let result = new Date(date)
            result.setDate(result.getDate() + days)
            return result
        },
        removeDays(date, days) {
            let result = new Date(date)
            result.setDate(result.getDate() - days)
            return result
        },
        mountPreset() {
            let preset = $('sans-options')
            if (!preset.length) {
                return
            }
            if (preset.attr('wi_resort_id')) {
                this.resorts.selected = preset.attr('wi_resort_id')
            }
            if (preset.attr('wi_parents_count')) {
                this.parents_count = preset.attr('wi_parents_count')
            }

            if (preset.attr('wi_childrens_ages')) {
                this.wi_childrens_ages = preset.attr('wi_childrens_ages')
            }

            if (preset.attr('wi_childrens_count')) {
                this.childrens_count = preset.attr('wi_childrens_count')
            }

            if (preset.attr('wi_date') && preset.attr('wi_date_delta_days')) {
                this.date = preset.attr('wi_date')
                this.date_delta_days = preset.attr('wi_date_delta_days')
            }

            if (preset.attr('wi_date') === '' && preset.attr('wi_date_delta_days')) {
                var date = new Date()
                date = this.addDays(date, parseInt(preset.attr('wi_date_delta_days')))
                this.date = this.formatDate(date)
                $('.dateSelect input').val(this.date)
            }

            if (preset.attr('wi_days_from')) {
                var DF = preset.attr('wi_days_from')
                this.old_days_from = DF
                this.days_from = DF
            }
            if (preset.attr('wi_days_to')) {
                var DT = preset.attr('wi_days_to')
                this.old_days_to = DT
                this.days_to = DT
            }
            if (preset.attr('wi_search_by_hotel_name')) {
                this.search_by_hotel_name = preset.attr('wi_search_by_hotel_name')
            }
            $('.dateSelect input').val(this.date)

            if (this.childrens_count) {
                if (this.childrens_ages.length) {
                    this.sendData();
                } else {
                    // Проследить когда появится
                    this.wait_childrens_ages = true
                }
            } else {
                this.sendData();
            }
        },
        daysBalance() {
            let F = parseInt(this.days_from)
            let T = parseInt(this.days_to)
            let OF = parseInt(this.old_days_from)
            let OT = parseInt(this.old_days_to)
            let D = 4; // Days diff

            if (F !== OF) {
                this.old_days_to = F
                this.old_days_from = F
                this.days_to = F
                this.days_from = F
                return;
            }
            if (T !== OT) {
                if (T < F) {
                    this.old_days_to = T
                    this.old_days_from = T
                    this.days_to = T
                    this.days_from = T
                    return;
                }
                if (T > F + 4) {
                    this.old_days_to = T
                    this.old_days_from = T - D
                    this.days_to = T
                    this.days_from = T - D
                    return
                }
            }
        },
        getMedicals() {
            let mv = $('[option="medical-value"][checked="checked"]')
            let options_count = mv.length
            this.second_filter.medical_selected = []
            for (let i = 0; i < options_count; i++) {
                this.second_filter.medical_selected.push(mv.eq(i).attr('name'))
            }
        },
        resetSecondPanel() {
            this.second_panel = false
            $('#SansResults').html('')
            this.second_filter_enabled = true
            this.second_filter.hotel_levels.selected = 0
            this.second_filter.hotel_types.selected = 0
            this.second_filter.meals.selected = 0
            this.second_filter.price_from = 0
            this.second_filter.price_to = 10000000
        },
        childrens_ages_values() {
            let $return = []
            for (let i in this.childrens_ages) {
                $return[i] = this.childrens_ages[i].value
            }
            return $return.join(',')
        },
        sendData(preset) {

            let data = null

            if (preset) {
                data = preset
            } else {
                this.date = $('.dateSelect input').val()
                this.typeSearch = $('.s-sel__line-head span').attr('type-search')

                if ($('.search_nomer').attr('hotel-name')) {
                    this.search_by_hotel_name = $('.search_nomer').attr('hotel-name')
                }

                this.getMedicals()
                data = {
                    'resort_id': this.resorts.selected,
                    'adults': this.parents_count,
                    'kids': this.childrens_count,
                    'kidsAges': this.childrens_ages_values(),
                    'dateFrom': this.dateFrom,
                    'dateTo': this.dateTo,
                    'nightsMin': this.days_from,
                    'nightsMax': this.days_to,
                    'search_by_hotel_name': this.search_by_hotel_name,
                    'page': this.page,
                    'second_filter': this.second_filter,
                    'typeSearch': this.typeSearch,
                    'type': 'sans'
                };
            }

            //console.log(data);

            let $this = this;

            $this.preset = data

            $.ajax({
                type: 'post',
                url: location.origin + '/sans/api/v1/search/query',
                beforeSend: function () {
                    $('#default-booking-wrap').hide()
                    $this.preloaderShow()
                },
                data: data,
                success: function (data) {
                    if (data === 'no_results') {
                        $('#SansResults').html('<h4>Поиск не дал результатов</h4>')
                        $this.preloaderHide()
                        return;
                    }

                    SearchApi.onSearch(JSON.stringify($this.preset))

                    $this.preloaderHide()
                    data = JSON.parse(data)
                    $this.results = data.json
                    if ($this.second_filter_enabled) {
                        $this.fillSecondFilters()
                        $this.second_filter_enabled = false
                    }

                    $this.second_panel = true
                    $('#SansResults').html(data.html)

                },
                error: function (x) {
                    z(x);
                },
            });
        },
        preloaderShow() {
            let si = this.preloaderStartInterval
            $(this.preloaderSelector).attr("complite", "false")
            let preloader = this.preloaderSelector
            setTimeout(function () {
                if ($(preloader).attr("complite") === "false") {
                    $(preloader).fadeIn(150)
                    $('body').css('cursor', 'wait')
                }
            }, si)
        },

        /* Preloader hide */
        preloaderHide() {
            $(this.preloaderSelector).attr("complite", "true")
            $(this.preloaderSelector).hide()
            $('body').css('cursor', 'default')
        },
        resetSecondFilter() {
            this.second_filter.hotel_levels.options = [{name: 'Не важно', value: 0}],
                this.second_filter.hotel_types.options = [{name: 'Не важно', value: 0}],
                this.second_filter.meals.options = [{name: 'Не важно', value: 0}],
                this.second_filter.medical_options = [],
                this.second_filter.price_from = 1000000000
            this.second_filter.price_to = 0
            this.second_filter.pool_exist = false
        },
        fillSecondFilters() {
            // Reset uniques
            var level_uniq = []
            var type_uniq = []
            var meal_uniq = []
            var medical_uniq = []

            // Reset values
            this.resetSecondFilter()

            for (let i in this.results) {
                level_uniq[this.results[i].level] = this.results[i].level
                type_uniq[this.results[i].hotel_type] = this.results[i].hotel_type
                meal_uniq[this.results[i].meal] = this.results[i].meal
                if (this.results[i].pool === true) {
                    this.second_filter.pool_exist = true
                }

                if (this.results[i].price < this.second_filter.price_from) {
                    this.second_filter.price_from = Math.floor(this.results[i].price)
                }

                if (this.results[i].price > this.second_filter.price_to) {
                    this.second_filter.price_to = Math.ceil(this.results[i].price)
                }

                for (let ii in this.results[i].medical) {
                    medical_uniq[this.results[i].medical[ii]] = this.results[i].medical[ii]
                }
            }
            for (let i in level_uniq) {
                this.second_filter.hotel_levels.options.push({name: level_uniq[i], value: level_uniq[i]})
            }
            for (let i in type_uniq) {
                this.second_filter.hotel_types.options.push({name: type_uniq[i], value: type_uniq[i]})
            }
            for (let i in meal_uniq) {
                this.second_filter.meals.options.push({name: meal_uniq[i], value: meal_uniq[i]})
            }
            let other = false
            for (let i in medical_uniq) {
                if (medical_uniq[i] !== '-- Прочее --') {
                    this.second_filter.medical_options.push(medical_uniq[i])
                } else {
                    other = true
                }
            }
            if (other) {
                this.second_filter.medical_options.push('-- Прочее --')
            }

            this.feelLevelsInput()
            this.feelTypesInput()
            this.feelMealsInput()
        },
    },
})

/*если не через хук*/
/*document.addEventListener('mouseup', function(e) {
	var cont = document.querySelector('.s-sel__select-list-item');
	if(cont && e.target.className !== cont.className) {
		Sans.resorts_options = false;
		console.log('da')
	}
});*/

$(document).on('click', '.sans.pagination a', function (event) {
    event.preventDefault();
    var page = $(this).attr('page');
    Sans.page = page;
    Sans.sendData();
    var pos = $('#SansResults').offset().top - 100;
    $('body, html').animate({scrollTop: pos}, 300);
});

function ajaxLoadGallery() {
    $(document).ready(function () {
        $('.modalContent .s-card__room-images  .s-card__room-image').nivoLightbox();
    });
    var roomsmore = document.querySelectorAll('.s-card__room-more .more-button');
    [].forEach.call(roomsmore, function (item) {
        item.onclick = function () {
            this.classList.toggle('active');
            this.parentNode.parentNode.classList.toggle('active');
        }
    });
}

/* Modal Graphic */
$(document).on('click', '[href="#modalApartaments--sans"]', function () {
    var hotel_id = $(this).attr('hotel-id');
    var room_id = $(this).attr('order');
    ion.cmd('type=get;ajax=/sans/api/v1/parser/room_profile/' + hotel_id + '/' + room_id + ';'
        + 'html=#modalApartaments--sans .modalContent;afterajax=ajaxLoadGallery();');
    $(this).magnificPopup({
        type: 'inline',
        preloader: false,
        callbacks: {
            afterClose: function () {
                $('#modalApartaments--sans .modalContent').html('<span style="font-size: 20px">Загрузка...</span>');
            },
        },
    }).magnificPopup('open');
});
