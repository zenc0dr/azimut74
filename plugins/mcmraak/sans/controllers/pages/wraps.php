<?php
    if(!$model->id) {
        echo 'Страница ещё не создана!';
        return;
    }
?>
<script src="/plugins/mcmraak/sans/assets/js/vue.min.js"></script>
<link rel="stylesheet" href="/plugins/mcmraak/sans/assets/css/sans.wraps.css">

<div id="Wraps">
    <template v-if="!resort_changed">
    <div v-for="item in boxes" class="wrap-type-box">
        <div class="label">${ item.type }</div>
        <div class="options">
            <div @click="select(option, item.type)" v-for="option in item.options"
                 class="option"
                 :class="[(option == item.selected) ? 'selected' : '']"
            >
                ${option}
            </div>
        </div>
        <div class="add">
            <input :item-type="item.type">
            <div @click="add(item.type)" class="addbtn btn btn-primary">Добавить</div>
        </div>
    </div>
    </template>
    <div v-if="resort_changed">
        Изменён курорт, сохраните и обновите страницу для изменения эвентов.
    </div>
</div>

<script>
    var Wraps = new Vue({
        el: '#Wraps',
        delimiters: ['${', '}'],
        data:{
            resort_id: 0,
            boxes: {},
            resort_changed: false,
        },
        mounted: function () {
            this.resort_id = $('#Form-field-Page-resort_id').val();
            this.page_id = <?=$model->id?>;
            this.sync();
            this.resortChangeEvent();
        },
        methods: {
            sync: function(){
                var $this = this;
                $.ajax({
                    type: 'post',
                    data: {
                        page_id:$this.page_id,
                        resort_id:$this.resort_id,
                    },
                    url: location.origin + '/sans/api/v1/wraps/getdata',
                    beforeSend: function (){

                    },
                    success: function (data)
                    {
                        $this.boxes = JSON.parse(data);
                    },
                    error: function(x)
                    {
                        $('html').html(x.responseText);
                    },
                });
            },
            resortChangeEvent: function(){

                var $this = this;
                $(document).on('change', '#Form-field-Page-resort_id', function(){
                    //$this.resort_id = $(this).val();
                    //$this.sync();
                    $this.resort_changed = true;
                    console.log('changed');
                });
            },
            select: function(option,type_name){
                var $this = this;
                $.ajax({
                    type: 'post',
                    data: {
                        page_id:$this.page_id,
                        resort_id:$this.resort_id,
                        option:option,
                        type_name:type_name,
                    },
                    url: location.origin + '/sans/api/v1/wraps/select',
                    success: function (data)
                    {
                        //$this.boxes = JSON.parse(data);
                        console.log(data);
                        $this.sync();
                    },
                    error: function(x)
                    {
                        $('html').html(x.responseText);
                    },
                });


            },
            add: function(type_name){
                var new_type = $("[item-type='"+type_name+"']").val();
                $("[item-type='"+type_name+"']").val('');
                var $this = this;
                $.ajax({
                    type: 'post',
                    data: {
                        page_id: $this.page_id,
                        resort_id: $this.resort_id,
                        option: new_type,
                        type_name: type_name,
                    },
                    url: location.origin + '/sans/api/v1/wraps/add',
                    success: function (data)
                    {
                        //$this.boxes = JSON.parse(data);
                        //console.log(data);
                        $this.sync();
                    },
                    error: function(x)
                    {
                        $('html').html(x.responseText);
                    },
                });
            },
        },
    });
</script>