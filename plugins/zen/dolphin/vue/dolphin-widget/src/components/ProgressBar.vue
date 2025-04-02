<template>
        <div class="dw-progress-bar">
            <div class="dwpb-body" :class="{process:process}">
                <div v-if="percent" :style="'width:'+percent+'%'" class="dwpb-line">{{ of }} из {{ to }}</div>
            </div>
        </div>
</template>
<script>
    export default {
        props: ['of', 'to', 'process'],
        watch: {
            of()
            {
                this.percent = Math.round((this.of*100)/this.to)
            }
        },
        data()
        {
            return {
                percent:null
            }
        }
    }
</script>
<style>
    /* Progress Bar */
    .dw-progress-bar {
        padding: 0 8px;
        margin-top: 15px;
    }

    .dwpb-body {
        display: inline-block;
        width: 100%;
        background-color: #62aae6;
        height: 25px;
    }

    .dwpb-line {
        display: inline-block;
        background: #bfe2ff;
        height: inherit;
        text-align: right;
        font-size: 12px;
        padding-top: 4px;
        padding-right: 9px;
        padding-left: 9px;
        color: #43637f;
        transition: 300ms;
        white-space: nowrap;
    }

    .dwpb-body.process {
        background-image: url(/plugins/zen/dolphin/assets/images/orange_line.png);
        animation: walk 10s linear infinite;
    }

    @keyframes walk {
        from {
            background-position: -1000px 100%, 0 0;
        }

        to {
            background-position: 0 100%, 0 0;
        }
    }

    .dw-date-picker .mx-datepicker {
        width: 187px;
    }
</style>
