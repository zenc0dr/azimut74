import axios from "axios";

export default {
    sync(opts) {
        let domain = location.origin
        let data = (opts.data) ? opts.data : null
            let api_url = domain + opts.url

            console.log(api_url, data) // todo:debug

        // Если нет данных то запрос GET
            if (!data) {
                axios.get(api_url)
                .then((response) => {
                    opts.then(response.data)
                    console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        if (opts.error) {
                            opts.error(error)
                        }
                        console.log(error) // todo:debug
                    })
            }
        // Если есть data то запрос POST
            else {
                axios.post(api_url, data)
                .then((response) => {
                    opts.then(response.data)
                    console.log(response) // todo:debug
                    })
                    .catch((error) => {
                        if (opts.error) {
                            opts.error(error)
                        }
                        console.log(error) // todo:debug
                    })
            }
    },
    // Считывание пресета экскурсионных туров
    readQuery() {
        let preset = null;

        preset = document.getElementById('dolphin-preset')
        if (preset) {
            preset = preset.content
        }

        // Если пресет существует
        if (preset) {
            preset = preset.replace(/^#/, '')
            preset = preset.split(';');

            let opts = {};
            for (let i in preset) {
                let opt = preset[i].split('=')
                opts[opt[0]] = opt[1]
            }

            let dates = opts.d.split('-')


            let days = []

            if (opts.ds) {
                days = opts.ds.split(',').map(function (item) {
                    return parseInt(item)
                })
            }

            let childrens = []
            if (opts.a) {
                childrens = opts.a.split(',').map(function (item) {
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
                childrens,
                list_type: opts.t
            }
        }
    },

    // Генерирование пресета экскурсионных туров в хэш
    queryToString(data) {
        return 'g=' + data.geo_objects.join(',') +
            ';d=' + data.date_of + '-' + data.date_to +
            ';ds=' + data.days.join(',') +
            ';p=' + data.adults +
            ';a=' + data.childrens.join(',') +
            ';t=' + data.list_type
    },
}
