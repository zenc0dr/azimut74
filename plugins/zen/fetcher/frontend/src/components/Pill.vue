<template>
    <div v-if="value" class="pill">
        <div class="pill__name">
            {{ name }}
        </div>
        <div class="pill__value">
            {{ value }}
        </div>
    </div>
</template>

<script>
export default {
    name: "Pill",
    props: {
        name: {
            default: null
        },
        value: {
            default: null
        },
    }
}
</script>

<style lang="scss">
.pill {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    border-radius: 5px;
    overflow: hidden;

    &__name {
        padding: 3px;
        font-weight: bold;
        color: #555c68;
        background: #e0e0e0;
        padding-left: 7px;
    }
    &__value {
        padding: 3px;
        background: #f4f4f4;
        flex: 1 0 0;
        padding-left: 9px;
    }
}
</style>
