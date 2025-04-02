<template>
    <div class="smart-input">
        <div class="smart-input__label">
            {{ title }}
        </div>
        <div v-if="output" class="smart-input__output">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "SmartInput",
    props: {
        output: {
            Type: Boolean,
            default: false
        },
        title: {
            Type: String,
            default: ''
        }
    }
}
</script>

<style>
.smart-input {

}
.smart-input__label {

}
.smart-input__output {

}
</style>
