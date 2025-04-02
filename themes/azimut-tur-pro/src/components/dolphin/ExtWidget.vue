<template>
    <div class="wrapper-sticky__widget">
        <template v-if="!collapse_widget">
            <div v-if="data_loaded" class="dolphin-widget container">
                <div class="dolphin-widget__title">
                    {{ tour_name }}
                    <i v-if="admin" @click="copyToClipboard" class="fa fa-cog admin-cog"></i>
                </div>
                <div class="input-block">
                    <div class="input-block__label">Куда</div>
                    <div class="input-block__wrap">
                        <TreeMultiSelect
                            :geo-tree="geo_tree"
                            :default="form.geo_objects"
                            no-select="Все направления"
                            @select="form.geo_objects = $event"
                        />
                    </div>
                </div>
                <div class="input-block__group dates">
                    <div class="input-block">
                        <div class="input-block__label">Дата С:</div>
                        <div class="input-block__wrap">
                            <DatePicker
                                :date="form.date_of"
                                placeholder="Дата с"
                                @change="form.date_of = $event"
                                :allowed-dates="form.available_dates"
                            />
                        </div>
                    </div>
                    <div class="input-block">
                        <div class="input-block__label">Дата ПО:</div>
                        <div class="input-block__wrap">
                            <DatePicker
                                :date="form.date_to"
                                placeholder="Дата по"
                                @change="form.date_to = $event"
                                :allowed-dates="form.available_dates"
                            />
                        </div>
                    </div>
                </div>
                <div class="input-block">
                    <div class="input-block__label">Дней</div>
                    <div class="input-block__wrap">
                        <NumMultiSelect
                            :value="form.days"
                            @change="form.days = $event"
                            :min="1"
                            :max="14"
                            :limit="14"
                        />
                    </div>
                </div>
                <div class="input-block">
                    <div class="input-block__label">Состав</div>
                    <div class="input-block__wrap">
                        <PeoplesSelector
                            :adults-max="max_adults"
                            :adults-min="1"
                            :adults="form.adults"
                            @adults-change="form.adults = $event"
                            :childrens-max="max_childrens"
                            :childrens="form.childrens"
                            @childrens-change="form.childrens = $event"
                        />
                    </div>
                </div>
                <div class="input-block">
                    <div class="input-block__wrap widget-submit">
                        <button @click="search()" class="btn-submit">
                            Найти
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="!start && !results_loaded" class="search-widget-preloader">
                <img src="/themes/azimut-tur-pro/assets/images/preloaders/cubesline_preloader.gif" alt="Loading...">
            </div>
        </template>
        <div class="expand-widget" v-else @click="expand()">
            <div class="btn red">
                Изменить параметры поиска
            </div>
        </div>
    </div>
</template>

<script>
import api from '../vue-components/api';
import TreeMultiSelect from "../vue-components/TreeMultiSelectInline";
import NumMultiSelect from "../vue-components/NumMultiSelect";
import DatePicker from "../vue-components/DatePicker";
import NumSelect from "../vue-components/NumSelect";
import PeoplesSelector from "../vue-components/PeoplesSelector";
import clipboardCopy from "clipboard-copy";

