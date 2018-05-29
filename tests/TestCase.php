<?php

class TestCase extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Mts88\MongoGrid\Providers\MongoGridServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'MongoGrid' => 'Mts88\MongoGrid\Facades\MongoGrid'
        ];
    }
}
