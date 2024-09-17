<?php

namespace Tv2regionerne\StatamicLargeAssets\Fieldtypes;

use Statamic\Facades\AssetContainer;
use Statamic\Fieldtypes\Assets\Assets;

class LargeAssets extends Assets
{
    protected $icon = 'assets';

    protected $indexComponent = 'assets';

    protected function configFieldItems(): array
    {
        $containers = AssetContainer::all()
            ->filter(function ($container) {
                return $container->disk()->filesystem()->getConfig()['driver'] === 's3';
            });

        return [
            [
                'display' => __('Appearance & Behavior'),
                'fields' => [
                    ...collect(parent::configFieldItems()[0]['fields'])->only([
                        'max_files',
                        'min_files',
                        'container',
                        'allow_uploads',
                    ])->all(),
                    'container' => [
                        'display' => __('Container'),
                        'instructions' => __('statamic::fieldtypes.assets.config.container'),
                        'type' => 'select',
                        'default' => $containers->count() == 1 ? $containers->first()->handle() : null,
                        'options' => $containers->mapWithKeys(function ($container) {
                            return [$container->handle() => $container->title()];
                        })->all(),
                    ],
                ],
            ],
        ];
    }

    public function augment($values)
    {
        $cacheKey = (is_array($values) ? serialize($values) : $values);

        return Blink::once('advanced_assets::'.$cacheKey, function () use (&$values) {
            return parent::augment($values);
        });
    }
}
