<template>
    <section :id="id"></section>
</template>

<script>
import $ from 'jquery'
export default {
    name: "SelfInjection",
    props: {
        html: {
            type: String,
            default: null
        }
    },
    mounted() {
        $('#' + this.id).html(this.html)
    },
    computed: {
        id() {
            return 'inject-' + this._uid
        }
    }
}
</script>
