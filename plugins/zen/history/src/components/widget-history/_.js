import Vue from 'vue';

window.md5 = require('md5');
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
Vue.config.productionTip = false;


import App from "./WidgetHistory";


window.APP = {
    data: {}, // Общий пул данных
    requests_register: {}, // Объект фиксации запросов
    events: [], // Хранилище событий

    api(opts) {
        let domain = location.origin
        let data = (opts.data) ? opts.data : null
        let config = (opts.config) ? opts.config : null
        let api_url = domain + opts.url
        let request_key = md5(api_url) // + JSON.stringify(data) для снижения чувствительности
        if (this.requests_register[request_key]) {
            return
        }


        if (!opts.no_preloader) {
            this.requests_register[request_key] = setTimeout(() => {
                if (this.requests_register[request_key]) {
                    this.preloader(true)
                }
            }, 2000) // Две секунды
        }

        // Если нет данных то запрос GET
        if (!data) {
            axios.get(api_url, config)
                .then((response) => {
                    console.log(response.data) // todo:debug
                    this.afterResponse(response.data, opts.then, request_key)
                })
                .catch((error) => {
                    if (error.response && error.response.status === 419) {
                        location.reload()
                        return
                    }
                    delete this.requests_register[request_key]
                    this.preloader(false)
                    this.errorMessage(error)
                    console.log(error) // todo:debug
                })
        } else {
            axios.post(api_url, data, config)
                .then((response) => {
                    console.log(response.data) // todo:debug
                    this.afterResponse(response.data, opts.then, request_key)
                })
                .catch((error) => {
                    if (error.response && error.response.status === 419) {
                        location.reload()
                        return
                    }
                    delete this.requests_register[request_key]
                    this.preloader(false)
                    this.errorMessage(error)
                    console.log(error) // todo:debug
                })
        }
    },
    afterResponse(response, then, request_key) {
        delete this.requests_register[request_key]
        this.preloader(false)

        // Если в ответе есть сообщения
        if (response.alerts) {
            this.pushAlerts(response.alerts)
        }

        if (response.system_error) {
            this.errorMessage(response.system_error)
            return
        }

        // Если вернулся ответ с массивом confirmation
        if (response.confirmation) {
            this.pushConfirmation(response, then)
        } else if (then) {
            then(response)
        }
    },
    makeId(length) { // Строковой бредогенератор
        let result = '';
        let characters = 'abcdefghijklmnopqrstuvwxyz0123456789'
        let charactersLength = characters.length
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength))
        }
        return result
    },
    errorMessage(message) {
        if (typeof ErrorMessage !== "undefined") {
            ErrorMessage.showMessage(message)
        }
    },
    preloader(state) {
        if (typeof Preloader !== "undefined") {
            Preloader.push(state)
        }
    },
    pushAlerts(alerts) {
        if (typeof Alerts !== "undefined") {
            Alerts.push(alerts)
        }
    },
    pushConfirmation(response, then) {
        if (typeof Confirmation !== "undefined") {
            Confirmation.push(response, then)
        } else {
            if (then) {
                then(response)
            }
        }
    },
    /* Добавить событие */
    addEvent(name, fn) {
        this.events.push({ name, fn })
    },
    /* Добавить уникальное событие */
    addUniqueEvent(name, fn) {
        let fn_exists = false
        this.events.forEach(event => {
            if (event.name === name) {
                event.fn = fn
                fn_exists = true
            }
        })
        if (!fn_exists) {
            this.addEvent(name, fn)
        }
    },
    launchEvent(name) {
        this.events.forEach(event => {
            if (event.name === name) {
                event.fn()
            }
        })
    },

    /* Определить, является ли строка JSON данными */
    IsJsonString(str) {
        try {
            JSON.parse(str)
        } catch (e) {
            return false
        }
        return true
    },
    /* Склонение существительных */
    inc(number, words) { // Функция склонения числительных ex: ['товар', 'товара', 'товаров']
        let i = (number % 100 > 4 && number % 100 < 20)
            ? 2 : [2, 0, 1, 1, 1, 2][(number % 10 < 5) ? number % 10 : 5]
        return words[i]
    },
    clickFavorite(e, item) {
        let typeCode,id,title,url,isActive,days,other = null
        if (item) {
            typeCode = item.type
            id = item.inner_id
            title = item.title
            url = item.url
            days = item.days
            other = item.other
            isActive = true
        } else {
            typeCode = e.getAttribute('type')
            id = e.getAttribute('widget-id')
            title = e.getAttribute('title-element').replace(/\([^)]*\)/g, "")
            url = e.getAttribute('url')
            days = e.getAttribute('days')
            other = e.getAttribute('other')
            isActive = e.classList.contains('active')
        }
        this.api({
            url: '/zen/history/api/History:setFavoriteItems',
            data: {
                typeCode,
                id,
                'visiterId': window.WidgetHistory.userId,
                title,
                url,
                other,
                days
            },
            then: resonse => {
                let favoriteItems = window.WidgetHistory.favoriteItems
                if (!item) {
                    if (isActive) {
                        let indexDel = favoriteItems.findIndex(item => item.id == id && item.type == typeCode);
                        favoriteItems.splice(indexDel, 1);

                    } else {
                        let dateNow = new Date();
                        let dateOptions = {
                            year: "2-digit",
                            month: "2-digit",
                            day: "2-digit"
                        };
                        let date =  dateNow.toLocaleString('ru', dateOptions);
                        favoriteItems.unshift({
                            'url':url,
                            'title':title,
                            'type':typeCode,
                            'visiter_id':window.WidgetHistory.userId,
                            'inner_id': id,
                            'date': date,
                            'days': days,
                            'other': JSON.parse(other),
                        })
                    }
                    e.classList.toggle('active')
                } else {
                    favoriteItems.splice(item.index, 1);
                }
                window.WidgetHistory.favoriteItems = favoriteItems
            }
        })
    }
}


new Vue({
    render: h => h(App)
}).$mount('#widget-history');


