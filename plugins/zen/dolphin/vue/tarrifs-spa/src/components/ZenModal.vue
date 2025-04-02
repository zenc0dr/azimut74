<template>
    <transition name="zen-fade">
        <div v-show="show" class="zen-modal" :style="animationDuration"> <!-- @click.self="closeModal" -->
            <transition name="zen-door">
                <div v-show="show" class="zen-modal-body" :style="animationDuration">
                    <div class="zen-modal-header">
                        <div class="zen-modal-title">
                            {{ title }}
                        </div>
                        <div class="zen-modal-close" @click="closeModal"></div>
                    </div>
                    <div class="zen-modal-content">
                        <slot></slot>
                    </div>
                </div>
            </transition>
        </div>
    </transition>
</template>
<script>
    export default {
        name:'ZenModal',
        props: {
            show: {
                type: Boolean,
                required: true
            },
            title: {
                type: String,
                default: ''
            },
            transition: {
                type: Number,
                default: 200
            }
        },
        watch: {
            show(isShow){
                if(isShow) {
                    document.body.style.overflowY = 'hidden'
                } else {
                    document.body.style.overflowY = 'auto'
                }
            }
        },
        computed: {
            animationDuration()
            {
                return {
                    animationDuration: `${this.transition}ms`
                }
            }
        },
        methods: {
            closeModal() {
                this.$emit('hide')
            }
        }
    }
</script>
<style>
    /* Zen Modal */
    .zen-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 100;
        overflow-y: auto;
    }

    .zen-modal-body {
        margin: 0 auto;
        margin-top: 20px;
        width: 90%;
        min-height: 400px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 1px 7px 16px 0 rgba(0, 0, 0, 0.45);
    }

    .zen-modal-header {
        display: flex;
        justify-content: space-between;
        padding: 15px 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #d7d7d7;
    }

    .zen-modal-title {
        font-size: 20px;
        color: #616161;
    }

    .zen-modal-close {
    }

    .zen-modal-close {
        cursor: pointer;
        padding: 0;
        float: right;
        font-size: 24px;
        background: #f2f2f2;
        width: 25px;
        height: 25px;
        border-radius: 3px;
        color: #878787;
    }

    .zen-modal-close::after {
        content: 'x';
        position: absolute;
        margin-left: 7px;
        margin-top: -4px;
    }

    .zen-modal-close:hover {
        background: #ff2d2d;
        color: #fff;
    }

    .zen-modal-content {
        padding: 15px;
    }

    /* -- fade -- */
    @-webkit-keyframes zen-fade-enter {
        from {
            opacity: 0;
        }
    }

    @keyframes zen-fade-enter {
        from {
            opacity: 0;
        }
    }

    .zen-fade-enter-active {
        -webkit-animation: zen-fade-enter both ease-in;
        animation: zen-fade-enter both ease-in;
    }

    @-webkit-keyframes zen-fade-leave {
        to {
            opacity: 0
        }
    }

    @keyframes zen-fade-leave {
        to {
            opacity: 0
        }
    }

    .zen-fade-leave-active {
        -webkit-animation: zen-fade-leave both ease-out;
        animation: zen-fade-leave both ease-out;
    }

    /* -- door -- */
    @-webkit-keyframes zen-door-enter {
        from {
            -webkit-transform: scale3d(0, 1, 1);
            transform: scale3d(0, 1, 1);
        }
    }

    @keyframes zen-door-enter {
        from {
            -webkit-transform: scale3d(0, 1, 1);
            transform: scale3d(0, 1, 1);
        }
    }

    .zen-door-enter-active {
        -webkit-animation: zen-door-enter both cubic-bezier(0.4, 0, 0, 1.5);
        animation: zen-door-enter both cubic-bezier(0.4, 0, 0, 1.5);
    }

    @-webkit-keyframes zen-door-leave {
        60% {
            -webkit-transform: scale3d(.01, 1, 1);
            transform: scale3d(.01, 1, 1);
        }

        to {
            -webkit-transform: scale3d(0, 1, .1);
            transform: scale3d(0, 1, .1);
        }
    }

    @keyframes zen-door-leave {
        60% {
            -webkit-transform: scale3d(.01, 1, 1);
            transform: scale3d(.01, 1, 1);
        }

        to {
            -webkit-transform: scale3d(0, 1, .1);
            transform: scale3d(0, 1, .1);
        }
    }

    .zen-door-leave-active {
        -webkit-animation: zen-door-leave both;
        animation: zen-door-leave both;
    }
</style>
