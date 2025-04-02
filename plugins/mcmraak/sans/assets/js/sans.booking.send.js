var SansBooking = new Vue({
    el: '#SansBooking',
    delimiters: ['${', '}'],
    data: {
        preloaderStartInterval: 700,
        preloaderSelector: '.revloader',
        send: {
            confirm: false,
            name:'',
            email:'',
            phone:'',
            desc:'',
        },
        warnings: {
            'messages':[],
            'badfields':[],
            success: false
        }
    },
    mounted: function(){
    	var $vm = this;
        $('.modal__human-details-line .phone').inputmask(
        	'+7(999)999-99-99',
        	{
        		showMaskOnHover: true,
				"oncomplete": function(){ $vm.send.phone = this.value; }
        	});
    },
    // created: function() {
    // 	document.addEventListener('click', this.clickOver);
    // },
    methods: {
        confirm: function()
        {
            if(this.send.confirm){
                this.send.confirm = false;
            } else {
                this.send.confirm = true;
            }
        },
        sendData: function()
        {
            var $this = this;
            let send_data = this.send
            send_data.refer = location.href
            $.ajax({
                type: 'post',
                data: send_data,
                url: location.origin + '/sans/api/v1/booking/send',
                beforeSend: function(){
                    $this.preloaderShow();
                },
                success: function (data)
                {
                    $this.preloaderHide();
                    // console.log(data);
                    // return;

                    //$('#SansBooking').html(data);

                    if(!data) return;
                    data = JSON.parse(data);
                    $this.warnings.messages = data.messages;
                    $this.warnings.badfields = data.badfields;
                    $this.warnings.success = data.success;

                    if(data.success)
                    {
                        $this.send = {
                            name:'',
                            email:'',
                            phone:'',
                            desc:'',
                        }
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    }
                },
                error: function(x)
                {
                    console.log(x.responseText);
                },
            });

        },
        preloaderShow: function ()
        {
            var si = this.preloaderStartInterval;
            $(this.preloaderSelector).attr("complite", "false");
            var preloader = this.preloaderSelector;
            setTimeout(function ()
            {
                if ($(preloader).attr("complite") === "false") {
                    $(preloader).fadeIn(300);
                    $('body').css('cursor','wait');
                }
            }, si);
        },
        preloaderHide: function ()
        {
            $(this.preloaderSelector).attr("complite", "true");
            $(this.preloaderSelector).hide();
            $('body').css('cursor','default');
        },
    }
});
