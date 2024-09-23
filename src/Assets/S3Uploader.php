<?php

namespace Tv2regionerne\StatamicLargeAssets\Assets;

use Statamic\Assets\AssetUploader as StatamicAssetUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3Uploader extends StatamicAssetUploader
{
    public function upload(UploadedFile $file)
    {
        $source = $file->getPathname();

        $this->write($source, $path = $this->uploadPath($file));

        return $path;
    }

    private function write($sourceFile, $destinationPath)
    {
        $this->disk()->move($sourceFile, $destinationPath);
    }
}
