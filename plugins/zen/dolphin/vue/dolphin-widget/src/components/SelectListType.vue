<template>
    <div class="dw-select-list-type">
        <div>
            <div class="lt-btn" :class="{active:(type == 'schedule')}" @click="selectListType('schedule')">
                <i class="fa fa-calendar"></i> Расписание
            </div>
            <div class="lt-btn" :class="{active:(type == 'catalog')}" @click="selectListType('catalog')">
                <i class="fa fa-list"></i> Каталог
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'SelectListType',
        props: ['process', 'store'],
        computed: {
            type() {
                return this.$store.getters['get'+this.store]
            }
        },
        methods: {
            selectListType(type)
            {
                if(this.type == type) return
                if(this.process) return
                this.$store.commit('set'+this.store, type)
                this.$emit('change')
            }
        }
    }
</script>
<style>
    /* SelectListType */
    .dw-select-list-type {
        display: flex;
        width: 100%;
        justify-content: flex-end;
        padding: 3px;
        background: #80bff3;
        color: #fff;
        margin-top: 6px;
        margin-bottom: 10px;
    }

    .dw-select-list-type > div {
        display: flex;
    }

    .dw-select-list-type .lt-btn {
        padding: 5px;
        margin: 3px;
        cursor: pointer;
        border-radius: 3px;
    }

    .dw-select-list-type .lt-btn:hover {
        background: #62aae6;
    }

    .dw-select-list-type .lt-btn i {
    }

    .dw-select-list-type .lt-btn.active {
        background: #fff;
        color: #62aae6;
    }
</style>
