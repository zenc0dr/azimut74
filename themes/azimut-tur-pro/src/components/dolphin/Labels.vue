<template>
    <div v-if="labels" class="labels">
        <div class="label" v-for="label in labels"
             :class="{ checked:isChecked(label) }"
             @click="check(label)">
            {{ label.name }}
        </div>
    </div>
</template>

<script>
export default {
    name: "Labels",
    props: {
        labels: {
            type: Array,
            default: null
        },
        allow_labels: {
            type: Array,
            default: null,
        }
    },
    computed: {
        selected: {
            get() {
                return this.allow_labels
            },
            set(selected) {
                this.$emit('change', selected)
            }
        }
    },
    methods: {
        isChecked(label)
        {
            return this.selected.includes(label.id)
        },
        check(label) {
            let index = this.selected.indexOf(label.id)
            if (index === -1) {
                this.selected.push(label.id)
            } else {
                this.selected.splice(index, 1)
            }
        }
    }
}
</script>
<style lang="scss" scoped>
.labels {
    background-color: #d7eafa;
    display: flex;
    flex-wrap: wrap;
    padding: 5px;
    border-radius: 5px;
    margin-bottom: 5px;
    .label {
        background-color: #fff;
        margin: 3px 3px;
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        &.checked {
            background-color: #e2422f;
            color: #fff;
        }
    }
}
</style>
