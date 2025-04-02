<template>
    <div class="mb-5">
        <section v-if="pages_count > 1" class="project-pagination d-flex justify-content-center align-items-center my-3 ">
            <ul class="pagination d-flex justify-content-center ps-0 mt-0 mb-0">
                <template v-if="pages_count < 11">
                    <li v-for="n in pages_count" class="test_f fs-h2 fs-sm-h3fw-bolder c-primary-200 d-flex align-items-center">
                        <span @click="goPage(n - 1)"
                              :class="{active:(n === page + 1)}"
                              class="pagination-item d-flex align-items-center justify-content-center mx-1">
                            {{ n }}
                        </span>
                    </li>
                </template>
                <template v-else>
                    <!-- Стрелка влево -->
                    <li @click="goPage(page - 1)" class="control-arrow fs-sm-h3 fs-h1 fw-bolder c-primary-200 d-flex align-items-center px-3 fs-">
                        <span :class="{active:(page > 0)}" class="pagination-control">
                            <img src="/themes/azimut-tur-pro/assets/images/components/pagination/angle-left-solid.svg" alt="left-pagination">
                        </span>
                    </li>

                    <!-- Первая страница, показывается только если выбрана страница > 3 -->
                    <li v-if="page > 3" @click="goPage(0)" class="test_f fs-h2 fs-sm-h3fw-bolder c-primary-200 d-flex align-items-center">
                        <span class="pagination-item d-flex align-items-center justify-content-center mx-1">1</span>
                    </li>

                    <!-- Многоточие -->
                    <li v-if="page > 3" class="fs-sm-h3fs-h2 fw-bolder c-primary-200 d-flex align-items-center">
                        <span>...</span>
                    </li>

                    <!-- Активная страница -->
                    <li class="test_y fs-h2 fs-sm-h3fw-bolder c-primary-200 d-flex align-items-center">
                        <span class="pagination-item active d-flex align-items-center justify-content-center mx-1">{{ page + 1 }}</span>
                    </li>

                    <!-- Дополнительная страница 1 -->
                    <li v-if="(page + 1) < pages_count && (page + 2) !== pages_count"
                        @click="goPage(page + 1)"
                        class="test_y fs-h2 fs-sm-h3fw-bolder c-primary-200 d-flex align-items-center">
                        <span class="pagination-item d-flex align-items-center justify-content-center mx-1">{{ page + 2 }}</span>
                    </li>

                    <!-- Дополнительная страница 2 -->
                    <li v-if="(page + 2) < pages_count && (page + 3) !== pages_count"
                        @click="goPage(page + 2)"
                        class="test_y fs-h2 fs-sm-h3fw-bolder c-primary-200 d-flex align-items-center">
                        <span class="pagination-item d-flex align-items-center justify-content-center mx-1">{{ page + 3 }}</span>
                    </li>

                    <!-- Многоточие -->
                    <li v-if="(page + 2) < pages_count" class="fs-sm-h3fs-h2 fw-bolder c-primary-200 d-flex align-items-center">
                        <span>...</span>
                    </li>

                    <!-- Стрелка вправо -->
                    <li @click="goPage(page + 1)" class="control-arrow  d-flex align-items-center px-3">
                        <span :class="{active:(page !== pages_count - 1)}" class="pagination-control">
                            <img src="/themes/azimut-tur-pro/assets/images/components/pagination/angle-right-solid.svg" alt="right-pagination">
                        </span>
                    </li>
                </template>
            </ul>
        </section>
    </div>
</template>

<script>
export default {
    name: 'Pagination',
    props: {
        page: null,
        pages_count:null
    },
    methods: {
        goPage(page)
        {
            this.$emit('go', page)
        }
    }
}
</script>
