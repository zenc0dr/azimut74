<template>
    <div id="widget-history">
        <div class="widget-inner" >
            <div class="widget-content__items" v-click-outside="hide">
                <favorite-history
                    :favorite="favoriteItems"
                    :activeMenu="activeMenu"
                    @switch-menu="switchActiveMenu"
                    @remove-all="removeFavAll"
                />
                <search-history
                    :search="favoriteSearch"
                    :activeMenu="activeMenu"
                    @switch-menu="switchActiveMenu"
                    @remove-all="removeSearchAll"
                />
            </div>
        </div>
    </div>
</template>

<script>
import Modal from "@components/modal/Modal";
import ClickOutside from 'vue-click-outside';
import SearchHistory from './SearchHistory.vue';
import FavoriteHistory from './FavoriteHistory.vue';

export default {
    name: "WidgetSearch",
    components: {
        Modal,
        SearchHistory,
        FavoriteHistory
    },
    directives: {
        ClickOutside
    },
    data() {
        return {
            activeMenu: 0,
            favoriteItems: {
                items: [],
                maxPrevItem: 3,
                showModal: false
            },
            favoriteSearch: {
                items: [],
                maxPrevItem: 4,
                showModal: false,
            },
            userId: null,
        }
    },
    created() {
        //this.deleteAllCookies()
        this.initUserId()

        const self = this;
        setInterval(function() {
            self.setFavDom()
        }, 3300)

        this.getFavItems()
        this.getSearchItems()
    },
    mounted() {
        if (window.SearchApi) {
            SearchApi.onSearch = preset => {
                this.setSearchHistory(preset)
            }
        }
    },
    computed: {
        getFavItemsSize() {
            return this.favoriteItems.items  ? this.favoriteItems.items.length : 0;
        },
        titleCountFavorite() {
            if (this.favoriteSearch.items) {
                if (this.favoriteSearch.items.length > this.favoriteSearch.maxPrevItem ) {
                    return `${this.favoriteSearch.maxPrevItem} из ${this.favoriteSearch.items.length}`
                }
            }
        },
    },
    methods: {
        hide () {
            this.activeMenu = 0
        },
        initUserId() {
            let visiterId = localStorage.getItem('visiterID')

            let userID = null
            if (visiterId == null) {
                let userID = this.generateUserId()
                localStorage.setItem('visiterID', userID)
            } else {
                userID = visiterId
            }
            this.userId = userID

            window.WidgetHistory = {
                'userId': this.userId,
                'url':  document.URL,
                'title': document.title,
                'favoriteItems': [],
                'favoriteSearch': []
            }
        },
        setSearchHistory(preset) {
            APP.api({
                url: '/zen/history/api/History:setSearchHistoryItems',
                data: {
                    preset,
                    'visiterId': this.userId
                },
                then: resonse => {
                    this.favoriteSearch.items = resonse.items
                    window.WidgetHistory.favoriteSearch = resonse.items
                }
            })
        },
        setFavDom() {
            this.favoriteItems.items = window.WidgetHistory.favoriteItems
            if (this.favoriteItems.items) {
                let results = document.getElementsByClassName("widget-history__favorite");
                for (let i = 0; i < results.length; i++) {
                    let id = results[i].getAttribute('widget-id')
                    let type_code = results[i].getAttribute('type')
                    let url = results[i].getAttribute('url')

                    let filter = value => value.inner_id == id && value.type == type_code && value.url == url
                    if (type_code == 'autobus-tours') {
                        filter = value => value.inner_id == id && value.type == type_code
                    }

                    let favorite = this.favoriteItems.items.filter(filter)

                    if (favorite.length) {
                        results[i].classList.add('active')
                    } else if (results[i].classList.contains('active')) {
                        results[i].classList.remove('active')
                    }
                }
            }
        },
        removeFavAll() {
            APP.api({
                url: '/zen/history/api/History:removeFavAll',
                data: {
                    'visiterId': this.userId
                },
                then: resonse => {
                    this.favoriteItems.items = []
                    window.WidgetHistory.favoriteItems = this.favoriteItems.items
                }
            })
        },
        removeSearchAll() {
            APP.api({
                url: '/zen/history/api/History:removeSearchAll',
                data: {
                'visiterId': this.userId
                },
                then: resonse => {
                    this.favoriteSearch.items = []
                    window.WidgetHistory.favoriteSearch = this.favoriteSearch.items
                }
            })
        },
        generateUserId() {
            return Math.random().toString(36).substr(2, 12);
        },
        deleteAllCookies() {
            const cookies = document.cookie.split(";");

            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i];
                const eqPos = cookie.indexOf("=");
                const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=None;";
            }
        },
        switchActiveMenu(id) {
            if (this.activeMenu == id) {
                this.activeMenu = 0
            } else {
                this.activeMenu = id
            }
        },

        getFavItems() {
            APP.api({
                url: '/zen/history/api/History:getFavoriteItems',
                data: {
                    'visiterId': this.userId
                },
                then: resonse => {
                    this.favoriteItems.items = resonse.items
                    window.WidgetHistory.favoriteItems = resonse.items
                }
            })
        },

        getSearchItems() {
            APP.api({
                url: '/zen/history/api/History:getSearchHistoryItems',
                data: {
                    'visiterId': this.userId
                },
                then: resonse => {
                    this.favoriteSearch.items = resonse.items
                    window.WidgetHistory.favoriteSearch = resonse.items
                }
            })
        }
    },
}
</script>
