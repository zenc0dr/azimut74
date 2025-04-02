<template>
    <div v-if="data_loaded" class="info-block">
         <div class="info-block__title">
            Цена тура
         </div>
         <div class="info-block__html">
            <div class="offers">
               <div class="offers__body">
                  <div class="offers__info">
                    <div class="offers__price">
                        {{ order.price|priceFormat }} руб.
                     </div>
                  </div>
                  <div class="offers__booking">
                     <div class="offers__btn" @click="show = true">
                        <span class="text-uppercase">Бронировать</span>
                        <span>Узнать больше</span>
                     </div>
                  </div>
               </div>
            </div>
            <div v-if="false">
               Цена рассчитана при количестве группы 40+4. При другой численности цена может отличаться
            </div>
         </div>
        <BookingApp
            :order="order"
            :show="show"
            @close="show = false"
        />
    </div>
</template>

<script>

import BookingApp from "./gt-booking-app";

export default {
    name: "ExtOffersWidget",
    components: {  BookingApp },
    data() {
        return {
            process: false,
            data_loaded: false,
            show: false,
            order: null,
        }
    },
    mounted()
    {
        this.loadPageData(() => {
            this.data_loaded = true
        })
    },
    filters: {
        priceFormat(value) {
            value += "";
            return value.replace(/(\d{1,3})(?=((\d{3})*)$)/g, "  $1");
        }
    },
    methods: {
        // Загрузка начальных данных
        loadPageData(fn)
        {
            this.order = JSON.parse($('meta[name="order"]').attr('content'))
            fn()
        }
    }
}
</script>
