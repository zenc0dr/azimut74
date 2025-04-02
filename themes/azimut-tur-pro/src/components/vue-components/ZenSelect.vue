<template>
    <div class="zen-select" v-click-outside="clickOutside">
        <div class="title" @click.self="dropdown = !dropdown">
            {{ title }}
        </div>
        <div v-if="dropdown" class="dropdown">
            <div class="options">
                <div v-for="option in options"
                     @click="selectItem(option)"
                     class="option">
                    {{ option }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ClickOutside from "./directives/ClickOutside";
export default {
    name: "ZenSelect",
    props: {
        value: {
            Type: String,
            default: null
        },
        options: {
            Type: Array,
            default: []
        }
    },
    directives: {
        'click-outside': ClickOutside
    },
    data() {
        return {
            dropdown: false,
        }
    },
    computed: {
        title() {
            if (!this.options) {
                return ' -- '
            }
            let selected = this.options.find(item => {
                return this.value === item
            })
            return selected ?? ' -- '
        }
    },
    methods: {
        selectItem(item) {
            this.dropdown = false
            this.$emit('change', item)
        },
        clickOutside() {
            this.dropdown = false
        }
    }
}
</script>

<style lang="scss" scoped>
.zen-select {
    display: inline-block;
    width: 100%;
    position: relative;
    .title {
        background-color: #fff;
        border-radius: 3px;
        border: 1px solid #d0d0d0;
        cursor: pointer;
        padding: 5px 11px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        &::after {
            content: '\f107';
            font-family: FontAwesome;
            font-size: 20px;
            color: #1e88e5;
            display: inline-block;
            margin-left: 5px;
            float: right;
        }
    }
    .dropdown {
        position: absolute;
        background-color: #fff;
        width: inherit;
        padding: 10px;
        padding-top: 5px;
        margin-top: -2px;
        border: 1px solid #d0d0d0;
        border-top: none;
        z-index: 1;
        .options {
            max-height: 310px;
            overflow-y: auto;
            padding: 5px 0;
            .option {
                font-size: 15px;
                cursor: pointer;
                padding: 3px 8px;
                &:hover {
                    background: #1a81db;
                    color: #fff;
                }
            }
        }
    }
}
</style>
