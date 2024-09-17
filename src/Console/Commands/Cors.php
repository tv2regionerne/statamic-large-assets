<?php

namespace Tv2regionerne\StatamicLargeAssets\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\AssetContainer;

class Cors extends Command
{
    protected $signature = 'large-assets:cors {container}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $container = $this->argument('container');
        $disk = AssetContainer::find($container)->disk()->filesystem();

        if ($disk->getConfig()['driver'] !== 's3') {
            return $this->error('Container must use an S3 disk');
        }

        $client = $disk->getClient();
        $bucket = $disk->getConfig()['bucket'];

        $client->putBucketCors([
            'Bucket' => $bucket,
            'CORSConfiguration' => [
                // https://uppy.io/docs/aws-s3/#setting-up-your-s3-bucket
                'CORSRules' => [
                    [
                        'AllowedOrigins' => [config('app.url')],
                        'AllowedMethods' => ['GET', 'PUT'],
                        'AllowedHeaders' => [
                            'Authorization',
                            'x-amz-date',
                            'x-amz-content-sha256',
                            'content-type',
                        ],
                        'ExposeHeaders' => ['ETag', 'Location'],
                        'MaxAgeSeconds' => 3000,
                    ],
                    [
                        'AllowedOrigins' => ['*'],
                        'AllowedMethods' => ['GET'],
                        'MaxAgeSeconds' => 3000,
                    ],
                ],
            ],
        ]);

        $this->info('CORS configuration set for '.$bucket);
    }
}
