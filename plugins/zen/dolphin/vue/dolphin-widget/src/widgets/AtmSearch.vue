<template>
    <div class="preloader-wrap">
        <div v-if="process" class="preloader"></div>
        <div v-if="process" class="dw-preloader-line"
             :style="`background-image:url(${domain}/plugins/zen/dolphin/assets/images/orange_line.png)`"></div>
        <div class="dw-body">
            <div class="dw-title">{{ atm_widget_title }}</div>
            <div class="responsive-grid">

                <div v-if="!atp" class="input-cell">
                    <CapFrom/>
                </div>

                <div class="input-cell">
                    <Date title="Дата"
                          :confirm="false"
                          :editable="false"
                          :multiple="true"
                          :allowed-dates="allowed_dates"
                          :mutation="dateMutation"
                          :default-position="default_date_position"
                          store="AtmDates"/>
                </div>

                <div v-if="!atp" class="input-cell">
                    <TreeMultiSelect
                        title="Курорт"
                        :geo-tree="geo_tree"
                        :default="default_geo_objects"
                        @select="selectGeoObjects"
                        @open="openGeo"
                        @onload="$store.dispatch('fetchAtmDb')"
                    />
                </div>

                <div v-if="!atp" class="input-cell">
                    <MultiSelect
                        title="Отель"
                        store="AtmSelectedHotels"
                        store-options="AtmAllowedHotels"
                    />
                </div>

                <div class="input-cell">
                    <MultiSelect
                        title="Удобства"
                        store="AtmSelectedComforts"
                        store-options="AtmAllowedComforts"
                    />
                </div>

                <div class="input-cell">
                    <MultiSelect
                        title="До моря"
                        store="AtmSelectedToSea"
                        store-options="AtmAllowedToSea"
                    />
                </div>

                <div class="input-cell">
                    <MultiSelect
                        title="Услуги"
                        store="AtmSelectedServices"
                        store-options="AtmAllowedServices"
                    />
                </div>

                <div class="input-cell">
                    <MultiSelect
                        title="Питание"
                        store="AtmSelectedPansions"
                        store-options="AtmAllowedPansions"
                    />
                </div>
            </div>

            <zen-peoples
                adults-store="AtmAdults"
                childrens-store="AtmChildrens"
                children-ages-store="AtmChildrenAges"
                :insert-data="peoples_mounted"
            />
        </div>
        <AtmResults :items="results" :result-key="result_key" :atp="atp"/>
    </div>
</template>
<script>
import TreeMultiSelect from "../components/TreeMultiSelect";
import ChildrenAges from "../components/ChildrenAges";
import MultiSelect from "../components/MultiSelect";
import AtmResults from "../components/AtmResults";
import ZenPeoples from "../components/ZenPeoples";
import NumSelect from "../components/NumSelect";
import CapFrom from "../components/CapFrom";
import Date from "../components/Date";
import axios from "axios";
import $ from 'jquery'

