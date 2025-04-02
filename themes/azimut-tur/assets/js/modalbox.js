$(document).on('click', '[modalbox]', function (e) {
    e.preventDefault();
    var element = $(this);
    var settings = element.attr('modalbox');
    var css = /css:\{(.*)\}/g.exec(settings);

    if(css) {
        settings = settings.replace(css[0], '');
        css = css[1];
    }

    settings = '{'+settings.replace( /([^:',]+)/g, '"$1"')+'}';
    settings = settings.replace(',}', '}');
    settings = settings.replace('{,', '{');
    settings = JSON.parse(settings);

    var href = null;
    var content = null;

    if(settings.source_attr!==undefined){
        href = $(this).attr(settings.source_attr);
    }

    if(settings.source_url!==undefined){
        href = settings.source_url;
    }

    if(href === null) {
        href = $(this).attr('href');
    }

    if(href === null) {
        href = $(this).attr('src');
    }

    if(href === null) {
        return;
    }

    if(settings.type == 'img') {
        content = '<img src="'+href+'">';
    }

    if(settings.type === undefined) {

        var location_prefix = (/^http*/.test(href))?href:location.origin+href;

        $.ajax({
            url: location_prefix,
            type: 'get',
            beforeSend: function (){
                // Preloader show
            },
            success: function (data)
            {
                addModalBox(element, data, css);
                if(settings.eval !== undefined) {
                    eval(settings.eval);
                }
            },
            error: function(x)
            {
                addModalBox(element, x.responseText, css);
            },
        });
    }

    if(settings.type) {
        addModalBox(element, content, css);
    }

});
$(document).on('click', '.mb-close', function () {
    modalBoxClose($(this).closest('#_Modalbox'))
});

$(document).on("click", "#_Modalbox", function(e){
    if (e.target !== this) return;
    modalBoxClose($(this))
});

function modalBoxClose(modal_win)
{
    modal_win.fadeOut(200, function () {
        modal_win.remove();
        if(!$('#_Modalbox').length) $('body').css('overflow','auto');
    });
}

function addModalBox(element, content, css) {
    var modal_win =
    '<div id="_Modalbox">\n' +
    '    <div>\n' +
    '        <div>\n' +
    '            <div class="mb-header"><div class="mb-close">×</div></div>\n' +
    '            <div class="mb_body">'+content+'</div>\n' +
    '        </div>\n' +
    '    </div>\n' +
    '</div>';
    element.parent().append(modal_win);
    $('body').css('overflow','hidden');
    if(css) {
        css = '{'+css.replace( /([^:',]+)/g, '"$1"')+'}';
        eval("$('#_Modalbox>div').css("+css+");");
    }
    element.parent().find('#_Modalbox').show();
    //$('#_Modalbox').show();
}