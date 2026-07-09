<template>
    <div class="zen-date-picker">
        <date-picker
            v-model="date_value"
            type="date"
            valueType="format"
            format="DD.MM.YYYY"
            :disabled-date="isDateDisabled"
            :disabled-calendar-changer="isCalendarChangerDisabled"
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
        startOfToday() {
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            return today
        },
        startOfCurrentMonth() {
            const today = this.startOfToday()
            return new Date(today.getFullYear(), today.getMonth(), 1)
        },
        // true = дата заблокирована
        isDateDisabled(date) {
            if (date.getTime() < this.startOfToday().getTime()) {
                return true
            }
            if (!this.allowedDates) {
                return false
            }
            return this.allowedDates.indexOf(this.formattedDate(date)) === -1
        },
        // true = кнопка навигации заблокирована
        isCalendarChangerDisabled(date, type) {
            if (type !== 'last-month' && type !== 'last-year') {
                return false
            }
            return date.getTime() < this.startOfCurrentMonth().getTime()
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
