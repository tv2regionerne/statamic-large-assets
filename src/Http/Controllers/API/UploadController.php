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

        $path = $request->path;
        $size = $request->size;
        $expiry = now()->addMinutes(20);

        $partSize = 5 << 20; // 5mb
        $partCount = ceil($size / $partSize);

        $uploadId = $client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $request->path,
        ])->get('UploadId');

        $parts = [];
        for ($i = 0; $i < $partCount; $i++) {
            $number = $i + 1;
            $command = $client->getCommand('UploadPart', [
                'Bucket' => $bucket,
                'Key' => $path,
                'UploadId' => $uploadId,
                'PartNumber' => $number,
            ]);
            $url = (string) $client
                ->createPresignedRequest($command, $expiry)
                ->getUri();
            $start = $i * $partSize;
            $end = min($start + $partSize, $size);
            $parts[] = [
                'number' => $number,
                'url' => $url,
                'start' => $start,
                'end' => $end,
            ];
        }

        return response()->json([
            'uploadId' => $uploadId,
            'parts' => $parts,
        ]);
    }

    public function complete(Request $request)
    {
        $container = $request->container;
        $disk = AssetContainer::find($container)->disk()->filesystem();
        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $path = $request->path;
        $uploadId = $request->uploadId;
        $parts = $request->parts;

        $result = $client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $path,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => collect($parts)->map(fn ($part) => [
                    'ETag' => $part['eTag'],
                    'PartNumber' => $part['number'],
                ])->all(),
            ],
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}

// $result = $client->putBucketCors([
//     'Bucket' => $bucket, // REQUIRED
//     'CORSConfiguration' => [ // REQUIRED
//         'CORSRules' => [ // REQUIRED
//             [
//                 'AllowedHeaders' => ['*'],
//                 'AllowedMethods' => ['GET', 'HEAD', 'PUT', 'POST', 'DELETE'], // REQUIRED
//                 'AllowedOrigins' => ['*'], // REQUIRED
//                 'MaxAgeSeconds' => 3000,
//                 'ExposeHeaders' => [
//                     'ETag',
//                 ],
//             ],
//         ],
//     ],
// ]);
// dd($result);

// $corsConfig = $client->getBucketCors([
//     'Bucket' => $bucket,
// ]);

// dd($bucket, $corsConfig);
