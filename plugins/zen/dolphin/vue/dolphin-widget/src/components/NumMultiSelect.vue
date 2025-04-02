<template>
    <div class="zen-num-multi-select">
        <div class="dw-input-title">{{ title }}</div>
        <div class="znms-body">
            <div class="znms-text" @click="openList">{{ show }}</div>
            <div v-if="show_list" class="znms-list" v-click-outside="closeList">
                <div @click="check(option.value)"
                     v-for="option in options"
                     class="znms-option"
                     :class="{limit:(!option.checked && selectedNums.length >= limit)}">
                    {{ option.value }} <i :class="checkIcon(option.checked)"></i>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import ClickOutside from "../directives/ClickOutside";
    export default {
        name: 'NumMultiSelect',
        props: {
            title: String,
            store: String,
            min: Number,
            max: Number,
            limit: { Type: Number, default: 3 },
        },
        directives: {
            'click-outside': ClickOutside
        },
        data()
        {
            return {
                output: [],
                show_list: 0,
            }
        },
        watch: {
          limit(limit) {
              if(limit == this.max) {

                  let value = []
                  for(let i=0; i<this.max + 1;i++) {
                      value.push(i)
                  }

                  this.$store.commit('set'+this.store, value)
              } else {
                  this.$store.commit('set'+this.store, [3,4])
              }
          }
        },
        computed: {
            show(){
                let value = this.selectedNums
                if(value.length < 4) {
                    return value.join(', ')
                } else {
                    let min = value[0]
                    let max = min
                    for (let i = 1; i < value.length; ++i) {
                        if (value[i] > max) max = value[i];
                        if (value[i] < min) min = value[i];
                    }
                    return min+' ~ '+max
                }
            },
            options() {
                let values = [];
                let modelValues = this.$store.getters['get'+this.store];

                for(let i=this.min;i<=this.max;i++) {
                    i = parseInt(i)
                    values.push({value:i, checked:(modelValues.indexOf(i)!=-1)})
                }
                return values
            },
            selectedNums: {
                get()
                {
                    return this.$store.getters['get'+this.store]
                },
                set(value)
                {
                    this.$store.commit('set'+this.store, value)
                }
            },
        },
        methods: {
            checkIcon(value)
            {
                if(value !== false) {
                    return 'fa fa-check-square checked'
                } else {
                    return 'fa fa-square'
                }
            },
            openList()
            {
                if(this.show_list > 0) {
                    this.show_list = 0
                } else {
                    this.show_list = 2
                }
            },
            closeList()
            {
                if(this.show_list > 0) this.show_list--
            },
            check(num)
            {
                num = parseInt(num)
                let index = this.selectedNums.indexOf(num)

                if(index == -1) {
                    if(this.selectedNums.length < this.limit) {
                        this.selectedNums.push(num)
                        this.selectedNums.sort(function(a,b){
                            return a - b
                        })
                    }
                } else {
                    this.selectedNums.splice(index, 1)
                    if(!this.selectedNums.length) {
                        this.selectedNums = [3]
                    }
                }
            },
        }
    }
</script>
<style>
    /* NumMultiSelect */
    .zen-num-multi-select {
        width: 80px;
        user-select: none;
    }

    .znms-body {
        border: 1px solid #d0d0d0;
        background: #fff;
        border-radius: 3px;
        display: flex;
        justify-content: center;
        height: 34px;
        width: inherit;
    }

    .znms-text {
        text-align: center;
        width: 100%;
        padding-top: 5px;
        cursor: pointer;
    }

    .znms-list {
        position: absolute;
        background-color: #fff;
        width: inherit;
        padding: 10px;
        padding-top: 5px;
        margin-top: -2px;
        border: 1px solid #d0d0d0;
        border-top: none;
        z-index: 1;
        height: 184px;
        border-radius: 3px;
        overflow-y: auto;
    }

    .znms-option {
        display: inline-block;
        width: 100%;
        text-align: right;
        padding-right: 4px;
        cursor: pointer;
    }

    .znms-option i {
        color: #80bff3;
        transition: 300ms;
    }

    .znms-option i.checked {
        color: #e2412f;
    }
    .znms-option.limit i {
        color: #dbdbdb;
    }
</style>
