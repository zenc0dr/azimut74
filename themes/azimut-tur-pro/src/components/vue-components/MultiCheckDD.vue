<template>
    <div class="multi-check-dd">
        <template v-if="selected && selected.length">
            <div v-if="!fixed" class="multi-check-dd__dropdown__clear">
                <i @click.self="selected = []" title="Очистить" class="bi bi-x"></i>
            </div>
        </template>
        <div @click="openDropdown"
             class="w-form-input d-flex align-items-center justify-content-between bg-primary-100 px-3 py-2 rounded w-100">
            <span class="c-gray-300 fs-s fs-sm-def fs-lg-s fs-xxl-def">{{ title }}</span>
            <div v-if="!fixed" class="w-form-input-suffix c-blue-200 ps-1 ps-sm-3">
                <i :class="`bi bi-chevron-${dropdown ? 'up' : 'down'}`"></i>
            </div>
        </div>
        <div class="multi-check-dd__dropdown_wrap" v-if="dropdown" v-click-outside="closeDropdown">
            <div class="multi-check-dd__filter">
                <input v-model="filter">
            </div>
            <div class="multi-check-dd__dropdown">
                <div class="multi-check-dd__dropdown__item"
                     v-for="option in options"
                     v-if="filtered(option)"
                     @click="check(option)"
                >
                    <span>{{ option.name }}</span>
                    <i v-if="isChecked(option)" class="bi bi-check"></i>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ClickOutside from "../search-widget/directives/ClickOutside"

export default {
    name: "MultiCheckDD",
    directives: {
        'click-outside': ClickOutside
    },
    props: {
        value: {
            type: Array,
            default: null
        },
        options: {
            type: Array,
            default: null
        },
        sex: {
            type: Boolean,
            default: true // true = male, false = female
        },
        filterEnabled: {
            type: Boolean,
            default: false
        },
        fixed: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        selected: {
            get() {
                let value = this.value
                if (!Array.isArray(this.value)) {
                    value = [value]
                }
                return value
            },
            set(value) {
                this.$emit('change', value)
            }
        },
        title() {
            if (!this.selected || !this.selected.length) {
                return this.sex ? 'Любой' : 'Любая'
            }

            if (this.selected.length === 1) {
                for (let i in this.options) {
                    if (parseInt(this.options[i].id) === this.selected[0]) {
                        return this.options[i].name
                    }
                }
            }

            return 'Выбрано: ' + this.selected.length
        }
    },
    data() {
        return {
            dropdown: 0,
            filter: null,
        }
    },
    methods: {
        openDropdown() {
            if (this.fixed) {
                return
            }
            if (!this.options.length) {
                return
            }
            if (this.dropdown > 0) {
                this.dropdown = 0
            } else {
                this.dropdown = 2
            }
        },
        closeDropdown() {
            if (this.dropdown > 0) {
                this.dropdown--
            }
        },
        check(option) {
            let id = parseInt(option.id)
            if (!this.isChecked(option)) {
                this.selected.push(id)
            } else {
                this.selected.splice(this.selected.indexOf(id), 1)
            }
        },
        isChecked(option) {
            return this.selected.includes(parseInt(option.id))
        },
        filtered(option) {
            if (!this.filter) {
                return true
            }
            return option.name.toLowerCase().indexOf(this.filter.toLowerCase()) !== -1
        }
    }
}
</script>
<style lang="scss">
    @import 'MultiCheckDD';
</style>
