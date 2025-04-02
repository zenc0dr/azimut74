<template>
    <div class="zen-prices" v-if="date">
        <div class="zen-prices-table">
            <div class="zen-prices-titles">
                <div class="zen-prices-title">Тип номера</div>
                <div class="zen-prices-title">Тип цены</div>
                <div class="zen-prices-title">Возраст</div>
                <div class="zen-prices-title">Цена</div>
                <div class="zen-prices-title plus">
                    <div class="cell-plus" @click="addRow(-1)">
                        +
                    </div>
                </div>
            </div>
            <div v-for="(record, i) in records" class="zen-prices-record">
                <div class="zen-prices-cell">
                    <select v-model="record.azroom_id">
                        <option v-for="(name, id) in azrooms" :value="id">{{ name }}</option>
                    </select>
                </div>
                <div class="zen-prices-cell">
                    <select v-model="record.pricetype_id">
                        <option v-for="(name, id) in pricetypes" :value="id">{{ name }}</option>
                    </select>
                </div>
                <div class="zen-prices-cell">
                    <input v-model="record.ages">
                </div>
                <div class="zen-prices-cell">
                    <input v-model="record.price" type="number">
                </div>
                <div class="zen-prices-cell plus">
                    <div class="cell-plus" @click="delRow(i)">
                        -
                    </div>
                </div>
                <div class="zen-prices-cell plus">

                    <div class="cell-plus" @click="addRow(i)">
                        +
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
<script>
    import ZenSelect from "./ZenSelect";
    export default {
        name: 'ZenPrices',
        props: ['date', 'prices', 'azrooms','pricetypes'],
        data(){
            return {
                records: [],
                saved_records: null,
            }
        },
        watch: {
            records: {
                handler(records){
                    this.$emit('update', {
                        date: this.date,
                        prices:records
                    })
                    this.saved_records = this.hardClone(records)
                },
                deep: true
            },
            date(date)
            {
                if(this.prices[date]) {
                    this.records = this.prices[date]
                } else {
                    if(this.saved_records) {
                        this.records = this.hardClone(this.saved_records)
                    } else {
                        this.records = [
                            {azroom_id:1,pricetype_id:1,ages:'',price:0},
                            {azroom_id:1,pricetype_id:2,ages:'',price:0},
                            {azroom_id:1,pricetype_id:3,ages:'',price:0},
                            {azroom_id:1,pricetype_id:4,ages:'',price:0},
                            {azroom_id:1,pricetype_id:5,ages:'',price:0},
                        ]
                    }
                }
            }
        },
        computed: {
            newDefaultLine()
            {
                return {azroom_id:1,pricetype_id:1,ages:'',price:0}
            }
        },
        methods: {
            addRow(after_index)
            {
                if(after_index == -1) {
                    this.records.unshift(this.newDefaultLine)
                    return
                }
                let item = this.hardClone(this.records[after_index])
                this.records.splice(after_index, 0, item);
            },
            delRow(index)
            {
                this.records.splice(index, 1);
            },
            hardClone(object)
            {
                return JSON.parse(JSON.stringify(object))
            }
        },
        components: {
            ZenSelect
        }
    }
</script>
