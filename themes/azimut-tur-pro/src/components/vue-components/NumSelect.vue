<template>
    <div class="dw-num-select">
        <!--<div class="dw-input-title">{{ title }}</div>-->
        <div class="dwns-body">
            <i @click="num--" class="fa fa-chevron-left" aria-hidden="true"></i>
            <div class="dwns-number">{{ display }}</div>
            <i @click="num++" class="fa fa-chevron-right" aria-hidden="true"></i>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            value: Number,
            title: String,
            min: Number,
            max: Number
        },
        computed: {
            display() {
                return this.num ? this.num : this.title
            },
            num: {
                get()
                {
                    return this.value
                },
                set(value)
                {
                    if (value < this.min || value > this.max) {
                        return
                    }
                    this.$emit('change', value)
                }
            }
        }
    }
</script>
<style>
    .dw-num-select {
        width: 80px;
        user-select: none;
    }
    .dw-num-select .dwns-body {
        display: flex;
        justify-content: space-between;
        border: 1px solid #d0d0d0;
        background: #fff;
        border-radius: 3px;
        overflow: hidden;
    }
    .dw-num-select .dwns-body i {
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f6f6f6;
        color: #b2b2b2;
        font-size: 11px;
        height: 32px;
        width: 20px;
        cursor: pointer;
    }
    .dw-num-select .dwns-body i:hover {
        background-color: #e0ebf4;
        color: #000;
    }
    .dw-num-select .dwns-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 32px;
    }
</style>
