let SelectTours = new Vue({
    el:'#SelectTours',
    delimiters: ['${', '}'],
    data: {
        page: 1,
        pages_count: null,
        per_page: 30,
        process: false,
        filter: null,
        tours: [],
        selected_tours: [],
        last_added_tour: null, // for animation
    },
    mounted()
    {
        this.loadItems()
        let extours_eids = $('meta[name="extours_eids"]').attr('content')
        if(extours_eids) this.selected_tours = JSON.parse(extours_eids)
    },
    computed: {
        records()
        {
            let items = this.tours
            if(this.filter) {
                this.page = 1
                items = items.filter((item) => {
                    return item.name.toLowerCase().indexOf(this.filter.toLowerCase()) !== -1
                })
            }

            this.pages_count = Math.floor(items.length / this.per_page)

            let start = (this.page - 1) * this.per_page;
            let end = ((this.page - 1) * this.per_page) + this.per_page

            return items.slice(start, end)
        },
        selected()
        {
            return this.tours.filter((item) => {
                return this.selected_tours.indexOf(item.id)!==-1
            })

        },
        save_value()
        {
            return JSON.stringify(this.selected_tours)
        }
    },
    methods: {
        sync(method, data, callback)
        {
            axios.post('/zen/dolphin/api/'+method, data).then((response) => {
                if(callback) callback(response.data)
            })
        },
        loadItems()
        {
            if(this.process) return
            this.process = true;
            this.sync('store:getTours', {filter: this.filter}, (data) => {
                this.tours = data.tours
                this.process = false;
            })
        },
        addTour(tour_id){
            tour_id = parseInt(tour_id)
            this.last_added_tour = tour_id
            if(this.selected_tours.indexOf(tour_id)!==-1) return
            this.selected_tours.push(tour_id)
        },
        removeTour(tour_id)
        {
            this.last_added_tour = null
            tour_id = parseInt(tour_id)
            let remove_key = this.selected_tours.indexOf(tour_id)
            this.selected_tours.splice(remove_key, 1)
        },
        openTour(tour_id){
            this.sync('store:openTour', {tour_id}, (data) => {
                $.popup({content: data.html});
            })
        },
        paginate(route)
        {
            if(route) {
                this.page++
            } else {
                this.page--
            }

            if(this.page < 1) this.page = 1;
            if(this.page > this.pages) this.page = this.pages;
        },
    }
})
