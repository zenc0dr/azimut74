/* Multu drop down */
Vue.component('mdropdown', {
    props: {
        id: String,
        opt: Array,
        any: {
            type: String,
            default: 'Любой(ая)'
        }
    },
    data() {
        return {
            anyDesktop: 'Любой(ая)',
            popup: false,
            options: [],
            filter: '',
        }
    },
    mounted() {
        if(!Array.isArray(this.opt.value)) {
            if(!this.opt.value) {
                this.opt.value = []
            } else {
                this.opt.value = this.opt.value.split(',')
            }
        }
        this.closeEvent()
    },
    computed: {
        title() {
            let ln = this.opt.value.length
            if(!ln) {
                if (document.documentElement.clientWidth < 992) {
                    return this.any
                } else {
                    return this.anyDesktop
                }
            }
            if(ln==1) return this.getValueById(this.opt.value[0])
            return 'Выбрано: '+ln
        }
    },
    methods: {
        isChecked(index) {
            let id = parseInt(this.opt.options[index].id)
            if(this.opt.value.indexOf(id) != -1) {
                return true
            }
            return false
        },
        getValueById(id) {
            for(let i in this.opt.options) {
                if(this.opt.options[i].id == id) {
                    return this.opt.options[i].name
                }
            }
        },
        check(index) {
            let id = parseInt(this.opt.options[index].id)
            let key = this.opt.value.indexOf(id)
            if(key == -1) {
                this.opt.value.push(id)
            }
            else {
                this.opt.value.splice(key, 1)
            }
        },
        closeEvent() {
            $(document).mouseup((e) => {
                let obj = $('#'+this.id+'.multi-drop')
                if(!obj.is(e.target) && obj.has(e.target).length === 0) {
                    this.popup = false
                    this.filter = ''
                }
            });
        },
        filterTest(need) {
            if(!this.filter) return true
            let filter = this.filter.toLowerCase()
            need = need.toLowerCase()
            if(need.indexOf(filter) !=-1 ) return true
        }
    },
    template: '<div :id="id" class="multi-drop">' +
        '<template v-if="popup && opt.options.length">' +
        '<div class="wrapper-absolute">' +
        '<div class="filter"><input v-model="filter" placeholder="Фильтр"></div>'+
        '<div @click="opt.value=[]" :class="{active:opt.value.length}" class="clean">Очистить</div>'+
        '<div class="options">'+
        '<div :class="{checked:isChecked(index)}" class="option"' +
        'v-if="filterTest(item.name)"'+
        'v-for="(item, index) in opt.options"' +
        '@click="check(index)">{{ item.name }}' +
        '<i class="fa fa-check"></i>' +
        '</div>' +
        '</div>' +
        '</div>'+
        '</template>'+
        '<div @click="popup=!popup" class="value" :class="{ selected : opt.value.length }">' +
        '<div>' +
        '<div class="label-original">{{ any }}</div>' +
        '<div class="input-wrapper">' +
        '<span>{{ title }}</span>'+
        '</div>'+
        '</div>'+
        '<i class="fa fa-angle-down"></i>' +
        '</div>'+
        '</div>'
});

/* Pagination */
Vue.component('pagination', {
    props: ['page', 'pages_count'],
    methods: {
        goPage:function (page) {
            this.$emit('go', page)
        }
    },
    template:   '<div v-if="pages_count > 1" class="pro-kruiz-pagination">' +
        '<ul class="pagination">' +
        '<template v-if="pages_count < 11">' +
        '<li v-for="n in pages_count">' +
        '<span class="pagination-item" :class="{active:(n == page+1)}" v-if="n == page+1">{{ n }}</span>' +
        '<span @click="goPage(n-1)" class="pagination-item" v-else href="#">{{ n }}</span>' +
        '</li>'+
        '</template>'+
        '<template v-else>' +
        '<li @click="goPage(page-1)" class="control-arrow"><span class="pagination-control" :class="{active:(page>0)}"><img src="/themes/prokruiz/assets/img/svg/left-arrow.svg" alt="left-pagination"></span></li>'+
        '<li @click="goPage(0)" v-if="page > 3"><span class="pagination-item">1</span></li>'+
        '<li v-if="page > 3"><span>...</span></li>' +
        '<li class="test_f"><span class="pagination-item active">{{ page+1 }}</span></li>' +
        '<li class="test_x" @click="goPage(page+1)" v-if="(page+1) < pages_count && (page+2) != pages_count"><span class="pagination-item">{{ page+2 }}</span></li>' +
        '<li class="test_y" @click="goPage(page+2)" v-if="(page+2) < pages_count && (page+3) != pages_count"><span class="pagination-item">{{ page+3 }}</span></li>' +
        '<li v-if="(page+2) < pages_count"><span>...</span></li>' +
        '<li class="test_z" v-if="(page+1) != pages_count" @click="goPage(pages_count-1)"><span class="pagination-item">{{ pages_count }}</span></li>' +
        '<li @click="goPage(page+1)" class="control-arrow"><span class="pagination-control" :class="{active:(page!=pages_count-1)}"><img src="/themes/prokruiz/assets/img/svg/right-arrow.svg" alt="right-pagination"></span></li>'+
        '</template>'+
        '</ul>'+
        '</div>'
});
