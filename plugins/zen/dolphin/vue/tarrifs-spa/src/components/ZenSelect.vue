<template>
    <div :class="`form-group span-${span}`">
        <label>{{ label }}</label>
        <v-select label="name"
                  @search="fetchOptions"
                  :reduce="record => record.id"
                  v-model="value.value"
                  :options="value.options">
            <div slot="no-options">Нет совпадений</div>
        </v-select>
    </div>
</template>
<script>
    import vSelect from "vue-select";

    export default {
        name: 'ZenSelect',
        props: {
            label: {
                type: String,
                default: ''
            },
            span: {
                type: String,
                default: 'auto',
            },
            value: {
                default: {
                    value: '--',
                    options: []
                }
            },
            updateOptions: {

            }
        },
        components: {
            vSelect
        },
        methods: {
            fetchOptions(search_text)
            {
                if(!search_text) return

                if(search_text.length < 3) {
                    this.value.options = []
                    return
                }

                if(!this.updateOptions) return
                let new_options = this.updateOptions(search_text)
                if(!new_options) return
                this.value.options = new_options
            }
        }
    }
</script>
