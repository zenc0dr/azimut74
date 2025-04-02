var Core = new Vue({
    methods: {
        sync: function (data, url, type, callback) {
            var $this = this;
            $.ajax({
                type: type,
                url: location.origin + url,
                data: data,
                success: function (data) {
                    if (data) {
                        data = JSON.parse(data);
                        callback(data);
                    } else {
                        callback();
                    }
                },
                error: function (x) {
                    console.log(x.responseText);
                },
            });
        },
        cacheString: function (string, item) {
            var live = (item.live) ? ' [Остаток времени кеша:' + item.live + ' мин.]' : '';
            var ok = (item.status == 'ok') ? ' <i class="icon-check-circle"></i>' : 'Error!';
            return string + ok + live;
        },
        lastString: function (message) {
            this.strings[this.strings.length - 1] = message;
        },
        simpleHandler: function (url, name, next) {
            var $this = this;
            this.sync(null, '/rivercrs/api/v2/cache/null/' + url, 'get', function (data) {
                var str = $this.cacheString(name, data);
                $this.strings.push(str);
                eval('$this.' + next);
            })
        },
        recursiveHandler: function (settings) {

            // count, ids, url, title, error_title, complite_title, container, next, query
            var $this = this;
            var id = settings.ids[settings.count];
            this.sync(null, settings.url + id, 'get', function (data) {

                if (data.status == 'ok') {
                    settings.count++;
                    if (settings.count < settings.ids.length) {
                        var message = settings.title + ' ' + settings.count + ' из ' + settings.ids.length;
                        $(settings.container).html(message);
                        $this.recursiveHandler(settings);
                    } else {
                        $(settings.container)
                            .html(settings.complite_title
                                + settings.count + ' из ' + settings.ids.length + ' <i class="icon-check-circle"></i>');
                        eval('$this.' + settings.next);
                    }
                } else {
                    $this.errors.push(settings.error_title + id + ' Запрос:' + settings.query + id);
                    settings.count++;
                    $this.recursiveHandler(settings);
                }
            })
        }
    }
});

