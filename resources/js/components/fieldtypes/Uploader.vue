<template>

    <div
        @dragenter="dragenter"
        @dragover="dragover"
        @dragleave="dragleave"
        @drop="drop">
        <div :class="{ 'pointer-events-none': dragging }">
            <input class="hidden" type="file" multiple ref="nativeFileField">
            <slot :dragging="enabled ? dragging : false"></slot>
        </div>
    </div>

</template>

<script>
import { Uppy } from '@uppy/core'
import AwsS3 from '@uppy/aws-s3'
import Tus from '@uppy/tus';
import '@uppy/core/dist/style.min.css';

export default {

    props: {
        enabled: {
            type: Boolean,
            default: () => true
        },
        container: String,
        path: String,
        uppy: null,
    },

    data() {
        return {
            dragging: false,
            uploads: [],
        }
    },

    computed: {

        extraData() {
            return {
                container: this.container,
                folder: this.path,
                _token: Statamic.$config.get('csrfToken')
            };
        },

    },

    mounted() {
        this.$refs.nativeFileField.addEventListener('change', this.addNativeFileFieldSelections);
    },

    beforeDestroy() {
        this.$refs.nativeFileField.removeEventListener('change', this.addNativeFileFieldSelections);
    },

    watch: {

        uploads(uploads) {
            this.$emit('updated', uploads);
        },

    },

    methods: {

        initialize(driver) {
            this.initializeUppy();
            if (driver === 's3') {
                this.initializeUppyS3();
            } else {
                this.initializeUppyTus();
            }
        },

        initializeUppy() {

            this.uppy = new Uppy({
                id: uniqid(),
                autoProceed: true,
            });
            this.uppy.on('upload', (uploadId, files) => {
                files.forEach(file => {
                    this.uploads.push({
                        id: file.id,
                        basename: file.name,
                        extension: file.extension,
                        percent: 0,
                        errorMessage: null,
                    });
                });
            });
            this.uppy.on('upload-progress', (file, progress) => {
                this.handleUploadProgress(file.id, progress.bytesUploaded / progress.bytesTotal);
            });
            this.uppy.on('upload-error', (file, { response }) => {
                this.handleUploadError(file.id, response.status, response.data);
            });

        },

        initializeUppyTus() {

            const uploadUrl = 'large-assets/api/upload-tus';

            this.uppy.use(Tus, {
                endpoint: '/tus',
                chunkSize: 5 << 20,
                headers: {
                    'X-CSRF-TOKEN': Statamic.$config.get('csrfToken'),
                },
            });
            
            this.uppy.on('upload-success', async (file, { uploadURL }) => {
                const url = cp_url(`${uploadUrl}/complete`);
                const response = await this.$axios.post(url, {
                    container: this.container,
                    uploadUrl: uploadURL,
                });
                this.handleUploadSuccess(file.id, response.data);
            });

        },

        initializeUppyS3() {

            const uploadUrl = 'large-assets/api/upload-s3';

            this.uppy.use(AwsS3, {
                chunkSize: 5 << 20,
                autoProceed: true,
                shouldUseMultipart: true,  
                createMultipartUpload: async (file, signal) => {
                    signal?.throwIfAborted();
                    const url = cp_url(`${uploadUrl}/create`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: file.name,
                    });
                    return response.data;
                },
                signPart: async (file, { uploadId, key, partNumber, signal }) => {
                    signal?.throwIfAborted();
                    const url = cp_url(`${uploadUrl}/sign-part`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                        partNumber: partNumber,
                    });
                    return response.data;
                },
                completeMultipartUpload: async (file, { key, uploadId, parts }, signal) => {
                    signal?.throwIfAborted()
                    const url = cp_url(`${uploadUrl}/complete`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                        parts: parts,
                    });
                    return response.data;
                },
                listParts: async (file, { key, uploadId }, signal) => {
                    signal?.throwIfAborted()
                    const url = cp_url(`${uploadUrl}/list-parts`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                    });
                    return response.data;
                },
                abortMultipartUpload: async (file, { key, uploadId }) => {
                    const url = cp_url(`${uploadUrl}/abort`);
                    await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                    });
                },
            });

            this.uppy.on('upload-success', (file, { body }) => {
                this.handleUploadSuccess(file.id, body);
            });

        },

        browse() {
            this.$refs.nativeFileField.click();
        },

        addNativeFileFieldSelections(e) {
            for (let i = 0; i < e.target.files.length; i++) {
                this.addFile(e.target.files[i]);
            }
        },

        dragenter(e) {
            e.stopPropagation();
            e.preventDefault();
            this.dragging = true;
        },

        dragover(e) {
            e.stopPropagation();
            e.preventDefault();
        },

        dragleave(e) {
            if (e.target !== e.currentTarget) {
                return;
            }
            this.dragging = false;
        },

        drop(e) {
            e.stopPropagation();
            e.preventDefault();
            this.dragging = false;

            for (let i = 0; i < e.dataTransfer.files.length; i++) {
                this.addFile(e.dataTransfer.files[i]);
            }
        },

        addFile(file) {
            if (! this.enabled) {
                return;
            }
            this.uppy.addFile(file);
        },

        findUpload(id) {
            return this.uploads.find(u => u.id === id);
        },

        findUploadIndex(id) {
            return this.uploads.findIndex(u => u.id === id);
        },

        handleUploadProgress(id, progress) {
            this.findUpload(id).percent = progress * 100;
        },

        handleUploadSuccess(id, response) {
            this.$emit('upload-complete', response.data);
            this.uploads.splice(this.findUploadIndex(id), 1);
        },

        handleUploadError(id, status, response) {
            const upload = this.findUpload(id);
            let msg = response?.message;
            if (! msg) {
                if (status === 413) {
                    msg = __('Upload failed. The file is larger than is allowed by your server.');
                } else {
                    msg = __('Upload failed. The file might be larger than is allowed by your server.');
                }
            } else {
                if (status === 422) {
                    msg = Object.values(response.errors)[0][0];
                }
            }
            upload.errorMessage = msg;
            this.$emit('error', upload, this.uploads);
        },
    }

}
</script>
