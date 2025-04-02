<template>
    <div class="file-uploader">
        <div v-if="selectedFiles.length" class="files-uploads">
            <div>
                Изображений: {{ selectedFiles.length }}
            </div>
        </div>
        <div class="drop-area" @dragover.prevent @drop="handleFileDrop" @click="handleFileClick">
            <div class="drop-message">
                <svg viewBox="0 0 25 24" width="18px" height="18px" class="d Vb UmNoP">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M1.486 2.936H21.35v10.22h-1.5v-8.72H2.986v12.59h13.456v1.5H1.486V2.937zM8.16 9.73a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm.4 5h-2.4c-.5 0-.7-.5-.4-.9l1.6-1.4c.2-.2.6-.2.8 0l1.1 1.1c.2.2.6.2.8 0l3-2.7c.198-.2.6-.2.8 0l3.6 3c.3.3.1.9-.4.9h-8.5zm11.257 3.69H17.6v-1.5h2.217v-2.606h1.5v2.606H23.6v1.5h-2.283v1.893h-1.5v-1.893z"></path>
                </svg>
                <span class="first-text">Нажмите, чтобы добавить фото</span>
                <span> или перетащите файлы</span>
            </div>
        </div>
        <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*" multiple/>
        <button @click="uploadFile" :disabled="!selectedFiles.length" class="green">Загрузить изображения</button>
    </div>
</template>

<script>
export default {
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: Array,
            default: () => [] // Используем функцию для возврата нового пустого массива
        }
    },
    data() {
        return {
            selectedFiles: [],
            uploadedFiles: []
        };
    },
    watch: {
        value: {
            immediate: true,
            handler(newValue) {
                this.selectedFiles = newValue;
            }
        },
        selectedFiles(newValue) {
            this.$emit('input', newValue);
        }
    },
    methods: {
        handleFileDrop(event) {
            event.preventDefault();
            this.selectedFiles = Array.from(event.dataTransfer.files);
        },
        handleFileChange(event) {
            this.selectedFiles = Array.from(event.target.files);
        },
        handleFileClick() {
            this.$refs.fileInput.click();
        },
        uploadFile() {
            this.uploadedFiles = this.selectedFiles;
        }
    }
};
</script>


<style lang="scss">
.file-uploader {
    display: flex;
    width: 100%;
    flex-direction: column;
    align-items: flex-start;

    .drop-area {
        width: 100%;
        height: 150px;
        background: #f2f2f2;
        //border: 2px solid #aaa;
        border-radius: 4px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;

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
    }

    input {
        display: none;
    }

    .files-uploads {
        display: flex;
        width: 100%;
        padding: 5px 0;
    }

    button {
        margin-top: 10px;
        padding: 8px 16px;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;

        &.green {
            background-color: #4caf50;
            color: white;
        }
    }

    button:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    div[hidden] {
        display: none;
    }

    /* Дополнительные стили для загруженных файлов */
    div[hidden] + div {
        margin-top: 10px;
    }
}


</style>
