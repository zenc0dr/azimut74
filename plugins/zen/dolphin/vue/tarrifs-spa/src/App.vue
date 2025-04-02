<template>
  <div id="app">
    <meta v-if="env == 'dev'" id="tour_id" content="4">
    <link v-if="env == 'dev'" rel="stylesheet" :href="domain+'/zen/dolphin/api/service:backendCss'">

    <div class="tarrifs-panel">
      <div @click="openTarrif(null)" class="btn btn-primary oc-icon-plus">Создать тариф</div>
      <div v-if="checked.length" @click="deleteTarrifs()" class="btn btn-danger oc-icon-remove">Удалить</div>
    </div>

    <ocv-list :records="records" @recordClick="openTarrif" @onChecked="onChecked" />
      <zen-modal :title="modalTitle" :show="show_modal" @hide="closeTarrif">
        <template v-if="tarrif">

          <div class="form-group span-left">
            <label>Название тарифа</label>
            <input v-model="tarrif.name" class="form-control">
          </div>

          <div class="form-group span-right">
            <label>Название номера</label>
            <input v-model="tarrif.number_name" class="form-control">
          </div>

          <zen-select label="Туроператор" v-model="tarrif.operator" span="left" />
          <zen-select label="Тип питания" v-model="tarrif.pansion" span="right" />
          <zen-select :update-options="updateHotelsOptions" label="Гостиница" v-model="tarrif.hotel" span="left" />
          <zen-select label="Комфортность номера" v-model="tarrif.comfort" span="right" />

          <div class="form-group span-left">
              <zen-select label="Скидки" v-model="tarrif.reduct" />
          </div>
          <div class="form-group span-right">
            <template v-if="tarrif.reduct.value">
              <label>Даты действия скидки</label>
              <reduct-dates :dates="tarrif.dates" :reduct-dates="tarrif.reduct_dates" @selectDate="selectReductDate" />
            </template>
          </div>

          <zen-dates v-model="tarrif.dates"
                     :prices="tarrif.prices"
                     :select-date="date"
                     @change="changeDates"/>
          <zen-tabs v-model="tarrif.dates" :prices="tarrif.prices" @select="selectDate" />

          <zen-prices :date="date"
                      @update="updatePrices"
                      :prices="tarrif.prices"
                      :azrooms="tarrif.azrooms"
                      :pricetypes="tarrif.pricetypes" />
          <div class="submit-panel">
            <button @click="deleteTarrif" type="button" class="btn btn-danger oc-icon-trash btn-lg">
              Удалить
            </button>
            <button @click="saveTarrif" type="button" class="btn btn-success oc-icon-save btn-lg">
              Сохранить
            </button>
          </div>
        </template>
      </zen-modal>
    <zen-alert v-model="alerts" @showed="alerts = []"/>
    <link rel="stylesheet" :href="domain+'/plugins/zen/dolphin/assets/css/zen.dolphin.tarrifs-spa.css'">
  </div>
</template>

