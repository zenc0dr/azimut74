<template>
    <div id="ErrorWindow" v-if="errors.length">
        <div v-for="error in errors" class="widget-error" v-html="error"></div>
    </div>
</template>
<script>
    export default {
        name: 'ErrorWindow',
        computed: {
            errors() {
                return this.$store.getters.getErrors
            }
        }
    }
</script>
