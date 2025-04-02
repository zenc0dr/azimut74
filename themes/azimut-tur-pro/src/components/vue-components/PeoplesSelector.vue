<template>
    <div class="peoples-selector" @click.self="show_modal = true">
        {{ title }}
        <Modal title="Сколько туристов" :show="show_modal" @close="show_modal = false" :max-width="850">
            <div class="zen-peoples-controls">
                <div class="peoples-selector-h">
                    Состав взрослых
                </div>
                <div class="adult-selector">
                    <div v-for="i in adultsMax"
                        class="adult-button"
                        :class="{active: i === adults}"
                        @click="selectAdult(i)"
                        :disabled="i + childrens_count > maxPeoples"
                    >
                        <img v-for="ii in i" :src="assets + '/images/people.svg'">
                    </div>
                </div>
                <template v-for="children_index in childrensMax">
                    <template v-if="adults <  maxPeoples - children_index + 1">
                        <div class="peoples-selector-h">
                            {{ children_index }} ребёнок (возраст)
                        </div>
                        <div class="children-selector">
                            <div>
                                <div class="children-button-title small-block"
                                     @click="resetAge(children_index)"
                                     :class="{active: childrens[children_index - 1] !== undefined && childrens[children_index - 1] !== null}">
                                    <img :src="assets + '/images/people.svg'">
                                </div>
                            </div>
                            <div>
                                <div v-for="i in maxChildrenAge + 1"
                                     class="small-block"
                                     @click="selectAge(children_index, i)"
                                     :class="{active: childrenAgeIsActive(children_index, i)}">
                                    {{ i - 1 }}
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </Modal>
    </div>
</template>

<script>
import Modal from "./Modal";
export default {
    name: "PeoplesSelector",
    components: { Modal },
    props: {
        maxPeoples: {
            type: Number,
            default: 4
        },
        adults: {
            type: Number,
            default: 1
        },
        adultsMax: {
            type: Number,
            default: 4
        },
        adultsMin: {
            type: Number,
            default: 1
        },
        childrens: {
            type: Array,
            default: [],
        },
        childrensMax: {
            type: Number,
            default: 2
        },
        childrensMin: {
            type: Number,
            default: 0
        },
        maxChildrenAge: {
            type: Number,
            default: 17
        }
    },
    data() {
        return {
            show_modal: false,
            assets: '/themes/azimut-tur-pro/assets'
        }
    },
    computed: {
        childrens_count() {
            let count = 0
            for (let i in this.childrens) {
                if (this.childrens[i] !== null && this.childrens[i] !== undefined) {
                    count ++
                }
            }
            return count
        },
        title() {
            let childrens = ''
            if (this.childrens_count) {
                let ages = this.childrens.join(',')
                childrens = ' Дети: ' + this.childrens_count + ` (${ages})`
            }

            return 'Взрослых: ' + this.adults + childrens
        }
    },
    methods: {
        selectAdult(i)
        {
            if (i + this.childrens_count > this.maxPeoples) {
                return
            }

            //this.adults = i
            this.$emit('adults-change', i)

            // // Реверс значений возрастов
            // if (this.childrens[0] == null && this.childrens[1] !== null) {
            //     this.childrens = this.clone([this.childrens[1], null])
            // }
        },
        setChildrens(ages)
        {
            let output = []
            for (let i in ages) {
                if (ages[i] !== undefined && ages[i] !== null) {
                    output.push(ages[i])
                }
            }
            this.$emit('childrens-change', output)
        },
        resetAge(children_index) {
            let ages = this.clone(this.childrens)
            ages[children_index - 1] = null
            this.setChildrens(ages)
        },
        childrenAgeIsActive(children_index, i)
        {
            if (this.childrens[children_index - 1] === undefined) {
                return false
            }
            if (i - 1 === this.childrens[children_index - 1]) {
                return true
            }
        },
        clone(obj)
        {
            return JSON.parse(JSON.stringify(obj))
        },
        selectAge(children_index, i)
        {
            let ages = this.clone(this.childrens)
            ages[children_index - 1] = i - 1
            this.setChildrens(ages)
        }
    }
}
</script>

<style lang="scss">
.peoples-selector {
    background: #fff;
    padding: 8px 11px;
    border-radius: 3px;
    border: 1px solid #d0d0d0;
    width: 100%;
    font-size: 14px;
    white-space: nowrap;
    .zen-peoples-controls {
        border: 1px solid #deeefc;
        border-radius: 0 0 5px 5px;
        border-top: none;
    }
    .peoples-selector-h {
        font-family: OpenSansBold, sans-serif;
        color: #000;
        text-transform: uppercase;
        font-size: 15px;
        border-bottom: 2px solid #fff;
        margin: 0 15px;
        padding-bottom: 3px;
        margin-bottom: 10px;
    }
    .adult-selector {
        display: flex;
        padding: 0 10px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .adult-button {
        flex: 1 1 0;
        background-color: #8E8E8E;
        margin: 5px;
        text-align: center;
        cursor: pointer;
        border-radius: 5px;
        padding: 12px 5px;
        user-select: none;
    }
    .adult-button.active {
        background-color: #F30000;
    }
    .adult-button > img {
        height: 30px;
        margin: 0 1px;
    }
    .adult-button[disabled="disabled"] {
        opacity: 0.5;
    }
    .children-selector {
        margin: 0 15px;
        display: flex;
        margin-bottom: 15px;
    }
    .children-button-title img {
        height: 23px;
    }
    .children-selector > div:last-child {
        display: flex;
        flex-wrap: wrap;
    }
    .small-block {
        width: 36px;
        height: 36px;
        background-color: #8e8e8e;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 5px;
        margin-bottom: 5px;
        border-radius: 3px;
        cursor: pointer;
        color: #fff;
        font-family: OpenSansBold, sans-serif;
        font-size: 16px;
        user-select: none;
    }

    .small-block.active {
        background-color: #F30000;
    }

    @media (max-width: 472px) {
        .adult-button {
            min-width: 150px;
        }
    }
}
</style>
