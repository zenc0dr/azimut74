<template>
    <div class="ocv-list">
        <div v-if="records.length" class="ocv-records">
            <div class="ocv-titles">
                <div class="ch ocv-cell-ch">
                    <input :checked="records.length === checked.length" type="checkbox" @click.stop="checkAll()">
                </div>
                <div class="ocv-cell-1">id</div>
                <div class="ocv-cell-2">Название тарифа</div>
                <div class="ocv-cell-3">Гостиница</div>
                <div class="ocv-cell-4">Название номера</div>
                <div class="ocv-cell-5">Комфортность номера</div>
                <div class="ocv-cell-6">Тип питания</div>
                <div class="ocv-cell-7">Туроператор</div>
            </div>
            <div v-for="record in records" class="ocv-record" @click="onRecordClick(record)">
                <div class="ch ocv-cell-ch">
                    <input :checked="isChecked(record)" type="checkbox" @click.stop="checkItem(record)">
                </div>
                <div class="ocv-cell-1">
                    {{ record.id }}
                </div>
                <div class="ocv-cell-2">
                    {{ record.name }}
                </div>
                <div class="ocv-cell-3">
                    {{ record.hotel }}
                </div>
                <div class="ocv-cell-4">
                    {{ record.number }}
                </div>
                <div class="ocv-cell-5">
                    {{ record.comfort }}
                </div>
                <div class="ocv-cell-6">
                    {{ record.pansion }}
                </div>
                <div class="ocv-cell-7">
                    {{ record.operator }}
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'OcvList',
        props: ['records'],
        data() {
            return {
                checked: []
            }
        },
        watch: {
            checked(value) {
                this.$emit('onChecked', value)
            }
        },
        methods: {
            isChecked(item)
            {
                let item_id = parseInt(item.id)
                if(this.checked.indexOf(item_id) !== -1) return true
            },
            checkItem(item)
            {
                let item_id = parseInt(item.id)
                if(this.checked.indexOf(item_id) === -1) {
                    this.checked.push(item_id)
                } else {
                    this.checked.splice(this.checked.indexOf(item_id), 1)
                }
            },
            checkAll()
            {
                this.checked = []
                for(let i in this.records) {
                    let item_id = parseInt(this.records[i].id)
                    this.checked.push(item_id)
                }
            },
            onRecordClick(record)
            {
                this.$emit('recordClick', record.id)
            }
        }
    }
</script>
