<?php

namespace Mts88\MongoGrid\Contracts;

interface MongoGridFactory {

    public function prefix( string $prefix );

    public function storeFile( string $fileSource, string $fileName, array $metadata );

    public function getFileContent( $source, $revision = null );

    public function getFile($source);

    public function findOne($query, $options = null );

    public function find($query, $options = null );

    public function download( $source, string $path, $revision = NULL );

    public function rename( \MongoDB\BSON\ObjectId $_id, string $newFilename);

    public function delete( \MongoDB\BSON\ObjectId $_id);

    public function drop();

    //Utilities

    public function incrementDownload( \MongoDB\BSON\ObjectId $_id );

    public function getBucketName();

    public function getChunksCollection();

    public function getChunkSizeBytes();

    public function getDatabaseName();

    public function getFilesCollection();
}
