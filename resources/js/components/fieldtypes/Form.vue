<template>

    <modal
        width="600px"
        @closed="$emit('closed')">
        <div slot-scope="{ close }">
            <header class="text-lg font-semibold px-5 py-3 bg-gray-200 dark:bg-dark-550 rounded-t-lg flex items-center justify-between border-b dark:border-dark-900">
                {{ __('Upload File') }}
            </header>
            <div class="flex-1 px-5 py-6 text-gray dark:text-dark-150">
                <slot name="body">
                    123
                </slot>
            </div>
            <div class="px-5 py-3 bg-gray-200 dark:bg-dark-550 rounded-b-lg border-t dark:border-dark-900 flex items-center justify-end text-sm">
                <button class="btn" @click="close" v-text="__('Cancel')" />
                <button class="btn-primary rtl:mr-4 ltr:ml-4" @click="$emit('saved')" v-text="__('Save')" />
            </div>
        </div>
    </modal>

</template>

<script>
export default {

    

}
</script>
