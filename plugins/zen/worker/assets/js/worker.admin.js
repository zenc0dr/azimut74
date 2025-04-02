Vue.component('progress-bar', {
    props: ['progress'],
    template:
        '<div v-if="progress" class="ProgressBar">\n' +
        '<div class="progress">\n' +
        '    <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" :style="\'width: \'+progress+\'%;\'">\n' +
        '    </div>\n' +
        '</div>' +
        '</div>'
});

let DolphinAdmin = new Vue({
    el: '#Worker',
    delimiters: ['${', '}'],
    data: {
        rotor: null,
        speed: 3000,
        state: false,
        inputBlock: false,
        process: false,
    },

    mounted() {
        this.getState()
        this.rotorRun()
    },

    methods: {
        sync(method, post, callback) {
            console.log('Запрос: ' + '/zen/worker/api/' + method, post)
            axios.post('/zen/worker/api/' + method, post).then(function (response) {
                if (callback) {
                    callback(response.data)
                }
            })
        },

        getState() {
            this.sync('admin:state', null, (data) => {
                if (data) {
                    this.state = data.state
                    this.process = data.process
                }
            })
        },

        getProgress(pool) {
            return (pool.progress_to * 100) / pool.progress_of
        },

        rotorRun() {
            if (this.rotor != null) {
                return
            }
            this.rotor = setInterval(() => {
                this.getState();
            }, this.speed);
        },

        go() {
            this.sync('admin:go')
        }
    }
})
