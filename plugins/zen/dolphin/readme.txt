# Описание жизненного цикла поискового виджета (экскурсионные туры)

1) Роут /ex-tours/:?* Приводит на страницу /themes/azimut-tur/pages/dolphin/extours.htm

2) На этой странице подключён компонент DolphinWidget генерируемый файлом /plugins/zen/dolphin/components/Widget.php

3) Компонент вставляет в тело страницы содержимое файла /plugins/zen/dolphin/vue/dolphin-widget/assets/index.html

4) В момент загрузки виджет наполняет форму информацие из роута /zen/dolphin/api/Search@geoTree?widget=ext
   где передаётся параметр widget, который может быть равен следующим значениям
   ext - Поисковый виджет экскурсионных туров
   exp - Поисковый виджет автобусных туров
   возвращаемая информация это дерево гео-объектов наполняющее элемент управления "Куда"

5) Загрузку данных с бекенда на виджет осуществляет метод-обёртка apiQuery() /plugins/zen/dolphin/vue/dolphin-widget/src/store/index.js

6) Пользователь выбирает параметры и нажимает кнопку "Подобрать"
7) Срабатывает метод searchStart() который запускает метод streamSync() в файле plugins/zen/dolphin/vue/dolphin-widget/src/ExtoursWidget.vue
6) В файле plugins/zen/dolphin/vue/dolphin-widget/src/store/ext_widget.js метод streamQuery() делает запрос к бекенду через обёртку apiQuery()
7) Данные идут по роуту /zen/dolphin/api/Search@stream в файл plugins/zen/dolphin/api/Search.php в метод stream()
8) Если поток уже был создан, то обрабатывается его состояние и возвращается методом renderStream()
9) Если поток ещё не создан то:
    9.1 - Методом renderStream() вычисляются гео-объекты приналдежащие дельфину, и записываются в атрибут areas
    9.2 - Методом getExtours() Вычисляются комплексные туры и записываются в атрибут extours

10) Из данных формы создаётся поток методом runStream()

