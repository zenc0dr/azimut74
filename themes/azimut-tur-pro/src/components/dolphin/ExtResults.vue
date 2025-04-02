<template>
    <div class="dw-results" :class="{process}">
        <Labels :labels="labels_data"
                :allow_labels="allow_labels"
                @change="allow_labels = $event"
        />
        <SelectListType
            v-if="loaded"
            :list-type="list_type"
            @change="changeListType($event)"
            :process="process"
        />
        <template v-if="width">
            <template v-if="result_items.length && list_type === 'schedule'">
                <template v-if="isTourShow(index, item)" v-for="(item, index) in result_items">
                    <template v-if="!item.injection">
                        <ExtResultSchedule
                            v-if="list_type === 'schedule'"
                            :item="item"
                            :width="width"
                            :labels="getLabels(item)"
                            :design-url="designUrl"
                            @show-label="allow_labels = [$event.id]"
                        />
                        <ExtResultCatalog
                            v-if="list_type === 'catalog'"
                            :item="item"
                            :labels="getLabels(item)"
                            :design-url="designUrl"
                            @show-label="allow_labels = [$event.id]"
                        />
                    </template>
                    <SelfInjection v-else :html="item.injection" />
                </template>
            </template>
            <div class="schedule-table" v-if="result_items.length && list_type === 'table'">
                <ScTable
                    :width="width"
                    :min-width="690"
                    :items="result_items"
                    :resolving="isTourShow"
                />
            </div>
        </template>

        <div v-if="results_limit < result_items.length && !allow_ids.length" class="p-4 d-flex justify-content-center">
            <div class="btn d-flex align-items-center justify-content-center bg-red-300 c-primary-100 py-3 px-4 fw-bolder text-uppercase"
                @click="results_limit += 20">
                Ещё результаты
            </div>
        </div>
        <template v-if="loaded && !result_items.length">
            Ничего не найдено. Попробуйте изменить параметры поиска
        </template>
    </div>
</template>
<script>
import api from '../vue-components/api';
import SelectListType from "./SelectListType";
import ExtResultSchedule from "./ext-results/ExtResultSchedule";
import ExtResultCatalog from "./ext-results/ExtResultCatalog";
import Labels from "./Labels";
import SelfInjection from "../vue-components/SelfInjection";
import ScTable from "./ScTable";
export default {
    name: "ExtResults",
    components: { SelectListType, ExtResultSchedule, ExtResultCatalog, Labels, SelfInjection, ScTable },
    data() {
        return {
            form: null,
            loaded: false, // Первая загрузка результатов
            process: false, // Процесс загрузки новых результатов
            result_items: [],
            width: null, // Ширина области с результатами
            labels_data: null, // Метки результатов
            list_type: 'schedule', // Тип данных ex: 'catalog' || 'schedule' || 'offers'
            results_limit_default: 20,
            results_limit: 20, // Максимальное количество результатов
            allow_labels: [], // Разрешённые метки
            allow_ids: [], // Разрешённые id результатов
            page_id: null, // id посадочной страницы для формирования FAQ
            blocks: null, // Блоки для инъектора банеров
        }
    },
    created() {
        window.ExtResults = this
    },
    mounted() {
        this.bindWidthDetect()
        this.page_id = parseInt($('meta#page-id').attr('content'))
        //this.getInjectorBlocks() // Временно отключено за отсутствием блоков
    },
    watch: {
        allow_labels() {
            if (!this.allow_labels.length) {
                this.allow_ids = []
                return
            }
            let new_allow_ids = []
            this.labels_data.map(label => {
                if (this.allow_labels.includes(label.id)) {
                    label.tours.map(tour_id => {
                        if (!new_allow_ids.includes(tour_id)) {
                            new_allow_ids.push(tour_id)
                        }
                    })
                }
            })
            this.allow_ids = new_allow_ids
        }
    },
    methods: {
        bindWidthDetect()
        {
            $(document).ready(() => {
                this.setWidth()
            })

            $(window).resize(() => {
                this.setWidth()
            })
        },
        getSearchPreset()
        {
            let preset = JSON.parse(JSON.stringify(this.form))
            preset.labels = this.allow_labels
            return JSON.stringify(preset)
        },
        getLabels(item)
        {
            let labels = [];
            this.labels_data.forEach(label => {
                if (label.tours.includes(item.id)) {
                    labels.push(label)
                }
            })
            return labels
        },
        isTourShow(index, item)
        {
            if (this.allow_ids.length) {
                return this.isLabelFilterAllow(item)
            }
            return index < this.results_limit
        },
        isLabelFilterAllow(item)
        {
            if (!this.allow_ids.length) {
                return true
            }
            return this.allow_ids.includes(item.id)
        },
        setWidth()
        {
            this.width = $('.dw-results').width()
        },
        setProcess(process)
        {
            this.process = process
        },
        resetItemsLimit()
        {
            this.results_limit = this.results_limit_default
        },

        // Метод через который приходят результаты
        setResults(data)
        {
            this.resetItemsLimit()
            this.result_items = data.results.result_items
            this.labels_data = data.results.labels_data
            this.list_type = data.results.list_type
            this.loaded = true
            this.form = data.form

            if (this.form.labels) {
                this.allow_labels = this.form.labels
            }

            /* Инъекция рекламных блоков происходит везде кроме таблицы */
            if (this.list_type !== 'table') {
                this.injectBlocks()
            }
        },
        changeListType(list_type)
        {
            this.resetItemsLimit()
            ExtWidget.changeListType(list_type)
        },
        designUrl(item)
        {
            let days = this.form.days

            if (days.length === 14) {
                days = 'all'
            }

            let url_data = {
                ad: this.form.adults,
                ch: this.form.childrens,
                ds: days,
                go: this.form.geo_objects,
                id: item.id,
                dt: item.date,
                lp: this.page_id
            }
            return '/ex-tours/ext/booking?d=' + JSON.stringify(url_data)
        },

        // Функция для инъектора
        getInjectorBlocks()
        {
            api.sync({
                url: '/blocks/injects',
                data: {
                    scope_id: 1
                },
                then: (response) => {
                    this.blocks = response
                }
            })
        },

        // Функция для инъектора
        injectBlocks()
        {
            if (!this.blocks || !this.blocks.length) {
                return;
            }
            for (let i in this.blocks) {
                let block = this.blocks[i]
                let sequence = block.sequence // Последовательность
                console.log(sequence)
                let markers = sequence.split(',')

                for (let ii in markers) {
                    this.putBlock(block.html, markers[ii])
                }
            }
        },

        // Функция для инъектора
        putBlock(block, marker)
        {
            if (/^\+/.test(marker)) {
                let m = parseInt(marker);
                m--;
                if (this.result_items[m + 1]) {
                    this.result_items.splice(m + 1, 0, { injection:block })
                }
            }
            else {
                let m = parseInt(marker);
                let pp = this.per_page
                let page = this.page
                let ip = Math.ceil(m / pp)
                if (page === ip) {
                    let ia = pp - (ip * pp - m);
                    ia--;
                    if (this.result_items[ia + 1]) {
                        this.result_items.splice(ia + 1, 0, { injection:block })
                    }
                }
            }
        }
    }
}
</script>
