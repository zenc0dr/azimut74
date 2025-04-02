<template>
    <div class="zen-alerts-box">
        <div :class="alert.type" class="zen-alert" v-for="(alert, index) in alerts" @click="del(index)">
            {{ alert.text }}
        </div>
    </div>
</template>
<script>
    export default {
        name: 'ZenAlertsBox',
        props: {
            alerts: {
                type: Array,
                default: []
            }
        },
        watch: {
            alerts(value)
            {
                this.$emit('update', value)
            }
        },
        methods: {
            del(index)
            {
                this.alerts.splice(index, 1);
            }
        }
    }
</script>
<style>
    .zen-alerts-box {
        margin-top: 20px;
    }

    .zen-alerts-box .zen-alert {
        font-size: 15px;
        color: #777;
        background: #d2d2d2;
        border: 1px solid #535353;
        margin: 5px;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: bold;
    }

    .zen-alerts-box .zen-alert.danger {
        color: #f00;
        background: #ffcece;
        border: 1px solid red;
    }
    .zen-alerts-box .zen-alert.success {
        color: #1f5908;
        background: #b6ffb5;
        border: 1px solid #1f5908;
    }
</style>
