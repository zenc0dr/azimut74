<template>
    <div class="list-type-selector">
        <div class="list-type-selector__button"
             :class="{active: list_type === 'schedule'}"
             @click="changeListType('schedule')"
        >
            <i class="fa fa-th-list"></i> Каталог
        </div>
        <!--
        <div class="list-type-selector__button"
             :class="{active: list_type === 'catalog'}"
             @click="changeListType('catalog')"
        >
            <i class="fa fa-list"></i> Каталог
        </div>
        -->
        <div class="list-type-selector__button"
             :class="{active: list_type === 'table'}"
             @click="changeListType('table')"
        >
            <i class="fa fa-list"></i> График
        </div>
    </div>
</template>

<script>
export default {
    name: "SelectListType",
    props: {
        listType: {
            type: String,
            default: 'schedule'
        },
        process: false,
    },
    data() {
        return {
            list_type: 'schedule'
        }
    },
    mounted() {
        this.list_type = this.listType
    },
    watch: {
        list_type(list_type) {
            this.$emit('change', list_type)
        }
    },
    methods: {
        changeListType(list_type) {
            if (this.process) {
                return
            }
            this.list_type = list_type
        }
    }
}
</script>

<style lang="scss">
.list-type-selector {
    display: flex;
    justify-content: center;
    margin-top: 15px;
    margin-bottom: 10px;
    &__button {
        cursor: pointer;
        display: flex;
        padding: 10px;
        border-radius: 5px;
        i {
            margin-right: 8px;
        }
        &.active {
            background-color: #e0292c;
            color: #fff;
        }
    }

}
</style>
