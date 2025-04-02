<template>
    <div class="discounts-popup">
        <Modal :show="!!record" @close="$emit('close')" title="Действующие скидки">
            <template v-if="!!record">
                <div v-for="discount in discounts" class="discount-block">
                    <div class="discount-block__header">
                        <img
                            v-if="discount.image" class="discount-block__image"
                            :src="`/storage/app/media/${discount.image}`" alt="Скидка"
                        />
                        <img
                            v-else class="discount-block__image"
                            src="/themes/prokruiz/assets/img/svg/discount.svg" alt="Скидка"
                        />
                        <div class="discount-block__title">
                            {{ discount.title }}
                        </div>
                    </div>
                    <div class="discount-block__desc" v-html="discount.text"></div>
                </div>
            </template>
        </Modal>
    </div>
</template>
<script>
import Modal from "../../vue-components/Modal";
export default {
    name: "DiscountsModal",
    components: { Modal },
    props: {
        record: null
    },
    computed: {
        discounts() {
            let pd = this.record.permanent_discounts
            let td = this.record.temporary_discounts
            let output = []

            if (pd && pd.length) {
                output = output.concat(pd)
            }
            if (td && td.length) {
                output = output.concat(td)
            }

            return output
        }
    }
}
</script>
