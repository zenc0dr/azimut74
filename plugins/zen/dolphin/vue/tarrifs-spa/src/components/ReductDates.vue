<template>
    <div class="reduct-dates-blocks">
        <div :class="{selected:isSelected(date)}" @click="selectDate(date)" v-for="date in dates">{{ date }}</div>
    </div>
</template>
<script>
    export default {
        name: 'ReductDates',
        props: {
            dates: {
                type: Array
            },
            reductDates: {
                type: Array,
                default: []
            }
        },
        // watch: {
        //     reductDates(value) {
        //         console.log(value)
        //     }
        // },
        methods: {
            selectDate(date)
            {
                this.$emit('selectDate', date)
            },
            isSelected(date) {

                //console.log(date, this.reduct_dates)

                if(!this.reductDates) return
                if(this.reductDates.indexOf(date) !== -1) return true
            }
        }
    }
</script>
