<?php

use MongoGrid;

class FileStoreTest extends TestCase
{

    /**
     * @dataProvider fileProvider
     */
    public function testSinglePrefix($path)
    {
        $file = file_get_contents($path);
        MongoGrid::storeFile($file, 'star-wars-rogue-one.jpg');

        $this->assertTrue(true);
    }

    /**
     * @dataProvider fileProvider
     */
    public function testMultiplePrefix($path)
    {
        $file = file_get_contents($path);
        MongoGrid::prefix('images')->storeFile($file, 'star-wars-rogue-one.jpg');
        MongoGrid::storeFile($file, 'star-wars-rogue-one.jpg');

        $this->assertTrue(true);

    }

    public function fileProvider()
    {
        return [
            [dirname(__DIR__).'/src/star-wars-rogue-one.jpg']
        ];
    }

}
