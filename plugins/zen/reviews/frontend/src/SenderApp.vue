<template>
    <div class="sendmail">
        <div v-if="!auth" class="sendmail__panel">
            <input v-model="password" type="password">
            <div @click="sendMail" class="sendmail__panel__button">
                Войти
            </div>
        </div>
        <template v-else>
            <div class="sendmail__title">
                Писем отправлено: {{ mails_count }}
            </div>
            <div class="sendmail__panel">
                <input v-model="email">
                <div @click="sendMail" class="sendmail__panel__button">
                    Отправить
                </div>
            </div>
        </template>
        <div v-if="alert !== null" class="sendmail__alert">
            {{ alert }}
        </div>
    </div>
</template>

<script>
export default {
    name: "SenderApp",
    data() {
        return {
            password: null,
            auth: false,
            email: null,
            alert: null,
            mails_count: 0,
        }
    },
    mounted() {
        this.sendMail()
    },
    methods: {
        showAlert(alert) {
            this.alert = alert
            setTimeout(() => {
                this.alert = null
            }, 2000)
        },
        sendMail() {
            SenderApp.api({
                url: '/zen/reviews/api/Store:sendInvitationEmail',
                data: {
                    email: this.email,
                    password: this.password
                },
                then: response => {
                    if (response.success) {
                        if (response.auth) {
                            this.auth = true
                            this.mails_count = response.count
                            if (response.alert) {
                                this.showAlert(response.alert)
                            }
                            this.email = null
                        } else {
                            this.showAlert('Введите пароль')
                        }
                    }
                }
            })
        }
    }
}
</script>

<style lang="scss">
.sendmail {
    display: flex;
    flex-direction: column;
    max-width: 460px;
    background: #f8f8f8;
    border: 1px solid #d5d5d5;
    border-radius: 5px;
    padding: 30px;
    margin-left: auto;
    margin-right: auto;
    min-height: 200px;

    &__title {
        font-size: 13px;
        color: #555;
        margin-bottom: 3px;
        margin-left: 4px;
    }

    &__panel {
        display: flex;
        justify-content: space-between;
        background: #f8f8f8;
        margin-bottom: 15px;

        input {
            border: 1px solid #ebebeb;
            font-size: 20px;
            padding: 8px 16px;
            color: #747474;
            border-radius: 5px 0 0 5px;
            flex: 1 0 0;
        }
        &__button {
            background: #666c8c;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 5px 14px;
            border-radius: 0 5px 5px 0;
            user-select: none;
            cursor: pointer;
            transition: 200ms;
            &:active {
                transform: scale(0.95);
            }
        }
    }

    &__alert {
        background: #cbf2d1;
        border: 1px solid #b8e0bf;
        color: #485e4c;
        padding: 8px 15px;
        border-radius: 10px;
        animation: show-alert 300ms;
    }

    @keyframes show-alert {
        from {
            transform: scale(0.7);
            opacity: 0.5;
        }
    }
}
</style>
