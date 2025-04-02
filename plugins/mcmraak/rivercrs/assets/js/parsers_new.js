var Parsers = new Vue({
    el: '#Parsers',
    delimiters: ['${', '}'],
    data: {
        parsers_process: false,
        seeders_process: false,
        strings: [],
        errors: [],
        errors_show: false,
        repositories: [],
        infopanel: {},
        records_limit: false,
        tasks_switch: {
            waterway: true,
            volga: true,
            gama: true,
            germes: true,
            trashCleaner: true,
            dubleCheckins: false,
            cleanPrices: false,
        },
        tasks: [
            {
                name:'Кэш: Водоход - Теплоходы',
                method:'waterway-motorships',
                code:'waterway',
            },
            {
                name:'Водоход - Теплоходы',
                method:'waterwayMotorshipsmethod',
                code:'waterway'
            },
            {
                name:'Водоход - Палубы',
                method:'waterwayDecksmethod',
                code:'waterway'
            },
            // {
            //     name:'Водоход - Каюты и цены',
            //     method:'waterwayCabinsmethod',
            //     list:true,
            //     code:'waterway'
            // },
            // {
            //     name:'Волга - Палубы',
            //     method:'volgaDecksmethod',
            //     code:'volga'
            // },
            // {
            //     name:'Волга - Каюты и цены',
            //     method:'volgaCabinsmethod',
            //     list:true,
            //     code:'volga'
            // },
            // {
            //     name:'Гама - Каюты и цены',
            //     method:'gamamethod',
            //     list:true,
            //     code:'gama'
            // },
            // {
            //     name:'Гермес - Каюты и цены',
            //     method:'germesmethod',
            //     list:true,
            //     code:'germes'
            // },
            // {
            //     name:'Проверка данных',
            //     method:'trashCleaner',
            //     list:true,
            //     code:'trashCleaner'
            // },
            // {
            //     name:'Дополнительная проверка дублей заездов',
            //     method:'dublesCleaner',
            //     list:true,
            //     code:'dubleCheckins'
            // },
            // {
            //     name:'Дополнительная проверка цен',
            //     method:'cleanPrices',
            //     code:'cleanPrices'
            // }
        ]
    },
    mounted: function () {
        this.info();
    },
    methods: {
        sync: function (data, method, type, callback) {
            method = '/rivercrs/api/v2/parser/' + method;
            var $this = this;
            $.ajax({
                type: type,
                url: location.origin + method,
                data:data,
                success: function (data)
                {
                    if(data) {
                        data = JSON.parse(data);
                        callback(data);
                    } else {
                        callback();
                    }
                },
                error: function(x)
                {
                    console.log(x.responseText);
                },
            });
        },
        info: function () {
            var $this = this;
            this.sync(null, 'infoPanel', 'get', function (data) {
                $this.infopanel = data;
            });
        },
        taskSkip: function (task) {
            if(task.code===undefined) return false;
            var code = task.code;
            if(this.tasks_switch[code]) {
                return false;
            } else {
                return true;
            }
        },
        tasks_handler: function (steep) {
            var tasks = this.tasks;
            if(steep < tasks.length){

                if(this.taskSkip(tasks[steep])) {
                    steep++;
                    this.tasks_handler(steep);
                    return;
                }

                tasks[steep].steep = steep;

                var $this = this;
                this.task_handler(tasks[steep], function () {
                    $this.info();
                    steep++;
                    $this.tasks_handler(steep);
                });

            } else {
                this.info();
                //this.seeders_process = false;
            }
        },
        task_handler: function (task, callback) {
            var $this = this;
            if(task.method.indexOf('-')>0) {
                this.sync({method:task.method}, 'getCache', 'get', function (data) {
                    callback();
                })
            } else {

            }
            callback();
        }
    }
});