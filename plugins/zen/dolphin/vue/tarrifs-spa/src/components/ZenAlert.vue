<template>
    <div class="zen-alert">
        <div class="zen-alert-body">
            <div v-for="alert in localAlerts" :class="(typeof alert.type !== 'undefined')?alert.type:'success'">
                {{ alert.text }}
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'ZenAlert',
        props: {
            value: {
                default: [],
            }
        },
        data(){
            return {
                localAlerts: []
            }
        },
        watch: {
            value(value)
            {
                if(value.length) {
                    this.localAlerts = value
                    if (this.timer) {
                        clearTimeout(this.timer);
                        this.timer = null;
                    }
                    this.timer = setTimeout(() => {
                        this.localAlerts = []
                        this.$emit('showed')
                    }, 2000);
                }
            }
        }
    }
</script>
<style>
    .zen-alert {
        position: fixed;
        width: 100%;
        top: 30px;
        z-index: 10000;
        pointer-events: none;
    }

    .zen-alert-body {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .zen-alert-body > div {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 5px;
        box-shadow: 0px 5px 6px 0 #0003;
        color: #fff;
        font-size: 17px;
        font-family: sans-serif;
        margin-bottom: 9px;
        animation: show-alert 300ms ease;
    }

    .zen-alert .success {
        background: #6da742;
    }
    .zen-alert .danger {
        background: #f10400;
    }

    @keyframes show-alert {
        from {
            opacity: 0;
            transform: translate3d(0, -100px, 0);
        }
    }

</style>
