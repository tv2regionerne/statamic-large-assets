<?php

namespace Tv2regionerne\StatamicLargeAssets\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use KalynaSolutions\Tus\Facades\Tus;
use KalynaSolutions\Tus\Helpers\TusFile;
use Statamic\Facades\AssetContainer;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Resources\CP\Assets\Asset as AssetResource;

class UploadTusController extends CpController
{
    public function complete(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $folder = $request->folder;
        $url = $request->uploadUrl;
        $values = $request->values;

        $id = Str::afterlast($url, '/');
        $tusFile = TusFile::find($id);

        $file = Tus::storage()->path($tusFile->path);
        $name = $tusFile->metadata['name'];
        $path = $folder !== '/' ? "{$folder}/{$name}" : $name;

        $upload = new UploadedFile($file, $name, null, 0, true);

        $asset = $container->makeAsset($path);
        $values = $container
            ->blueprint()
            ->fields()
            ->addValues($values)
            ->process()
            ->values()
            ->each(function ($value, $name) use ($asset) {
                $asset->set($name, $value);
            });
        $asset->upload($upload);

        Tus::storage()->delete(Tus::path($id, 'json'));

        return (new AssetResource($asset))->additional([
            'data' => [
                'location' => $url,
            ],
        ]);
    }
}
