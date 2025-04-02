<template>
    <div v-if="show_component" class="banner-section__cruise">
        <div class="banner-section__top">
            Более 3000 рейсов, 80 теплоходов, 300 кают, десятки видов тарифов и скидок.
            Разобраться самому долго и трудно…
        </div>
        <div class="banner-section__center">
            <img class="" src="/plugins/zen/quiz/frontend/assets/images/banner-img.jpg" alt="banner img">
        </div>
        <div class="banner-section__bot">
            <div class="banner-section__bot-text">Не тратьте свое время, мы подберем круиз быстро и с учетом всех
                возможных скидок
            </div>
            <button class="banner-section__btn" @click="openModal">Подберите мне круиз</button>
        </div>
    </div>
    <Modal
        title="Подберите мне круиз"
        :show="open_modal"
        @close="open_modal=false"
        :maxWidth="getModalWidth()"
        :class_ext="'banner-modal'"
    >
        <SectionCruise
            :isModal="true"
            @success="open_modal=false"
        />
    </Modal>
</template>
<script>
import Modal from './Modal';
import SectionCruise from './SectionCruise.vue';

export default {
    name: "Quiz",
    components: {
        Modal,
        SectionCruise
    },
    created() {
        window.QuizApp = this
    },
    mounted() {
        this.checkScreenWidth();
        window.addEventListener('resize', this.checkScreenWidth);
    },
    data() {
        return {
            show_component: false,
            open_modal: false,
            isMobile: false,
        }
    },
    methods: {
        getModalWidth() {
            if (this.isMobile) {
                return '100%';
            }
            return '90%'
        },
        checkScreenWidth() {
            this.isMobile = window.innerWidth < 768;
        },
        openModal() {
            this.open_modal = true
            ym(13605125,'reachGoal','open_kruiz_injector3')
        }
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.checkScreenWidth);
    }
}
</script>
