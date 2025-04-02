<template>
    <div class="multi-select" :class="{disabled:!options.length}">
        <div class="dw-input-title">{{ title }}</div>
        <div class="multi-select-text" @click="openDropdown">{{ selectedName }}</div>

        <div v-if="dropdown" class="multi-select-dropdown-wrap" v-click-outside="closeDropdown">
            <input class="multi-select-filter" v-model="filter" placeholder="Начните вводить название...">
            <div @click="cleanSelected" :class="{active:cleanAllowed}" class="multi-select-clean">Снять выделение</div>
            <div class="multi-select-options">
                <div v-for="option in options"
                     v-if="!filter || option.name.toLowerCase().indexOf(filter.toLowerCase()) != -1"
                     class="multi-select-option"
                     @click="selectOption(option)">
                    <div><i :class="checkIcon(isChecked(option))"></i></div>
                    <div>{{ option.name }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import ClickOutside from "../directives/ClickOutside";
    export default {
        name: 'MultiSelect',
        props: {
            title: {
                type: String
            },
            storeOptions: {
                type: String,
            },
            store: {
                type: String
            }
        },
        directives: {
            'click-outside': ClickOutside
        },
        data() {
            return {
                output: [],
                filter: null,
                dropdown: 0,
            }
        },
        computed: {
            selectedName() {
                if(!this.selected || this.selected.length == 0) return 'Не важно'
                if(this.selected.length == 1) return this.getSingleName
                return 'Выбрано: '+this.selected.length
            },
            cleanAllowed() {
                if(this.selected === null) return false
                if(this.selected.length > 0) return true
            },
            selected: {
                get() { return this.$store.getters['get'+this.store] },
                set(value) { this.$store.commit('set'+this.store, value) }
            },
            options() { return this.$store.getters['get'+this.storeOptions] },
            getSingleName() {
                let id = this.selected[0]
                for(let i in this.options) {
                    if(this.options[i].id == id) return this.options[i].name
                }

                // Очистить выбранное
                this.$store.commit('set'+this.store, [])
                return 'Не важно'
            },
        },
        methods: {
            selectOption(option)
            {
                if(this.selected === null) this.selected = []

                let num = parseInt(option.id)
                let index = this.selected.indexOf(num)
                if(index == -1) {
                    this.selected.push(num)
                } else {
                    this.selected.splice(index, 1)
                }
            },
            isChecked(option)
            {
                if(!this.selected) return false
                if(this.selected.indexOf(option.id) !== -1) return true
                return false
            },
            openDropdown() {
                if(!this.options.length) return
                if(this.dropdown > 0) {
                    this.dropdown = 0;
                } else {
                    this.dropdown = 2;
                }
            },
            closeDropdown() {
                if(this.dropdown > 0) {
                    this.dropdown--
                    if(!this.dropdown) {
                        this.$emit('close', JSON.parse(JSON.stringify(this.output)))
                    }
                }
            },
            cleanSelected() {
                this.selected = []
            },
            checkIcon(value)
            {
                if(value !== false) {
                    return 'fa fa-check-square checked'
                } else {
                    return 'fa fa-square'
                }
            },
        },
    }
</script>
<style>
    /* MultiSelect */
    .multi-select {
        display: inline-block;
        width: 100%;
        position: relative;
    }

    .multi-select.disabled .multi-select-text{
        background-color: #e8e8e8;
        color: #8f8f8f;
    }

    .multi-select .multi-select-text {
        background-color: #fff;
        border-radius: 3px;
        border: 1px solid #d0d0d0;
        cursor: pointer;
        padding: 7px 15px;
        font-size: 14px;
        user-select: none;
        display: flex;
        justify-content: space-between;
    }

    .multi-select .multi-select-text::after {
        content: '\f107';
        font-family: FontAwesome;
        font-size: 20px;
        color: #1e88e5;
        display: inline-block;
        margin-left: 5px;
        float: right;
    }

    .multi-select .multi-select-dropdown-wrap {
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

    .multi-select .multi-select-filter {
        display: inline-block;
        border: 1px solid #519bee;
        border-radius: 3px;
        margin-top: 6px;
        padding: 5px 7px;
        font-size: 15px;
        width: 100%;
        text-align: center;
    }

    .multi-select .multi-select-clean {
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

    .multi-select .multi-select-clean:hover {
        background: #e12c2e;
        color: #fff;
    }

    .multi-select .multi-select-clean:not(.active) {
        color: #a6a6a6;
        background: #f2f2f2;
    }

    .multi-select .multi-select-options {
        max-height: 310px;
        overflow-y: auto;
        margin-top: 15px;
    }

    .multi-select .multi-select-option {
        background: #eee;
        padding: 5px 7px;
        margin-bottom: 5px;
        cursor: pointer;
        user-select: none;
        display: flex;
    }

    .multi-select .multi-select-option > div:first-child {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
    }

    .multi-select .multi-select-option > div:last-child {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3px 6px;
    }

    .multi-select .multi-select-option i {
        color: #75addb;
    }
</style>