<script>
  import ReductDates from "./components/ReductDates";
  import ZenPrices from "./components/ZenPrices";
  import ZenSelect from "./components/ZenSelect";
  import ZenModal from "./components/ZenModal";
  import ZenAlert from "./components/ZenAlert";
  import ZenDates from "./components/ZenDates";
  import OcvList from "./components/OcvList";
  import ZenTabs from "./components/ZenTabs";

  export default {
    name: 'App',
    components: {
      ReductDates,
      ZenPrices,
      ZenSelect,
      ZenModal,
      ZenAlert,
      ZenDates,
      OcvList,
      ZenTabs
    },
    data() {
      return {
        records: [],
        show_modal: false,
        tarrif: null,
        alerts: [],
        date: null,
        checked: [],
      }
    },
    mounted(){
      this.$store.commit('setTour', this.tour)
      this.$store.commit('setDomain', this.domain)
      this.getRecordsList()
    },
    computed: {
      env() {
        if(location.port == "8080") return 'dev'
      },
      domain()
      {
        return location.origin.replace(':8080', '')
      },
      tour()
      {
        return document.getElementById('tour_id').getAttribute('content')
      },
      tarrif_id()
      {
        return this.$store.getters.gettariffId
      },
      modalTitle()
      {
        if(this.tarrif)
        {
          let tariff_id = this.tarrif_id
          let badge = (tariff_id) ? ' [id:'+tariff_id+']' : ' [новый]'

          return this.tarrif.name + badge
        }
      }
    },
    methods: {
      onChecked(value){
        this.checked = value
      },
      getRecordsList()
      {
        let tour_id = this.tour
        this.$store.dispatch('getTarrifs', {
          tour_id,
          then:(response) => {
            this.records = response
          }
        })
      },
      openTarrif(tarrif_id) {
        this.$store.dispatch('openTarrif', {
          tarrif_id,
          then:(response) => {
            this.tarrif = response
            this.show_modal = true
          }
        })
      },
      saveTarrif() {
        let saveData = {
          name: this.tarrif.name,
          number_name: this.tarrif.number_name,
          hotel_id: this.tarrif.hotel.value,
          operator_id: this.tarrif.operator.value,
          pansion_id: this.tarrif.pansion.value,
          comfort_id: this.tarrif.comfort.value,
          prices: this.tarrif.prices,
          reduct_id: this.tarrif.reduct.value,
          reduct_dates: this.tarrif.reduct_dates,
        }
        let validate = true

        if(!saveData.hotel_id) {
          this.alerts.push({text:'Необходимо выбрать гостиницу', type:'danger'})
          validate = false
        }

        if(!saveData.operator_id) {
          this.alerts.push({text:'Необходимо выбрать туроператор', type:'danger'})
          validate = false
        }

        if(!saveData.comfort_id) {
          this.alerts.push({text:'Необходимо выбрать комфортность номера', type:'danger'})
          validate = false
        }

        if(!saveData.pansion_id) {
          this.alerts.push({text:'Необходимо выбрать тип питания', type:'danger'})
          validate = false
        }

        if(!validate) return


        this.$store.dispatch('saveTarrif', {
          saveData,
          then:(response) => {
            this.alerts = [{text:response.alert.text, type:response.alert.type}]
            this.getRecordsList()
          }
        })

      },
      deleteTarrif()
      {
        this.$store.dispatch('apiQuery', {
          url:'tarrifs:deleteTarrif',
          data: {
            tarrif_id:this.tarrif_id
          },
          then:(response)=> {
            this.alerts = [{text:response.alert.text, type:response.alert.type}]
            this.closeTarrif()
            this.getRecordsList()
          }
        })
      },
      deleteTarrifs() {
        if(!this.checked.length) return
        this.$store.dispatch('apiQuery', {
          url:'tarrifs:deleteTarrifs',
          data: {
            tarrif_ids:this.checked
          },
          then:(response)=> {
            this.alerts = [{text:response.alert.text, type:response.alert.type}]
            this.getRecordsList()
          }
        })
      },
      closeTarrif() {
        this.show_modal = false
        this.tarrif = null
        this.date = null
      },
      updatePrices(prices_data)
      {
        this.tarrif.prices[prices_data.date] = prices_data.prices
      },
      changeDates(dates)
      {
        dates.sort(function (a, b) {
          let date_a = new Date(a.replace( /(\d{2})\.(\d{2})\.(\d{4})/, "$2/$1/$3"))
          let date_b = new Date(b.replace( /(\d{2})\.(\d{2})\.(\d{4})/, "$2/$1/$3"))
          return date_a - date_b
        })

        for(let i in this.tarrif.prices) {
          if(dates.indexOf(i) === -1) delete this.tarrif.prices[i]
        }

        this.tarrif.dates = dates
      },

      // Выбор даты в компоненте ZenTabs
      selectDate(date)
      {
        this.date = date
      },

      updateHotelsOptions(search_text)
      {
        if(search_text.length < 3) return

        this.$store.dispatch('apiQuery', {
          url:'tarrifs:hotels',
          data: {search_text},
          then:(response)=> {
            if(!response) return;
            this.tarrif.hotel.options = response
          }
        })
      },
      selectReductDate(date)
      {
        if(!this.tarrif.reduct_dates) this.tarrif.reduct_dates = []
        if(this.tarrif.reduct_dates.indexOf(date) !== -1) {
          let delete_key = this.tarrif.reduct_dates.indexOf(date)
          this.tarrif.reduct_dates.splice(delete_key, 1)
        } else {
          this.tarrif.reduct_dates.push(date)
        }
      }
    }
  }
</script>
<style>
  @import "~vue-select/dist/vue-select.css";
</style>
