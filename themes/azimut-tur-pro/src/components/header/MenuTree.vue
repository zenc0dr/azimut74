<template>
    <div class="menu-tree__root" :class="rootClasses[level]">
        <div
            v-for="item in items"
            class="menu-tree__item"
            :class="getClass(item, level)"
            @click.self="dropdown(item)">
            {{ item.title }}
            <div v-if="item.items && carets[level]" class="dropdown-caret">
                <i :class="`bi bi-arrow-down-short${ dropdown_item == item ? ' rotate' : '' }`"></i>
            </div>
            <MenuTree
                v-if="item.items && dropdown_item == item"
                :tree-level="next_level"
                :items="item.items"
                :root-classes="rootClasses"
                :item-classes="itemClasses"
                :carets="carets"
            />
        </div>
    </div>
</template>
<script>
export default {
    name: 'MenuTree',
    props: {
        treeLevel: {
            default: 0
        },
        items: [],
        rootClasses: {
            type: Array,
            default: []
        },
        itemClasses: {
            type: Array,
            default: []
        },
        carets: {
            type: Array,
            default: []
        },
        clickOutside: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            level: null,
            next_level: null,
            dropdown_item: null,
        }
    },
    created() {
        this.level = this.treeLevel
        this.next_level = this.level + 1
    },
    watch: {
        clickOutside() {
            this.dropdown_item = null
        }
    },
    methods: {
        dropdown(item) {
            if (item.url) {
                location.href = location.origin + item.url
                return
            }

            if (this.dropdown_item === item) {
                this.dropdown_item = null
                return
            }
            this.dropdown_item = item
        },
        getClass(item, level) {
            let output = this.itemClasses[level]
            if (this.dropdown_item === item) {
                output += ' active'
            }
            return output
        }
    }
}
</script>
<style>
.menu-tree__root {
    user-select: none;
}
</style>
