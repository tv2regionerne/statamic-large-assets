<?php

namespace Tv2regionerne\StatamicLargeAssets\Fieldtypes;

use Statamic\Fieldtypes\Assets\Assets;

class LargeAssets extends Assets
{
    protected $icon = 'assets';

    protected $indexComponent = 'assets';

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
