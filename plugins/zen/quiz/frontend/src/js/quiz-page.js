import {createApp} from 'vue';
import SectionCruise from "../components/SectionCruise.vue";

const app = createApp(SectionCruise, {
    isModal: false
});

app.mount("#QuizPageApp");
