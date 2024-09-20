<?php

namespace Tv2regionerne\StatamicLargeAssets\Http\Controllers\API;

use Illuminate\Http\Request;
use Statamic\Facades\AssetContainer;
use Statamic\Http\Controllers\CP\CpController;

class AssetsController extends CpController
{
    public function create(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $blueprint = $container->blueprint();

        $fields = $blueprint
            ->fields()
            ->preProcess();

        return [
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
        ];
    }

    public function store(Request $request)
    {
        $container = AssetContainer::find($request->container);

        $values = $container
            ->blueprint()
            ->fields()
            ->addValues($request->values)
            ->validate();

        return [
            'values' => $values,
        ];
    }
}
