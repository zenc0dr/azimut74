<template>
    <div>
        <div v-if="data_loaded" class="dolphin-widget container">
            <div class="dolphin-widget__title">
                {{ title }}
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
            <div class="input-block" style="width:100%;flex:auto">
                <div class="input-block__label">Теги</div>
                <div class="input-block__wrap">
                    <ZenTags
                        :value="form.tags"
                        :tags="tags"
                        @change="form.tags = $event"
                    />
                </div>
            </div>
        </div>
        <Results
            :results="results"
            :process="process"
            :list-type="form.type"
            :results-limit="results_limit"
            :width="width"
            @change-list-type="form.type = $event"
            @show-tag="form.tags = [$event.id]"
        />
    </div>
</template>
<script>
import TreeMultiSelect from "../vue-components/TreeMultiSelectInline";
import NumMultiSelect from "../vue-components/NumMultiSelect";
import clipboardCopy from "clipboard-copy";
import ZenTags from "../vue-components/ZenTags";
import Results from "./gt-results";
import api from '../vue-components/api';

export default {
    name: 'GroupToursSearchWidget',
    components: {
        TreeMultiSelect, NumMultiSelect, ZenTags, Results
    },
    data() {
        return {
            process: false,
            data_loaded: false, // Первоначальная загрузка данных
            geo_tree: null, // Дерево гео-объектов
            tags: null,
            admin: false,
            width: null, // Ширина контейнера
            form: {
                geo_objects: [], // Гео-объекты
                days: [], // Дни
                tags: [], // Теги (только id-шники)
                type: 'catalog'
            },
            results: [], // Результаты
            results_limit: 30,
            title: null,
            preset: null
        }
    },
    mounted() {
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

        this.title = this.meta('grouptours-title')

        if (window.location.hash !== '' &&  window.location.hash.indexOf("s=",0)) {
            let preset = window.location.hash.substring(3)
            this.preset = decodeURI(preset)
        }

        this.bindWidthDetect()
        this.fetchOptions(() => {
            this.data_loaded = true
            this.loadDefaults(preset_loaded => {
                if (preset_loaded) {
                    this.search()
                }
            })
        })
    },
    watch: {
        form: {
            handler() {
                this.search()
            },
            deep: true
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
            this.width = $('.result-items').width()
        },
        apiQuery(opts)
        {
            this.process = true
            api.sync({
                url: '/zen/gt/api/' + opts.method,
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
        meta(id) {
            let meta = $('#' + id)
            if (!meta.length) {
                return null
            }
            let content = meta.attr('content')
            if (!content) {
                return null
            }
            meta.remove()
            return content
        },
        fetchOptions(fn)
        {
            this.apiQuery({
                method: 'defaults:get',
                then: response => {
                    this.geo_tree = response.geo_tree
                    this.tags = response.tags
                    this.admin = response.admin
                    this.results_limit = response.results_limit
                    fn()
                }
            })
        },
        loadDefaults(fn)
        {
            let preset = this.meta('gt-preset')
            if(this.preset) {
                preset = this.preset
            }

            if (preset) {
                preset = JSON.parse(preset)
                preset.days = preset.days.map(function (item) {
                    return parseInt(item)
                })
                this.form = preset
            }
            fn(!!preset)
        },
        search()
        {
            this.apiQuery({
                method: 'search:go',
                data: { data:this.form },
                then: response => {
                    if (response.success) {
                        this.results = response.results
                        let preset = _.hardClone(this.form)
                        preset.geo = ''
                        if (preset.geo_objects.length) {
                            preset.geo += this.getGeoName(preset.geo_objects)
                        }
                        preset.title = this.title
                        preset.type = 'group-tours'
                        SearchApi.onSearch(JSON.stringify(preset))
                    }
                }
            })
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
        copyToClipboard()
        {
            clipboardCopy(JSON.stringify(this.form))
            alert('Поисковый пресет скопирован в буфер обмена')
        }
    }

}
</script>
