<template>
    <div class="result-items">
        <template v-if="width">
            <ListType
                :process="process"
                :list-type="listType"
                @change="$emit('change-list-type', $event)"
            />
            <div v-if="listType === 'catalog'" v-for="item in limited_results">
                <Result
                    :item="item"
                    :labels="item.labels"
                    :design-url="designUrl"
                    @show-label="$emit('show-tag', $event)"
                />
            </div>
            <div v-if="listType === 'table'" class="schedule-table">
                <ScTable
                    :width="width"
                    :min-width="690"
                    :items="limited_results"
                    :dates_enabled="false"
                    :design-url="designUrl"
                />
            </div>
            <div v-if="results.length > limit" class="p-4 d-flex justify-content-center">
                <div @click="limit += resultsLimit"
                     class="btn d-flex align-items-center justify-content-center bg-red-300 c-primary-100 py-3 px-4 fw-bolder text-uppercase">
                    Ещё результаты
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import Result from "../dolphin/ext-results/ExtResultCatalog";
import ListType from "./gt-list-type";
import ScTable from "../dolphin/ScTable";
export default {
    name: "GroupToursResults",
    components: { Result, ListType, ScTable },
    props: {
        results: {
            type: Array,
            default: null
        },
        process: {
            type: Boolean,
            default: false
        },
        listType: {
            type: String,
            default: 'catalog'
        },
        resultsLimit: {
            type: Number,
            default: 10,
        },
        width: {
            type: Number,
            default: null
        }
    },
    created() {
        this.resetLimit()
    },
    data() {
        return {
            limit: 0,
        }
    },
    computed: {
        limited_results() {
            let results = JSON.parse(JSON.stringify(this.results))
            return results.splice(0, this.limit)
        }
    },
    watch: {
        results()
        {
            this.resetLimit()
        }
    },
    methods: {
        resetLimit()
        {
            this.limit = this.resultsLimit
        },
        designUrl(item)
        {
            return '/group-tours/tour-' + item.id
        },
    }
}
</script>
