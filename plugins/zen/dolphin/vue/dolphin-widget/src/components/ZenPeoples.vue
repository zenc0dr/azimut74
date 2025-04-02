<template>
    <div class="zen-peoples">
        <div @click="open = !open" class="zen-peoples-title" :class="{open:open}">

            <span class="zen-peoples__h">Сколько туристов:</span>
            <span class="zen-peoples__info">
                Взрослых: {{ adults }}
                <template v-if="childrens"> +
                    Детей: {{ childrens }}
                    (
                    <template v-for="(i, k) in children_ages" v-if="i !== null">
                        {{ i }} лет
                        <template v-if="(childrens > 1) && !k"> и </template>
                    </template>
                    )
                </template>
            </span>
            <span class="zen-peoples__control">
                <template v-if="open">⯅</template>
                <template v-else>⯆</template>
            </span>
        </div>
        <div v-if="open" class="zen-peoples-controls">
            <div class="zen-peoples-h">
                Состав взрослых
            </div>
            <div class="zp-adult-selector">
                <div @click="selectAdult(i)" v-for="i in 4" class="zp-adult-button" :class="adultIsActive(i)" :disabled="adultIsDesibled(i)">
                    <img v-for="ii in i" :src="assets + '/images/people.svg'">
                </div>
            </div>

            <template v-if="adults < 4">
                <div class="zen-peoples-h">
                    1 ребёнок (возраст)
                </div>
                <div class="zp-children-selector">
                    <div>
                        <div @click="resetAge(0)" class="zp-children-button-title zpc-block" :class="(children_ages[0] !== null)?'active':''">
                            <img :src="assets + '/images/people.svg'">
                        </div>
                    </div>
                    <div>
                        <div @click="selectAge(0, i - 1)" v-for="i in maxChildrenAge + 1" class="zpc-block" :class="childrenAgeIsActive(0, i - 1)">
                            {{ i - 1 }}
                        </div>
                    </div>
                </div>
            </template>

            <template v-if="adults < 3">
                <div class="zen-peoples-h">
                    2 ребёнок (возраст)
                </div>
                <div class="zp-children-selector">
                    <div>
                        <div @click="resetAge(1)" class="zp-children-button-title zpc-block" :class="(children_ages[1] !== null)?'active':''">
                            <img :src="assets + '/images/people.svg'">
                        </div>
                    </div>
                    <div>
                        <div @click="selectAge(1, i - 1)" v-for="i in maxChildrenAge + 1" class="zpc-block" :class="childrenAgeIsActive(1, i - 1)">
                            {{ i - 1 }}
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>
</template>
<script>
    export default {
        name: 'ZenPeoples',
        props: {
            maxPeoples: {
                type: Number,
                default: 4
            },
            adultsMax: {
                type: Number,
                default: 4
            },
            adultsMin: {
                type: Number,
                default: 1
            },
            adultsStore: {
                type: String,
                default: null
            },
            childrensMax: {
                type: Number,
                default: 2
            },
            childrensMin: {
                type: Number,
                default: 0
            },
            childrensStore: {
                type: String,
                default: null
            },
            childrenAgesStore: {
                type: String,
                default: null
            },
            maxChildrenAge: {
                type: Number,
                default: 17
            },
            insertData: {
                type: Object,
                default: null
            }
        },

        data() {
            return {
                adults: 2,
                childrens: 0,
                open: false,
                children_ages: [null, null]
            }
        },
        computed: {
            assets()
            {
                return this.$store.getters.getSettings.assets
            },
        },
        watch: {
            adults(value) {
                this.$store.commit('set'+this.adultsStore, value)
            },
            childrens() {
                this.changeChildrens()
            },
            children_ages: {
                handler() {
                    this.changeChildrens()
                },
                deep: true
            },
            insertData(value)
            {
                if(!value) return
                this.adults = value.adults
                this.childrens = value.childrens.length
                this.children_ages = value.childrens
            }
        },
        methods: {
            changeChildrens() {
                this.$store.commit('set'+this.childrensStore, this.childrens)
                let ages = []
                for(let i in this.children_ages) {
                    if(this.children_ages[i] !== null) ages.push(this.children_ages[i])
                }
                this.$store.commit('set'+this.childrenAgesStore, ages)
            },
            adultIsActive(i) {
                if(i == this.adults) return 'active'
            },
            adultIsDesibled(i) {
                if(i + this.childrens > this.maxPeoples) return true
            },
            selectAdult(i)
            {
                if(i + this.childrens > this.maxPeoples) return
                this.adults = i

                if(this.children_ages[0] == null && this.children_ages[1] !== null) {
                    this.children_ages = JSON.parse(JSON.stringify([this.children_ages[1], null]))
                }
            },
            selectAge(k, i) {

                let ca = this.children_ages

                if(this.children_ages[k] == i) {
                    this.resetAge(k)
                    return
                }

                ca[k] = i
                this.children_ages = JSON.parse(JSON.stringify(ca))
                this.recountChildrens()
            },
            recountChildrens() {
                let childrens_count = 0
                for(let i in this.children_ages) {
                    if(this.children_ages[i] !== null) {
                        childrens_count++
                    }
                }
                this.childrens = childrens_count
            },
            resetAge(i) {
                let ca = this.children_ages
                ca[i] = null
                this.children_ages = JSON.parse(JSON.stringify(ca))
                this.recountChildrens()
            },
            childrenAgeIsActive(k, i) {
                if(this.children_ages[k] == i) return 'active'
            }
        }

    }
