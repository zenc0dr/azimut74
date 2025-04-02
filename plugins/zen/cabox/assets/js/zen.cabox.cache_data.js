let CacheData = new Vue({
    el:'#CacheData',
    delimiters: ['${', '}'],
    data: {
        storage_id: null,
        page: 1,
        pages: null,
        filters: {
            key: '',
            time: '',
        },
        filter_query: false,
        storage_items: [],
        process: false,
    },
    mounted() {
        this.storage_id = $('meta[name="storage_id"]').attr('content')
        this.loadItems()
        this.disableSubmit('.cabox-filters input')
    },
    watch: {
        filters:
        {
            handler()
            {
                this.page = 1
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
                this.timer = setTimeout(() => {
                    this.loadItems()
                }, 500);
            },
            deep: true
        }
    },
    methods: {
        sync(method, data, callback)
        {
            axios.post('/zen/cabox/api/'+method, data).then((response) => {
                if(callback) callback(response.data)
            })
        },
        loadItems()
        {
            if(this.process) return
            this.process = true;

            let post = {
                storage_id: this.storage_id,
                page: this.page,
                filters: this.filters
            }

            this.sync('Service@getStorageItems', post, (data) => {
                this.storage_items = data.storage_items
                this.page = data.page
                this.pages = data.pages
                this.process = false;
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

            this.loadItems()
        },
        showDump(filename)
        {
            $.popup({
                handler: 'onShowDump',
                extraData: {filename, storage_id:this.storage_id},
            });
        },
        deleteItem(key)
        {
            let post = {
                storage_id: this.storage_id,
                key,
            }

            this.sync('Service@deleteItem', post, () => {
                this.loadItems()
            })
        },
        disableSubmit(selector)
        {
            $(selector).keydown(function(event){
                if(event.keyCode == 13) {
                    alert('Просто подождите и результат появиться')
                    event.preventDefault();
                    return false;
                }
            });
        }
    }
})
