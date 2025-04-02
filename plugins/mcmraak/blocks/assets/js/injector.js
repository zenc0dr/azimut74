function Injector()
{
    this.options = {}
    this.blocks = {}
}

Injector.prototype = {
    ready() {
        let $this = this
        $.ajax({
            type: 'post',
            data: {
                scope_id: $this.options.scope_id
            },
            url: location.origin + '/blocks/injects',
            success(data)
            {
                if (!data) {
                    return
                }
                data = JSON.parse(data)
                $this.blocks = data
                $this.steady()
            },
            error(x)
            {
                console.log(x.responseText)
            },
        });
    },
    steady() {
        let $this = this;
        this.box = new MutationObserver(function (mutations ) {
            mutations.forEach(function (mutation) {
                let newNodes = mutation.addedNodes
                if ( newNodes !== null ) {
                    if ($('inject').length === 0) {
                        $($this.options.scope).append('<inject>')
                        $this.go()
                    }
                }
            })
        })
        this.box.observe($($this.options.scope)[0], {
            attributes: true,
            childList: true,
            characterData: true
        })
    },
    go() {
        let blocks_count = this.options.count();
        if (blocks_count > 0) {
            this.inject();
        }
    },
    inject() {
        for (let i in this.blocks) {
            let block = this.blocks[i];
            let sequence = block.sequence;
            let markers = sequence.split(',');

            for (let ii in markers) {
                this.putBlock(block.html, markers[ii]);
            }
        }
        if (typeof injectReady == 'function') {
            injectReady()
        }
    },
    putBlock(block, marker) {
        let i;
        if (/^\+/.test(marker)) {
            var m = parseInt(marker);
            m--;
            var afterblock = $(this.options.block)[m];
            $(block).insertAfter(afterblock);

        }
        else {
            let m = parseInt(marker);
            let pp = this.options.per_page
            let page = parseInt(this.options.page())
            let ip = Math.ceil(m / pp)
            if (page === ip) {
                let ia = pp - (ip * pp - m);
                ia--;
                let afterblock = $(this.options.block)[ia];
                $(block).insertAfter(afterblock);
            }
        }
    }
}
