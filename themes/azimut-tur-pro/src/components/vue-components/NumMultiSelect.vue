<template>
    <div class="zen-num-multi-select" v-click-outside="closeList">
        <div class="znms-text" @click="openList">{{ show }}</div>
        <div class="znms-list" v-if="show_list">
            <div class="znms-clean"
                 @click="clean"
                 :class="{ active:value.length }">
                Снять выделение
            </div>
            <div @click="check(option.value)"
                 v-for="option in options"
                 class="znms-option">
                {{ option.value }} <i :class="checkIcon(option.checked)"></i>
            </div>
        </div>
    </div>
</template>
<script>
import ClickOutside from "./directives/ClickOutside";

export default {
    name: 'NumMultiSelect',
    props: {
        title: {
            Type: String,
            default: 'Не важно'
        },
        value: {
            Type: Array,
            default: [], // Массив отмеченных чисел ex: [1, 3, 5]
        },
        min: Number,
        max: Number,
        limit: {
            Type: Number,
            default: 3
        },
    },
    directives: {
        'click-outside': ClickOutside
    },
    data() {
        return {
            show_list: false,
        }
    },
    watch: {

    },
    computed: {
        show() {
            let value = this.value
            if (value.length < 4) {
                if (!value.length) {
                    return this.title
                }
                return value.join(', ')
            } else {
                let min = value[0]
                let max = min
                for (let i = 1; i < value.length; ++i) {
                    if (value[i] > max) max = value[i];
                    if (value[i] < min) min = value[i];
                }
                return min + ' ~ ' + max
            }
        },
        options() {
            let values = [];

            for (let i = this.min; i <= this.max; i++) {
                i = parseInt(i)
                values.push({value: i, checked: this.value.includes(i)})
            }
            return values
        },
    },
    methods: {
        checkIcon(value) {
            if (value !== false) {
                return 'fa fa-check-square checked'
            } else {
                return 'fa fa-square'
            }
        },
        openList() {
            this.show_list = !this.show_list
        },
        closeList() {
            this.show_list = false
        },
        check(num)
        {
            num = parseInt(num)
            let value = this.value
            let index = value.indexOf(num)

            if (index === -1) {
                if (value.length < this.limit) {
                    value.push(num)
                    value.sort(function (a, b) {
                        return a - b
                    })
                }
            } else {
                value.splice(index, 1)
            }
            this.$emit('change', value)
        },
        clean()
        {
            this.$emit('change', [])
        }
    }
}
</script>
<style>
/* NumMultiSelect */
.zen-num-multi-select {
    display: inline-block;
    width: 100%;
    position: relative;
}

.znms-body {
    border: 1px solid #d0d0d0;
    background: #fff;
    border-radius: 3px;
    display: flex;
    justify-content: center;
    height: 34px;
    width: inherit;
}

.znms-text {
    background-color: #fff;
    border-radius: 3px;
    border: 1px solid #d0d0d0;
    cursor: pointer;
    padding: 8px 11px;
    font-size: 14px;
    display: flex;
    justify-content: center;
    white-space: nowrap;
    user-select: none;
}

.znms-list {
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

.znms-clean {
    font-size: 12px;
    color: #580303;
    background: #ffa7a8;
    padding: 5px;
    transition: 300ms;
    margin-bottom: 5px;
    text-align: center;
    border-radius: 5px;
    cursor: pointer;
    user-select: none;
}
.znms-clean:not(.active) {
    color: #a6a6a6;
    background: #f2f2f2;
}

.znms-option {
    display: inline-block;
    width: 100%;
    text-align: right;
    padding-right: 4px;
    cursor: pointer;
    user-select: none;
}

.znms-option i {
    color: #80bff3;
    transition: 300ms;
}

.znms-option i.checked {
    color: #e2412f;
}

.znms-option.limit i {
    color: #dbdbdb;
}
</style>
