import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'

import GeoTree from "./GeoTree";
import AtmStore from "./AtmStore";

Vue.use(Vuex)

export default new Vuex.Store({
    modules: {
        GeoTree,
        AtmStore
    },

    state: {
        settings: {
            widgetName: null, // ex: ext || atm || exp || atp ...
            domain: null,
            env: null // ex: dev || prod
        },
        list_type: 'catalog',
        errors: [],
        agreement_html: false,
        width: 0,
        process: true,
    },

    actions: {
        /* Инициировать базовые настройки */
        initSettings(ctx) {
            let domain = location.origin.replace(':8080', '')
            ctx.commit('setSettings', {
                widgetName:'ext',
                domain,
                env: (location.port === "8080") ? 'dev' : 'prod',
                assets: domain + '/plugins/zen/dolphin/vue/dolphin-widget/src/assets'
            })
        },

        /* Обёртка для запросов к api */
        apiQuery(ctx, fn) // fn = {url:'class:method', data:{}, then:(response)=>{}}
        {
            let domain = this.state.settings.domain
            let api_url = domain + '/zen/dolphin/api/' + fn.url
            let data = (fn.data) ? fn.data : null
            ctx.commit('setProcess', true)

            console.log(api_url, data)

            axios.post(api_url, data)
                .then((response) => {
                    ctx.commit('setProcess', false)
                    if (response.data.error) {
                        ctx.commit('putError', api_url + ' : ' + response.data.error)
                    } else {
                        console.log(response.data)
                        if (fn.then) {
                            fn.then(response.data)
                        }
                    }

                })
                .catch((error) => {
                    ctx.commit('setProcess', true)
                    console.log(error)
                    ctx.commit('putError', '[axios]' + api_url + ' : ' + error)
                })
        },

        sendToDebug(ctx, fn) {
            this.dispatch('apiQuery', {
                url: 'debug:save',
                data: {name: fn.name, data: fn.data}
            })
        },

        loadAgreement(ctx) {
            let agreement_html = ctx.getters.getAgreementHTML
            if (agreement_html) {
                return
            }
            ctx.dispatch('apiQuery', {
                url: 'service:getAgreement',
                then: response => {
                    console.log('Заполнил agreement')
                    ctx.commit('setAgreementHTML', response.html)
                }
            })
        }
    },

    mutations: {
        setAgreementHTML(state, value) {
            state.agreement_html = value
        },
        setSettings(state, value) {
            state.settings = value
        },
        setListType(state, value) {
            state.list_type = value
        },
        setWidth(state, value) {
            state.width = value
        },
        putError(state, value) {
            state.errors.push(value)
        },
        setProcess(state, value) {
            state.process = value
        }
    },

    getters: {
        getAgreementHTML(state) {
            return state.agreement_html
        },
        getSettings(state) {
            return state.settings
        },
        getListType(state) {
            return state.list_type
        },
        getWidth(state) {
            return state.width
        },
        getErrors(state) {
            return state.errors
        },
        getProcess(state) {
            return state.process
        }
    }
})
