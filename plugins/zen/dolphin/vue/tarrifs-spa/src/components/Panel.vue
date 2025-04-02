<template>
    <div class="tarrifs-panel">
        <div @click="$parent.openTarrif(null)" class="btn btn-primary oc-icon-plus">Создать тариф</div>
    </div>
</template>
<script>
    export default {
        name: 'Panel'
    }
</script>
