<template>
   <div class="widget-content__item widget-contet__item-history"
         :class="{'widget-content__item-active':activeMenu==2}"
         v-if="search.items"
         >
            <div class="widget-content__item-button widget-content__item-button-last" title="История просмотра"  @click="switchMenu(2)">
               <img v-if="mobile == false" src="/plugins/zen/history/assets/images/icons/clock-history.svg" alt="icon-clock-history">
                <img v-else src="/plugins/zen/history/assets/images/icons/clock-history-white.svg" alt="icon-clock-history">
               <div v-if="search.items.length" class="widget-content__item-button__unseen"></div>
            </div>
            <div class="widget-content__item-content">
               <div class="widget-content__content-tab">
                     <div class="widget-content__content-tab-title">
                        <div>История поиска {{ titleCountSearch }}</div>
                        <div v-if="search.items && search.items.length"
                           class="remove-all" @click="removeSearchAll()">
                           <img src="/plugins/zen/history/assets/images/icons/trash.svg" alt="удалить историю поиска">
                        </div>
                     </div>
                     <div class="widget-content__content-tab-message">
                        <div v-if="search.items && search.items.length" class="widget-content__content-tab-items">
                           <section v-for="(item, index) in search.items.slice(0, search.maxPrevItem)" class="widget-content__content-item">
                                 <a :href="getUrl(item)" target="_blank" :title="item.title">
                                    <div class="widget-content__content-item-left">
                                       <div class="index">{{index+1}}. </div>
                                       <div class="title-days">
                                             <div class="title">{{item.title}}</div>
                                             <div class="transform-data">
                                                <div class="transform-data__item transform-data__date "
                                                    v-if="item.transform_data.dates &&  item.transform_data.dates.length">
                                                    <img src="/plugins/zen/history/assets/images/icons/calendar.svg" alt="icon-calendar-red">
                                                    <template v-for="(date, index) in item.transform_data.dates">
                                                        {{date}}<template v-if="index != item.transform_data.dates.length - 1">,</template>
                                                        {{index.last}}
                                                    </template>
                                                </div>
                                                <div class="transform-data__item transform-data__days"
                                                    v-if="item.transform_data.days.length">
                                                    <img src="/plugins/zen/history/assets/images/icons/clock-history-red.svg" alt="icon-clock-history-red">
                                                    <template v-if="item.transform_data.days.length < 6">
                                                        Дней: <template v-for="(day, index) in item.transform_data.days">
                                                            {{day}}<template v-if="index != item.transform_data.days.length - 1">,</template>
                                                        </template>
                                                    </template>
                                                    <template v-else>Дней: много</template>
                                                </div>
                                                <div class="transform-data__item transform-data__adults"
                                                    v-if="item.transform_data.adults">
                                                    <img src="/plugins/zen/history/assets/images/icons/person.svg" alt="icon-person">
                                                    Взрослые: {{item.transform_data.adults}}
                                                    <template
                                                    v-if="item.transform_data.childrens &&  item.transform_data.childrens != 0">
                                                    и дети: {{item.transform_data.childrens}}
                                                    </template>
                                                </div>

                                             </div>
                                       </div>
                                    </div>
                                 </a>
                                 <button @click="deleteSearchItem(index)" class="delete" title="удалить">
                                     <img src="/plugins/zen/history/assets/images/icons/close.svg" alt="icon-remove-once">
                                </button>
                           </section>
                           <div v-if="search.items.length > search.maxPrevItem" class="widget-content__content-tab__buttonwrap">
                                 <button class="widget-content__content-tab-button" @click="search.showModal=true">
                                    <div class="widget-content__content-tab-button__text">Показать еще</div>
                                 </button>
                           </div>
                        </div>
                        <div v-else>
                           <section class="history-request--small">
                                 <div class="history-lk-more">
                                    <div>
                                       Похоже, вы ничего не искали в последнее время.
                                    </div>
                                 </div>
                           </section>
                        </div>
                     </div>
               </div>
            </div>

            <Modal
                :show="search.showModal"
                :max-width="600"
                @close="search.showModal = false"
                title="История просмотра"
                v-if="search.items"
            >
                <div class="modal-content">
                    <div v-if="search.items.length" class="modal-content__items">
                        <section v-for="(item, index) in search.items" class="modal-content__item">
                            <a :href="getUrl(item)" target="_blank" :title="item.title">
                                <div class="modal-content__item-left">
                                    <div class="modal-content__item-index">{{index+1}}. </div>
                                    <div class="modal-content__item-title-days">
                                        <div class="modal-content__item-title">{{item.title}}</div>
                                        <div class="transform-data">
                                            <div class="transform-data__item transform-data__date "
                                                v-if="item.transform_data.dates &&  item.transform_data.dates.length">
                                                <img src="/plugins/zen/history/assets/images/icons/calendar.svg" alt="icon-calendar">
                                                <template v-for="(date, index) in item.transform_data.dates">
                                                    {{date}}<template v-if="index != item.transform_data.dates.length - 1">,</template>
                                                    {{index.last}}
                                                </template>
                                            </div>
                                             <div class="transform-data__item transform-data__days"
                                                    v-if="item.transform_data.days.length">
                                                    <img src="/plugins/zen/history/assets/images/icons/clock-history-red.svg" alt="icon-clock-history-red">
                                                    <template v-if="item.transform_data.days.length < 6">
                                                        Дней: <template v-for="(day, index) in item.transform_data.days">
                                                            {{day}}<template v-if="index != item.transform_data.days.length - 1">,</template>
                                                        </template>
                                                    </template>
                                                    <template v-else>Дней: много</template>
                                                </div>
                                            <div class="transform-data__item transform-data__adults"
                                                v-if="item.transform_data.adults">
                                                <img src="/plugins/zen/history/assets/images/icons/person.svg" alt="icon-person">
                                                Взрослые: {{item.transform_data.adults}}
                                                <template
                                                v-if="item.transform_data.childrens &&  item.transform_data.childrens != 0">
                                                и дети: {{item.transform_data.childrens}}
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-content__item-right">
                                    <div class="modal-content__item-date">{{item.date}}</div>
                                </div>
                            </a>
                            <button @click="deleteSearchItem(index)"
                                title="удалить" class="delete">
                                <img src="/plugins/zen/history/assets/images/icons/close.svg" alt="icon-remove-once">
                            </button>
                        </section>
                    </div>
                    <div v-else>
                        <section class="modal-content--small">
                            <div class="modal-content-more">
                                <div>
                                    Похоже, вы ничего не искали в последнее время.
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </Modal>
         </div>
