<template>
<div class="images-uploader">
    <div v-if="images.length < images_count_max" class="images-uploader__drop-zone">
        <div class="images-uploader__drop-zone__drop">
            <i class="bi bi-cloud-arrow-up"></i> Перетащите файлы для загрузки
        </div>
        <input type="file" accept=".jpg,.jpeg,.png" multiple @input="isDropFiles">
    </div>
    <div v-if="images.length" class="images-uploader__preview-zone">
        <template v-for="image in images">
            <div class="images-uploader__thumb removable"
                 v-if="image.url"
                 :style="`background-image: url(${image.url})`">
                <div @click="removeImage(image)" class="removable__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 12L14 16M14 12L10 16M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="images-uploader__thumb image-upload"
                 v-else
                 :style="`background-image: url(${image.base64})`">
                <div class="spinner-border text-dark" role="status"></div>
            </div>
        </template>
    </div>
</div>
</template>

<script>
export default {
    name: "ImagesUploader",
    emits: ['update:modelValue', 'in_process'],
    props: {
        modelValue: {
            type: Array,
            default: []
        },
    },
    data() {
        return {
            //uploading_images: [], // Загружаемые изображения
            images: [],
            //images_upload_process: false, // Процесс загрузки изображений
            //images_upload_progress: 0, // Сколько загружено
            images_count_max: 10, // Сколько будет загружено всего
            images_count: 0,
            image_max_width: 2000, // Максимальная ширина изображения
            image_max_height: 2000, // Максимальная высота изображения
            in_process: false
        }
    },
    mounted() {
        if (this.modelValue === null) {
            this.$emit('update:modelValue', [])
        }
    },
    watch: {
        images: {
            handler(images) {
                this.$emit('update:modelValue', images.map(image => {
                    return {
                        file_name: image.file_name,
                        disk_name: image.disk_name
                    }
                }))
            },
            deep: true
        },
        in_process(state) {
            this.$emit('in_process', state)
        }
    },
    methods: {
        /* В дропзону добавлены файлы */
        isDropFiles(event) {
            let files = Array.from(event.target.files)
            if (this.images_count + files.length > this.images_count_max) {
                let over_count = (this.images_count + files.length) - this.images_count_max
                if (over_count >= files.length) {
                    return
                } else {
                    files.splice(files.length - over_count, over_count)
                }
            }

            this.in_process = true
            this.images_count = files.length

            let count = 0
            files.forEach(file => {
                if (!['jpg', 'jpeg', 'png'].includes(file.type.split('/')[1])) {
                    return
                }
                let file_reader = new FileReader()
                file_reader.readAsDataURL(file);
                file_reader.onload = (e) => {
                    let base64_data = e.target.result
                    this.resizeImage(
                        base64_data,
                        file.type,
                        this.image_max_width,
                        this.image_max_height,
                        resized_base64_data => {
                            this.images.push({
                                file_name: file.name,
                                base64: resized_base64_data,
                                url: null,
                                disk_name: null
                            })
                            count++
                            if (count === files.length) {
                                this.imageHandler()
                            }
                        })
                }
            })
        },

        // Функция ресайза изображений
        resizeImage(base64_data, file_type, max_width, max_height, onResize) {
            let image = new Image();
            image.src = base64_data;
            image.onload = function () {
                let canvas = document.createElement("canvas")
                let workflow = canvas.getContext("2d")
                workflow.drawImage(image, 0, 0)
                let width = image.width
                let height = image.height
                if (width > height) {
                    if (width > max_width) {
                        height *= max_width / width
                        width = max_width
                    }
                } else {
                    if (height > max_height) {
                        width *= max_height / height
                        height = max_height
                    }
                }
                canvas.width = width
                canvas.height = height
                workflow = canvas.getContext("2d")
                workflow.drawImage(image, 0, 0, width, height)
                onResize(canvas.toDataURL(file_type))
            }
        },

        removeImage(image) {
            let index = this.images.indexOf(image)
            this.images.splice(index, 1)
        },

        imageHandler() {
            let image = this.images.find(image => image.url === null)
            if (image) {
                ReviewsApp.api({
                    url: '/zen/reviews/api/Store:addImage',
                    data: {
                        image: {
                            file_name: image.file_name,
                            base64: image.base64,
                        }
                    },
                    no_preloader: true,
                    then: response => {
                        if (response.success) {
                            image.url = response.image.url
                            image.disk_name = response.image.disk_name
                            this.imageHandler()
                        }
                    }
                })
            } else {
                this.in_process = false
            }
        },
    }
}
</script>

<style lang="scss">
.images-uploader {
    .removable {
        cursor: pointer;
        &__icon {
            width: 100%;
            height: 100%;
            display: none;
            align-items: center;
            justify-content: center;
            color: red;
            background: #ff000054;
            animation: image-show 200ms;

            svg {
                width: 50px;
                height: 50px;
            }
        }
    }

    .removable:hover .removable__icon {
        display:flex;
    }

    &__drop-zone {
        position: relative;
        height: 84px;

        &__drop {
            position: absolute;
            display: flex;
            border: 2px dashed #b1b1b1;
            border-radius: 10px;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            pointer-events: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #b5b5b5;

            i {
                margin-right: 5px;
            }
        }

        input {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0;
            cursor: pointer;
        }
    }

    &__preview-zone {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }

    &__thumb {
        display: flex;
        width: 200px;
        height: 200px;
        border-radius: 10px;
        margin: 5px;
        background-size: cover;
        justify-content: center;
        align-items: center;
        transition: 200ms;
        animation: image-show;
        animation-duration: 300ms;

        &.image-upload {
            animation: preload-image-show;
            animation-duration: 300ms;
            opacity: 0.7;
            filter: grayscale(1);
        }
    }

    @keyframes image-show {
        from {
            opacity: 0;
        }
    }

    @keyframes preload-image-show {
        from {
            transform: scale(0.5);
        }
    }
}
</style>
