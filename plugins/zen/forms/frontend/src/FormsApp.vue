<template>
    <div class="forms-app container mt-5">
        <h2 class="mb-4">Форма для отправки лидов в АМО</h2>
        <div class="mb-3">
            <label for="name" class="form-label">Имя</label>
            <input v-model="name" type="text" class="form-control" id="name" placeholder="Введите имя" />
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input
                v-model="phone"
                ref="phoneInput"
                @keydown="onPhoneKeydown"
                @input="onPhoneInput"
                @focus="onPhoneFocus"
                type="tel"
                class="form-control"
                id="phone"
                placeholder="Введите телефон"
            />
        </div>
        <div class="mb-3">
            <label for="info" class="form-label">Информация</label>
            <textarea v-model="info" class="form-control" id="info" rows="3" placeholder="Введите информацию"></textarea>
        </div>
        <button @click="send" class="btn btn-primary">Отправить</button>
        <div v-if="alert !== null" :class="['forms-app__alert', alertType === 'error' ? 'forms-app__alert--error' : 'forms-app__alert--success']">
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
            phone: '+7',
            info: null,
            alert: null,
            alertType: 'success'
        }
    },
    mounted() {

    },
    watch: {
    },
    methods: {
        onPhoneFocus() {
            this.ensurePhonePrefix()
            this.$nextTick(() => {
                const input = this.$refs.phoneInput
                if (input) {
                    this.setCaretWithin(input, Math.max(2, (input.value || '').length))
                }
            })
        },
        onPhoneKeydown(e) {
            const input = e.target
            const start = input.selectionStart
            const end = input.selectionEnd
            const key = e.key
            const allowedNav = ['Tab', 'Home', 'End']
            if (key && (key.startsWith('Arrow') || allowedNav.includes(key))) {
                return
            }
            // Запрещаем удаление/редактирование префикса '+7'
            if ((key === 'Backspace' && start <= 2) || (key === 'Delete' && start < 2)) {
                e.preventDefault()
                this.$nextTick(() => this.setCaretWithin(input, 2))
                return
            }
            // Запрещаем ввод, если выделение захватывает префикс
            if (start < 2 || end < 2) {
                if (key && key.length === 1) {
                    e.preventDefault()
                    this.$nextTick(() => this.setCaretWithin(input, 2))
                }
            }
        },
        onPhoneInput(e) {
            const input = e.target
            const prevPos = input.selectionStart
            const normalized = '+7' + this.stripPrefix(input.value)
            if (input.value !== normalized) {
                this.phone = normalized
                this.$nextTick(() => this.setCaretWithin(input, Math.max(2, prevPos)))
            }
        },
        ensurePhonePrefix() {
            if (typeof this.phone !== 'string') {
                this.phone = '+7'
                return
            }
            if (!this.phone.startsWith('+7')) {
                this.phone = '+7' + this.stripPrefix(this.phone)
            }
        },
        stripPrefix(value) {
            if (!value) return ''
            if (value.startsWith('+7')) return value.slice(2)
            if (value.startsWith('+')) return value.slice(1)
            if (value.length >= 2) return value.slice(2)
            return ''
        },
        setCaretWithin(input, pos) {
            const p = Math.max(2, pos || 2)
            try {
                input.setSelectionRange(p, p)
            } catch (e) {}
        },
        showAlert(alert) {
            this.alert = alert
            setTimeout(() => {
                this.alert = null
            }, 4000)
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
                        this.alertType = (response && response.success === false) ? 'error' : 'success'
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
        padding: 8px 15px;
        border-radius: 10px;
        animation: show-alert 300ms;
    }

    &__alert--success {
        background: #cbf2d1;
        border: 1px solid #b8e0bf;
        color: #485e4c;
    }

    &__alert--error {
        background: #f8d7da;
        border: 1px solid #f5c2c7;
        color: #842029;
    }

    @keyframes show-alert {
        from {
            transform: scale(0.7);
            opacity: 0.5;
        }
    }
}
</style>
