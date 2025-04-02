<template>
    <div class="form-group span-full">
        <label>{{ label }}</label>
        <input v-model="value" class="form-control">
    </div>
</template>
<script>
    export default {
        name: 'OcvInput',
        props: {
            value: {
                type: String,
            },
            label: {
                type: String,
                default: '',
            }
        }
    }
</script>
