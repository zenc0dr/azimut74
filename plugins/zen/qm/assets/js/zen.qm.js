var QM = new Vue({
    el: '#QM',
    delimiters: ['${', '}'],
    data: {
        ajaxProcess: false,
        process: false,
        jobs_count: [],
        alerts: null,
        started: false,
    },
    mounted: function () {
        var $this = this;
        this.getJobs(function () {
            $this.reloader();
        });
    },
    computed: {
        icon: function () {
            return (this.process)?'oc-icon-refresh':'oc-icon-play-circle-o';
        }
    },
    methods: {
        sync: function (data, action, callback, async) {
            if(!async && this.ajaxProcess) return;

            var url = '/zen/qm/api/'+action;
            var $this = this;

            $.ajax({
                type: 'post',
                url: location.origin + url,
                data:data,
                beforeSend: function(){
                    $this.ajaxProcess = true;
                },
                success: function (data)
                {
                    $this.ajaxProcess = false;
                    if(data) {
                        data = JSON.parse(data);
                        if(data.alerts) {
                            $this.message = data.message;
                        }
                        callback(data);
                    } else {
                        callback();
                    }
                },
                error: function(x)
                {
                    $this.ajaxProcess = false;
                    $this.message = x.responseText;
                },
            });
        },
        getJobs: function (callback) {
            var $this = this;
            this.sync(null, 'Jobs@mounted', function (data) {
                $this.jobs_count = data.jobs_count;
                callback();
            }, 1);
        },
        run: function () {
            this.started = true;
            if(this.process) return;
            if(!this.jobs_count) return;
            this.go();
            this.process = true;
        },
        go: function () {
            var $this = this;
            this.sync('null', 'Jobs@work', function (data) {
                $this.jobs_count = data.jobs_count;
                if(data.jobs_count != 0) {
                    $this.go();
                } else {
                    $this.process = false;
                }
            });
        },
        reloader: function () {
            var $this = this;
            setInterval(function () {
                if($this.started) {
                    $this.getJobs(function () {
                        $this.run();
                    });
                }
            }, 1000);
        }
    }
});