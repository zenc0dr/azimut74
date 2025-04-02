let Statistics = new Vue({
    el:'#Statistics',
    delimiters: ['${', '}'],
    data: {
        storage_id: null,
        size: null,
        count_files: null,
        count_dirs: null,
    },
    mounted() {
        this.storage_id = $('meta[name="storage_id"]').attr('content')
        this.loadSize()
    },
    methods: {
        sync(method, callback)
        {
            axios('/zen/cabox/api/'+method+'?storage_id='+this.storage_id).then((response) => {
                if(callback) callback(response.data)
            })
        },
        loadSize()
        {
            this.sync('Service@getStorageSize', (data) => {
                this.size = data.size
                this.count_files = data.count_files
                this.count_dirs = data.count_dirs
            })
        },
        clearStorage()
        {
            this.sync('Service@cleanStorage', data => {
                location.reload()
            })
        }
    }
})
