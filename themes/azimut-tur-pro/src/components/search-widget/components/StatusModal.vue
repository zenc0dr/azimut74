<template>
    <Modal :show="record !== null" @close="$emit('close')" :title="record ? record.motorship_status : null">
        <template v-if="record !== null">
            <div class="status-desc" v-html="record.motorship_status_desc"></div>
        </template>
    </Modal>
</template>

<script>
import Modal from "../../vue-components/Modal";
export default {
    name: "StatusModal",
    components: { Modal },
    props: {
        record: null
    }
}
</script>
