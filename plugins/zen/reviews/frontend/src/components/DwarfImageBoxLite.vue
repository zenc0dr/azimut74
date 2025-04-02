<template>
    <div>
        <div ref="image_box" class="image-box image-box-lite" :class="size">
            <div v-if="inner_label" class="image-box__label">
                {{ inner_label }}
            </div>
            <div v-if="previewMode" class="image-box__edit">
                <i @click="edit = !edit" :class="{ active: edit }" class="bi bi-pencil-square"></i>
                <i @click="$emit('remove', modelValue)" class="bi bi-trash-fill"></i>
            </div>
            <div @click="show = true" class="image-box__body">
                <template v-if="thumbs && thumbs.length">
                    <div v-for="image in thumbs" class="image-box__thumb"
                         :style="`background-image: url(${image.disk_name})`"></div>
                    <div class="add-badge" v-if="modelValue.length > thumbs.length">
                        +{{ modelValue.length - thumbs.length }}
                    </div>
                </template>
                <div class="drop-area" v-else>
                    <div class="drop-message">
                        <svg viewBox="0 0 25 24" width="18px" height="18px" class="d Vb UmNoP">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                  d="M1.486 2.936H21.35v10.22h-1.5v-8.72H2.986v12.59h13.456v1.5H1.486V2.937zM8.16 9.73a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm.4 5h-2.4c-.5 0-.7-.5-.4-.9l1.6-1.4c.2-.2.6-.2.8 0l1.1 1.1c.2.2.6.2.8 0l3-2.7c.198-.2.6-.2.8 0l3.6 3c.3.3.1.9-.4.9h-8.5zm11.257 3.69H17.6v-1.5h2.217v-2.606h1.5v2.606H23.6v1.5h-2.283v1.893h-1.5v-1.893z"></path>
                        </svg>
                        <span class="first-text">Нажмите, чтобы добавить фото</span>
                        <span> или перетащите файлы</span>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="!previewMode || edit || !modelValue || !modelValue.length">
            <Modal title="Изображения" :show="show" @close="show = false">
                <div class="image-box__add-panel">
                    <div class="image-box__add-wrap">
                        <template v-if="!images_upload_process">
                            <i class="bi bi-plus-circle-dotted"></i> Добавить изображения
                            <input accept=".jpg,.jpeg,.png" type="file" multiple @input="isDropFiles"/>
                        </template>
                        <template v-else>
                            Загрузка изображений ({{ images_upload_progress }} из {{ uploading_images_count }}) ...
                            <div class="image-box__preloader" :style="`width:${preloader_progress}%`"></div>
                        </template>
                    </div>
                </div>

                <div
                    class="image-box__gallery"
                    :class="{ dragging: reorder_record !== null }"
                    @mouseup="dropReorder"
                    @mousemove="writeCursor"
                    @mouseleave="leaveReorder"
                >
                    <div
                        v-for="image in modelValue"
                        class="image-box__preview"
                        :class="[{ draggable: image.draggable }, image.reorder_class]"
                        :style="rowStyle(image)"
                        @mousemove="rowMouseMove(image, $event)"
                        @mouseleave="rowMouseLeave(image)"
                    >
                        <div class="image-box__preview__image" @click="editable_image = image"
                             :style="`background-image: url(${image.disk_name})`"></div>

                        <div class="image-box__preview__control">
                            <i @click="removeImage(image)" class="bi bi-trash-fill remove"></i>
                        </div>
                    </div>
                </div>

                <Modal :max-width="1000" :show="editable_image !== null" @close="editable_image = null"
                       title="Настройка изображения">
                    <div class="image-box__editor" v-if="editable_image !== null">
                        <div class="image-box__editor__image"
                             :style="`background-image: url(${editable_image.disk_name})`">
                        </div>
                    </div>
                </Modal>
            </Modal>
        </template>
        <template v-else>
            <div v-if="show" class="image-box__previews">
                <div @click.self="show = false" class="image-box__previews__wrap">
                    <div class="image-box__previews__btn" @click="switchPreview(-1)">
                        <i class="bi bi-chevron-compact-left"></i>
                    </div>
                    <div class="image-box__previews__image" @click.self="show = false">
                        <img :src="modelValue[preview_index].disk_name"/>
                    </div>
                    <div class="image-box__previews__btn" @click="switchPreview(1)">
                        <i class="bi bi-chevron-compact-right"></i>
                    </div>
                </div>
                <div class="image-box__info">
                    <div class="image-box__info__wrap">
                        {{ preview_index + 1 }} / {{ modelValue.length }}
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import Modal from "./Modal";


