var Parsers = new Vue({
    el: '#Parsers',
    delimiters: ['${', '}'],
    data: {
        parsers_process: false,
        seeders_process: false,
        strings: [],
        errors: [],
        notes: [],
        errors_show: false,
        repositories: [],
        infopanel: {},
        records_limit: false,
        parsers_switch: {
            waterway: true,
            volga: true,
            gama: true,
            germes: true,
            infoflot: true,
            trashCleaner: true,
            notActualCleaner: false,
            excludedMotorshipsCheckins: false,
            dubleCheckins: true,
            cleanPrices: false,
            checkCabins: false,
            createCheckinsBlocks: false,
            reActualCheckins: true,
        },
        criticalError: false,
        lastUrl: null,
        repeat_query: 0,
        re_query: 0, // Для кеша, пропускающий но выдающий ошибку
        ids:{},
        cache_mehods: [
            'waterway-motorships:Водоходъ - Кеш теплоходов',
            'waterway-cruises:Водоходъ - Кеш круизов',
            'waterwayCruisesCache:Водоходъ - Кеш цен',
            'waterwayRoutesCache:Водоходъ - Кеш маршрутов',
            'volgawolga-database-short:ВолгаWolga - База данных краткая',
            'volgawolga-database:ВолгаWolga - База данных полная',
            'gama-towns:Гама - Справочник городов',
            'gama-cruises:Гама - Информация о круизах',
            'gama-cabins:Гама - Справочник категорий кают',
            'gama-countries:Гама - Справочник стран',
            'gama-ships:Гама - Справочник теплоходов',
            'gamaCruisesCache:Гама - Кеш круизов',
            'gamaDecksCache:Гама - Кеш палуб',
            'germes-ships:Гермес - Справочник теплоходов',
            'germes-sity:Гермес - Справочник городов отправления',
            'germes-cruises:Гермес - Справочник круизов',
            'germes-cabins:Гермес - Справочник категорий кают',
            'germesStatusCache:Гермес - Кеш статусов',
            'germesTraceCache:Гермес - Кеш маршрутов',
            'germesExcursionCache:Гермес - Кеш экскурсий',

            //'infoflotMount:Инфофлот - Стартовые данные',
            //'infoflotToursByShip:Инфофлот - Сборщик идентификаторов заездов',
            //'infoflotToursById:Инфофлот - Подготовка кеша заездов'

            'infoflotShips:Инфофлот - Анализ теплоходов',
            'infoflotCruisesCache:Инфофлот - Кеш круизов',

            //'infoflot-ships:Инфофлот - Кеш теплоходов', // Простой тик
            //'infoflot-towns:Инфофлот - Кеш городов', // Простой тик
            //'infoflotTours:Инфофлот - Кеш круизов', // список ids и рекурсивный перебор
            //'infoflotRoutes:Инфофлот - Кеш детальных маршрутов',
            //'infoflotCabins:Инфофлот - Кеш кают',
            // 'infoflotCabinsDesc:Инфофлот - Кеш описаний кают' // нереально, там 55000+
        ],
        import_methods: [
            {name:'Водоход - Теплоходы', seeder:'waterwayMotorshipsSeeder', eds:'waterway'},
            {name:'Водоход - Палубы', seeder:'waterwayDecksSeeder', eds:'waterway'},
            {name:'Водоход - Каюты и цены', seeder:'waterwayCabinsSeeder', list:true, eds:'waterway'},
            {name:'Волга - Палубы', seeder:'volgaDecksSeeder', eds:'volga'},
            {name:'Волга - Каюты и цены', seeder:'volgaCabinsSeeder', list:true, eds:'volga'},
            {name:'Гама - Каюты и цены', seeder:'gamaSeeder', list:true, eds:'gama'},
            {name:'Гермес - Каюты и цены', seeder:'germesSeeder', list:true, eds:'germes'},
            {name:'Инфофлот - Круизы', seeder:'infoflotSeeder', list:true, eds:'infoflot'},
            {name:'Проверка устаревших заездов', seeder:'notActualCleaner', list:true, eds:'notActualCleaner'},
            {name:'Дополнительная проверка дублей заездов', seeder:'dublesCleaner', list:true, eds:'dubleCheckins'},
            {name:'Дополнительная проверка цен', seeder:'cleanPrices', eds:'cleanPrices'},
            {name:'Проверка данных теплоходов', seeder:'excludedMotorshipsCheckins', list:true, eds:'excludedMotorshipsCheckins'},
            {name:'Проверка кают на исключения', seeder:'checkCabins', list:true, eds:'checkCabins'},
            {name:'Подготовка кеша', seeder:'createCheckinsBlocks', list:true, eds:'createCheckinsBlocks'},
            {name:'Очистка мусора', seeder:'trashCleaner', list:true, eds:'trashCleaner'},
            {name:'Реактуализация заездов', seeder:'reActualCheckins', list:true, eds:'reActualCheckins'},
        ]
    },
    mounted: function () {
        this.info()
    },
    methods: {
        parsers: function () {
            this.parsers_process = true
            this.strings = []
            this.cacheHandler(0)
        },
        seeders: function () {
            this.parsers_process = false
            this.seeders_process = true
            this.seeders_handler(0)
        },
        sync: function (data, url, type, callback) {
            var $this = this;
            $.ajax({
                type: type,
                url: location.origin + url,
                data:data,
                success: function (data)
                {
                    $this.repeat_query = 0;
                    if(data) {
                        data = JSON.parse(data)
                        callback(data);
                    } else {
                        callback();
                    }
                },
                error: function(x)
                {
                    if(x.status == 502 || x.status == 503 || x.status == 504 && $this.repeat_query < 5) {
                        $this.repeat_query++;
                        $this.sync(data, url, type, callback);
                        console.log('Repeat query '+$this.repeat_query);
                        return;
                    }
                    $this.lastUrl = 'url=' + url + ' Запрос:'+JSON.stringify(data);
                    $this.criticalError = x.responseText;
                    console.log(x.status);
                },
            });
        },
        info: function () {
            var $this = this;
            this.sync(null, '/rivercrs/api/v2/parser/infoPanel', 'get', function (data) {
                $this.infopanel = data;
            });
        },
        time: function () {
            var tm=new Date();
            return this.checkTime(tm.getHours())+':'+
                this.checkTime(tm.getMinutes())+':'+
                this.checkTime(tm.getSeconds())
        },
        part: function ($if, $of) {
            var p = ($if*100)/$of
            p = Math.round(p)
            return p
        },
        checkTime: function (i) {
            if (i<10) {
                i="0" + i
            }
            return i
        },

        skipJob:function (method) {
            for(var i in this.parsers_switch) {
                if(/^[a-z]+$/.test(i) && method.indexOf(i)>-1) {
                    return !this.parsers_switch[i]
                }
            }
            return false;
        },

        cacheHandler: function (index, id_index) {

            //console.log('index='+index+' id_index='+id_index);

            // Прогрев кеша /rivercrs/api/v2/cache/null/method
            // Метод трейта /rivercrs/api/v2/parser/

            // Ограничитель для тестирования
            if(this.records_limit && id_index >= this.records_limit) {
                index++;
                this.cacheHandler(index)
                return
            }

            var $this = this

            var jobs = this.cache_mehods

            if(jobs[index] === undefined) {
                this.seeders()
                return
            };

            var job = jobs[index].split(':');
            var method = job[0]
            var name = job[1]

            if(this.skipJob(method)) {
                index++
                $this.cacheHandler(index)
                return
            }

            var cache = (/\D+-\D+/.test(method))
            var url = null;
            var type = 'post'

            if(cache) {
                url = '/rivercrs/api/v2/cache/null/'+method
                this.strings.push('['+this.time()+'] <'+method+'>'+name+'</'+method+'>')
                type = 'get'
            } else {
                url = '/rivercrs/api/v2/parser/'+method
                if(id_index === undefined) {
                    if(!$(method).length) {
                        this.strings.push('['+this.time()+'] <'+method+'>'+name+'</'+method+'>')
                    }
                } else {
                    if(this.ids[method][id_index] !== undefined) {
                        $(method).html(name+' Обработка идентификатора #'+this.ids[method][id_index]
                            +': '+id_index+' из '+$this.ids[method].length
                            +' ['+$this.part(id_index, $this.ids[method].length)+'%]')
                    }
                }
            }

            var id = null
            if(id_index !== undefined) {
               if(this.ids[method][id_index] === undefined) {
                   $(method).html(name+' - Обработано <i class="icon-check-circle"></i>')
                   index++;
                   $this.cacheHandler(index)
                   return
               }
               id = this.ids[method][id_index]
            }

            this.sync({id:id}, url, type, function (answer) {

                if(answer.note) {
                    //$this.notes.push(answer.note)
                    console.log(answer.note);
                    $(method).html(answer.note)
                    id_index++
                    $this.cacheHandler(index, id_index)
                    return
                }

                if(answer.repeat) {
                    $this.re_query++;
                    $this.errors.push('Повтор запроса '+$this.re_query+' - причина: '+answer.repeat)
                    $(method).html(name+' - Повтор запроса '+$this.re_query+' , причина '+answer.repeat+' (ожидание 5 секунд)')

                    if(id) {

                        if($this.re_query > 3) {
                            $this.re_query = 0
                            $this.ids[method][id_index] += '_bad'
                        }

                        setTimeout(function() {$this.cacheHandler(index, id_index)}, 5000)
                    } else {
                        setTimeout(function() {$this.cacheHandler(index)}, 5000)
                    }

                    return
                }

                if(answer.error) {
                    $this.errors.push(answer.error)
                    if(id) {
                        id_index++
                        $this.cacheHandler(index, id_index)
                    } else {
                        index++
                        $this.cacheHandler(index)
                    }
                    return
                }

                // Запрос вернул ids
                if(answer.ids) {
                    $this.ids[method] = answer.ids
                    $this.cacheHandler(index, 0)
                    return
                }

                // Запрос вернул результат
                if(answer.html) {
                    $(method).html(answer.html)
                    id_index++
                    $this.cacheHandler(index, id_index)
                    return
                }

                // Обычный запрос
                $(method).html(name+' - Обработано <i class="icon-check-circle"></i>')
                index++
                $this.cacheHandler(index)

            })
        },

        edsSkip: function (s) {
            if(s.eds===undefined) return false;
            var eds = s.eds
            if(this.parsers_switch[eds]) {
                return false
            } else {
                return true
            }
        },

        seeder: function (s, callback) {
            this.strings.push('['+this.time()+'] <'+s.seeder+'>Обработка: '+s.name+'...</'+s.seeder+'>')
            var $this = this
            this.sync(null, '/rivercrs/api/v2/parser/'+s.seeder, 'get', function (data) {
                if(data!==undefined){
                    $(s.seeder).html(data.message)
                } else {
                    $(s.seeder).html('Обработка: '+s.name+' завершена. <i class="icon-check-circle"></i>')
                }
                callback();
            })
        },

        seeder_list: function (s) {
            var $this = this
            if(s.list === true) {
                this.strings.push('['+$this.time()+'] <'+s.seeder+'>Обработка: '+s.name+'...</'+s.seeder+'>')
                this.sync({id:'init'},'/rivercrs/api/v2/parser/'+s.seeder, 'post', function(list){
                    if(list.message !== undefined) {
                        $(s.seeder).html(list.message)
                        $this.seeders_handler(s.steep+1)
                        return;
                    }
                    s.list = list
                    s.count = 0;
                    $this.seeder_list(s)
                });
                return;
            }

            try {

                this.sync({id: s.list[s.count]}, '/rivercrs/api/v2/parser/' + s.seeder, 'post', function (data) {

                    if ($this.records_limit) s.list.splice($this.records_limit)

                    var count_title = '[' + s.count + ' из ' + s.list.length + '] '

                    if (data.message) {
                        $(s.seeder).html('Обработка: ' + s.name + ' ' + count_title + data.message)
                    } else {
                        $(s.seeder).html('Обработка: ' + s.list[s.count] + ' завершена.')
                    }


                    if (s.count < s.list.length - 1) {
                        s.count++;
                        $this.seeder_list(s)
                    } else {
                        $(s.seeder).html(
                            'Обработка: ' + s.name + ' завершена. Команд отработано: ' +
                            s.list.length + ' <i class="icon-check-circle"></i>'
                        );
                        $this.seeders_handler(s.steep + 1)
                    }
                })
            } catch (err) {
                if (s.count < s.list.length - 1) {
                    s.count++;
                    $this.seeder_list(s)
                } else {
                    $(s.seeder).html(
                        'Обработка: ' + s.name + ' завершена. Команд отработано: ' +
                        s.list.length + ' <i class="icon-check-circle"></i>'
                    );
                    $this.seeders_handler(s.steep + 1)
                }
            }
        },

        /* Seeders */
        seeders_handler: function (steep) {
            var seeders = this.import_methods
            if(steep < seeders.length){
                if(this.edsSkip(seeders[steep])) {
                    steep++
                    this.seeders_handler(steep)
                    return
                }
                seeders[steep].steep = steep
                var $this = this
                if(seeders[steep].list!==undefined) {
                    this.seeder_list(seeders[steep])
                } else {
                    this.seeder(seeders[steep], function () {
                        $this.info()
                        steep++
                        $this.seeders_handler(steep)
                    });
                }
            } else {
                this.info()
                this.seeders_process = false;
            }
        },
    }
});