export default {
    name: 'AtmSearch',
    components: {
        TreeMultiSelect,
        ChildrenAges,
        MultiSelect,
        AtmResults,
        ZenPeoples,
        NumSelect,
        CapFrom,
        Date
    },
    created() {
        window.SearchApi = {
            onSearch: (preset) => {},
            search: (preset) => {
                preset = JSON.parse(preset)
                location.hash = preset.preset
                this.bootstrap()
            }
        }
    },
    data() {
        return {
            filtred_tours_ids: [], // id туров которые прошли фильтрацию
            results: [], // Результаты
            open_geo: 0,
            result_key: null,
            runs_count: 0,
            peoples_mounted: null,
            atp: null,
            peoples_stamp: 'a2c0ages', // Отпечаток настроек состава людей
            peoples_changed: false,
            options_changeable: true,
            change_reaction: true,
            default_date_position: null,

            default_geo_objects: null,

            tour_id: null, // Для ATP выбранный тур
            atm_widget_title: 'Подбор автобусного тура к морю',

            blocks: null, // Рекламные блоки
        }
    },
    mounted() {
        this.bootstrap()
    },
    watch: {
        atm_dates(dates) {
            if (!this.change_reaction) {
                return
            }
            this.$store.dispatch('fetchGeoTree', {
                dates,
                then: (response) => {
                    if (this.atp) {
                        this.$store.dispatch('fetchAtmDb')
                    }
                }
            })
        },

        // Как только я выбираю гео-объекты, мы загружаем atmDb записываем в setAtmDb
        geo_objects() {
            if (!this.change_reaction) {
                return
            }
            this.$store.dispatch('fetchAtmDb')
        },

        // Отслеживаем состав
        adults() {
            this.createPeoplesStamp()
        },
        childrens() {
            this.createPeoplesStamp()
        },
        children_ages() {
            this.createPeoplesStamp()
        },


        // Как только atmDb загружено !!!!
        // *********** ЗАПОЛНИТЬ ОПЦИИ И ПОКАЗАТЬ РЕЗУЛЬТАТЫ
        atm_db() {
            if (!this.change_reaction) return
            this.work(0)
        },

        /* Реагируем на изменение */
        selected_hotels() {
            if (!this.change_reaction) return
            this.reWork(0)
        },
        selected_comforts() {
            if (!this.change_reaction) return
            this.reWork(1)
        },
        selected_to_sea() {
            if (!this.change_reaction) return
            this.reWork(2)
        },
        selected_services() {
            if (!this.change_reaction) return
            this.reWork(3)
        },
        selected_pansions() {
            if (!this.change_reaction) return
            this.reWork(4)
        },

        peoples_stamp() {
            if (!this.change_reaction) return
            this.peoples_changed = true
            this.$store.dispatch('fetchAtmDb')
        }
    },
    computed: {
        atm_db() {
            return this.$store.getters.getAtmDb
        },

        allowed_dates() {
            return this.$store.getters.getAtmAllowedDates
        },
        atm_dates() {
            return this.$store.getters.getAtmDates
        },
        geo_tree() {
            return this.$store.getters.getGeoTree
        },
        geo_objects() {
            return this.$store.getters.getAtmGeoObjects
        },

        allowed_hotels() {
            return this.$store.getters.getAtmAllowedHotels
        },
        allowed_comforts() {
            return this.$store.getters.getAtmAllowedComforts
        },
        allowed_services() {
            return this.$store.getters.getAtmAllowedServices
        },
        allowed_to_sea() {
            return this.$store.getters.getAtmAllowedToSea
        },
        allowed_pansions() {
            return this.$store.getters.getAtmAllowedPansions
        },

        selected_hotels() {
            return this.$store.getters.getAtmSelectedHotels
        },
        selected_comforts() {
            return this.$store.getters.getAtmSelectedComforts
        },
        selected_services() {
            return this.$store.getters.getAtmSelectedServices
        },
        selected_to_sea() {
            return this.$store.getters.getAtmSelectedToSea
        },
        selected_pansions() {
            return this.$store.getters.getAtmSelectedPansions
        },

        adults() {
            return this.$store.getters.getAtmAdults
        },
        childrens() {
            return this.$store.getters.getAtmChildrens
        },
        children_ages() {
            return this.$store.getters.getAtmChildrenAges
        },

        process() {
            return this.$store.getters.getProcess
        },
        domain() {
            return this.$store.getters.getSettings.domain
        }
    },
    methods: {
        bootstrap() {
            this.getInjectorBlocks(() => {
                this.$store.dispatch('fetchAtmAllowedDates', {
                    then: response => {
                        if (!response) return

                        // Установка позиции календаря на страницу с первой возможной для выбора датой
                        let date = response[0].split('.')
                        this.default_date_position = date[2] + '-' + date[1]

                        this.fillSettings()
                    }
                })
            })
        },
        /* Этот метод чисто для инъекции */
        sync(opts)
        {
            let domain = location.protocol + '//' + location.hostname
            let data = (opts.data) ? opts.data : null

            let api_url = null

            api_url = domain + opts.url

            console.log(api_url, data) // todo:debug
            // Если нет данных то запрос GET
            if (!data) {
                axios.get(api_url)
                    .then((response) => {
                        opts.then(response.data)
                        console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        console.log(error) // todo:debug
                    })
            }
            // Если есть data то запрос POST
            else {
                axios.post(api_url, data)
                    .then((response) => {
                        opts.then(response.data)
                        console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        console.log(error) // todo:debug
                    })
            }
        },
        getInjectorBlocks(fn)
        {
            this.sync({
                url: '/blocks/injects',
                data: {
                    scope_id: 5
                },
                then: (response) => {
                    this.blocks = response
                    fn()
                }
            })
        },
        injectBlocks()
        {
            for (let i in this.blocks) {
                let block = this.blocks[i]
                let sequence = block.sequence // Последовательность
                let markers = sequence.split(',')
                for (let ii in markers) {
                    this.putBlock(block.html, markers[ii])
                }
            }
        },
        putBlock(block, marker) {
            if (/^\+/.test(marker)) {
                let m = parseInt(marker);
                m--;
                if (this.results[m + 1]) {
                    this.results.splice(m + 1, 0, { injection:block })
                }
            }
            else {
                /*
                let m = parseInt(marker);
                let pp = this.per_page
                let page = this.page
                let ip = Math.ceil(m / pp)
                if (page === ip) {
                    let ia = pp - (ip * pp - m);
                    ia--;
                    if (this.result[ia + 1]) {
                        this.result.splice(ia + 1, 0, { injection:block })
                    }
                }
                */
                console.log('Инструкция putBlock не выполнена')
            }
        },


        makeHash() {
            let hash =
                'g=' + this.geo_objects.join(',') +
                ';d=' + this.atm_dates.join(',') +
                ';p=' + this.adults +
                ';c=' + this.children_ages.join(',')
            window.location.hash = hash
        },
        // Сгенерировать отпечаток состава людей
        createPeoplesStamp() {
            this.peoples_stamp = 'a' + this.adults + 'c' + this.childrens + 'ages' + this.children_ages.join('+')
        },

        // Мутатор отображения даты, инъектится в компонент Date
        dateMutation(dates) {
            if (!dates || !dates.length) {
                return 'Не выбрано'
            }
            if (dates.length == 1) {
                return dates[0]
            }
            return 'Выбрано дат: ' + dates.length
        },

        // Записываем в $store гео-объекты
        selectGeoObjects(geo_objects) {
            this.$store.commit('setAtmGeoObjects', geo_objects)
        },

        // Собирает уникальные идентификаторы в цикле
        // getUniqueIds(collection, ids) {
        //     if(!ids) return []
        //
        //     for(let i in ids) {
        //         if(collection.indexOf(ids[i]) === -1) collection.push(parseInt(ids[i]))
        //     }
        //     return collection
        // },

        // Отели всегда заполняются полностью
        fillAllowedHotels() {
            let allowed_hotels = []
            for (let hotel_id in this.atm_db.hotels) {
                let hotel = this.atm_db.hotels[hotel_id]
                allowed_hotels.push({id: parseInt(hotel_id), name: hotel.name})
            }
            this.$store.commit('setAtmAllowedHotels', allowed_hotels)
        },

        // универсальная функция для заполнения опций простых сущностей
        fillAllowedOptions(allowed_ids, db, setter) {
            let allowed = []
            for (let i in db) {
                let record = db[i]
                if (allowed_ids.indexOf(parseInt(i)) !== -1) {
                    allowed.push({id: parseInt(i), name: record})
                }
            }
            this.$store.commit(setter, allowed)
        },

        openGeo(is_open) {
            this.open_geo = is_open
        },

        reWork(N) {
            if (this.options_changeable) this.work(N)
        },

        work(N) {
            if (!N) N = 0

            let atm_db = this.atm_db

            this.options_changeable = false

            let allowed_hotels = []
            let allowed_comforts = []
            let allowed_to_sea = []
            let allowed_services = []
            let allowed_pansions = []

            let output_tours = [];

            if (!atm_db.tours) {
                this.results = []
                return
            }

            atm_db.tours.map(item => {

                let tour_is_actual = true

                let stamp = item.stamp.split('.')    //2.1.1.9.10.350.1.1
                let tour_id = parseInt(stamp[0])
                let comfort_id = parseInt(stamp[1])
                let pansion_id = parseInt(stamp[2])
                let tarrif_id = parseInt(stamp[3])
                let hotel_id = parseInt(stamp[4])
                let to_sea = parseInt(stamp[5])
                let days = parseInt(stamp[6])
                let services = stamp[7] // может быть NaN или 1-2-3

                // Загрузка выбранных параметров
                let selected_hotels = this.selected_hotels.map(id => {
                    return parseInt(id)
                })
                let selected_comforts = this.selected_comforts.map(id => {
                    return parseInt(id)
                })
                let selected_to_sea = this.selected_to_sea.map(id => {
                    return parseInt(id)
                })
                let selected_services = this.selected_services.map(id => {
                    return parseInt(id)
                })
                let selected_pansions = this.selected_pansions.map(id => {
                    return parseInt(id)
                })

                // Зона фильтрации
                if (selected_hotels.length && selected_hotels.indexOf(hotel_id) === -1) tour_is_actual = false
                if (N > 0 && selected_comforts.length && selected_comforts.indexOf(comfort_id) === -1) tour_is_actual = false
                if (N > 1 && selected_to_sea.length && selected_to_sea.indexOf(to_sea) === -1) tour_is_actual = false
                if (this.tour_id && this.tour_id != tour_id) tour_is_actual = false

                // Фильтрация услуг
                if (services) {
                    services = services.split('-') // Превращается в массив
                    services = services.map((item) => {
                        return parseInt(item)
                    })
                }
                let actual_services = null
                if (N > 2 && selected_services.length) {

                    // Вернёт allowed_services или null
                    actual_services = this.serviceHandler(services, selected_services)
                    if (!actual_services) tour_is_actual = false
                }

                if (N > 3 && selected_pansions.length && selected_pansions.indexOf(pansion_id) === -1) tour_is_actual = false


                // тут короче services равно либо NaN либо 1 либо 1-2-3 и т.д.
                // так-же тут есть массив selected_services который тоже может быть [1,2] и т.д.
                // на этом шаге нужно сравнить 1-2-3 и [1,2] и если в selected_services [1,2] есть что-то из 1-2-3
                // то во первых, tour_is_actual = true т.е. тур проходит а если не нашлось то tour_is_actual = false
                // а все сервисы которые обнаружились в туре попадают в массив true_services
                // потом этот массив уже попадёт в allowed_services и потом это будет загружено в store
                // если ни один сервис не выбран то все встреченные сервисы попадут в allowed_services

                // вернуться должно что?
                /* вернётся объект вида:

                {tour_is_actual, allowed_services}

                */


                // Отели заполняются всегда (кроме случая когда изменили состав людей)
                if (!this.peoples_changed) {
                    allowed_hotels[hotel_id] = this.atm_db.hotels[hotel_id]
                }


                // Зона заполнений опций


                if (tour_is_actual && N < 1) {
                    allowed_comforts[comfort_id] = this.atm_db.comforts[comfort_id]
                }

                if (tour_is_actual && N < 2 && to_sea) {
                    allowed_to_sea[to_sea] = to_sea + ' м'
                }

                if (tour_is_actual && N < 3) {
                    if (actual_services) {
                        actual_services.map(service_id => {
                            allowed_services[service_id] = this.atm_db.services[service_id]
                        })
                    } else if (services) {
                        services.map(service_id => {
                            allowed_services[service_id] = this.atm_db.services[service_id]
                        })
                    }
                }

                if (tour_is_actual && N < 4) {
                    allowed_pansions[pansion_id] = this.atm_db.pansions[pansion_id]
                }

                /* Тур прошёл фильтрацию */
                if (tour_is_actual) output_tours.push(item)


            })

            if (N == 0 && !this.peoples_changed) {
                this.optionsPush(allowed_hotels, 'setAtmAllowedHotels')
                this.peoples_changed = false
            }
            if (N == 0) this.optionsPush(allowed_comforts, 'setAtmAllowedComforts')
            if (N < 2) this.optionsPush(allowed_to_sea, 'setAtmAllowedToSea')
            if (N < 3) this.optionsPush(allowed_services, 'setAtmAllowedServices')
            if (N < 4) this.optionsPush(allowed_pansions, 'setAtmAllowedPansions')

            this.options_changeable = true

            output_tours.sort(function (a, b) {
                return a.timestamp - b.timestamp
            })
            output_tours.sort(function (a, b) {
                return a.sum - b.sum
            })

            this.results = []
            setTimeout(() => {
                this.results = output_tours
                this.injectBlocks()
                this.runs_count++

                if (this.runs_count > 2) {
                    this.result_key = null
                }
            }, 50)
        },

        serviceHandler(stamp_services, selected_services) {
            if (!stamp_services) return null
            // Вычисляем пересечение массивов
            let intersection_arr = stamp_services.filter(x => selected_services.includes(x))
            if (!intersection_arr.length) return null
            return intersection_arr
        },

        recollect(arr) {
            let output = []
            for (let i in arr) {
                output.push({id: parseInt(i), name: arr[i]})
            }
            return output
        },

        optionsPush(arr, store_name) {
            arr = this.recollect(arr)
            this.$store.commit(store_name, arr)
        },

        makeResults() {
            this.runs_count++

            if (this.runs_count > 1) {
                this.result_key = null
            }

            if (!this.filtred_tours_ids.length) {
                this.results = []
                return
            }

            this.$store.dispatch('atmQuery', {
                tours: this.filtred_tours_ids,
                then: (response) => {
                    if (response) {
                        this.results = response
                    } else {
                        this.results = []
                    }
                }
            })

        },

        getPreset() {
            let preset = null;
            preset = window.location.hash
            if (!preset) {
                preset = document.getElementById('dolphin-preset')
                if (preset) preset = preset.content
            }
            if (!preset) return

            preset = preset.substr(1)
            preset = preset.split(';');

            let opts = {};
            for (let i in preset) {
                let opt = preset[i].split('=')
                opts[opt[0]] = opt[1]
            }

            let dates = opts.d.split(',')

            let geo_objects = []
            if (opts.g) {
                geo_objects = opts.g.split(',')
            }

            let adults = parseInt(opts.a)

            let childrens = []
            if (opts.ca) childrens = opts.ca.split(',').map(function (item) {
                return item = parseInt(item)
            })

            return {dates, geo_objects, adults, childrens}

        },
        getHotelPreset() {
            let hotel_preset = /\/h(\d+)/.exec(location.pathname)
            if (!hotel_preset) return null
            return parseInt(hotel_preset[1])
        },

        fillSettings() {
            let path = location.pathname
            let hash = location.hash
            let scope = null
            if (path.indexOf('/atm/') !== -1) scope = 'atm'
            if (path.indexOf('/atp/') !== -1) scope = 'atp'
            if (!scope) return
            let preset = this.getPreset()
            let hotel_id = this.getHotelPreset()
            let link_key = (scope == 'atp') ? path.split('/').pop() : null

            if (!preset && !hotel_id && !link_key) return;

            let q_dates = null
            let q_geo_objects = null
            let q_adults = 2
            let q_childrens = []

            let q_comforts = null
            let q_to_sea = null
            let q_services = null
            let q_pansions = null

            new Promise((resolve, reject) => {
                if (link_key) {
                    this.atp = true
                    let new_title = $('.atm-tour__name').text()
                    new_title = new_title.replace(/, \d+ \D+$/iu, '')
                    this.atm_widget_title = new_title
                    this.$store.dispatch('apiQuery', {
                        data: {key: link_key},
                        url: 'atm:getLinkData',
                        then: response => {
                            this.tour_id = parseInt(response.tour_id)
                            this.result_key = response.result_key
                            q_dates = response.dates
                            q_geo_objects = response.geo_objects
                            q_adults = response.adults
                            q_childrens = (response.childrens) ? response.childrens : null
                            hotel_id = response.hotel_id
                            q_comforts = response.comforts
                            q_to_sea = response.to_sea
                            q_services = response.services
                            q_pansions = response.pansions
                            resolve()
                        }
                    })
                } else if (preset) {
                    q_dates = preset.dates
                    q_geo_objects = preset.geo_objects
                    q_adults = preset.adults
                    q_childrens = preset.childrens
                    resolve()
                } else if (hotel_id) {
                    this.$store.dispatch('apiQuery', {
                        data: {hotel_id},
                        url: 'atm:getHotelPreset',
                        then: (response) => {
                            q_dates = response.dates
                            q_geo_objects = response.geo_objects
                            resolve()
                        }
                    })
                }
            })

                .then(() => {
                    return new Promise((resolve, reject) => {
                        this.change_reaction = false
                        this.$store.commit('setAtmDates', q_dates)
                        this.default_geo_objects = q_geo_objects
                        this.$store.commit('setAtmGeoObjects', q_geo_objects)
                        if (this.atp) {
                            resolve()
                        } else {
                            this.$store.dispatch('fetchGeoTree', {
                                dates: q_dates,
                                then: () => {
                                    resolve()
                                }
                            })
                        }
                    })
                })

                .then(() => {
                    this.$store.dispatch('fetchAtmDb', {
                        then: () => {
                            this.work(0)
                            if (hotel_id) this.$store.commit('setAtmSelectedHotels', [hotel_id])
                            if (q_comforts) this.$store.commit('setAtmSelectedComforts', q_comforts)
                            if (q_to_sea) this.$store.commit('setAtmSelectedToSea', q_to_sea)
                            if (q_services) this.$store.commit('setAtmSelectedServices', q_services)
                            if (q_pansions) this.$store.commit('setAtmSelectedPansions', q_pansions)
                            this.work(0)
                            setTimeout(() => this.change_reaction = true, 30)
                        }
                    })
                })
        },
    }
}
</script>
