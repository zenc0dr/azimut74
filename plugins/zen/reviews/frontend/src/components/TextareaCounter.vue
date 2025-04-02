<template>
    <div class="textarea-with-counter">
        <label for="textarea-input" class="label">{{ label }}</label>
        <textarea
            id="textarea-input"
            ref="textarea"
            minlength="200"
            rows="5"
            :class="characterCountClass"
            v-model="internalText"
            @input="handleInput"
            @scroll="adjustTextareaSize"
            placeholder="Введите текст"
        ></textarea>
        <div class="counter">мин. от {{ maxCharacters }} символов сейчас {{ characterCount }}</div>
    </div>
</template>

<script>

export default {
    props: {
        modelValue: {
            type: String,
            required: true,
            default: ''
        },
        label: {
            type: String,
            default: 'Текст'
        },
        maxCharacters: {
            type: Number,
            default: 200
        }
    },
    mounted() {
        this.adjustTextareaSize();
    },
    data() {
        return {
            internalText: this.modelValue,
            characterCount: this.modelValue.length,
            textarea: null
        }
    },
    computed: {
        characterCountClass() {
            if (this.characterCount !== 0 && this.characterCount < this.maxCharacters) {
                return 'no-good';
            } else if (this.characterCount >= this.maxCharacters) {
                return 'good';
            }
        }
    },
    methods: {
        handleInput() {
            this.$emit('update:modelValue', this.internalText)
            this.characterCount = this.internalText.length
            this.adjustTextareaSize()
        },
        adjustTextareaSize() {
            if (this.textarea) {
                this.textarea.style.height = 'auto';
                this.textarea.style.height = `${this.textarea.scrollHeight + 10}px`
            }
        }
    }
};
</script>

<style lang="scss">
.textarea-with-counter {
    position: relative;
}

textarea {
    -webkit-appearance: none;
    border: 2px solid transparent;
    border-radius: 4px;
    box-shadow: 0 0 0 2px #cacaca;
    margin: 2px;
    padding: 14px 12px 14px 14px;
    resize: none;
    scrollbar-color: var(--scrimImageStart) transparent;
    scrollbar-width: thin;
    width: calc(100% - 4px);

    &.no-good {
        &:focus {
            box-shadow: 0 0 0 2px orange;
        }
    }

    &.good {
        &:focus {
            box-shadow: 0 0 0 2px green;
        }
    }

    &:focus {
        box-shadow: 0 0 0 2px #000;
    }

    &:focus-visible {
        outline: none;
    }
}

.counter {
    position: absolute;
    right: 10px;
    font-size: 12px;
    bottom: 10px;
    color: #888;

}

.label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
</style>
