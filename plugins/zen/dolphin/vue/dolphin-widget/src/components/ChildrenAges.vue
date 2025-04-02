<template>
    <div v-if="childrens" class="dw-childrens-ages">
        <div class="dw-input-title">Возраст детей</div>
        <div class="dwca-inputs">
            <input v-for="(item, index) in childrens" type="number" v-model.numder="cells[index]" @change="save"/>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            childrensCount: {
                type: String,
                required: true
            },
            store: {
                type: String,
                required: true
            },
            min:{
                default: 1,
            },
            max: {
                default: 17,
            }
        },
        data(){
            return {
                first_run: false,
                cells: [],
            }
        },
        watch: {
            childrens(value)
            {
                // При первом запуске проверяется наличие уже загруженных данных о возрастах детей
                if(!this.first_run) {
                    this.cells = this.$store.getters.getExtChildrenAges
                }

                if(value < this.cells.length) {
                    this.cells.splice(value, this.cells.length - value)
                } else {
                    let i = 0
                    while(i < value) {
                        if(this.cells[i] == undefined) {
                            this.cells.push(this.min)
                        }
                        i++
                    }
                }
            },
            cells(value)
            {
                this.$store.commit('set'+this.store, value)
            }
        },
        computed: {
            childrens()
            {
                return parseInt(this.$store.getters['get'+this.childrensCount])
            },
        },
        methods: {
            rangs(value)
            {
                value = parseInt(value)
                let min = parseInt(this.min)
                let max = parseInt(this.max)
                if(value < min) value = min
                if(value > max) value = max
                return value
            },
            save()
            {
                let changed = false
                let cells = this.cells.map((item) => {
                    let original = item
                    item = this.rangs(item)
                    if(original != item) changed = true
                    return item
                })
                if(changed) this.cells = cells
            }
        }
    }
</script>
<style>
    .dwca-inputs input {
        border: none;
        width: 51px;
        height: 34px;
        font-size: 18px;
        text-align: center;
        border: 1px solid #d0d0d0;
        border-radius: 3px;
    }
    .dwca-inputs input:not(:first-child) {
        margin-left: 4px;
    }
</style>
