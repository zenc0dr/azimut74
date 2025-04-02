export default {
    state: {
        geo_tree: []
    },

    actions: {
        // Запросить дерево гео-объектов
        fetchGeoTree(ctx, fn) {
            let data = {
                widget: 'atm', // ext || atm
                dates: (fn.dates) ? fn.dates : null
            }

            ctx.dispatch('apiQuery', {
                data,
                url: 'store:geoTree',
                then: (response) => {
                    ctx.commit('setGeoTree', response) // Установить объекты в state.geo_tree
                    if (fn.then) {
                        fn.then(response)
                    }
                }
            })
        }
    },

    mutations: {
        setGeoTree(state, value) // Установить гео.объекты
        {
            state.geo_tree = value
        },
    },

    getters: {
        getGeoTree(state) {
            return state.geo_tree
        }
    }
}
