<script>
import Uploader from './Uploader.vue';

export default {

    extends: Vue.component('assets-fieldtype'),

    components: {
        Uploader,
    },

    mounted() {
        this.$el.closest('.large_assets-fieldtype').classList.add('assets-fieldtype');
        this.$refs.uploader.initialize(this.config, this.meta);
    },

};
</script>
