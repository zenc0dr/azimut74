<template>
    <div v-if="reallyShow" class="modal zen-modal-pro" style="display:block">
        <div class="modal-dialog" :style="`max-width:${maxWidth}px`">
            <div class="modal-content">
                <template v-if="loaded || loaded === null">
                    <div class="modal-header d-flex">
                        <div v-if="title" class="fs-3 fw-boldest">{{ title }}</div>
                        <slot name="header"></slot>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary zen-modal-pro-close" @click="closeModal">
                        <span class="svg-icon svg-icon-2x">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path opacity="0.25" fill-rule="evenodd" clip-rule="evenodd" d="M2.36899 6.54184C2.65912 4.34504 4.34504 2.65912 6.54184 2.36899C8.05208 2.16953 9.94127 2 12 2C14.0587 2 15.9479 2.16953 17.4582 2.36899C19.655 2.65912 21.3409 4.34504 21.631 6.54184C21.8305 8.05208 22 9.94127 22 12C22 14.0587 21.8305 15.9479 21.631 17.4582C21.3409 19.655 19.655 21.3409 17.4582 21.631C15.9479 21.8305 14.0587 22 12 22C9.94127 22 8.05208 21.8305 6.54184 21.631C4.34504 21.3409 2.65912 19.655 2.36899 17.4582C2.16953 15.9479 2 14.0587 2 12C2 9.94127 2.16953 8.05208 2.36899 6.54184Z" fill="#12131A"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.29289 8.29289C8.68342 7.90237 9.31658 7.90237 9.70711 8.29289L12 10.5858L14.2929 8.29289C14.6834 7.90237 15.3166 7.90237 15.7071 8.29289C16.0976 8.68342 16.0976 9.31658 15.7071 9.70711L13.4142 12L15.7071 14.2929C16.0976 14.6834 16.0976 15.3166 15.7071 15.7071C15.3166 16.0976 14.6834 16.0976 14.2929 15.7071L12 13.4142L9.70711 15.7071C9.31658 16.0976 8.68342 16.0976 8.29289 15.7071C7.90237 15.3166 7.90237 14.6834 8.29289 14.2929L10.5858 12L8.29289 9.70711C7.90237 9.31658 7.90237 8.68342 8.29289 8.29289Z" fill="#12131A"></path>
                            </svg>
                        </span>
                        </div>
                    </div>
                    <div class="modal-body">
                        <slot></slot>
                    </div>
                    <div v-if="$slots.footer" class="modal-footer">
                        <slot name="footer"></slot>
                    </div>
                </template>
                <template v-else>
                    <div class="modal-body">
                        <div class="zen-modal-pro-preloader">
                            <img src="/assets/media/preloaders/cubesline.gif">
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name:'Modal',
    props: {
        show: {
            type: Boolean,
            required: true,
        },
        title: {
            type: String,
            default: null
        },
        loaded: {
            type: Boolean,
            default: null
        },
        maxWidth: {
            type: Number,
            default: 800
        }
    },
    data() {
        return {
            reallyShow: false
        }
    },
    watch: {
        show(value) {

            if(!value) {
                this.reallyShow = false
                return
            }

            if(this.loaded === null) {
                this.reallyShow = true
                return
            }

            setTimeout(() => {
                this.reallyShow = true
            }, 300);
        },
        loaded(value) {
            if(value) this.reallyShow = true
        },
        reallyShow(value)
        {
            if (value) {
                $('body').css('overflow-y', 'hidden')
            } else {
                $('body').css('overflow-y', 'auto')
            }
        }
    },
    methods: {
        closeModal() {
            this.reallyShow = false
            this.$emit('close')
        }
    }
}
</script>
<style>
.zen-modal-pro {
    background: #0000009c;
    overflow-y: auto;
    animation: modal_flopped 200ms;
}
.zen-modal-pro .modal-dialog {
    transition: all 300ms ease 0s;
    margin-top: 60px;
}
.zen-modal-pro-preloader {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
}
.zen-modal-pro-close {
    margin-left: auto;
}

/* Animations */
@keyframes modal_flopped {
    from {
        transform: scale(1.3);
    }
    to {
        transform: scale(1);
    }
}
</style>
