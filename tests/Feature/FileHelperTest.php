<?php

use MongoGrid;

class FileHelperTest extends TestCase
{
    /**
     * Get Bucket Name
     */
    public function testHelperBucketNameFile()
    {
        $name = MongoGrid::getBucketName();

        $this->assertInternalType('string', $name);

    }

    /**
     * Get Chunks Collection
     */
    public function testHelperChunkCollection()
    {
        $collection = MongoGrid::getChunksCollection();

        $this->assertInstanceOf('\MongoDB\Collection', $collection);

    }

    /**
     * Get Chunks size
     */
    public function testHelperChunkSize()
    {
        $size = MongoGrid::getChunkSizeBytes();

        $this->assertInternalType('integer', $size);

    }

    /**
     * Get Chunks size
     */
    public function testHelperDatabaseName()
    {
        $database = MongoGrid::getDatabaseName();

        $this->assertInternalType('string', $database);

    }

    /**
    * Get Files Collection
    */
    public function testHelperFilesCollection()
    {
        $collection = MongoGrid::getFilesCollection();

        $this->assertInstanceOf('\MongoDB\Collection', $collection);

    }

    /**
    * Drop GridFS prefix Collections
    */
    public function testHelperDrop()
    {
        MongoGrid::drop();

        $this->assertTrue(true);

    }

}
