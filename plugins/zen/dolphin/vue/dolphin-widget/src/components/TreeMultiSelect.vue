<template>
    <div class="zen-tree-multi-select" :class="{disabled:!geoTree.length}">
    <div class="dw-input-title">{{ title }}</div>
    <div @click="openList" class="ztms-text">{{ selectedName }}</div>
    <div v-if="show_list" class="ztms-list" v-click-outside="closeList">
        <input class="ztms-filter" v-model="filter" placeholder="Начните вводить название...">
        <div @click="cleanSelected" :class="{active:cleanAllowed}" class="ztms-clean">Снять выделение</div>
        <div class="ztms-options">

            <!-- Уровень 1 - Страны -->
            <template v-if="geoTree" v-for="itemL1 in geoTree">

                <!-- для выбора страны добавить в тег ниже @click="addSelected(itemL1.id)" -->
                <div v-if="filterGate(itemL1.name)" :class="{selected:isSelected(itemL1.id)}" class="ztms-item ztms-country">{{ itemL1.name }}</div>

                <!-- Если существует уровень 2 перебираем его items-->
                <div class="ztms-group" v-if="itemL1.items" v-for="itemL2 in itemL1.items">

                    <!-- Отображение пункта с иконкой -->
                    <div v-if="filterGate(itemL2.name)" @click="addSelected(itemL2.id)" :class="{selected:isSelected(itemL2.id)}" class="ztms-item">
                        <i v-if="isMarked(itemL2.id)" :class="isMarked(itemL2.id)"></i>{{ itemL2.name }}
                    </div>

                    <!-- Если существует уровень 3 -->
                    <div class="ztms-group" v-if="itemL2.items" v-for="itemL3 in itemL2.items">

                        <div v-if="filterGate(itemL3.name)" @click="addSelected(itemL3.id)" :class="{selected:isSelected(itemL3.id)}" class="ztms-item">
                            <i v-if="isMarked(itemL3.id)" :class="isMarked(itemL3.id)"></i>{{ itemL3.name }}
                        </div>

                        <div class="ztms-group" v-if="itemL3.items" v-for="itemL4 in itemL3.items">
                            <div v-if="filterGate(itemL4.name)" @click="addSelected(itemL4.id)" :class="{selected:isSelected(itemL4.id)}" class="ztms-item">
                                <i v-if="isMarked(itemL4.id)" :class="isMarked(itemL4.id)"></i>{{ itemL4.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        </div>
    </div>
</template>
<script>
    import ClickOutside from "../directives/ClickOutside";

    export default {
        name: 'TreeMultiSelect',
        props: ['title', 'geoTree', 'default'],
        directives: {
            'click-outside': ClickOutside
        },
        data()
        {
            return {
                run: false,
                output: [],
                filter: '',
                show_list: 0
            }
        },
        // mounted()
        // {
        //     this.output = this.default
        // },
        computed: {
            selectedName()
            {
                if(!this.output || this.output.length == 0) return 'Не выбрано'
                if(this.output.length == 1) return this.nameById(this.output[0])
                return 'Выбрано: '+this.output.length
            },
            cleanAllowed()
            {
                if(!this.output || !this.output.length) return false
                return true
            }
        },
        watch: {
            geoTree(value)
            {
                if(!value || !value.length) {
                    this.output = []
                } else {
                    this.checkOutput()
                    this.$emit('onload')
                }
                if(!this.default) return
                this.output = this.default
            },
            // Изменение выбранных городов
            output(value) {
                this.$emit('select', value)
                // Игнор первого запуска
                if(!this.run) {
                    this.run = true
                    return
                }
            },

            show_list(value) {
                if(value == 2) return
                this.$emit('open', value)
            }
        },
        methods: {
            checkOutput()
            {

                let filtred_output = []
                this.geoTree.map((counties) => {
                    counties.items.map((region) => {
                        if(this.output.indexOf(region.id) !== -1) {
                            filtred_output.push(region.id)
                        }
                        if(region.items) {
                            region.items.map((city) => {
                                if(this.output.indexOf(city.id) !== -1) {
                                    filtred_output.push(city.id)
                                }
                                if(city.items) {
                                    region.items.map((place) => {
                                        if(this.output.indexOf(place.id) !== -1) {
                                            filtred_output.push(place.id)
                                        }
                                    })
                                }
                            })
                        }
                    })
                })

                this.output = filtred_output
            },
            openList()
            {
                if(!this.geoTree.length) return
                if(this.show_list > 0) {
                    this.show_list = 0;
                } else {
                    this.show_list = 2;
                }
            },
            closeList()
            {
                if(this.show_list > 0) {
                    this.show_list--
                    if(!this.show_list) {
                        this.$emit('closeTree', JSON.parse(JSON.stringify(this.output)))
                    }
                }
            },
            filterGate(string)
            {
                if(!this.filter) return true
                if(string.toLowerCase().indexOf(this.filter.toLowerCase()) != -1) return true
            },

            // Выбран гео-объект
            addSelected(id)
            {
                if(this.isSelected(id)) {
                    this.removeSelected(id)
                } else {
                    this.output.push(id)
                    this.recursiveRecheck(id)
                }
            },
            removeSelected(id)
            {
                this.output.map((i, key) => {
                    if(i==id) {
                        this.output.splice(key, 1)
                    }
                })
            },
            isSelected(id)
            {
                if(!this.output) return
                if(this.output.indexOf(id) != -1) return true
            },
            isExtour(id)
            {
                if(id.indexOf(':ex') !== -1) return true
            },
            isMarked(id)
            {
                if(id.indexOf(':ex') !== -1) return 'fa fa-map-marker'
                if(id.indexOf(':ps') !== -1) return 'fa fa-dot-circle-o'

                return false
            },
            cleanSelected()
            {
                this.output = []
            },
            nameById(id)
            {
                return this.recursiveSearch(this.geoTree, id)
            },
            recursiveSearch(arr, search_id)
            {
                for(let i in arr) {
                    if(arr[i].id == search_id) {
                        return arr[i].name
                    } else if (arr[i].items) {
                        let name = this.recursiveSearch(arr[i].items, search_id)
                        if(name) return name
                    }
                }
            },

            // Рекурсивный анализ и снятие выделения с предков и потомков нода по целевой ветке
            recursiveRecheck(id)
            {
                let chain = {}
                let outChain = null
                let $this = this

                // Выбор потомков нода
                function check(arr)
                {
                    for(let i in arr) {
                        let level = arr[i].id.split(':')[0]
                        chain['level'+level] = arr[i].id
                        if(arr[i].id == id) {
                            outChain = JSON.parse(JSON.stringify(chain));
                            return true
                        }
                        if(arr[i].items) {
                            if(check(arr[i].items)) return
                        }
                    }
                }

                check(this.geoTree)

                // Очистка выбора потомков нода по id
                function checkOff(arr, id)
                {
                    if(id == 'clean') {
                        for(let i in arr) {
                            if ($this.output.indexOf(arr[i].id) != -1) {
                                $this.removeSelected(arr[i].id)
                            }
                            if (arr[i].items) {
                                checkOff(arr[i].items, 'clean')
                            }
                        }
                    } else {
                        for(let i in arr) {
                            if(arr[i].id == id) {
                                checkOff(arr[i].items, 'clean')
                                break
                            }
                            if(arr[i].items) {
                                checkOff(arr[i].items, id)
                            }
                        }
                    }
                }

                // Очистка выбора предков нода по ветке
                let node = null
                for(let i in outChain) {
                    if(outChain[i] == id) node = true
                    if(!node) {
                        this.removeSelected(outChain[i])
                    } else {
                        checkOff(this.geoTree, outChain[i])
                    }
                }
            }
        }
    }
</script>
<style>
    .zen-tree-multi-select {
        display: inline-block;
        width: 100%;
        position: relative;
    }

    .zen-tree-multi-select.disabled .ztms-text {
        background-color: #e8e8e8;
        color: #8f8f8f;
    }

    .zen-tree-multi-select .ztms-text {
        background-color: #fff;
        border-radius: 3px;
        border: 1px solid #d0d0d0;
        cursor: pointer;
        padding: 7px 15px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
    }

    .zen-tree-multi-select .ztms-text::after {
        content: '\f107';
        font-family: FontAwesome;
        font-size: 20px;
        color: #1e88e5;
        display: inline-block;
        margin-left: 5px;
        float: right;
    }

    .zen-tree-multi-select .ztms-title {
        display: block;
        font-size: 14px;
        font-family: OpenSansSemiBold;
        margin-bottom: 5px;
    }

    .zen-tree-multi-select .ztms-filter {
        display: inline-block;
        border: 1px solid #519bee;
        border-radius: 3px;
        margin-top: 6px;
        padding: 5px 7px;
        font-size: 15px;
        width: 100%;
        text-align: center;
    }

    .zen-tree-multi-select .ztms-list {
        position: absolute;
        background-color: #fff;
        width: inherit;
        padding: 10px;
        padding-top: 5px;
        margin-top: -2px;
        border: 1px solid #d0d0d0;
        border-top: none;
        z-index: 1;
    }

    .zen-tree-multi-select .ztms-options {
        max-height: 310px;
        overflow-y: auto;
        padding: 5px;
        margin-top: 15px;
    }

    .zen-tree-multi-select .ztms-item {
        font-size: 14px;
        margin-bottom: 3px;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 5px;
    }

    .zen-tree-multi-select .ztms-item.selected::after {
        content: "\f00c";
        font-family: FontAwesome;
        font-size: 16px;
        color: #2baa2f;
        display: inline-block;
        margin-left: 5px;
    }

    .zen-tree-multi-select .ztms-item:not(.ztms-country):hover {
        background: #c0e2ff;
    }

    .zen-tree-multi-select .ztms-item i {
        color: #e2412f;
        margin-right: 5px;
    }

    .zen-tree-multi-select .ztms-group {
        padding-left: 15px;
    }
    .zen-tree-multi-select .ztms-clean {
        font-size: 12px;
        color: #580303;
        background: #ffa7a8;
        padding: 5px;
        transition: 300ms;
        margin-top: 5px;
        text-align: center;
        border-radius: 5px;
        cursor: pointer;
        user-select: none;
    }
    .zen-tree-multi-select .ztms-clean:hover {
        background: #e12c2e;
        color: #fff;
    }
    .zen-tree-multi-select .ztms-clean:not(.active) {
        color: #a6a6a6;
        background: #f2f2f2;
    }
    .zen-tree-multi-select .ztms-country {
        background: #f2f2f2;
        border-radius: 0;
        cursor: auto;
    }
</style>
