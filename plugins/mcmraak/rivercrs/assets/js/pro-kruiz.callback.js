new Vue({
    el: '#CallBackForm',
    delimiters: ['${', '}'],
    data: {
        alerts: [],
        name: null,
        phone: null,
        email: null,
        note: null,
        bad_name: null,
        bad_email: null,
        bad_phone: null,
        bad_note: null,
        bad_time: null,
        success: null,
        process: false,
        agree: false,
    },
    methods: {
        sync(data, callback) {
            if(this.process) return
            if(!this.agree) return
            this.process = true
            $.ajax({
                type: 'post',
                url: location.origin + '/prok/api/callback',
                data,
                success: (response) => {
                    this.process = false
                    if(response) {
                        let data = JSON.parse(response)
                        //console.log(data)
                        callback(data)
                    } else {
                        callback()
                    }
                },
                error: (x) => console.log(x.responseText)
            })
        },

        isBad(field) {

            if(!this.alerts.length) return
            for(let i in this.alerts) {
                if(this.alerts[i].field == field) {
                    return this.alerts[i].text
                }
            }
            return false
        },

        send() {
            let data = { name:this.name, email:this.email, phone:this.phone, note:this.note }
            this.sync(data, (response) => {
                this.success = response.success
                if(response.success) {
                    this.name = null
                    this.email = null
                    this.phone = null
                    this.note = null
                    this.bad_time = null
                }
                if(response.alerts) {
                    this.alerts = response.alerts
                } else {
                    this.alerts = []
                }
            })
        }
    }
})
