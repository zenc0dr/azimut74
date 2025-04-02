var EX = new Vue({
    el: '#EX',
    data: {
        modal: false,
        points: [],
        cabins_list: [],
        default:{
            w:25,
            h:25,
        }
    },
    mounted: function () {
        this.loadPoints();
    },
    watch: {
        points: {
            handler: function() {
                this.savePoints();
            },
            deep: true
        },
    },
    methods: {
        openPoint: function (point) {
            for(i in this.points){
                this.points[i].popup = false;
            }
            point.popup = true;
            this.default.w = point.w;
            this.default.h = point.h;
        },
        addPoint: function (x,y) {
            this.points.push({
                popup: false,
                name: '000',
                c:0,
                w:this.default.w,
                h:this.default.h,
                x:x,
                y:y,
            });
        },
        delPoint: function (key) {
            this.points.splice(key, 1);
        },
        loadPoints: function () {
            var points = $('[name="Motorships[exist_rooms]"]').val();
            if(!points) points = '[]';
            points = JSON.parse(points);
            for(i in points) {
                if(points[i].c === undefined) {
                    points[i].c = 0;
                }
                this.points.push({
                    popup: false,
                    name: points[i].n,
                    c:points[i].c,
                    w:points[i].w,
                    h:points[i].h,
                    x:points[i].x,
                    y:points[i].y,
                });
            }
            var cabins_list = $('[name="cabins_list"]').val();
            if(!cabins_list) cabins_list = '[]';
            this.cabins_list = JSON.parse(cabins_list);
        },
        savePoints: function () {
            var points = [];
            for(i in this.points) {
                points.push({
                    n:this.points[i].name,
                    c:this.points[i].c,
                    w:this.points[i].w,
                    h:this.points[i].h,
                    x:this.points[i].x,
                    y:this.points[i].y,
                });
            }
            $('[name="Motorships[exist_rooms]"]').val(JSON.stringify(points));
        }
    }
});

$(document).on('click', '#Scheme', function (e) {
    var x = e.offsetX==undefined?e.layerX:e.offsetX;
    var y = e.offsetY==undefined?e.layerY:e.offsetY;
    EX.addPoint(x - 10, y - 10);
});