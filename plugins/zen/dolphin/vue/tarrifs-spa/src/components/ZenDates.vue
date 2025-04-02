<template>
    <div class="zen-dates">
        <div class="zen-dates-control" @click="slideMonth(0)">
            <
        </div>
        <div v-if="start_year && (ca = calendar(i))" v-for="i in calendars_count" class="zen-dates-calendar">
            <div class="calendar-date">{{ ca.month }} - {{ ca.date }}</div>
            <div v-for="n in day_names" class="calendar-day-name">{{ n }}</div>
            <div class="calendar-day-empty" v-for="eday in emptyDays(ca.date)">

            </div>
            <div v-for="day in ca.days"
                 class="calendar-day"
                 :date="getDate(day, ca.date)"
                 @click="clickDay"
                 :class="{active:isActive(day, ca.date), loaded:isLoaded(day, ca.date), selected:isSelect(day, ca.date)}">
               {{ day }}
            </div>
        </div>
        <div class="zen-dates-control"  @click="slideMonth(1)">
            >
        </div>
    </div>
</template>
<script>
    import * as moment from "moment/moment";

    export default {
        name: 'ZenDates',
        props: {
            value: {
                type: Array,
                default: []
            },
            prices: {
                type: Object
            },
            selectDate: {
                type: String
            }
        },
        data()
        {
            return {
                day_names: ['пн','вт','ср','чт','пт','сб','вс'],
                calendars_count: 4,
                start_month: null,
                start_year: null,
                offset: 0,
            }
        },
        mounted()
        {
            this.adaptiveWidth()

            window.addEventListener(`resize`, event => {
                this.adaptiveWidth()
            }, false);

            let now = new Date()
            this.start_month = now.getMonth()
            this.start_year = now.getFullYear()
        },
        computed: {

        },
        methods: {
            adaptiveWidth(){
                let width = document.getElementsByClassName('zen-modal-body')[0].offsetWidth
                this.calendars_count = Math.floor(width / 300)
            },
            isLoaded(day, date){
                date = this.getDate(day, date)
                for(let i in this.prices) {
                    if(i == date) return true
                }
            },
            isActive(day, date)
            {
                if(this.value.indexOf(this.getDate(day, date)) !== -1) return true
            },
            isSelect(day, date)
            {
                return this.selectDate == this.getDate(day, date)
            },
            getDate(day, date)
            {
                if(day < 10) day = '0'+day
                date = day+'.'+date
                return date
            },
            emptyDays(date)
            {
                let day_num = moment('01.'+date, "DD.MM.YYYY").day()
                if(!day_num) day_num = 7
                return day_num - 1
            },
            calendar_date(num)
            {
                let x = Math.floor(num / 12)
                let r = num - (x * 12)
                let month_names = {
                    1: 'Январь',
                    2: 'Февраль',
                    3: 'Март',
                    4: 'Апрель',
                    5: 'Май',
                    6: 'Июнь',
                    7: 'Июль',
                    8: 'Август',
                    9: 'Сентябрь',
                    10: 'Октябрь',
                    11: 'Ноябрь',
                    12: 'Декабрь',
                }

                if(r < 1) return {
                    date: '01.' + (this.start_year + x),
                    month: month_names[1]
                }

                let m = r + 1
                let mm = m

                if(m < 10) m = '0' + m

                return {
                    date: m + '.' + (this.start_year + x),
                    month: month_names[mm]
                }
            },
            calendar(num)
            {
                num--

                let my = this.calendar_date(this.start_month + this.offset + num) // 7.2020, 8.2020 ...

                return {
                    date: my.date,
                    days: moment(my.date, "M.YYYY").daysInMonth(),
                    month: my.month
                }
            },
            dow(day, date)
            {
                if(day < 10) day = '0' + day
                let day_num = moment(day+'.'+date, "DD.MM.YYYY").day()
                if(!day_num) day_num = 7
                return day_num
            },
            slideMonth(slideRight)
            {
                if(slideRight) {
                    this.offset++
                } else {
                    this.offset--
                }
            },
            clickDay(event)
            {
                let date = event.target.getAttribute('date')
                let day_key = this.value.indexOf(date)
                if(day_key == -1) {
                    this.value.push(date)
                } else {
                    this.value.splice(day_key, 1)
                }

                this.$emit('change', this.value)
            }
        }
    }
</script>
