<template>

    <div>

        <div v-if="canUpload">
            <div ref="statusBar"></div>
            <div ref="dragDrop"></div>
        </div>

    </div>

</template>

<script>
import { Uppy } from '@uppy/core'
import DragDrop from '@uppy/drag-drop';
import StatusBar from '@uppy/status-bar';
import AwsS3 from '@uppy/aws-s3'

import '@uppy/core/dist/style.min.css';
import '@uppy/drag-drop/dist/style.min.css';
import '@uppy/status-bar/dist/style.min.css';

export default {

    mixins: [Fieldtype],

    data() {
        return {
            //
        };
    },

    mounted() {
        this.initUppy();
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

        initUppy() {
            if (!this.canUpload) {
                return;
            }

            const uppy = new Uppy({
                autoProceed: true,
            });

            uppy.use(DragDrop, {
                target: this.$refs.dragDrop,
            });
            uppy.use(StatusBar, {
                target: this.$refs.statusBar,
            });

            uppy.use(AwsS3, {
                shouldUseMultipart: true,  
                createMultipartUpload: async (file, signal) => {
                    signal?.throwIfAborted();
                    const url = cp_url('large-assets/api/upload/create');
                    const response = await this.$axios.post(url, {
                        container: this.config.container,
                        key: file.name,
                    });
                    return response.data;
                },
                signPart: async (file, { uploadId, key, partNumber, signal }) => {
                    signal?.throwIfAborted();
                    const url = cp_url('large-assets/api/upload/sign-part');
                    const response = await this.$axios.post(url, {
                        container: this.config.container,
                        key: key,
                        uploadId: uploadId,
                        partNumber: partNumber,
                    });
                    return response.data;
                },
                completeMultipartUpload: async (file, { key, uploadId, parts }, signal) => {
                    signal?.throwIfAborted()
                    const url = cp_url('large-assets/api/upload/complete');
                    const response = await this.$axios.post(url, {
                        container: this.config.container,
                        key: key,
                        uploadId: uploadId,
                        parts: parts,
                    });
                    return response.data;
                },
                listParts: async (file, { key, uploadId }, signal) => {
                    signal?.throwIfAborted()
                    const url = cp_url('large-assets/api/upload/list-parts');
                    const response = await this.$axios.post(url, {
                        container: this.config.container,
                        key: key,
                        uploadId: uploadId,
                    });
                    return response.data;
                },
                abortMultipartUpload: async (file, { key, uploadId }) => {
                    const url = cp_url('large-assets/api/upload/abort');
                    await this.$axios.post(url, {
                        container: this.config.container,
                        key: key,
                        uploadId: uploadId,
                    });
                },
            });

            uppy.on('complete', (result) => {
                console.log(
                    'Upload complete! We’ve uploaded these files:',
                    result.successful,
                )
            });
            uppy.on('upload-success', (file, data) => {
                console.log(
                    'Upload success! We’ve uploaded this file:',
                    file.meta['name'],
                )
            });
        }

    }

};
</script>
