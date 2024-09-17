import LargeAssets from './components/fieldtypes/LargeAssets.vue'

Statamic.booting(() => {
    Statamic.component('large_assets-fieldtype', LargeAssets)
})
