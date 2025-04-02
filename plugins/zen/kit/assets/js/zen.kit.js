var Kit = new Vue({
    data: {
        preloader: {
            time: 700,
            selector: false,
            show: false
        },
        url_prefix: false,
    },
    methods: {
        sync: function (data, url, callback, options) {

            var defaults = {
                type: 'post',
                html: false,
                processData: true,
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            }

            var options = Object.assign(defaults, options);

            if(this.url_prefix) url = this.url_prefix+url;
            var $this = this;
            $.ajax({
                type: options.type,
                url: location.origin + url,
                processData: options.processData,
                contentType: options.contentType,
                data:data,
                beforeSend: function(){
                    $this.preloaderShow();
                },
                success: function (data)
                {
                    $this.preloaderHide();
                    if(data) {
                        data = (options.html)?data:JSON.parse(data);
                        callback(data);
                    } else {
                        callback();
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
            if (!this.preloader.selector) return;
            var $this = this;
            $this.preloader.show = false;
            setTimeout(function (){
                if ($this.preloader.show === false) {
                    $($this.preloader.selector).fadeIn(300);
                    $('body').css('cursor','wait');
                }
            }, $this.preloader.time);
        },
        preloaderHide: function ()
        {
            if (!this.preloader.selector) return;
            this.preloader.show = true;
            $(this.preloader.selector).hide();
            $('body').css('cursor','default');
        },
    }
});