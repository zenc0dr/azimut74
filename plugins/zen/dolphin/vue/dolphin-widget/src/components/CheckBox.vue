<template>
    <div class="dw-checkbox" @click="changeStatus">
        <div class="dw-checkbox-box">
            <i :class="checkIcon(status)"></i>
            <div>{{ title }}</div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'CheckBox',
        props: ['title', 'store'],
        computed: {
            status: {
                get()
                {
                    return this.$store.getters['get'+this.store]
                },
                set(value)
                {
                    this.$store.commit('set'+this.store, value)
                }
            }
        },
        methods: {
            checkIcon(value)
            {
                if(value !== false) {
                    return 'fa fa-check-square checked'
                } else {
                    return 'fa fa-square'
                }
            },
            changeStatus()
            {
                this.status = !this.status
            }
        }
    }
</script>
<style>
    .dw-checkbox {
        cursor: pointer;
        user-select: none;
    }

    .dw-checkbox-box {
        display: flex;
        background: #ffffff;
        padding: 8px 15px;
        margin: 6px 8px;
        border-radius: 9px;
        color: #c9c9c9;
    }

    .dw-checkbox-box > i {
        margin-top: 2px;
        font-size: 20px;
    }

    .dw-checkbox-box > i.checked {
        color: #e2422f;
    }

    .dw-checkbox-box > div {
        margin-left: 10px;
        color: #000;
    }
</style>
