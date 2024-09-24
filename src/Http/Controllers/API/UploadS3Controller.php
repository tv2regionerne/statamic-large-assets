<?php

namespace Tv2regionerne\StatamicLargeAssets\Http\Controllers\API;

use Illuminate\Http\Request;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Events\AssetCreated;
use Statamic\Events\AssetCreating;
use Statamic\Events\AssetUploaded;
use Statamic\Facades\AssetContainer;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Resources\CP\Assets\Asset as AssetResource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tv2regionerne\StatamicLargeAssets\Assets\S3Uploader;

class UploadS3Controller extends CpController
{
    public function create(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $key = $request->key;
        $type = $request->type;

        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $uploadId = $client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => 'temp/'.$key,
            'ContentType' => $type,
        ])->get('UploadId');

        return response()->json([
            'key' => $key,
            'uploadId' => $uploadId,
        ]);
    }

    public function signPart(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $key = $request->key;
        $type = $request->type;
        $uploadId = $request->uploadId;
        $partNumber = $request->partNumber;

        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $expires = now()->addMinutes(10);

        $command = $client->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key' => 'temp/'.$key,
            'ContentType' => $type,
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
        $folder = $request->folder;
        $name = $request->name;
        $values = $request->values;
        $key = $request->key;
        $uploadId = $request->uploadId;
        $parts = $request->parts;

        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $location = $client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key' => 'temp/'.$key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
        ])->get('Location');

        $path = $folder !== '/' ? "{$folder}/{$name}" : $name;

        $upload = new UploadedFile('temp/'.$key, $name, null, UPLOAD_ERR_NO_FILE, true);

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
        $this->upload($asset, $upload);

        return (new AssetResource($asset))->additional([
            'data' => [
                'location' => $location,
            ],
        ]);
    }

    public function listParts(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $key = $request->key;
        $uploadId = $request->uploadId;

        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $parts = $client->listParts([
            'Bucket' => $bucket,
            'Key' => 'temp/'.$key,
            'UploadId' => $uploadId,
        ])->get('Parts');

        return response()->json($parts);
    }

    public function abort(Request $request)
    {
        $container = AssetContainer::find($request->container);
        $key = $request->key;
        $uploadId = $request->uploadId;

        $disk = $container->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $client->abortMultipartUpload([
            'Bucket' => $bucket,
            'Key' => 'temp/'.$key,
            'UploadId' => $uploadId,
        ]);

        return response()->json();
    }

    protected function upload(AssetContract $asset, UploadedFile $file)
    {
        if (AssetCreating::dispatch($asset) === false) {
            return false;
        }

        $path = S3Uploader::asset($asset)->upload($file);

        $asset
            ->path($path)
            ->syncOriginal()
            ->save();

        AssetUploaded::dispatch($asset);

        AssetCreated::dispatch($asset);

        return $asset;
    }
}
