<template>
    <div class="tinybox-gallery container">
        <div class="tinybox-gallery__previews row">
            <div v-for="(image, i) in images"
                 @click="index = i"
                 class="col-sm-6 col-md-3 col-lg-3">
                <div :prop="image.src" :style="`background-image:url(${ image.src })`">
                    <div>{{ image.title }}</div>
                </div>
            </div>
        </div>
        <Tinybox
            v-model="index"
            :images="images"
        />
    </div>
</template>

<script>
import Tinybox from "vue-tinybox";
export default {
    name: "TinyboxGallery",
    components: { Tinybox },
    data() {
        return {
            index: null,
            images: []
        }
    },
    mounted() {
        try {
            this.images = JSON.parse($('meta[name="tinybox-gallery-data"]').attr('content'))
        } catch (e) {
            console.log('Изображения не найдены')
        }
    }
}
</script>