export default {
    name: "ExtWidget",
    components: {
        TreeMultiSelect,
        DatePicker,
        NumMultiSelect,
        NumSelect,
        PeoplesSelector
    },
    data()
    {
        return {
            process: false,
            data_loaded: false,
            scope: 'ext',
            start: true, // Стартовый виджет или нет
            results_loaded: false,

            width: null, // Ширина
            scroll: 0, // Был скролл
            widget_top: 0, // y-позиция виджета
            collapse_block: false, // Блокировка сворачивания виджета

            tour_name: null, // Имя посадочной страницы (Dolphin.name)

            form: {
                geo_objects: [],
                date_of: '10.03.2022',
                date_to: '15.03.2022',
                days: [],
                adults: 1,
                childrens: [],
                list_type: 'schedule'
            },

            geo_tree: null,

            /* Ограничения людей */
            max_people: 4, /* Максимально людей всего */
            max_adults: 4, /* Максимально взрослых */
            max_childrens: 2, /* Максимально детей */
            allowed_adults: 4,
            allowed_childrens: 2,
            admin: 0, /* Является ли пользователь админом сайта */
        }
    },
    created() {
        window.ExtWidget = this
        window.SearchApi = {
            onSearch: (preset) => {},
            search: (preset) => {
                preset = JSON.parse(preset)
                preset.days = preset.days.map(function (item) {
                    return parseInt(item)
                })
                this.form = preset
                this.search()
            }
        }
    },
    mounted()
    {
        this.bindEvents()
        this.tour_name = $('meta#tour-name').attr('content')
        this.admin = $('meta#admin').attr('content')
        this.fetchGeoTree(() => {
            /* Определяем, есть ли модуль результатов на странице */
            if (typeof ExtResults !== 'undefined') {
                this.start = false
            }
            this.data_loaded = true
            this.loadDefaults(() => {
                // Если это НЕ стартовая страница, произвести поиск
                if (!this.start) {
                    this.search()
                }
            })
        })
        this.widget_top = Math.round($('.wrapper-sticky__widget').offset().top)
    },
    computed: {
        collapse_widget()
        {
            return this.scroll > this.widget_top && !this.collapse_block && this.width < 900
        }
    },
    watch: {
        form()
        {
            this.recountPeoples()
        },
        process(process)
        {
            // Передаём состояние процесса в апплет с результатами
            if (!this.start) {
                ExtResults.setProcess(process)
            }
        }
    },
    methods: {
        bindEvents()
        {
            $(document).ready(() => {
                this.setWidth()
            })

            $(window).resize(() => {
                this.setWidth()
            })
            $(window).scroll(() => {
                this.scroll = $(window).scrollTop()
            })
        },
        setWidth()
        {
            this.width = $('.wrapper-sticky__widget').width()
        },
        expand()
        {
            this.collapse_block = true
            this.scroll = 0
        },
        apiQuery(opts)
        {
            this.process = true
            api.sync({
                url: '/zen/dolphin/api/' + opts.method,
                data: opts.data,
                then: response => {
                    this.process = false
                    if (opts.then) {
                        opts.then(response)
                    }
                },
                error: error => {
                    console.log(error)
                    this.process = false
                }
            })
        },
        getInjectorBlocks(fn)
        {
            this.sync({
                url: '/blocks/injects',
                data: {
                    scope_id: 1
                },
                then: (response) => {
                    this.blocks = response
                    fn()
                }
            })
        },
        fetchGeoTree(fn)
        {
            this.apiQuery({
                method: 'store:geoTree',
                data: {
                    widget: 'ext',
                    dates: fn.dates ? fn.dates : null
                },
                then: response => {
                    this.geo_tree = response
                    fn()
                }
            })
        },
        recountPeoples()
        {
            let peoples_count = this.form.adults + this.form.childrens

            if (peoples_count < this.max_people) {
                this.allowed_adults = this.max_adults
                this.allowed_childrens = this.max_childrens
            } else {
                this.allowed_adults = this.form.adults
                this.allowed_childrens = this.form.childrens
            }
        },
        loadDefaults(fn)
        {
            let preset = document.getElementById('dolphin-preset')
            if (preset && preset.content) {
                preset = preset.content
                preset = JSON.parse(preset)
                preset.days = preset.days.map(function (item) {
                    return parseInt(item)
                })
                this.form = preset
            }
            fn()
        },
        search()
        {
            // Если мы находимся на стартовой странице
            if (this.start) {
                //let query = api.queryToString(this.form)
                let query = JSON.stringify(this.form)
                location.href = location.origin + '/ex-tours/ext/search?query=' + query
            }
            // Если мы находимся на посадочной странице
            else {
                this.apiQuery({
                    method: 'ext:search',
                    data: {
                        geo_objects: this.form.geo_objects,
                        date_of: this.form.date_of,
                        date_to: this.form.date_to,
                        days: this.form.days,
                        adults: this.form.adults,
                        childrens: this.form.childrens,
                        list_type: this.form.list_type
                    },
                    then: response => {
                        this.collapse_block = false
                        this.results_loaded = true
                        ExtResults.setResults({
                            results: response,
                            form: this.form
                        })
                        let preset = ExtResults.getSearchPreset()
                        preset = JSON.parse(preset)
                        preset.type = 'tours'
                        preset.name = 'Экскурсионные туры: Саратов'
                        console.log('PRESET_OBJECT', preset)
                        if (preset.geo_objects.length) {
                            preset.name += ' > ' + this.getGeoName(preset.geo_objects)
                        }
                        preset = JSON.stringify(preset)
                        SearchApi.onSearch(preset)
                    }
                })
            }
        },
        getGeoName(geo_codes) {
            let names = this.recursiveSearch(this.geo_tree, geo_codes, [])
            return names.join(',')
        },
        recursiveSearch(tree, geo_codes, names) {
            for (let i in tree) {
                if (tree[i].id && geo_codes.includes(tree[i].id)) {
                    names.push(tree[i].name)
                }
                if (tree[i].items) {
                    let items_names = this.recursiveSearch(tree[i].items, geo_codes, names)
                    for (let ii in items_names) {
                        if (!names.includes(items_names[ii])) {
                            names.push(items_names[ii])
                        }
                    }
                }
            }
            return names
        },
        changeListType(list_type)
        {
            this.form.list_type = list_type
            this.search()
        },
        copyToClipboard()
        {
            let preset = ExtResults.getSearchPreset()
            clipboardCopy(preset)
            alert('Поисковый пресет скопирован в буфер обмена')
        }
    }
}
</script>
