function z(m){
    console.log(m);
}

function BlockGallery(){
    this.run();
}

BlockGallery.prototype = {
    run: function(){
        var count = $('hr').length;
        for(var i=0;i<count;i++)
        {
            var gallery = $('hr').eq(i);
            var field = '';
            var code = '';
            if(gallery.attr('code') != undefined) {
                code = gallery.attr('code');
                field = 'code';
            }
            if(gallery.attr('id') != undefined) {
                code = gallery.attr('id');
                field = 'id';
            }
            if(gallery.attr('type') != undefined) {
                var style = gallery.attr('type');
                this.send({code:code, field:field, style:style});
            }
        }

    },
    send: function (data) {
        $.ajax({
            type: 'post',
            url: location.origin + '/blocks/gallery',
            data: data,
            success: function (html)
            {
                $('hr['+data.field+'="'+data.code+'"]').replaceWith(html);
            }
        });
    }
}

new BlockGallery;