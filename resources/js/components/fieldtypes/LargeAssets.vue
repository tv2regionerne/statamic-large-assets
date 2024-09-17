<template>

    <div>

        <input
            v-if="canUpload"
            @input="fileSelected"
            ref="upload"
            type="file"
            :accept="acceptMimeTypes"
            class="hidden" />
            
        <button
            v-if="canUpload"
            type="button"
            class="btn btn-with-icon grow w-full"
            @click="openUpload"
            @keyup.space.enter="openUpload"
            tabindex="0">
            <svg-icon name="regular/folder-image" class="w-4 h-4 text-gray-800"></svg-icon>
            <span class="tv2r-sm">{{ __('Upload Media') }}</span>
        </button>

    </div>

</template>

<script>
import {
  CreateMultipartUploadCommand,
  UploadPartCommand,
  CompleteMultipartUploadCommand,
  AbortMultipartUploadCommand,
  S3Client,
} from "@aws-sdk/client-s3";

export default {

    mixins: [Fieldtype],

    data() {
        return {
            //
        };
    },

    computed: {

        acceptMimeTypes() {
            return null;
            // const types = Statamic.$config.get(`advanced_assets.containers.${this.container}.mime_types`, []);
            // return types ? types.join(',') : null;
        },

        canUpload() {
            return this.config.allow_uploads && (this.can('configure asset containers') || this.can('upload '+ this.container +' assets'))
        },

    },

    methods: {

        openUpload() {
            this.$refs.upload.click();
        },

        fileSelected(event) {
            if (!this.canUpload) {
                return;
            }
            const files = event.target.files || event.dataTransfer.files;
            if (!files.length) {
                return;
            }
            const file = files[0];
            this.fileUpload(file);
            // this.editorMode = 'create';
            // this.editorFile = files[0];
            // this.openAsset();
        },

        async fileUpload(file) {
            const axios = this.freshAxios();

            const container = this.config.container;
            const path = file.name;
            const size = file.size;

            const createUrl = cp_url('large-assets/api/upload/create');
            const createResponse = await axios.post(createUrl, {
                container, path, size
            });

            const uploadId = createResponse.data.uploadId;
            const parts = await Promise.all(createResponse.data.parts
                .map(async (part) => {
                    const slice = file.slice(part.start, part.end);
                    const uploadResponse = await axios.put(part.url, slice);
                    return {
                        ...part,
                        eTag: uploadResponse.headers.etag,
                    };
                }));
                      
            const completeUrl = cp_url('large-assets/api/upload/complete');  
            const completeResponse = await axios.post(completeUrl, {
                container, path, size, uploadId, parts,
            });
        },

        freshAxios() {
            // Create a fresh axios instance without the Statamic/Laravel headers
            // https://github.com/statamic/cms/blob/5.x/resources/js/app.js#L58-L59
            const axios = this.$axios.create();
            delete axios.defaults.headers.common['X-CSRF-TOKEN'];
            delete axios.defaults.headers.common['X-Requested-With'];
            return axios;
        },
    }

};
</script>
