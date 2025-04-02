<template>
    <div class="zen-date-picker">
        <date-picker
            v-model="date_value"
            type="date"
            valueType="format"
            format="DD.MM.YYYY"
            :disabled-date="isDataAllow(date)"
            lang="ru"
            :confirm="false"
            @change="change"
        >
        </date-picker>
    </div>
</template>
<script>
import DatePicker from 'vue2-datepicker'; // https://github.com/mengxiong10/vue2-datepicker
import 'vue2-datepicker/locale/ru';

export default {
    components: { DatePicker },
    props: {
        date: null, // Format d.m.Y
        allowedDates: {
            type: Array,
            default: null
        },
    },
    mounted() {
        this.date_value = this.date
    },
    data() {
        return {
            date_value: null,
        }
    },
    watch: {
        date(date) {
            this.date_value = date
        }
    },
    methods: {
        change(date)
        {
            this.$emit('change', date)
        },
        isDataAllow(date)
        {
            if(!this.allowedDates) {
                return ()=>false
            }

            return (date) => {
                if (this.allowedDates.indexOf(this.formattedDate(date)) !== -1) {
                    return false
                }
                return true
            }
        },
        formattedDate(date) {
            let dd = date.getDate()
            if (dd < 10) {
                dd = '0' + dd
            }
            let mm = date.getMonth() + 1;
            if (mm < 10) {
                mm = '0' + mm
            }
            return dd + '.' + mm + '.' + date.getFullYear()
        },
    }
};
</script>
<style>
    .mx-datepicker {
        width: 100%!important;
    }
    .mx-calendar-content .cell.active {
        font-weight: bold;
    }
    .mx-calendar-content .cell:not(.disabled):not(.active){
        background-color: #64b7ff;
        color: #ffffff;
        font-weight: bold;
    }
</style>