export default {
    name: "DwarfImageBox",
    components: {
        Modal
    },
    emits: ['update:modelValue', 'remove'],
    props: {
        modelValue: {
            type: Array,
            default: function () {
                return [];
            }
        },
        size: {
            type: String,
            default: 'full'
        },
        label: {
            type: [String, Function],
            default: null
        },
        previewMode: {
            type: Boolean,
            default: false
        }
    },
    mounted() {
        if (this.modelValue === null) {
            this.$emit('update:modelValue', [])
        }
        this.getWidth()
        $(window).resize(() => {
            this.getWidth()
        })
    },
    data() {
        return {
            width: null,
            show: false,
            editable_image: null, // Редактирование изображения

            preview_index: 0,
            edit: false,

            reorder_record: null, // Экземпляр который подвергается смене порядка
            cursor_y: null,
            reorder_raw_height: null, // Высота элемента над которым проводится экземпляр меняющий свой порядок
            reorder_target_record: null,

            images_upload_process: false, // Процесс загрузки изображений
            images_upload_progress: 0, // Сколько загружено
            uploading_images_count: 0, // Сколько будет загружено всего
            uploading_images: [], // Загружаемые изображения
            image_max_width: 2000, // Максимальная ширина изображения
            image_max_height: 2000, // Максимальная высота изображения

            media_folder: false, // Открыта библиотека изображений
        }
    },
    watch: {
        modelValue(modelValue) {
            this.$emit('update:modelValue', modelValue)
        },
        cursor_y(y) {
            if (this.reorder_record) {
                this.reorder_record.y = y - 15
            }
        }
    },
    computed: {
        inner_label() {
            if (this.label === null) {
                return null
            }
            if (typeof this.label === 'string') {
                return this.label
            }
            return this.label()
        },
        image_count() {
            let count = this.modelValue ? this.modelValue.length : 0
            return count + ' ' + APP.inc(count, ['изображение', 'изображения', 'изображений'])
        },
        preloader_progress() {
            let of = this.uploading_images_count // всего
            let to = this.images_upload_progress // счётчик
            if (!of) {
                return 0
            }
            return to * 100 / of
        },
        thumbs() {
            if (!this.modelValue) {
                return null
            }
            let limit = parseInt(this.width) / 90
            return this.modelValue.slice(0, limit)
        }
    },
    methods: {
        getWidth() {
            this.width = this.$refs?.image_box?.clientWidth
            if (!this.width) {
                setTimeout(() => {
                    this.getWidth()
                }, 100)
            }
        },
        holdReorder(record, event) {
            record.draggable = true
            this.reorder_record = record
            this.reorder_raw_height = $(event.target).closest('.image-box__preview').height()
        },
        dropReorder() {
            if (this.reorder_record === null) {
                return
            }
            this.makeReorder(this.reorder_record, this.reorder_target_record)
            delete this.reorder_record.draggable
            this.reorder_record = null
        },
        makeReorder(source, target) {
            if (!source || !target) {
                return
            }
            let position = target.reorder_class === 'drop-top' ? 'before' : 'after'
            let new_array = []
            for (let i = 0; i < this.modelValue.length; i++) {
                if (this.modelValue[i] === source) {
                    continue
                }
                if (this.modelValue[i] === target && position === 'before') {
                    new_array.push(source)
                }
                new_array.push(this.modelValue[i])
                if (this.modelValue[i] === target && position === 'after') {
                    new_array.push(source)
                }
            }
            this.$emit('update:modelValue', new_array)
        },
        writeCursor(event) {
            this.cursor_y = event.layerY
        },
        leaveReorder() {
            if (!this.reorder_record) {
                return
            }
            if (this.reorder_record.draggable) {
                this.reorder_record.draggable = false
            }
            this.reorder_record = null
        },
        rowStyle(record) {
            let style = []
            if (this.reorder_record) {
                if (this.reorder_record === record) {
                    style.push(`top:${this.reorder_record.y}px`)
                    style.push('pointer-events:none')
                }
            }
            return style.join(';')
        },
        rowMouseMove(record, event) {
            if (!this.reorder_record) {
                return
            }
            let y = event.offsetY
            let h = this.reorder_raw_height

            if (y < h / 2) {
                record.reorder_class = 'drop-top'
                this.reorder_target_record = record
            } else {
                record.reorder_class = 'drop-bottom'
                this.reorder_target_record = record
            }
        },
        rowMouseLeave(record) {
            delete record.reorder_class
        },

        // Добавление новых изображений
        isDropFiles(event) {
            this.edit = true
            let files = Array.from(event.target.files)
            this.uploading_images_count = files.length
            files.map(file => {
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
                        (resized_base64_data) => {
                            //this.image = resized_base64_data
                            this.addImage(file.name, resized_base64_data)
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
                let base64_data = canvas.toDataURL(file_type)
                onResize(base64_data)
            }
        },

        // Добавить изображение
        addImage(file_name, base64) {
            this.uploading_images.push({file_name, base64})
            this.uploadImages()
        },
        uploadImages() {
            if (this.images_upload_process) {
                return
            }
            this.images_upload_process = true
            this.uploadImage()
        },
        uploadImage() {
            if (!this.uploading_images.length) {
                this.images_upload_process = false
                this.images_upload_progress = 0
                return
            }

            this.images_upload_progress++
            let image = this.uploading_images.shift()

            APP.api({
                url: '/zen/reviews/api/Store:addImage',
                data: {image},
                no_preloader: true,
                then: response => {
                    if (response.success) {
                        this.modelValue.push(response.image)
                        this.uploadImage()
                    }
                }
            })
        },
        removeImage(image) {
            // APP.api({
            //     url: '/zen/crs/api/review:images-remove',
            //     data: {
            //         image_id: image.id
            //     },
            //     then: response => {
            //         if (response.success) {
            //             let index = this.modelValue.indexOf(image)
            //             this.modelValue.splice(index, 1)
            //         }
            //     }
            // })
        },
        selectFromMediaFolder(image) {
            this.modelValue.push(image)
        },
        switchPreview(step) {
            //this.preview_index + step
            if (this.preview_index + step > this.modelValue.length - 1) {
                this.preview_index = 0
                return
            }
            if (this.preview_index + step < 0) {
                this.preview_index = this.modelValue.length - 1
                return
            }
            this.preview_index += step
        }
    }
}
</script>

<style lang="scss">
.image-box {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    margin-bottom: 20px;
    font-size: 14px;

    &.image-box-lite {
        .drop-area {
            width: 100%;
            height: 150px;
            background: #f2f2f2;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;

        }

        .drop-message {
            font-size: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #555;
            text-align: center;

            span {
                margin-top: 8px;
                font-size: 14px;

                &.first-text {
                    font-weight: bold;
                }
            }
        }

        .image-box__body {
            height: auto;
            border: none;
            padding: 0;
            background: transparent;
        }
    }

    &__edit {
        display: flex;
        justify-content: space-between;

        i {
            cursor: pointer;
            color: #777;
        }

        i.active {
            color: #0a53be;
        }
    }

    &__previews {
        position: fixed;
        display: flex;
        flex-direction: row;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgb(0 0 0 / 70%);
        animation: show-gallery;
        animation-duration: 200ms;
        z-index: 10000;

        &__wrap {
            display: flex;
            flex: 1 1 0;
            justify-content: space-between;
        }

        &__btn {
            width: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            background: rgb(255 255 255 / 5%);
            font-size: 50px;
            cursor: pointer;
            transition: 200ms;

            &:hover {
                background: rgb(255 255 255 / 16%);
            }
        }

        &__image {
            display: flex;
            flex: 1 1 0;
            max-width: calc(100% - 150px);
            align-items: center;
            justify-content: center;

            img {
                max-height: 90%;
                max-width: 90%;
            }
        }
    }

    &__info {
        position: fixed;
        display: flex;
        width: 100%;
        bottom: 5px;
        height: 40px;
        justify-content: center;
        align-items: center;

        &__wrap {
            background: #ffffff96;
            padding: 3px 11px;
            border-radius: 5px;
            font-size: 13px;
        }
    }

    @keyframes show-gallery {
        from {
            opacity: 0;
        }
    }

    &__preloader {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        background: #699dff80;
        transition: 100ms;
    }

    &__add-panel {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    &__add-wrap {
        display: flex;
        flex: 1 0 0;
        position: relative;
        flex-direction: row;
        justify-content: center;
        height: 50px;
        align-items: center;
        border-radius: 5px;
        margin: 0 5px;
        color: #a1a1a1;
        background: #f8f8f8;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        transition: 200ms;
        overflow: hidden;
        @media screen and (max-width: 500px) {
            margin: 0
        }

        i {
            margin-right: 9px;
            font-size: 24px;
        }

        &:hover {
            background: #e8ebf3;
            color: #7787a5;
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

    &__label {
        font-weight: bold;
        color: #676B89;
        font-size: 14px;
        margin-bottom: 4px
    }

    &__body {
        border: 1px solid #ced4da;
        height: 91px;
        border-radius: 5px;
        background: #fafafa;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        font-size: 17px;
        color: #475dff;
        cursor: pointer;
        transition: 200ms;
        padding: 5px;

        i {
            margin-right: 7px;
        }

        &:hover {
            background: #e8efff;
        }
    }

    &__thumb {
        width: 70px;
        height: 67px;
        margin: 5px;
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
        border-radius: 5px;
    }

    &__no-images {
        display: flex;
        width: 100%;
        justify-content: center;
    }

    .add-badge {
        display: flex;
        width: 67px;
        height: 67px;
        margin: 5px;
        background: #0b5ed7;
        color: #fff;
        border-radius: 5px;
        justify-content: center;
        align-items: center;
    }

    &__editor {
        &__image {
            width: 968px;
            height: 650px;
            margin-bottom: 15px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
    }

    &__gallery {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        @media screen and (max-width: 500px) {
            justify-content: space-between;
        }

        &.dragging {
            user-select: none;
            padding-bottom: 150px;
        }
    }

    &__preview {
        display: flex;
        flex-direction: row;
        margin-bottom: 10px;
        border: 1px solid #dee2e6;
        //padding: 10px;
        border-radius: 5px;
        //padding-left: 5px;
        margin-right: 10px;
        position: relative;
        justify-content: space-between;
        @media screen and (max-width: 500px) {
            width: calc(50% - 4px);
            margin-right: 8px;
            &:nth-child(2n) {
                margin-right: 0;
            }
        }

        &__drag {
            font-size: 20px;
            color: #8e8e8e;

            i {
                margin-right: 3px;
                cursor: n-resize;
            }
        }

        &__image {
            width: 150px;
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 5px;
            cursor: pointer;
            @media screen and (max-width: 500px) {
                width: 100%;
            }
        }

        &__info {
            margin-left: 15px;
            flex: 1 0 0;

            &__row {
                display: flex;
                font-size: 14px;
            }

            &__name {
                font-weight: bold;
                margin-right: 5px;
            }

            &__value {

            }
        }

        &__control {
            padding-top: 10px;
            position: absolute;
            right: 10px;

            i.remove {
                color: red;
                cursor: pointer;
                transition: 200ms;

                &:hover {
                    color: red;
                }
            }
        }

        &.draggable {
            position: absolute;
            z-index: 100;
            width: 100%;
            box-shadow: -1px 2px 14px 0 #00000014;
            opacity: 0.5;
        }

        &.drop-top {
            border-top: 1px solid #00b7ff;
        }

        &.drop-bottom {
            border-bottom: 1px solid #00b7ff;
        }
    }

    &.full {
        width: 100%
    }

    &.half {
        width: calc(50% - 7px);
    }

    &.quarter {
        width: calc(25% - 7px);
    }
}
</style>
