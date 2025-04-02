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
        parsers_switch: {
            waterway: true,
            volga: true,
            gama: true,
            germes: true,
            infoflot: true,
            trashCleaner: true,
            notActualCleaner: false,
            dubleCheckins: true,
            cleanPrices: false,
            checkCabins: false,
            createCheckinsBlocks: false,
        },
        criticalError: false,
        lastUrl: null,
        repeat_query: 0,
        ids:{},
        cache_mehods: [
            'infoflot-ships:Инфофлот - Кеш теплоходов', // Простой тик
            'infoflot-towns:Инфофлот - Кеш городов', // Простой тик
            'infoflotTours:Инфофлот - Кеш круизов', // список ids и рекурсивный перебор
            'infoflotRoutes:Инфофлот - Кеш детальных маршрутов',
            'infoflotCabins:Инфофлот - Кеш кают',
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
            {name:'Проверка устаревших заездов', seeder:'notActualCleaner', list:true, eds:'notActualCleaner'},
            {name:'Проверка данных', seeder:'trashCleaner', list:true, eds:'trashCleaner'},
            {name:'Дополнительная проверка дублей заездов', seeder:'dublesCleaner', list:true, eds:'dubleCheckins'},
            {name:'Дополнительная проверка цен', seeder:'cleanPrices', eds:'cleanPrices'},
            {name:'Проверка кают на исключения', seeder:'checkCabins', list:true, eds:'checkCabins'},
            {name:'Подготовка кеша', seeder:'createCheckinsBlocks', list:true, eds:'createCheckinsBlocks'},
            {name:'Инфофлот - Круизы', seeder:'infoflotSeeder', list:true, eds:'infoflot'},
        ]
    },
    mounted: function () {
        this.info()
    },
    methods: {
        parsers: function () {
            this.parsers_process = true
            this.strings = []
            //this.infoflot_ships()
            this.waterway_motorships()
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
        checkTime: function (i) {
            if (i<10) {
                i="0" + i
            }
            return i
        },
        cacheString: function(string, item){
            if(!item.error) {
                return string + '<i class="icon-check-circle"></i> [Остаток времени кеша:'+item.live+' мин.]'
            } else {
                return string + ' <i class="icon-exclamation-circle"></i> Ошибка парсинга: ' + item.error
            }
            var live = (item.live)?' [Остаток времени кеша:'+item.live+' мин.]':''
            var ok = (item.status==true)?' <i class="icon-check-circle"></i>':'Error!'
            return string + ok + live
        },
        // lastString: function(message){
        //     this.strings[this.strings.length - 1] = message;
        // },
        simpleHandler: function(url, name, next)
        {
            var $this = this;
            this.sync(null, '/rivercrs/api/v2/cache/null/'+url, 'get', function (data) {
                var str = $this.cacheString(name, data);
                $this.strings.push('['+$this.time()+'] Парсинг и кэширование: '+str);
                eval('$this.'+next);
            })
        },
        idsHandler: function (settings) {
            var $this = this;
            this.sync(null, settings.url, 'get', function (ids) {
                if(!ids.length) {
                    $this.strings.push('['+$this.time()+'] Парсинг и кэширование: '+settings.cache_full+' <i class="icon-check-circle"></i>');
                    eval('$this.'+settings.next);
                } else {
                    $this[settings.ids] = ids;
                    $this.strings.push('['+$this.time()+'] <'+settings.container+'>Парсинг и кэширование: '+settings.intro+'</'+settings.container+'>');
                    eval('$this.'+settings.handler);
                }
            })
        },
        // Проценты
        part: function ($if, $of) {
            var p = ($if*100)/$of
                p = Math.round(p)
                return p
        },
        recursiveHandler: function(settings){

            // count, ids, url, title, error_title, complite_title, container, next, query
            var $this = this;
            var id = settings.ids[settings.count];
            this.sync(null, settings.url+id, 'get', function (data) {

                if(data.status==true) {
                    settings.count++;
                    if(settings.count < settings.ids.length){
                        var part = $this.part(settings.count, settings.ids.length);
                        var message = settings.title+' '+settings.count+' из '+settings.ids.length
                          +' [ '+part+'% ]';

                        $(settings.container).html(message);
                        $this.recursiveHandler(settings);
                    } else {
                        $(settings.container)
                            .html(settings.complite_title
                                +settings.count+' из '+settings.ids.length+' <i class="icon-check-circle"></i>');
                        eval('$this.'+settings.next);
                    }
                } else {
                    $this.errors.push(settings.error_title+id+' Запрос:'+settings.query+id);
                    settings.count++;
                    if(settings.count < settings.ids.length) {
                        $this.recursiveHandler(settings);
                    } else {
                        $(settings.container)
                            .html(settings.complite_title
                                +' Сплошные ошибки, что-то не так...');
                        eval('$this.'+settings.next);
                    }
                }
            })
        },

        skipJob:function (method) {
            console.log(method);
            for(var i in this.parsers_switch) {
                if(this.parsers_switch[i].indexOf(method)>-1) {
                    console.log(this.parsers_switch[i]);
                }
            }
            return true;
        },

        cacheHandler: function (index, id_index) {

            // Прогрев кеша /rivercrs/api/v2/cache/null/method
            // Метод трейта /rivercrs/api/v2/parser/

            var $this = this

            var jobs = this.cache_mehods

            if(jobs[index] === undefined) {
                this.seeders()
                return
            };

            var job = jobs[index].split(':');
            var method = job[0]
            var name = job[1]

            /* TODO: Доработать остальные парсеры
            if(this.skipJob(method)) {
                index++
                $this.cacheHandler(index)
                return
            }
            */

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
                    this.strings.push('['+this.time()+'] <'+method+'>'+name+'</'+method+'>')
                } else {
                    if(this.ids[method][id_index] !== undefined) {
                        $(method).html(name+' Обработка идентификатора #'+this.ids[method][id_index]+': '+id_index+' из '+$this.ids[method].length+' ['+$this.part(id_index, $this.ids[method].length)+'%]')
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

        // LOGIC
        waterway_motorships: function () {
            this.simpleHandler(
                'waterway-motorships',
                'Водоходъ - Теплоходы:',
                'waterway_cruises()'
            );
        },
        waterway_cruises: function () {
            this.simpleHandler(
                'waterway-cruises',
                'Водоходъ - Круизы:',
                'waterway_prices()'
            );
        },
        waterway_prices: function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/waterwayCruisesIdsForPrices',
                cache_full:'Водоходъ - Парсинг цен - Кеш в норме',
                next:'waterway_route()',
                ids: 'ww_cruises_prices_ids',
                container:'wwprices',
                intro:'Водоходъ - Парсинг цен:',
                handler: 'waterway_prices_handler()',
            });
        },
        waterway_prices_handler: function () {
            this.recursiveHandler({
                count:0,
                ids:this.ww_cruises_prices_ids,
                url:'/rivercrs/api/v2/cache/null/waterway-prices/',
                title:'Водоходъ - Парсинг цен:',
                error_title: 'Ошибка на круизе #',
                complite_title: 'Водоходъ - Парсинг цен завершен, записей обработано: ',
                container: 'wwprices',
                next: 'waterway_route()',
                query: 'https://api.vodohod.com/json/v2/cruise-prices.php?pauth=kefhjkdRgwFdkVHpRHGs&cruise='
            });
        },
        waterway_route: function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/waterwayCruisesIdsForRoutes',
                cache_full:'Водоходъ - Парсинг маршрутов - Кеш в норме',
                next:'volgaWolgaShort()',
                ids: 'ww_cruises_routes_ids',
                container:'wwroute',
                intro:'Водоходъ - Парсинг маршрутов:',
                handler: 'waterway_route_handler()',
            });
        },
        waterway_route_handler: function () {
            this.recursiveHandler({
                count:0,
                ids:this.ww_cruises_routes_ids,
                url:'/rivercrs/api/v2/cache/null/waterway-route/',
                title:'Водоходъ - Парсинг маршрутов:',
                error_title: 'Ошибка на круизе #',
                complite_title: 'Водоходъ - Парсинг маршрутов завершена, записей обработано: ',
                container: 'wwroute',
                next: 'volgaWolgaShort()',
                query: 'https://api.vodohod.com/json/v2/cruise-days.php?pauth=kefhjkdRgwFdkVHpRHGs&cruise='
            });
        },
        volgaWolgaShort: function () {
            this.simpleHandler(
                'volgawolga-database-short',
                'ВолгаWolga - База данных краткая:',
                'volgaWolga()'
            );
        },
        volgaWolga: function () {
            this.simpleHandler(
                'volgawolga-database',
                'ВолгаWolga - База данных полная:',
                'gama_cruises()'
            );
        },
        gama_cruises:function () {
            this.simpleHandler(
                'gama-cruises',
                'Гама - Информация о круизах:',
                'gama_cruises_ids()'
            );
        },
        gama_cruises_ids:function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/gamaCruisesIdsForCruises',
                cache_full:'Гама - Парсинг круизов - Кеш в норме',
                next:'gama_towns()',
                ids: 'gama_cruises_ids',
                container:'gamacruises',
                intro:'Гама - Парсинг круизов:',
                handler: 'gama_cruises_handler()',
            });
        },
        gama_cruises_handler: function(){
            this.recursiveHandler({
                count:0,
                ids:this.gama_cruises_ids,
                url:'/rivercrs/api/v2/cache/null/gama-cruise/',
                title:'Гама - Парсинг круизов:',
                error_title: 'Ошибка на круизе #',
                complite_title: 'Гама - Парсинг круизов завершен, записей обработано: ',
                container: 'gamacruises',
                next: 'gama_towns()',
                query: 'http://gama-nn.ru/execute/way/'
            });
        },
        gama_towns:function () {
            this.simpleHandler(
                'gama-towns',
                'Гама - Справочник городов:',
                'gama_cabins()'
            );
        },
        gama_cabins:function () {
            this.simpleHandler(
                'gama-cabins',
                'Гама - Справочник категорий кают:',
                'gama_countries()'
            );
        },
        gama_countries:function () {
            this.simpleHandler(
                'gama-countries',
                'Гама - Справочник стран:',
                'gama_ships()'
            );
        },
        gama_ships:function () {
            this.simpleHandler(
                'gama-ships',
                'Гама - Справочник теплоходов:',
                'gama_deck_ids()'
            );
        },
        gama_deck_ids: function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/gamaShipIdsForDecks',
                cache_full:'Гама - Парсинг палуб - Кеш в норме',
                next:'germes_ships()',
                ids: 'gama_decks_ids',
                container:'gamadecks',
                intro:'Гама - Парсинг палуб:',
                handler: 'gama_decks_handler()',
            });
        },
        gama_decks_handler: function () {
            this.recursiveHandler({
                count:0,
                ids:this.gama_decks_ids,
                url:'/rivercrs/api/v2/cache/null/gama-deck/',
                title:'Гама - Парсинг палуб:',
                error_title: 'Ошибка на корабле #',
                complite_title: 'Гама - Парсинг палуб завершен, записей обработано: ',
                container: 'gamadecks',
                next: 'germes_ships()',
                query: 'https://api.gama-nn.ru/execute/view/deck/'
            });
        },
        germes_ships:function () {
            this.simpleHandler(
                'germes-ships',
                'Гермес - Справочник теплоходов:',
                'germes_sity()'
            );
        },
        germes_sity:function () {
            this.simpleHandler(
                'germes-sity',
                'Гермес - Справочник городов отправления:',
                'germes_cruises()'
            );
        },
        germes_cruises:function () {
            this.simpleHandler(
                'germes-cruises',
                'Гермес - Справочник туров (круизов):',
                'germes_cabins()'
            );
        },
        germes_cabins:function () {
            this.simpleHandler(
                'germes-cabins',
                'Гермес - Справочник категорий кают:',
                'germes_status_ids()'
            );
        },
        germes_status_ids: function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/germesStatusIds',
                cache_full:'Гермес - Парсинг статусов кают - Кеш в норме',
                next:'germes_trace_ids()',
                ids: 'germes_status_ids',
                container:'germesstatuses',
                intro:'Гермес - Парсинг статусов:',
                handler: 'germes_status_handler()',
            });
        },
        germes_status_handler: function () {
            this.recursiveHandler({
                count:0,
                ids:this.germes_status_ids,
                url:'/rivercrs/api/v2/cache/null/germes-status/',
                title:'Гермес - Парсинг статусов:',
                error_title: 'Ошибка в круизе #',
                complite_title: 'Гама - Парсинг палуб завершен, записей обработано: ',
                container: 'germesstatuses',
                next: 'germes_trace_ids()',
                query: 'http://river.sputnik-germes.ru/XML/exportKauta.php?tur='
            });
        },
        germes_trace_ids: function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/germesTraceIds',
                cache_full:'Гермес - Парсинг маршрутов - Кеш в норме',
                next:'germes_excursion_ids()',
                ids: 'germes_trace_ids',
                container:'germestrace',
                intro:'Гермес - Парсинг маршрутов:',
                handler: 'germes_trace_handler()',
            });
        },
        germes_trace_handler: function () {
            this.recursiveHandler({
                count:0,
                ids:this.germes_trace_ids,
                url:'/rivercrs/api/v2/cache/null/germes-trace/',
                title:'Гермес - Парсинг маршрутов:',
                error_title: 'Ошибка в круизе #',
                complite_title: 'Гермес - Парсинг маршрутов завершен, записей обработано: ',
                container: 'germestrace',
                next: 'germes_excursion_ids()',
                query: 'http://river.sputnik-germes.ru/XML/exportTrace.php?tur='
            });
        },
        germes_excursion_ids: function () {
            this.idsHandler({
                url:'/rivercrs/api/v2/parser/germesExcursionIds',
                cache_full:'Гермес - Парсинг экскурсий - Кеш в норме',
                next:'cacheHandler(0)',
                ids: 'germes_excursion_ids',
                container:'germesex',
                intro:'Гермес - Парсинг экскурсий:',
                handler: 'germes_excursion_handler()',
            });
        },
        germes_excursion_handler: function () {
            this.recursiveHandler({
                count:0,
                ids:this.germes_excursion_ids,
                url:'/rivercrs/api/v2/cache/null/germes-excursion/',
                title:'Гермес - Парсинг экскурсий:',
                error_title: 'Ошибка в круизе #',
                complite_title: 'Гермес - Парсинг экскурсий завершен, записей обработано: ',
                container: 'germesex',
                next: 'cacheHandler(0)',
                query: 'http://river.sputnik-germes.ru/XML/exportExcursion.php?tur='
            });
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

