<template>
    <div class="agreement">
        <zen-modal max-width="1000px" z-index="100501" title="Пользовательское соглашение" :show="open" @hide="close">
            <div class="agreement-html" v-html="agreement_html"></div>
        </zen-modal>
    </div>
</template>
<script>
    import ZenModal from './ZenModal'
    export default {
        components: {
            ZenModal
        },
        props: {
            show: {
                Type: Boolean,
                default: false
            },
            html: {
                Type: String,
                default: null,
            }
        },
        mounted() {
            this.$store.dispatch('loadAgreement')
        },
        computed: {
            agreement_html() {
                return this.$store.getters.getAgreementHTML
            }
        },
        data() {
            return {
                open: false,
            }
        },
        watch: {
            show(value) {
                if(!value) return
                this.open = true
            }
        },
        methods: {
            close() {
                this.open = false
                this.$emit('close')
            }
        }
    }
</script>
<style>
    .agreement-html {
        padding: 10px;
    }
</style>
