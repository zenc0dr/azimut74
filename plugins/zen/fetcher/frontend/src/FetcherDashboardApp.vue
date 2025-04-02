<template>
    <div class="fetcher-dashboard">
        <div v-if="pools !== null" class="fetcher-dashboard__pools">
            <div v-for="pool in pools" class="fetcher-dashboard__pool">
                <div class="fetcher-dashboard__pool__code">
                    {{ pool.name }} ({{ pool.code }})
                </div>
                <div class="fetcher-dashboard__pool__controls">
                    <button
                        @click="poolSwitch(pool)"
                        :class="pool.in_process ? 'rotator' : null"
                        class="parser-btn btn btn-primary oc-icon-refresh">
                        {{ pool.in_process ? 'Остановить' : 'Запустить' }}
                    </button>
                    <button
                        v-if="!pool.in_process && (pool.states && pool.states.length)"
                        @click="truncatePool(pool)"
                        class="parser-btn btn btn-primary oc-icon-trash">
                        Очистить
                    </button>
                </div>
                <div v-if="pool.states" class="fetcher-dashboard__states">
                    <div v-for="state in pool.states" class="fetcher-dashboard__states__state">
                        <Pill name="Процесс" :value="state.batch_handler"/>
                        <Pill name="Поток" :value="state.process ? 'Да' : 'Нет'"/>
                        <Pill name="Время начала процесса" :value="state.time_start"/>
                        <Pill name="Время последней операции" :value="state.time_latest"/>
                        <Pill v-if="state.batches_processed_count"
                              name="Прогресс"
                              :value="state.batches_processed_count"
                        />
                        <Pill name="Время завершения процесса" :value="state.time_end"/>
                        <div v-if="state.error" class="fetcher-dashboard__states__error">
                            {{ state.error_time }}: {{ state.error }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Pill from "./components/Pill";
export default {
    name: "FetcherDashBoardApp",
    components: {
        Pill
    },
    mounted() {
        this.stateRotation()
    },
    data() {
        return {
            pools: null,
        }
    },
    methods: {
        stateRotation() {
            if (!document.getElementById('FetcherDashBoardApp')) {
                return
            }
            this.getStates()
            setTimeout(() => {
                this.stateRotation()
            }, 3000)
        },
        getStates() {
            Fetcher.api({
                url: '/fetcher/api/streams.State:all',
                then: response => {
                    this.pools = response.pools
                }
            })
        },
        poolSwitch(pool) {
            let method = pool.in_process ? 'stopPool' : 'runPool';
            console.log(method)
            Fetcher.api({
                url: '/fetcher/api/streams.Dispatcher:' + method,
                data: {
                    pool_code: pool.code
                }
            })
        },
        truncatePool(pool)
        {
            Fetcher.api({
                url: '/fetcher/api/streams.Dispatcher:truncatePool',
                data: {
                    pool_code: pool.code
                }
            })
        }
    }
}
</script>

<style lang="scss">
.fetcher-dashboard {

    &__pools {
        display: flex;
        flex-wrap: wrap;
        flex-direction: row;
    }

    &__pool {
        border: 1px solid #dedede;
        padding: 15px;
        border-radius: 5px;
        background: #fff;
        width: 600px;

        &__code {
            font-size: 21px;
            color: #424465;
            font-weight: bold;
            background: #f2f2f2;
            padding: 5px 12px;
            border-radius: 5px;
            padding-bottom: 8px;
        }

        &__controls {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin: 8px 0;
        }
    }

    &__states {
        display: flex;
        flex-direction: column;
        margin-top: 5px;

        &__error {
            background: red;
            color: #fff;
            padding: 10px;
        }

        &__state {
            padding-top: 6px;
            margin-bottom: 14px;
        }
    }
    .rotator::before {
        animation-name: rotation;
        animation-duration: 1s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
    }

    @keyframes rotation {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
}
</style>
