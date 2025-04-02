<template>
    <div class="zen-tags" v-click-outside="closeList">
        <div @click.self="openList" class="zen-tags__title">
            <div v-if="!value.length" class="zen-tags__empty">
                Нажмите чтобы выбрать теги
            </div>
            <div v-for="tag in selected_tags"
                 @click="removeTag(tag)"
                 class="zen-tags__tag">
                {{ tag.name }} <i class="bi bi-x-circle"></i>
            </div>
        </div>
        <div v-if="dropdown" class="zen-tags__dropdown">
            <div v-for="tag in options_tags"
                 @click="addTag(tag)"
                 class="zen-tags__tag">
                {{ tag.name }}
            </div>
        </div>
    </div>
</template>

<script>
import ClickOutside from "./directives/ClickOutside";
export default {
    name: "ZenTags",
    directives: {
        'click-outside': ClickOutside
    },
    props: {
        value: {
            type: Array,
            default: []
        },
        tags: {
            type: Array,
            default: [],
        }
    },
    data() {
        return {
            dropdown: false
        }
    },
    computed: {
        selected_tags()
        {
            let selected = []
            this.tags.forEach(tag => {
                if (this.value.includes(tag.id)) {
                    selected.push(tag)
                }
            })
            return selected
        },
        options_tags()
        {
            let options = []
            this.tags.forEach(tag => {
                if (!this.value.includes(tag.id)) {
                    options.push(tag)
                }
            })
            return options
        }
    },
    methods: {
        openList()
        {
            this.dropdown = true
        },
        closeList()
        {
            this.dropdown = false
        },
        addTag(tag)
        {
            let output = this.value
            output.push(tag.id)
            this.$emit('change', output)
        },
        removeTag(tag)
        {
            let output = this.value.filter(id => {
                return id !== tag.id
            })
            this.$emit('change', output)
        }
    }

}
</script>
<style lang="scss">
.zen-tags {
    position: relative;
    &__title {
        background-color: #fff;
        border-radius: 3px;
        border: 1px solid #d0d0d0;
        min-height: 33px;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
    }
    &__empty {
        color: #b3b3b3;
        width: 100%;
        text-align: center;
        user-select: none;
        pointer-events: none;
    }
    &__dropdown {
        position: absolute;
        width: 100%;
        background-color: #fff;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        margin-top: -2px;
        border: 1px solid #d0d0d0;
        border-top: none;
        border-radius: 0 0 5px 5px;
    }
    &__tag {
        padding: 5px;
        margin: 5px;
        background: #d7eafa;
        font-size: 12px;
        border-radius: 5px;
        cursor: pointer;
        animation: fadeIn;
        animation-duration: 100ms;
        user-select: none;
        i {
            margin-left: 3px;
        }
    }
}

@keyframes fadeIn {
    from {
        transform: scale(0.5);
    }
}

</style>
