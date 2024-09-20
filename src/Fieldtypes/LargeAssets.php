<?php

namespace Tv2regionerne\StatamicLargeAssets\Fieldtypes;

use Statamic\Fieldtypes\Assets\Assets;

class LargeAssets extends Assets
{
    protected $icon = 'assets';

    protected $indexComponent = 'assets';

    protected function configFieldItems(): array
    {
        $items = parent::configFieldItems();

        $items[0]['fields'] += [
            'show_form' => [
                'display' => __('Show Form'),
                'instructions' => __('Show and validate form data before upload'),
                'type' => 'toggle',
            ],
        ];

        return $items;
    }

    public function preload()
    {
        $disk = $this->container()->disk()->filesystem();
        $driver = $disk->getConfig()['driver'];

        return [
            ...parent::preload(),
            'driver' => $driver,
        ];
    }
}
