<style>
    #exist_debug {
        width: 100%;
        height: 500px;
    }
    .exist_desc {
        background: #3194ce;
        font-family: Verdana;
        font-size: 11px;
        font-weight: bold;
        padding: 10px;
        color: #fff;
    }
    .exist_desc tab {
        display: inline-block;
        width: 25px;
    }
</style>
<h3>Дамп данных</h3>
<iframe id="exist_debug" src="/rivercrs/api/v2/exist/<?=$model->id?>?debug" frameborder="0"></iframe>