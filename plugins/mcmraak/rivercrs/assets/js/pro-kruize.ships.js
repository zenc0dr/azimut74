let ShipSelector = new Vue({
    el: '#ShipSelector',
    delimiters: ['${','}'],
    data: {
        // form: {
        //     ship_name: null,
        //     desks_count: {
        //         value: [],
        //         options: []
        //     },
        //     status: {
        //         value: [],
        //         options: []
        //     },
        // }
        form: null,
        default_form: null,
    },
    mounted() {
        this.form = this.default_form
    },
    computed: {
        output() {
            if(!this.form) return
            let output = {}
            if(this.form.ship_name) output.n = this.form.ship_name
            if(this.form.desks_count.value) output.d = this.form.desks_count.value
            if(this.form.status.value) output.s = this.form.status.value
            return JSON.stringify(output);
        }
    },
    methods: {
        fillForm(form_data)
        {
            this.default_form = form_data
            return true
        }
    }
})
