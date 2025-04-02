<template>
    <div class="schedule-table container">
        <h2 class="schedule-table__title title-section">График туров</h2>

        <ScTable
            :items="schedule_table"
            :width="width"
            :min-width="min_width"
        />

        <div v-if="start_page" class="d-flex align-items-center justify-content-center mt-4">
            <a :href="link" class="btn d-flex align-items-center justify-content-center bg-red-300 c-primary-100 py-3 px-4 fw-bolder text-uppercase">
                Смотреть весь график
            </a>
        </div>
    </div>
</template>

<script>
import api from '../vue-components/api';
import ScTable from "./ScTable";
import moment from 'moment';
export default {
    name: "ScheduleTable",
    components: { ScTable },
    mounted() {
        this.process = true
        if (location.pathname === '/ex-tours/schedule/ext') {
            this.start_page = false
        }
        this.bindWidthDetect()
        this.getData()
    },
    data() {
        return {
            process: false,
            schedule_table: null, // departure, arrival, days, desc (urls, waybill, tour_name), tour_name, price
            start_page: true,
            width: null,
            min_width: 690, // Минимальная ширина десктоп версии
        }
    },
    computed: {
        link() {
            let query = {
                "geo_objects": [],
                "date_of": moment().format('DD.MM.YYYY'),
                "date_to": null,
                "days": [],
                "adults": 1,
                "childrens": [],
                "list_type": "table"
            }
            return '/ex-tours/ext/search?query=' + JSON.stringify(query)
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
        setWidth()
        {
            this.width = $('.schedule-table').width()
        },
        getData()
        {
            let limit = this.start_page ? 10 : 0

            api.sync({
                url: '/zen/dolphin/api/ext:schedule?limit=' + limit,
                then: response => {
                    this.process = false
                    this.schedule_table = response
                },
                error: error => {
                    console.log(error)
                    this.process = false
                }
            })
        }
    }
}
</script>
