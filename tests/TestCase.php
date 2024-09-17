<?php

namespace Tv2regionerne\StatamicLargeAssets\Tests;

use Tv2regionerne\StatamicLargeAssets\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
