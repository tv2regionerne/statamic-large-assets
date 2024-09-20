<template>

    <modal
        width="600px"
        @closed="$emit('closed')">

        <div slot-scope="{ close }">

            <header class="text-lg font-semibold px-5 py-3 bg-gray-200 dark:bg-dark-550 rounded-t-lg flex items-center justify-between border-b dark:border-dark-900">
                {{ __('Upload File') }}
            </header>

            <div class="flex-1 px-5 py-6 text-gray dark:text-dark-150">
                <div v-if="loading" class="text-center">
                    <loading-graphic :text="null" />
                </div>
                <div v-else>
                    <publish-container
                        name="asset"
                        :blueprint="blueprint"
                        :values="values"
                        :meta="meta"
                        :errors="errors"
                        @updated="values = $event"
                    >
                        <div slot-scope="{ setFieldValue, setFieldMeta }">
                            <publish-sections
                                :sections="blueprint.tabs[0].sections"
                                @updated="setFieldValue"
                                @meta-updated="setFieldMeta"
                            />
                        </div>
                    </publish-container>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-200 dark:bg-dark-550 rounded-b-lg border-t dark:border-dark-900 flex items-center justify-end text-sm">
                <button class="btn" @click="close" v-text="__('Cancel')" />
                <button class="btn-primary rtl:mr-4 ltr:ml-4" @click="save" :disabled="loading" v-text="__('Upload')" />
            </div>
            
        </div>

    </modal>

</template>

<script>
export default {

    props: {
        container: String,
        file: Object,
    },

    data() {
        return {
            loading: true,
            saving: false,
            blueprint: {},
            values: {},
            meta: {},
            errors: {},
        };
    },

    mounted() {
        this.load();
    },

    methods: {

        load() {
            this.loading = true;
            const url = cp_url(`large-assets/api/assets?container=${this.container}`);
            this.$axios.get(url).then(response => {
                this.blueprint = response.data.blueprint;
                this.values = response.data.values;
                this.meta = response.data.meta;
                this.loading = false;
            });
        },

        save() {
            this.saving = true;
            const url = cp_url(`large-assets/api/assets?container=${this.container}`);
            this.$axios.post(url, {
                values: this.values,
            }).then(response => {
                this.saving = false;
                this.$emit('saved', response.data.values);
            }).catch(e => {
                this.saving = false;
                if (e.response && e.response.status === 422) {
                    const { message, errors } = e.response.data;
                    this.error = message;
                    this.errors = errors;
                    this.$toast.error(message);
                } else if (e.response) {
                    this.$toast.error(e.response.data.message);
                } else {
                    this.$toast.error(__('Something went wrong'));
                }
            });
        },

    }

}
</script>
