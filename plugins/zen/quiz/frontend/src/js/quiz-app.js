import {createApp} from 'vue';
import QuizApp from "../components/Quiz";

const md5 = require('md5');
const axios = require('axios');

window.QuizApp = {
    requests_register: {},
    api(opts) {
        let domain = location.origin
        let data = (opts.data) ? opts.data : null
        let config = (opts.config) ? opts.config : null
        let api_url = domain + opts.url
        let request_key = md5(api_url)
        if (this.requests_register[request_key]) {
            return
        }

        console.log(api_url, data) // todo:debug

        if (!opts.no_preloader) {
            this.requests_register[request_key] = setTimeout(() => {
                if (this.requests_register[request_key]) {
                    this.preloader(true)
                }
            }, 2000) // Две секунды
        }

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
                    //this.errorMessage(error)
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
                    //this.errorMessage(error)
                    console.log(error) // todo:debug
                })
        }
    },
    afterResponse(response, then, request_key) {
        delete this.requests_register[request_key]
        this.preloader(false)
        if (response.alerts) {
            this.pushAlerts(response.alerts)
        }
        if (then) {
            then(response)
        }
    },
    preloader(state) {
        console.log('Preloader = ' + state)
    },
    pushAlerts(alerts) {
        console.log('Preloader = ' + alerts)
    },
}


const app = createApp(QuizApp);
app.mount("#QuizApp");
