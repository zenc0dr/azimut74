<template>
      <div class="widget-content__item widget-contet__item-favorite"
      :class="{'widget-content__item-active':activeMenu==1}">
      <div class="widget-content__item-button widget-content__item-button-first"
         title="Избранное"
          @click="switchMenu(1)">
         <img v-if="mobile == false" src="/plugins/zen/history/assets/images/icons/heart.svg" alt="Избранное">
         <img v-else src="/plugins/zen/history/assets/images/icons/heart-white.svg" alt="Избранное">
         <div v-if="favorite.items && favorite.items.length" class="widget-content__item-button__unseen"></div>
      </div>
      <div class="widget-content__item-content">
         <div class="widget-content__content-tab">
               <div class="widget-content__content-tab-title">
                  <div>Избранное {{ titleCountFavorite }}</div>
                  <div v-if="favorite.items && favorite.items.length"
                     class="remove-all" @click="removeFavAll()">
                     <img src="/plugins/zen/history/assets/images/icons/trash.svg" alt="удалить все">
                  </div>
               </div>
               <div class="widget-content__content-tab-message">
                  <div v-if="favorite.items && favorite.items.length" class="widget-content__content-tab-items">
                     <section v-for="(item, index) in favorite.items.slice(0, favorite.maxPrevItem)" class="widget-content__content-item widget-content__content-item__favorite">
                           <a :href="item.url" target="_blank" :title="item.title">
                              <div class="widget-content__content-item-left">
                                 <div class="index">{{index+1}}. </div>
                                 <div class="title-days">
                                       <div class="title">{{item.title}}</div>
                                       <div class="transform-data">
                                          <div class="transform-data__item transform-data__days"
                                                v-if="item.days">
                                                <img src="/plugins/zen/history/assets/images/icons/calendar.svg" alt="icon-calendar">
                                                {{item.days}}
                                          </div>
                                          <template v-if="item.other">
                                             <div class="transform-data__item transform-data__waybill"
                                                   v-if="item.other.waybill">
                                                   <img src="/plugins/zen/history/assets/images/icons/waybill.svg" alt="icon-calendar">
                                                   <span v-html="item.other.waybill"></span>
                                             </div>
                                          </template>
                                       </div>
                                 </div>
                              </div>
                           </a>
                           <button @click="deleteFavoriteItem(index)" class="delete" title="удалить">
                               <img src="/plugins/zen/history/assets/images/icons/close.svg" alt="удалить">
                            </button>
                     </section>
                     <div v-if="favorite.items.length > favorite.maxPrevItem" class="widget-content__content-tab__buttonwrap">
                           <button class="widget-content__content-tab-button" @click="favorite.showModal=true">
                              <div class="widget-content__content-tab-button__text">Показать еще</div>
                           </button>
                     </div>
                  </div>
                  <div v-else>
                     <section class="history-request--small">
                           <div class="history-lk-more">
                              <div>
                                 В этом списке пока пусто. Коснитесь <img src="/plugins/zen/history/assets/images/icons/heart.svg" alt="Избранное"> рядом с понравившимся круизом или туром, чтобы добавить его сюда
                              </div>
                           </div>
                     </section>
                  </div>
               </div>
         </div>
      </div>
      <Modal
            :show="favorite.showModal"
            :max-width="600"
            @close="favorite.showModal = false"
            title="Избранное"
            v-if="favorite.items"
        >
            <div class="modal-content">
                <div v-if="favorite.items.length" class="modal-content__items">
                    <section v-for="(item, index) in favorite.items" class="modal-content__item modal-content__item__favorite">
                        <a :href="item.url" target="_blank" :title="item.title">
                            <div class="modal-content__item-left">
                                <div class="modal-content__item-index">{{index+1}}. </div>
                                <div class="modal-content__item-title-days">
                                    <div class="modal-content__item-title">{{item.title}}</div>
                                       <div class="transform-data">
                                          <div class="transform-data__item transform-data__days"
                                                v-if="item.days">
                                                <img src="/plugins/zen/history/assets/images/icons/calendar.svg" alt="icon-calendar">
                                                {{item.days}}
                                          </div>
                                          <template v-if="item.other">
                                             <div class="transform-data__item transform-data__waybill"
                                                   v-if="item.other.waybill">
                                                   <img src="/plugins/zen/history/assets/images/icons/waybill.svg" alt="icon-calendar">
                                                   <span v-html="item.other.waybill"></span>
                                             </div>
                                          </template>
                                       </div>
                                </div>
                            </div>
                            <div class="modal-content__item-right">
                                <div class="modal-content__item-date">{{item.date}}</div>
                            </div>
                        </a>
                        <button @click="deleteFavoriteItem(index)"
                            title="удалить" class="delete">
                            <img src="/plugins/zen/history/assets/images/icons/close.svg" alt="удалить">
                        </button>
                    </section>
                </div>
                <div v-else>
                    <section class="modal-content--small">
                        <div class="modal-content-more">
                            <div>
                                В этом списке пока пусто. Коснитесь <img src="/plugins/zen/history/assets/images/icons/heart.svg" alt="Избранное"> рядом с понравившимся круизом или туром, чтобы добавить его сюда
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
   name: 'FavoriteHistory',
    components: {Modal},
    data() {
        return {
            mobile: false
        }
    },
    props: {
        favorite: {
            type: Object,
            required: true,
            default: {
                items: [],
                maxPrevItem: 3,
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
      titleCountFavorite() {
         if (this.favorite.items) {
            if (this.favorite.items.length > this.favorite.maxPrevItem ) {
               return `${this.favorite.maxPrevItem} из ${this.favorite.items.length}`
            }
         }
         return
      }
    },
    methods: {
      switchMenu(id) {
         this.$emit('switch-menu',id)
      },
      deleteFavoriteItem(index) {
         let item = this.favorite.items[index]
         item.index = index
         APP.clickFavorite(null, item)
      },
      removeFavAll() {
         this.$emit('remove-all')
      },
    }
}
</script>
