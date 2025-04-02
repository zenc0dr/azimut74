<template>
    <div class="forms-app container mt-5">
        <div class="mb-3">
            <label for="name" class="form-label">Имя</label>
            <input v-model="name" type="text" class="form-control" id="name" placeholder="Введите имя" />
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input v-model="phone" type="tel" class="form-control" id="phone" placeholder="Введите телефон" />
        </div>
        <div class="mb-3">
            <label for="info" class="form-label">Информация</label>
            <textarea v-model="info" class="form-control" id="info" rows="3" placeholder="Введите информацию"></textarea>
        </div>
        <button @click="send" class="btn btn-primary">Отправить</button>
        <div v-if="alert !== null" class="forms-app__alert">
            {{ alert }}
        </div>
    </div>
</template>

<script>
export default {
    name: "FormsApp",
    data() {
        return {
            name: null,
            phone: null,
            info: null,
            alert: null
        }
    },
    mounted() {

    },
    methods: {
        showAlert(alert) {
            this.alert = alert
            setTimeout(() => {
                this.alert = null
            }, 2000)
        },
        send() {
            FormsApp.api({
                url: '/zen/forms/api/Sender:send',
                data: {
                    name: this.name,
                    phone: this.phone,
                    info: this.info
                },
                then: response => {
                    if (response.alert) {
                        this.showAlert(response.alert)
                    }
                    if (response.success) {
                        this.name = null
                        this.phone = null
                        this.info = null
                    }
                }
            })
        }
    }
}
</script>

<style lang="scss">
.forms-app {

    &__alert {
        margin-top: 50px;
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
