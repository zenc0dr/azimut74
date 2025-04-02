<template>
    <div class="dwarf-select" :class="{size, css}" >
        <div v-if="inner_label" class="dwarf-select__label">
            {{ inner_label }}
        </div>
        <vSelect
            label="name"
            :reduce="record => record.id"
            :create-option="record => ({id:record, name:record})"
            :options="inner_options"
            v-model="modelValueRef"
            :clearable="!!element.editable"
            :multiple="!!element.multiple"
            :taggable="!!element.editable"
            :loading="preloader"
            @input="onInput"
            @search="onSearch"
            placeholder="Нажмите для выбора"
        >
            <template #selected-option="{ id, name }">
                {{ title(id, name) }}
            </template>
            <template #no-options="{ search, searching, loading }">
                <template v-if="search">
                    Совподений не найдено
                </template>
                <template v-else>
                    Опций не обнаружено
                </template>
            </template>
        </vSelect>
    </div>
</template>

<script>
import vSelect from 'vue-select'
import { ref, computed, watch, onMounted } from 'vue';
import './DwarfSelect.scss'

export default {
    name: "DwarfSelect",
    components: {
        vSelect
    },
    emits: ['update:modelValue'],
    props: {
        modelValue: {
            type: Number,
            default: null
        },
        options: {
            type: Array,
            default: null
        },
        size: {
            type: String,
            default: 'full'
        },
        css: {
            type: String,
            default: null,
        },
        label: {
            type: [String, Function],
            default: null
        },
        search: {
            type: String,
            default: null
        },
        element: {
            type: Object,
            default() {
                return {};
            }
        },
        loading: {
            type: Boolean,
            default: null
        },
        title_override: {
            type: Function,
            default: null
        }
    },
    setup(props, context) {
        const modelValueRef = ref(props.modelValue);
        const inner_label = computed(() => {
            if (props.label === null) {
                return null
            }
            if (typeof props.label === 'string') {
                return props.label
            }
            return props.label()
        });

        const inner_options = computed(() => {
            if (loaded_options.value.length) {
                return loaded_options.value
            }
            return props.options
        });

        const search_text = ref(null);
        const loaded_options = ref([]);
        const preloader = ref(false);
        const timer = ref(null);

        const title = (id, name) => {
            if (props.title_override) {
                return props.title_override(id, name)
            }

            if (id === name) {
                return name
            }
            if (!inner_options.value.length) {
                return ''
            }
            return name
        }


        watch(() => props.loading, (state) => {
            if (state === null) {
                return
            }
            preloader.value = state
        })

        watch(() => modelValueRef.value, (val) => {
            context.emit('update:modelValue', val);
        })

        const onInput = (value) => {
            modelValueRef.value = value;  // Обновляем значение референса
        }

        // Continue with the rest of your setup...

        return {
            modelValueRef,
            inner_label,
            inner_options,
            search_text,
            loaded_options,
            preloader,
            timer,
            title,
            onInput
            // ... Export the rest of your computed properties, data properties, and methods
        }
    },
    // Continue with the rest of your component...
}
</script>

<style lang="scss">
.dwarf-select {
    margin-bottom: 20px;

    &__label {
        font-weight: bold;
        color: #777;
        font-size: 14px;
        margin-bottom: 4px;
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
