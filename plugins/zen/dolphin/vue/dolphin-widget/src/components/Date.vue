<template>
    <div class="dw-date-picker">
    <div class="dw-input-title">{{ title }}</div>
    <date-picker
        :range="range"
        :editable="editable"
        :confirm="confirm"
        :confirm-text="confirmText"
        :multiple="multiple"
        :default-value="defaultPosition"
        @pick="pick"
        @change="change"
        :disabled-date="isDataAllow(date)"
        v-model="date"
        type="date"
        valueType="format"
        format="DD.MM.YYYY">
        <template v-if="mutation" slot="input">
            <div class="mx-input">{{ output }}</div>
        </template>
    </date-picker>
    </div>
</template>
<script>
    import DatePicker from 'vue2-datepicker'; // https://github.com/mengxiong10/vue2-datepicker
    import 'vue2-datepicker/locale/ru';
    export default {
        props: {
            title: {
                type: String
            },
            store: {
                type: String
            },
            allowedDates: {
                type: Array,
                default: null
            },
            range: {
                type: Boolean,
                default: false
            },
            editable: {
                type: Boolean,
                default: true
            },
            confirm: {
                type: Boolean,
                default: false
            },
            confirmText: {
                type: String,
                default: 'Применить'
            },
            multiple: {
                type: Boolean,
                default: false
            },
            mutation: {
                type: Function,
                default: null
            },
            defaultPosition: {

            }
        },
        components: {
            DatePicker
        },
        data(){
            return {
                output: 'Не выбрано'
            }
        },
        watch: {
            date(value) {
                this.change(value)
            }
        },
        computed: {
            date: {
                get() {
                    return this.$store.getters['get'+this.store]
                },
                set(value) {
                    this.$store.commit('set'+this.store, value)
                }
            }
        },
        methods: {
            isDataAllow(date)
            {
                if(!this.allowedDates) {
                    return ()=>false
                }

                return (date) => {
                    if(this.allowedDates.indexOf(this.formatedDate(date)) !=-1) {
                        return false
                    }

                    return true
                }
            },
            formatedDate(date) {
                var dd = date.getDate();
                if (dd < 10) dd = '0' + dd;
                var mm = date.getMonth() + 1;
                if (mm < 10) mm = '0' + mm;
                var yy = date.getFullYear();
                if (yy < 10) yy = '0' + yy;
                return dd + '.' + mm + '.' + yy;
            },
            pick(event)
            {
                this.$emit('pick', event)
            },
            change(value)
            {

                if(this.mutation) {
                    this.output = this.mutation(value)
                }

                this.$emit('change', value)
            }
        }
    }
</script>
<style>
    .mx-datepicker {
        width: 100%!important;
    }
    .mx-calendar-content .cell:not(.disabled):not(.active){
        background-color: #a5dafc;
    }
</style>
