let functions = {
    clone(value) {
        return JSON.parse(JSON.stringify(value))
    },

    // Считывание пресета экскурсионных туров
    extHashGet() {
        let preset = null;

        preset = window.location.hash
        if (!preset) {
            preset = document.getElementById('dolphin-preset')
            if (preset) {
                preset = preset.content
            }
        }

        // Если пресет хранился в хэш-строке
        if (preset) {
            preset = preset.substr(1)
            preset = preset.split(';');
            let opts = {};
            for (let i in preset) {
                let opt = preset[i].split('=')
                opts[opt[0]] = opt[1]
            }

            let dates = opts.d.split('-')

            let days = opts.ds.split(',').map(function (item) {
                return parseInt(item)
            })

            let children_ages = []
            if (opts.a) {
                children_ages = opts.a.split(',').map(function (item) {
                    return parseInt(item)
                })
            }

            let geo_objects = []
            if (opts.g) {
                geo_objects = opts.g.split(',')
            }

            return {
                geo_objects,
                date_of: dates[0],
                date_to: dates[1],
                days,
                adults: parseInt(opts.p),
                childrens: parseInt(opts.c),
                children_ages,
                list_type: opts.t,
                from_saratov: opts.s
            }
        }
    },

    // Генерирование пресета экскурсионных туров в хэш
    extHashPut(data) {
        window.location.hash = 'g=' + data.geo_objects.join(',') +
            ';d=' + data.date_of + '-' + data.date_to +
            ';ds=' + data.days.join(',') +
            ';p=' + data.adults +
            ';c=' + data.childrens +
            ';a=' + data.children_ages.join(',') +
            ';t=' + data.list_type +
            ';s=' + data.from_saratov
    },

    /* Преобразует дату вида 01.01.2020 в массив понятный moment.js */
    toDateArr(date_string) {

        if (!date_string) {
            return
        }

        let arr = date_string.split('.');
        return [arr[2], arr[1] - 1, arr[0]]
    },

    limitDateOf(dof, dto, moment) {
        let date_of = moment(this.toDateArr(dof))
        let date_to = moment(this.toDateArr(dto))
        let days_diff = date_of.diff(date_to, 'days')
        if (days_diff > 14) {
            return date_of.subtract(14, 'day').format('DD.MM.YYYY');
        }
    },

    limitDateTo(dto, dof, moment) {
        let date_to = moment(this.toDateArr(dto))
        let date_of = moment(this.toDateArr(dof))

        if (date_to.isAfter(date_of)) {
            return date_to.add(14, 'day').format('DD.MM.YYYY')
        }

        let days_diff = date_of.diff(date_to, 'days')
        if (days_diff > 14) {
            return date_to.add(14, 'day').format('DD.MM.YYYY')
        }
    },

    getGet() {
        let uri = window.location.href.split('?')
        if (uri.length === 2) {
            let vars = uri[1].split('&')
            let getVars = {}
            let tmp = ''
            vars.forEach(function (v) {
                tmp = v.split('=')
                if (tmp.length === 2) {
                    getVars[tmp[0]] = tmp[1]
                }
            });
            return getVars
        }
    },

    getJsonGet() {
        let uri = window.location.href.split('?');
        if (uri.length === 2) {
            let json_string = decodeURI(uri[1])
            return JSON.parse(json_string)
        }
    }
}

module.exports = functions
