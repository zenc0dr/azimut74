<template>
    <div class="ship-search">
        <section v-if="loaded" class="search-widget bg-blue-300 rounded p-2 p-sm-3 p-xl-4 c-primary-100 mb-4">
            <div class="widget-wrapper">
                <div class="widget-content my-2 w-100">
                    <h1 class="fs-h2 fs-lg-h3 text-center text-lg-start fw-bolder mb-5 mb-lg-4">Теплоходы</h1>
                    <div class="widget-form  d-flex flex-wrap align-items-center justify-content-center">
                        <div class="widget-form-input mb-1 mb-lg-4 col-12 col-lg-3 c0 fs-s fs-xxl-def">
                            <div class="w-form-label fw-bolder mb-1 c-blue-100 c-lg-primary-100 fs-ss fs-sm-s fs-xxl-def">
                                Теплоход
                            </div>
                            <MultiCheckDD
                                :value="form.ships.value"
                                :options="form.ships.options"
                                @change="form.ships.value = $event"
                            />
                        </div>
                        <div class="widget-form-input mx-md-5 mx-lg-5 mb-1 mb-lg-4 col-12 col-lg-auto c0 fs-s fs-xxl-def">
                            <div class="w-form-label fw-bolder mb-1 c-blue-100 c-lg-primary-100 fs-ss fs-sm-s fs-xxl-def">
                                Количество палуб
                            </div>
                            <MultiCheckDD
                                :value="form.desks_count.value"
                                :options="form.desks_count.options"
                                @change="form.desks_count.value = $event"
                            />
                        </div>
                        <div class="widget-form-input mb-1 mb-lg-4 col-12 col-lg-auto c0 fs-s fs-xxl-def">
                            <div class="w-form-label fw-bolder mb-1 c-blue-100 c-lg-primary-100 fs-ss fs-sm-s fs-xxl-def">
                                Уровень
                            </div>
                            <MultiCheckDD
                                :value="form.status.value"
                                :options="form.status.options"
                                @change="form.status.value = $event"
                            />
                        </div>
                        <div class="widget-count__result w-100 d-flex justify-content-center align-items-center">
                            <div class="mt-0 mt-lg-2 fw-bolder fs-s">Найдено: {{ count }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <ShipsResults :items="items" />
    </div>
</template>

<script>
import axios from "axios";
import ShipsResults from "./components/ShipsResults";
import MultiCheckDD from "../vue-components/MultiCheckDD";
export default {
    name: "ShipSearch",
    components: { MultiCheckDD, ShipsResults },
    data() {
        return {
            loaded: false,
            process: false,
            form: {},
            count: 0,
            items: null
        }
    },
    mounted() {
        this.loadFormData(() => {
            //this.query()
        })
    },
    watch: {
        form: {
            handler() {
                this.query()
            },
            deep: true
        }
    },
    methods: {
        sync(opts)
        {
            this.process = true
            let domain = location.origin
            let data = (opts.data) ? opts.data : null
            let api_url = domain + '/rivercrs/api/' + opts.method
            console.log(api_url, data) // todo:debug
            // Если нет данных то запрос GET
            if (!data) {
                axios.get(api_url)
                    .then((response) => {
                        this.process = false
                        opts.then(response.data)
                        console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        this.process = false
                        console.log(error) // todo:debug
                    })
            }
            // Если есть data то запрос POST
            else {
                axios.post(api_url, data)
                    .then((response) => {
                        this.process = false
                        opts.then(response.data)
                        console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        this.process = false
                        console.log(error) // todo:debug
                    })
            }
        },
        loadFormData(fn)
        {
            this.sync({
                method: 'ships',
                then: (response) => {
                    this.form = response.form
                    this.loaded = true
                    if (fn) {
                        fn()
                    }
                }
            })
        },
        query()
        {
            this.sync({
                method: 'searchShips',
                data: {
                    form: {
                        ships: this.form.ships.value,
                        desks_count: this.form.desks_count.value,
                        status: this.form.status.value
                    }
                },
                then: (response) => {
                    this.items = response.items
                    this.count = response.count
                    setTimeout(() => {
                        PopoverInit()
                    }, 100)
                }
            })
        }
    }
}
</script>
