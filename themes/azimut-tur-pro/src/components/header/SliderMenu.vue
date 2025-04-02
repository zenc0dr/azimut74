<template>
    <div class="slider-menu">
         
        <div
            v-for="level in max_levels"
            class="slider-menu__slide py-4"
            :style="level === 1 ? `margin-left:-${offset_factor}px` : null"
        >
            <div class="slider-menu__back"
                 v-if="level !== 1"
                 @click="slideMove(level - 2)">
                <i class="bi bi-arrow-left-circle"></i>
            </div>
            <template
                v-if="menu_items[level]"
                v-for="item in menu_items[level]"
            >
                <div class="slide-item p-2"
                    v-if="item.items"
                     @click.self="fillBLock(level, item.items)"
                    style="font-weight:bold">
                    {{ item.title }}
                </div>
                <div v-else class="p-2"
                     @click="go(item)">
                    {{ item.title }}
                </div>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: "SliderMenu",
    props: {
        items: [],
        width: 0
    },
    data() {
        return {
            max_levels: 0,
            menu_items: [],
            offset_factor: 0
        }
    },
    created() {
        this.getMaxLevels(this.items,1)
        this.menu_items[1] = this.fillItems(this.items)
    },
    methods: {
        getMaxLevels(items, level)
        {
            if (this.max_levels < level) {
                this.max_levels = level
            }
            for (let key in items) {
                if (items[key].items) {
                    this.getMaxLevels(items[key].items, level + 1)
                }
            }
        },
        fillItems(items)
        {
            let output = []
            items.map((item) => {
                output.push(item)
            })
            return output
        },
        fillBLock(level, items)
        {
            let menu_items = this.menu_items
            menu_items[level + 1] = this.fillItems(items)
            this.menu_items = JSON.parse(JSON.stringify(menu_items))
            this.slideMove(level)
        },
        slideMove(level)
        {
            if (this.width < 576) {
                this.offset_factor = level * (this.width - 30)
            } else {
                this.offset_factor = level * (this.getContainerWidth()-30)
            }
        },
        getContainerWidth() {
           if (this.width < 992 && this.width > 768 ) {
               return 720;
           }
           return 540;
        },
        go(item)
        {
            location.href = location.origin + item.url
        }
    }
}
</script>

<style>
.slider-menu {
    display: flex;
    flex-direction: row;
    width: 100%;
    overflow: hidden;
    color: #fff;
}
.slider-menu__back {
    border-bottom: 1px solid #ffffff6e;
    padding-bottom: 12px;
    margin-bottom: 9px;
}
.slider-menu__back i {
    font-size: 23px;
}
.slider-menu__slide {
    min-width: 100%;
    transition: 200ms;
}
</style>
