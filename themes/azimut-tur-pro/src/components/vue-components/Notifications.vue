<template>
    <div class="notifications">
        <div v-for="(notification, index) in notificationsArr"
             @click="removeAlarm(index)"
             class="notification"
             :class="notification.type"
        >
            {{ notification.text }}
        </div>
    </div>
</template>

<script>
export default {
    name: "Notifications",
    props: {
        notifications: {
            type: Array,
            default: [],
        }
    },
    data() {
        return {
            notificationsArr: []
        }
    },
    mounted() {
        this.notificationsArr = this.notifications
    },
    watch: {
        notifications(notifications) {
            this.notificationsArr = notifications
        }
    },
    methods: {
        removeAlarm(index) {
            this.notificationsArr.splice(index, 1)
            this.$emit('change', this.notificationsArr)
        }
    }
}
</script>

<style lang="scss" scoped>
.notifications {
    margin-top: 20px;
    .notification {
        font-size: 15px;
        padding: 4px 13px;
        margin-bottom: 6px;
        border-radius: 5px;
        animation: notifications-show;
        animation-duration: 200ms;
    }
    .danger {
        border: 1px solid #ffb7b7;
        color: #ff4040;
        background: #ffe5e5;
    }
    .success {
        border: 1px solid #66ab4c;
        color: #107112;
        background: #c4ffa6;
    }
}

@keyframes notifications-show {
    from {
        transform: scale(0.6);
    }

    to {
        transform: scale(1);
    }
}
</style>
