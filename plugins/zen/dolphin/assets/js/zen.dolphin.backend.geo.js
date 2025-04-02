function geoObjects() {
    this.data = {}
}

geoObjects.prototype = {
    mounted(){
        let hotel_id = $('meta[name="hotel_id"]').attr('content')
        this.data = this.getMeta('geo_data')
        this.fillCountry()
    },
    fillCountry()
    {
        this.fillOptions('country', 'fillRegion')
    },
    fillRegion(){
        this.fillOptions('region', 'fillCity')
    },
    fillCity()
    {
        this.fillOptions('city', 'saveResult')
    },
    changeSelect(node)
    {
        let select = $(node)
        let id = select.attr('id')
        let value = select.val()
        this.data.hotel[id+'_id'] = value // Изменяется пресет сущности

        if(id == 'country') {
            this.data.hotel.region_id = 0
            this.fillRegion()
        }
        if(id == 'region') {
            this.data.hotel.city_id = 0
            this.fillCity()
        }
        if(id == 'city') {
            this.saveResult()
        }
    },
    getMeta(name) {
        return JSON.parse($('[name="'+name+'"]').attr('content'))
    },
    saveResult(){
        $('[name="Hotel[save_geo]"]').val(JSON.stringify(this.data.hotel))
    },
    fillOptions(id, next)
    {
        let selected = this.data.hotel[id+'_id'] // Значение выбранного пресета

        let options = ''

        // Заполнение опции
        for(let i in this.data[id]) {
            let item = this.data[id][i]

            // Фильтрация
            if(item.id != 0) {
                if(id=='region' && item.country_id != this.data.hotel.country_id) continue
                if(id=='city' && item.region_id != this.data.hotel.region_id) continue
            }

            let selectedAttr = (item.id == selected)?' selected':''
            options+='<option value="'+item.id+'"'+selectedAttr+'>' +
                          item.name +
                     '</option>'
        }

        $('#'+id).html(options)
        if(next) this[next]()
    }
}

let GEO = new geoObjects()
GEO.mounted()
