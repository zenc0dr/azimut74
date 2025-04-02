export default {
    state: {
        token: null,       // Токен текущего запроса
        atm_db: null,      // Поисковая база данных
        last_query: null,  // Последний запрос

        /* ======= Опции полей ввода ========= */
        allowed_dates: [],     // Допустимые даты (получаются при загрузке)
        allowed_hotels: [],    // Допустимые гостиницы
        allowed_comforts: [],  // Допустимые удобства
        allowed_to_sea: [],    // Допустимые расстояния до моря
        allowed_services: [],  // Допустимые услуги (Инфраструктура гостиницы)
        allowed_pansions: [],  // Допустимые типы питания

        /* ======= Выбранные данные ========= */
        dates: null,         // Выбранные даты
        geo_objects: null,  // Выбранные гео-объекты
        hotels: [],         // Выбранные гостиницы
        comforts: [],       // Выбранные удобства
        to_sea: [],       // Выбранные расстояния до моря
        services: [],     // Выбранные услуги (Инфраструктура гостиницы)
        pansions: [],     // Выбранные типы питания

        adults: 2,        // Взрослых
        childrens: 0,        // Детей
        children_ages: [],   // Возрасты детей

        /* Заказ - бронирование */
        order: null,
    },

    actions: {
        fetchAtmAllowedDates(ctx, fn) {  // Запросить допустимые даты
            ctx.dispatch('apiQuery', {
                url: 'atm:allowedDates',
                then: response => {
                    ctx.commit('setAtmAllowedDates', response)
                    if (fn.then) {
                        fn.then(response)
                    }
                }
            })
        },

        makeAtmHash(ctx) {
            if (location.pathname.indexOf('/atm/') == -1) {
                return
            }

            let geo_objects = ctx.getters.getAtmGeoObjects
            let dates = ctx.getters.getAtmDates
            let adults = ctx.getters.getAtmAdults
            let childrens = ctx.getters.getAtmChildrenAges

            let hash = ''
            if (geo_objects && geo_objects.length) {
                hash += 'g=' + geo_objects.join(',')
            }
            if (dates && dates.length) {
                hash += ';d=' + dates.join(',')
            }
            if (adults) {
                hash += ';a=' + adults
            }
            if (childrens && childrens.length) {
                hash += ';ca=' + childrens.join(',')
            }
            let preset = {
                preset: hash,
                type: 'autobus-tours',
                geo: geo_objects
            }
            window.location.hash = preset.preset
            preset = JSON.stringify(preset)
            SearchApi.onSearch(preset) 
        },

        fetchAtmDb(ctx, fn) {
            let dates = ctx.getters.getAtmDates // Выбранные даты
            let geo_objects = ctx.getters.getAtmGeoObjects // Выбранные гео-объекты
            if (!geo_objects || !geo_objects.length) {
                return
            }
            let query = {
                dates: ctx.getters.getAtmDates,
                adults: ctx.getters.getAtmAdults,
                childrens: ctx.getters.getAtmChildrenAges,
            }

            let query_data = {dates, geo_objects, query}

            ctx.dispatch('apiQuery', {
                url: 'atm:db',
                data: query_data,
                then: response => {
                    ctx.commit('setAtmDb', response)
                    if (fn) {
                        fn.then()
                    }
                    ctx.dispatch('makeAtmHash')
                }
            })
        }
    },
    mutations: {
        setAtmDb(state, value) {
            state.atm_db = value
        },

        setAtmAllowedDates(state, value) {
            state.allowed_dates = value
        },
        setAtmDates(state, value) {
            state.dates = value
        },
        setAtmGeoObjects(state, value) {
            state.geo_objects = value
        },

        setAtmAllowedHotels(state, value) {
            state.allowed_hotels = value
        },
        setAtmAllowedComforts(state, value) {
            state.allowed_comforts = value
        },
        setAtmAllowedToSea(state, value) {
            state.allowed_to_sea = value
        },
        setAtmAllowedServices(state, value) {
            state.allowed_services = value
        },
        setAtmAllowedPansions(state, value) {
            state.allowed_pansions = value
        },

        setAtmSelectedHotels(state, value) {
            state.hotels = value
        },
        setAtmSelectedComforts(state, value) {
            state.comforts = value
        },
        setAtmSelectedToSea(state, value) {
            state.to_sea = value
        },
        setAtmSelectedServices(state, value) {
            state.services = value
        },
        setAtmSelectedPansions(state, value) {
            state.pansions = value
        },

        setAtmAdults(state, value) {
            state.adults = value
        },
        setAtmChildrens(state, value) {
            state.childrens = value
        },
        setAtmChildrenAges(state, value) {
            state.children_ages = value
        },
        setAtmLastQuery(state, value) {
            state.last_query = value
        },

        /* Методы заказа и бронирования */
        buildAtmOrder(state, item) {
            let order = {}
            // Собрать данные с формы
            order.scope = 'atp'
            order.tour_name = item.tour_name
            order.tarrif_id = item.tarrif_id
            order.tarrif_name = item.tarrif_name
            order.hotel_id = item.hotel_id
            order.hotel_name = item.hotel_name
            order.date_of = item.date.d1 // Дата начала тура
            order.date_of_dow = item.date.d1d // День недели начала тура
            order.date_to = item.date.d2 // Дата окончания тура
            order.date_to_dow = item.date.d2d // День недели окончания тура
            order.days = item.days
            order.adults = state.adults
            order.childrens = state.childrens
            order.children_ages = state.children_ages
            order.price = item.price.sum

            state.order = order
        },

        setAtmOrder(state, value) {
            state.order = value
        },
    },
    getters: {
        getAtmDb(state) {
            return state.atm_db
        },

        getAtmAllowedDates(state) {
            return state.allowed_dates
        },

        getAtmDates(state) {
            return state.dates
        },

        getAtmGeoObjects(state) {
            return state.geo_objects
        },

        getAtmAllowedHotels(state) {
            return state.allowed_hotels
        },

        getAtmAllowedComforts(state) {
            return state.allowed_comforts
        },

        getAtmAllowedToSea(state) {
            return state.allowed_to_sea
        },

        getAtmAllowedServices(state) {
            return state.allowed_services
        },

        getAtmAllowedPansions(state) {
            return state.allowed_pansions
        },

        getAtmSelectedHotels(state) {
            return state.hotels
        },

        getAtmSelectedComforts(state) {
            return state.comforts
        },

        getAtmSelectedToSea(state) {
            return state.to_sea
        },

        getAtmSelectedServices(state) {
            return state.services
        },

        getAtmSelectedPansions(state) {
            return state.pansions
        },

        getAtmAdults(state) {
            return state.adults
        },

        getAtmChildrens(state) {
            return state.childrens
        },

        getAtmChildrenAges(state) {
            return state.children_ages
        },

        getAtmLastQuery(state) {
            return state.last_query
        },

        getAtmOrder(state) {
            return state.order
        }
    }
}