</script>
<style>
    /* zen-peoples */
    .zen-peoples {
        margin-top: 16px;
    }

    .zen-peoples-title {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 6px 13px;
        border: 1px solid #deeefc;
        border-radius: 5px;
        cursor: pointer;
    }

    .zen-peoples-title.open {
        border-bottom: none;
        border-radius: 5px 5px 0 0;
    }

    .zen-peoples-title .zen-peoples__info {
        background: #ffffffbd;
        padding: 5px 14px;
        border-radius: 4px;
    }

    .zen-peoples-title .zen-peoples__control {
        display: flex;
        color: #fff;
        margin-left: auto;
    }

    .zen-peoples-title .zen-peoples__h {
        margin-left: auto;
        margin-right: 10px;
    }

    .zen-peoples-controls {
        border: 1px solid #deeefc;
        border-radius: 0 0 5px 5px;
        border-top: none;
    }

    .zen-peoples-h {
        font-family: OpenSansBold, sans-serif;
        color: #fff;
        text-transform: uppercase;
        font-size: 15px;
        border-bottom: 2px solid #fff;
        margin: 0 15px;
        padding-bottom: 3px;
        margin-bottom: 10px;
    }

    .zp-adult-selector {
        display: flex;
        padding: 0 10px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .zp-adult-button {
        flex: 1 1 0;
        background-color: #8E8E8E;
        margin: 5px;
        text-align: center;
        cursor: pointer;
        border-radius: 5px;
        padding: 12px 5px;
        user-select: none;
    }

    .zp-adult-button.active {
        background-color: #F30000;
    }

    .zp-adult-button > img {
        height: 30px;
        margin: 0px 1px;
    }

    .zp-adult-button[disabled="disabled"] {
        opacity: 0.5;
    }

    .zp-children-selector {
        margin: 0px 15px;
        display: flex;
        margin-bottom: 15px;
    }

    .zp-children-selector > div:first-child {
    }

    .zp-children-button-title {
    }

    .zp-children-button-title img {
        height: 23px;
    }

    .zp-children-selector > div:last-child {
        display: flex;
        flex-wrap: wrap;
    }

    .zpc-block {
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

    .zpc-block.active {
        background-color: #F30000;
    }

    @media (max-width: 472px) {
        .zp-adult-button {
            min-width: 150px;
        }
    }
</style>
