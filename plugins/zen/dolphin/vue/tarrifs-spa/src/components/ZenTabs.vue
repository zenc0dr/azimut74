<template>
    <div class="zen-tabs" v-if="value.length">
        <div v-for="date in value"
             class="zen-tab"
             :class="{active:(date == selected), filled:(prices[date] !== undefined)}"
             @click="selectDate(date)"
        >
            {{ date }}
        </div>
    </div>
</template>
<script>
    export default {
        name: 'ZenTabs',
        props: {
            value: {
                type: Array,
                default: []
            },
            prices: {
                type: Object
            }
        },
        data()
        {
            return {
                selected: null
            }
        },
        methods: {
            selectDate(date)
            {
                this.selected = date
                this.$emit('select', date)
            }
        }
    }
</script>
