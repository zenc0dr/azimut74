var SansReview = new Vue({
    el: '#SansReview',
    delimiters: ['${', '}'],
    data: {
        preloaderStartInterval: 700,
        preloaderSelector: '.revloader',
        send: {
            comment:'',
            liked: '',
            startdoing: '',
            stopdoing: '',
            doing: '',
            name:'',
            email:'',
            town: '',
            confirm: false,
            stars: 5,
            hotel_id: 0,
            },
        warnings: {
            'messages':[],
            'badfields':[],
            success: false
        },
    },
    mounted: function () {
        this.send.hotel_id = $('hotel').attr('id');
    },
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
            var data = new FormData();
            data.append('comment', this.send.comment);
            data.append('liked', this.send.liked);
            data.append('startdoing', this.send.startdoing);
            data.append('stopdoing', this.send.stopdoing);
            data.append('doing', this.send.doing);
            data.append('name', this.send.name);
            data.append('email', this.send.email);
            data.append('town', this.send.town);
            data.append('stars', this.send.stars);
            data.append('hotel_id', this.send.hotel_id);
            var images_count = $('[rev-send=image]').length;
            for(var i=0;i<images_count - 1;i++){
                data.append('image_'+i, $('[rev-send=image]')[i].files[0]);
            }


            var $this = this;
            $.ajax({
                type: 'post',
                data: data,
                url: location.origin + '/sans/reviews/api/send',
                processData: false,
                contentType: false,
                beforeSend: function(){
                    $this.preloaderShow();
                },
                success: function (data)
                {
                    $this.preloaderHide();
                    if(!data) return;
                    data = JSON.parse(data);
                    $this.warnings.messages = data.messages;
                    $this.warnings.badfields = data.badfields;
                    $this.warnings.success = data.success;

                    if(data.success)
                    {
                        $this.send = {
                            comment:'',
                            liked: '',
                            startdoing: '',
                            stopdoing: '',
                            doing: '',
                            name:'',
                            email:'',
                            town: '',
                            confirm: false,
                            stars: 5,
                        }
                        $('.addPhotoClose').click();
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