import LargeAssets from './components/fieldtypes/LargeAssets.vue'
import Uploader from './components/fieldtypes/Uploader.vue'

Statamic.booting(() => {
    Statamic.component('large_assets-fieldtype', LargeAssets)
    Statamic.component('large_assets-uploader', Uploader)
})
