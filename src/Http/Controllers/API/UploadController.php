<?php

namespace Tv2regionerne\StatamicLargeAssets\Http\Controllers\API;

use Illuminate\Http\Request;
use Statamic\Facades\AssetContainer;
use Statamic\Http\Controllers\CP\CpController;

class UploadController extends CpController
{
    public function create(Request $request)
    {
        $container = $request->container;
        $disk = AssetContainer::find($container)->disk()->filesystem();
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
        $container = $request->container;
        $disk = AssetContainer::find($container)->disk()->filesystem();
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
        $container = $request->container;
        $disk = AssetContainer::find($container)->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

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

        return response()->json([
            'location' => $location,
        ]);
    }

    public function listParts(Request $request)
    {
        $container = $request->container;
        $disk = AssetContainer::find($container)->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $key = $request->key;
        $uploadId = $request->uploadId;

        $parts = $client->listParts([
            'Bucket' => $bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ])->get('Parts');

        return response()->json([
            'parts' => $parts,
        ]);
    }

    public function abort(Request $request)
    {
        $container = $request->container;
        $disk = AssetContainer::find($container)->disk()->filesystem();
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
}
