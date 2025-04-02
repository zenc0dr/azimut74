# Виджит История

## Краткое описание мест инъекций в других модулях


Избранное

На сайте присутствуют несколько основных модулей:
1. Туры
2. Груповые туры
3. Автобусные туры
4. Круизы
5. Отели и санатории



1. Туры
Фронт VUE находится - /themes/azimut-tur-pro/src/components/dolphin/ext-results/ExtResultSchedule.vue
Фронт SCSS находится - /themes/azimut-tur-pro/src/components/dolphin/ext-results.scss

2. Груповые туры
Фронт VUE находится - /themes/azimut-tur-pro/src/components/dolphin/ext-results/ExtResultCatalog.vue
Фронт SCSS находится - /themes/azimut-tur-pro/src/components/dolphin/ext-results.scss

3. Автобусные туры
Фронт VUE находится - /plugins/zen/dolphin/vue/dolphin-widget/src/components/AtmResults.vue
Фронт SCSS находится -  /plugins/zen/dolphin/vue/dolphin-widget/src/assets/scss/widget.scss

4. Круизы
Фронт VUE находится -  /themes/azimut-tur-pro/src/components/search-widget/components/SearchRecords.vue
Фронт SCSS находится - /themes/azimut-tur-pro/src/components/search-widget/_.scss

5. Отели и санатории
Фронт VUE находится - /plugins/mcmraak/sans/views/results.htm
Фронт SCSS находится - /themes/azimut-tur/assets/css/sans.css

### О самом виджите
Сам компонент vue /plugins/zen/history/src/components/widget-history/WidgetHistory.vue
Front-end widget - /plugins/zen/history/src собирается через npx mix

## Как использовать ?

1. В шаблон сайта неходимо подключить собранные front-end файлы 
<link rel="stylesheet" href="/plugins/zen/history/assets/css/history.css">
<script src="/plugins/zen/history/assets/js/manifest.js"></script>
<script src="/plugins/zen/history/assets/js/history.js"></script>

2. Создать блок с id id="widget-history" в него будет загружен виджет

## Нюансы

1. Куки должны жить 30 дней (зависит от браузера) с момента захода пользователя на сайт, после история и избранное пользователя должны будут создаваться заново!
2. Сохраненные поиски трансформируются с помощью SearchTransformer в зависимости от типа поиска
3. Сердечки обновляются каждые 3.3с если удалить из списка)


Взаимодействуем с поиском через searchAPI 



# SearchApi

## Как использовать

```javascript
created() { // В хуке created или mounted
	SearchApi.onSearch = preset => {
		console.log(preset) // Получить пресет
	}
}

// Выполнить поиск с помощью полученного  пресета
SearchApi.search(preset)
```







### Общая механика для интеграции в поисковые виджеты

##### Зарегистрировать объект

```js
window.SearchApi = {
            onSearch: (preset) => {},
            search: (preset) => {
                preset = JSON.parse(preset)
                // Заполнение формы из пресета
                this.search()
            }
        }
```

##### Вызвать поиск

```js
this.sync({
    data: {ids:this.ids[page]},
    method: 'search',
    then: response => {
        this.result = response.items
        //------------------------------------
        if (response.items && response.items.length) {
            let preset = {
                name: 'Речные круизы',
                d1: this.form.date_1,
                d2: this.form.date_2,
                t1: this.form.town.value,
                t2: this.form.dest.value,
                days: this.form.days.value,
                ship: this.form.ship.value
            }
            SearchApi.onSearch(JSON.stringify(preset))
        }
        //---------------------------------------
    }
})
```



## Изменяемые файлы

### RiverCRS

themes/azimut-tur-pro/src/components/search-widget/SearchWidget.vue 147, 610



### DOLPHIN

EXT themes/azimut-tur-pro/src/components/dolphin/ExtWidget.vue 147, 319

ATM plugins/zen/dolphin/vue/dolphin-widget/src/widgets/AtmSearch.vue 111

ATM plugins/zen/dolphin/vue/dolphin-widget/src/store/AtmStore.js 68



### GROUP TOURS

themes/azimut-tur-pro/src/components/group-tours/gt-search-widget.vue 85, 195



### SANS

plugins/mcmraak/sans/assets/js/sans.search.js 526