</template>

<script>

import Modal from "@components/modal/Modal";
export default {
    name: 'SearchHistory',
    components: {Modal},
    data() {
        return {
            mobile: false
        }
    },
    props: {
        search: {
            type: Object,
            required: true,
            default: {
                items: [],
                maxPrevItem: 4,
                showModal: false,
            }
        },
        activeMenu: {
            type: Number,
            required: true,
        },

    },
    created() {
        if (window.innerWidth < 992) {
            this.mobile = true
        } else {
            this.mobile = false
        }
    },
    computed: {
      titleCountSearch() {
         if (this.search.items) {
            if (this.search.items.length > this.search.maxPrevItem ) {
               return `${this.search.maxPrevItem} из ${this.search.items.length}`
            }
         }
         return
      },
    },
    methods: {
    getUrl(item) {
        let prefix, url = ''
        let format_url = item.url.replace(/"/g, "'")
        switch(item.transform_data.type) {
            case 'tours':
                prefix = '/ex-tours/ext/search?query='
                url = prefix+item.url
                break
            case 'cruise':
                prefix = '/russia-river-cruises#s='
                 url = prefix+format_url
                break
            case 'group-tours':
                prefix = '/group-tours/shkolnye-tury-v-saratove#s='
                url = prefix+item.url
                break
            case 'sans':
                prefix = '/sans-pages'
                 url = prefix+item.url
                break
        }
        //заменяем ковычки
        return url.replace(/'/g, '"');

      },
        goSearch(preset) {
            SearchApi.search(preset)
        },
      switchMenu(id) {
         this.$emit('switch-menu',id)
      },
      deleteSearchItem(index) {
         let item = this.search.items[index]
         let id = item.id
         APP.api({
            url: '/zen/history/api/History:removeSearchHistoryItems',
            data: {id},
            then: resonse => {
               if (resonse.status) {
                  let indexDel = this.search.items.findIndex(item => item.id == id)
                  this.search.items.splice(indexDel, 1)
                  window.WidgetHistory.favoriteSearch = this.search.items
               }
            }
         })
      },
      removeSearchAll() {
         this.$emit('remove-all')
      },
    }
}
</script>
