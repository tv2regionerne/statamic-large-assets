<template>

    <div>

        <div
            v-if="initialized"
            @dragenter="dragenter"
            @dragover="dragover"
            @dragleave="dragleave"
            @drop="drop">
            <div :class="{ 'pointer-events-none': dragging }">
                <input
                    class="hidden"
                    type="file"
                    ref="input"
                    :multiple="!shouldShowForm"
                    @input="select">
                <slot :dragging="enabled ? dragging : false"></slot>
            </div>
        </div>

        <Form
            v-if="showForm"
            :container="container"
            :file="file"
            @cancel="closeForm"
            @saved="saveForm" />

    </div>

</template>

<script>
import { Uppy } from '@uppy/core'
import AwsS3 from '@uppy/aws-s3'
import Tus from '@uppy/tus';
import '@uppy/core/dist/style.min.css';
import Form from './Form.vue';

export default {

    components: {
        Form,
    },

    props: {
        enabled: {
            type: Boolean,
            default: () => true
        },
        container: String,
        path: String,
    },

    data() {
        return {
            initialized: false,
            uppy: null,
            config: null,
            meta: null,
            dragging: false,
            uploads: [],
            showForm: false,
            file: null,
            values: {},
        }
    },

    watch: {

        uploads(uploads) {
            this.$emit('updated', uploads);
        },

    },

    computed: {

        shouldShowForm() {
            return this.config.show_form;
        },

    },

    methods: {

        initialize(config, meta) {
            this.config = config;
            this.meta = meta;
            this.initializeUppy();
            if (this.meta.driver === 's3') {
                this.initializeUppyS3();
            } else {
                this.initializeUppyTus();
            }
            this.initialized = true;
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
            this.uppy.on('upload-error', (file, error) => {
                this.handleUploadError(file.id, error.response.status, error.response.data);
            });
            this.uppy.on('complete', () => {
                this.file = null;
                this.values = {};
            });
        },

        initializeUppyTus() {
            const baseUrl = 'large-assets/api/upload-tus';
            this.uppy.use(Tus, {
                endpoint: cp_url(`${baseUrl}/endpoint`),
                chunkSize: Statamic.$config.get('large_assets.tus.chunk_size'),
                headers: {
                    'X-CSRF-TOKEN': Statamic.$config.get('csrfToken'),
                },
            });
    
            this.uppy.on('upload-success', async (file, data) => {
                const url = cp_url(`${baseUrl}/complete`);
                try {
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        folder: this.path,
                        uploadUrl: data.uploadURL,
                        values: this.values,
                    });
                    this.handleUploadSuccess(file.id, response.data);
                } catch (error) {
                    this.handleUploadError(file.id, error.response.status, error.response.data);
                }
            });
        },

        initializeUppyS3() {
            const baseUrl = 'large-assets/api/upload-s3';
            this.uppy.use(AwsS3, {
                chunkSize: Statamic.$config.get('large_assets.s3.chunk_size'),
                autoProceed: true,
                shouldUseMultipart: true,  
                createMultipartUpload: async (file, signal) => {
                    signal?.throwIfAborted();
                    const url = cp_url(`${baseUrl}/create`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: `${uniqid()}.${file.extension}`,
                        type: file.type,
                    });
                    return response.data;
                },
                signPart: async (file, { uploadId, key, partNumber, signal }) => {
                    signal?.throwIfAborted();
                    const url = cp_url(`${baseUrl}/sign-part`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        type: file.type,
                        uploadId: uploadId,
                        partNumber: partNumber,
                    });
                    return response.data;
                },
                completeMultipartUpload: async (file, { key, uploadId, parts }, signal) => {
                    signal?.throwIfAborted()
                    const url = cp_url(`${baseUrl}/complete`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                        parts: parts,
                        folder: this.path,
                        name: file.name,
                        values: this.values,
                    });
                    return response.data;
                },
                listParts: async (file, { key, uploadId }, signal) => {
                    signal?.throwIfAborted()
                    const url = cp_url(`${baseUrl}/list-parts`);
                    const response = await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                    });
                    return response.data;
                },
                abortMultipartUpload: async (file, { key, uploadId }) => {
                    const url = cp_url(`${baseUrl}/abort`);
                    await this.$axios.post(url, {
                        container: this.container,
                        key: key,
                        uploadId: uploadId,
                    });
                },
            });
            this.uppy.on('upload-success', (file, data) => {
                this.handleUploadSuccess(file.id, data.body);
            });
        },

        browse() {
            this.$refs.input.click();
        },

        select(e) {
            this.addFiles(Array.from(e.target.files));
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
            this.addFiles(Array.from(e.dataTransfer.files));
        },

        addFiles(files) {
            if (this.shouldShowForm) {
                this.addFile(files[0]);
            } else {
                files.forEach(file => this.addFile(file));
            }
        },

        addFile(file) {
            if (! this.enabled) {
                return;
            }
            if (this.shouldShowForm) {
                this.openForm(file);
            } else {
                this.uppy.addFile(file);
            }
        },

        openForm(file) {
            this.file = file;
            this.showForm = true;
        },

        closeForm() {
            this.file = null;
            this.showForm = false;
        },

        saveForm(values) {
            this.values = values;
            this.uppy.addFile(this.file);
            this.showForm = false;
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

        handleUploadError(id, status, data) {
            const upload = this.findUpload(id);
            let message = data.message;
            if (!message) {
                message = status;
            }
            upload.errorMessage = message;
            this.$emit('error', upload, this.uploads);
        },
    },

}
</script>
