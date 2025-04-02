<template>
    <div v-if="block_data">
        <InjectReviews v-if="block_data.block_type === 'reviews'" :reviews-data="block_data"/>
    </div>
</template>

<script>
import InjectReviews from "./InjectReviews";
export default {
    name: "LiveComponent",
    components: { InjectReviews },
    props: {
        jsonString: {
            type: String,
            default: null
        }
    },
    computed: {
        block_data()
        {
            return JSON.parse(this.jsonString)
        }
    }
}
</script>
