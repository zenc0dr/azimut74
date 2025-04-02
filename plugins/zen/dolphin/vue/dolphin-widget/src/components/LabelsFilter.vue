<template>
    <div v-if="labels.length" id="LabelsFilter">
        <div
            v-for="label in labels"
            @click="checkLabel(label)"
            class="label-item"
            :class="{checked:label.checked}">
            {{ label.name }}
        </div>
    </div>
</template>
<script>
    export default {
        name: 'LabelsFilter',
        data() {
            return {
                labels: [],
                default_labels: null
            }
        },
        computed: {
            labels_data() {
                return this.$store.getters.getLabelsData
            }
        },
        mounted() {
            let default_labels = /labels=([^;]*);/g.exec(location.hash)

            if(!default_labels) {
                let preset = document.getElementById('dolphin-preset')
                if(preset) {
                    default_labels = /labels=([^;]*);/g.exec(preset.content)
                }
            }

            if(default_labels) this.default_labels = default_labels[1].split(',').map(function (item) { return parseInt(item) })
        },
        watch: {
            labels_data(labels_data) {
                this.labels = []

                labels_data.forEach((item) => {

                    item.id = parseInt(item.id)

                    let checked = false
                    if(this.default_labels && this.default_labels.indexOf(item.id) !== -1) checked = true

                    this.labels.push({
                        id:parseInt(item.id),
                        name:item.name,
                        tours:item.tours,
                        checked
                    })
                })

                if(this.default_labels) {
                    this.default_labels = null
                    this.filtrate()
                }
            }
        },
        methods: {
            checkLabel(label) {
                label.checked = !label.checked
                this.filtrate()
            },

            filtrate() {
                this.$emit('filtrate', this.labels)
            }
        }
    }
</script>
