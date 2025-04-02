import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    tours: [],
    tour_id: null,
    tarrif_id: null,
    domain: '',
  },
  mutations: {
    setDomain(state, value)
    {
      state.domain = value
    },
    setTour(state, value)
    {
      state.tour_id = value
    },
    setTarrifId(state, value)
    {
      state.tarrif_id = value
    }
  },
  actions: {
    // Врап для api
    apiQuery(ctx, opts) // opts = {url:'search:searchStream',data:{}, then:(response)=>{}}
    {
      let domain = this.state.domain
      let data = (opts.data)?opts.data:null
      let api_url = domain+'/zen/dolphin/api/'+opts.url

      console.log(api_url, data)

      axios.post(api_url, data)
          .then((response) => {
            opts.then(response.data)
          })
          .catch((error) => {
            console.log(error)
          })
    },

    getTarrifs(ctx, opts)
    {
      let tour_id = opts.tour_id

      ctx.dispatch('apiQuery', {
        url:'tarrifs:records',
        data: {tour_id},
        then:(response)=> {
          opts.then(response)
        }
      })
    },

    // Открытие/создание тарифа
    openTarrif(ctx, opts)
    {

      let tour_id = this.state.tour_id
      let tarrif_id = opts.tarrif_id

      ctx.commit('setTarrifId', tarrif_id)

      ctx.dispatch('apiQuery', {
        url:'tarrifs:open',
        data: {
          tour_id,
          tarrif_id
        },
        then:(response)=>{
          opts.then(response)
        }
      })
    },

    // Сохранение/Обновление тарифа
    saveTarrif(ctx, opts) {

      let tour_id = this.state.tour_id
      let tarrif_id = this.state.tarrif_id

      ctx.dispatch('apiQuery', {
        url:'tarrifs:save',
        data: {
          tour_id,
          tarrif_id,
          data: opts.saveData,
        },
        then:(response)=>{

          if(!tarrif_id && response.tarrif_id) {
            ctx.commit('setTarrifId', response.tarrif_id)
          }
          opts.then(response)
        }
      })
    }
  },
  getters: {
    gettariffId(state)
    {
      return state.tarrif_id
    }
  }
})
