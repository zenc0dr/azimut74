<template>
    <div class="circle-rating"
        :class="{'hover':hoverRating}"
    >
        <div
            v-for="i in maxRating"
            :key="i"
            class="circle "
            :class="{
                filled: i <= currentRating,
                active: i <= hoverRating 
            }"
            @mouseover="hoverRating = i"
            @mouseout="hoverRating = 0 "
            @click="setCurrentRating(i)"
        ></div>
        <div class="rating-text">{{ getRatingDescription() }}</div>
    </div>
</template>

<script>
import {ref, watch} from 'vue';

export default {
    props: {
        maxRating: {
            type: Number,
            required: true,
            default: 5
        },
        modelValue: {
            type: Number,
            required: true
        }
    },
    setup(props, {emit}) {
        const currentRating = ref(props.modelValue);
        const hoverRating = ref(0);

        const setCurrentRating = (rating) => {
            currentRating.value = rating;
        };

        // Отслеживаем изменения currentRating и эмитируем обновление modelValue
        watch(
            () => currentRating.value,
            (newValue) => emit('update:modelValue', newValue)
        );

        const getRatingDescription = () => {
            if (hoverRating.value > 0) {
                return ratings[hoverRating.value - 1];
            } else {
                return ratings[currentRating.value - 1];
            }
        };

        const ratings = [
            'Ужасно',
            'Плохо',
            'Неплохо',
            'Хорошо',
            'Отлично'
        ];

        return {
            currentRating,
            hoverRating,
            setCurrentRating,
            getRatingDescription,
            ratings
        };
    }
};
</script>


<style lang="scss">

$circle-main-color: #e0292c;
.circle-rating {
    display: flex;
    align-items: center;
    &.hover {
        .circle.filled.active {
            background-color: $circle-main-color;
        }
         .circle.filled:not(.active) {
            background-color: transparent;
        }
    }

    .circle {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        margin-right: 5px;
        background-color: transparent;
        border: 2px solid $circle-main-color;
        @media screen and (max-width: 460px) {
            width: 35px;
            height: 35px;
            margin-right: 10px;
        }
    }

    .filled,
    .active {
        background-color: $circle-main-color;
    }
 

    .rating-text {
        margin-left: 18px;
        font-style: italic;
        opacity: .7;
        @media screen and (max-width: 768px) {
            margin-left: 10px;
            font-size: 14px
        }
    }
}

</style>
