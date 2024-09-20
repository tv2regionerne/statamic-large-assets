<?php

namespace Tv2regionerne\StatamicLargeAssets\Http\Controllers\API;

use Illuminate\Http\Request;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Events\AssetCreated;
use Statamic\Events\AssetCreating;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Resources\CP\Assets\Asset as AssetResource;

class UploadS3Controller extends CpController
{
    public function create(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $key = $request->key;

        $uploadId = $client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
        ])->get('UploadId');

        return response()->json([
            'key' => $key,
            'uploadId' => $uploadId,
        ]);
    }

    public function signPart(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $key = $request->key;
        $uploadId = $request->uploadId;
        $partNumber = $request->partNumber;

        $expires = now()->addMinutes(10);

        $command = $client->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);
        $url = (string) $client
            ->createPresignedRequest($command, $expires)
            ->getUri();

        return response()->json([
            'url' => $url,
            'expires' => $expires,
        ]);
    }

    public function complete(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];
        $values = $request->values;

        $key = $request->key;
        $uploadId = $request->uploadId;
        $parts = $request->parts;

        $location = $client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ])->get('Location');

        $asset = $container->asset($key);
        $values = $container
            ->blueprint()
            ->fields()
            ->addValues($values)
            ->process()
            ->values()
            ->each(function ($value, $name) use ($asset) {
                $asset->set($name, $value);
            });
        if (! $this->saveAsset($asset)) {
            abort(500, 'Failed to save asset');
        }

        return (new AssetResource($asset))->additional([
            'data' => [
                'location' => $location,
            ],
        ]);
    }

    public function listParts(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $key = $request->key;
        $uploadId = $request->uploadId;

        $parts = $client->listParts([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ])->get('Parts');

        return response()->json($parts);
    }

    public function abort(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $key = $request->key;
        $uploadId = $request->uploadId;

        $client->abortMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);

        return response()->json();
    }

    protected function saveAsset(AssetContract $asset)
    {
        // Custom logic to save the asset as it alreday exists so if
        // we use Asset::save() the wrong events will be dispatched.

        if (AssetCreating::dispatch($asset) === false) {
            return false;
        }

        Asset::save($asset);

        AssetCreated::dispatch($asset);

        $asset->syncOriginal();

        return true;
    }
}
