<template>
    <div class="confirm-module">
        <i :class="`bi bi${ confirm ? '-check' : '' }-square`"
           @click="$emit('change', !confirm)">
        </i>
        <div class="confirm-module__text">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "ConfirmModule",
    props: {
        confirm: {
            Type: Boolean,
            default: false
        }
    }
}
</script>

<style lang="scss" scoped>
.confirm-module {
    display: flex;
    margin-top: 15px;
    margin-bottom: 20px;
    justify-content: center;
    align-items: center;
    &__text {
        font-size: 13px;
    }
    i {
        margin-right: 5px;
    }
    span {
        color:#0a53be;
        text-decoration: underline;
        cursor: pointer;
    }
}
</style>
