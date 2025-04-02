<template>
    <div class="dw-datetime" :class="size">
        <div v-if="label" class="dw-datetime__label">
            {{ label }}
        </div>
        <flat-pickr
            v-model="modelValueRef"
            :config="config"
            @input="onInput"
        />
        <span class="calendar-icon"><i class="bi bi-calendar-week"></i></span>
    </div>
</template>

<script>
import { ref } from 'vue';
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

export default {
    name: "DwarfDatetime",
    components: {
        flatPickr
    },
    props: {
        modelValue: String,
        label: {
            type: String,
            default: 'Строка'
        }
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const modelValueRef = ref(props.modelValue);
        const config = {
            dateFormat: "Y-m-d",
            altFormat: "j F Y",
            altInput: true,
            locale: Russian,
            minDate: "today",
            "disable": [
                function(date) {
                    return (date < new Date());
                }
            ]
        };
        const onInput = (event) => {
            emit('update:modelValue', event.target.value);
        };

        return {
            modelValueRef,
            config,
            onInput
        };
    }
};
</script>

<style lang="scss">
@import 'flatpickr/dist/flatpickr.css';
.dw-datetime {
    position: relative;
    .calendar-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        //transform: translateY(-50%);
        font-size: 20px;
        color: #525252;
        pointer-events: none;
    }
    .form-control:focus {
        box-shadow: none;
    }
    &__label {
        font-weight: bold;
        color: #777;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .dp__pointer {
        height: 42px;
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
