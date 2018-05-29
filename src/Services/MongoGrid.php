<?php

namespace Mts88\MongoGrid\Services;

use \Mts88\MongoGrid\Services\MongoClient;
use \Mts88\MongoGrid\Contracts\MongoGridFactory;
use \Ramsey\Uuid\Uuid;
use \Carbon\Carbon;
use Storage;

class MongoGrid implements MongoGridFactory {

    /**
     * App instance
     */
    private $instance;

    /**
    *  MongoDB\Client of connection
    */
    private $client;

    /**
     * MongoDB\GridFS\Bucket
     */
	private $bucket;

    /**
    *   MongoDB\Database selected
    */
    private $db;

    /**
    *   Config of package
    */
    private $config;

    /**
    *   Temporary storage disk
    */
    private $storage;

    /**
    *   Prefix of storage
    */
    private $path_prefix;

    /**
     * Construct of MongoGrid
     *
     * @method __construct
     * @param  string      $prefix Prefix of GridFS collections
     */
    public function __construct($prefix = null) {

        $this->config = config('gridfs');

		$this->client = new MongoClient();
        $this->db = $this->client->getMongoDB();

        $this->setBucket( $prefix ); // select bucket with prefix

        $this->storage     = Storage::disk($this->config['storage']);
        $this->path_prefix = $this->storage->getAdapter()->getPathPrefix();

        return $this;
    }

    /**
     * Create new instance of class with another prefix for GridFS
     *
     * @method prefix
     * @param  string $prefix Prefix of GridFS's bucket
     * @return MongoClient         Instance of MongoClient
     */
    public function prefix( string $prefix ) {
        if ($this->instance == null) {
            $className = __CLASS__;
            $this->instance = new $className( $prefix );
        }
        return $this->instance;
    }

    /**
     * Store file on MongoDB GridFS
     *
     * @method storeFile
     * @param  string  $fileSource File content
     * @param  string  $fileName   Name of file
     * @param  array   $metadata   Array with custom metadata
     * @return \MongoDB\BSON\ObjectId            ObjectId of saved document
     * @throw \MongoDB\Exception\InvalidArgumentException
     * @throw \MongoDB\Exception\RuntimeException
     */
	public function storeFile( string $fileSource, string $fileName, array $metadata = [] ) {

        $f = finfo_open();
        $mime_type = finfo_buffer($f, $fileSource, FILEINFO_MIME_TYPE);

        $options = array(
            'contentType'	=>	 $mime_type,
            'metadata'      =>   [
            ]
        );

        if( $this->config['add_meta'] ) {
            $options['metadata'] = array_merge($options['metadata'], array(
                'created_at'    =>  new \MongoDB\BSON\UTCDateTime( Carbon::now() ),
                'updated_at'    =>  new \MongoDB\BSON\UTCDateTime( Carbon::now() ),
                'uuid'          =>  Uuid::uuid1()->toString(),
                'downloads'     =>  0
            ), $metadata);
        }

        try {
            $this->storage->put($fileName, $fileSource);
            $path = $this->path_prefix . $fileName;

            $file = fopen( $path , 'rb');

            $file_id = $this->bucket->uploadFromStream( $fileName, $file, $options);

            unlink($path);
        } catch (\MongoDB\Exception\InvalidArgumentException $e) {
            \Log::error("InvalidArgumentException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }

		return $file_id;
	}

    /**
     * Retrive the content of file
     *
     * @method getFileContent
     * @param  \MongoDB\BSON\ObjectId|string  $source   ObjectId or Name of file
     * @param  string|null  $revision Revision number of file or null
     * @return string            Content of file
     * @throw Exception
     */
    public function getFileContent( $source, $revision = NULL ) {
        if( $source instanceof \MongoDB\BSON\ObjectId ) {

            return stream_get_contents( $this->getFileStreamById( $source ) );

        } elseif( is_string($source) ) {

            return stream_get_contents( $this->getFileStreambyName( $source, $revision ) );

        } else {
            throw new \Exception('Invalid $source parameter: must be string or an instance of \MongoDB\BSON\ObjectId.');
        }
    }

    /**
     * Finds the document of the file $source.
     *
     * @method getFile
     * @param  \MongoDB\BSON\ObjectId|string  $source ObjectId or name of file
     * @return \MongoDB\Model\BSONDocument          Document of file
     * @throw Exception
     */
    public function getFile($source) {
        if( $source instanceof \MongoDB\BSON\ObjectId ) {
            return $this->findOne(['_id' => $source]);
        } elseif( is_string($source) ) {
            return $this->findOne(['filename' => $source]);
        } else {
            throw new \Exception('Invalid $source parameter: must be string or an instance of \MongoDB\BSON\ObjectId.');
        }
    }

    /**
     * Finds a single document from the selected GridFS bucket matching the query.
     *
     * @method findOne
     * @param  array|object  $query   Array or Object with query
     * @param  array|object  $options Array or Object with options
     * @return \MongoDB\Model\BSONDocument|null      An array or object for the first document that matched the query, or null if no document matched the query.
     * @throw \MongoDB\Exception\UnsupportedException
     * @throw \MongoDB\Exception\InvalidArgumentException
     * @throw \MongoDB\Exception\RuntimeException
     */
    public function findOne( $query, $options = null ) {
        if( is_null($options) ) {
            $options = [];
        }
        try {
            return $this->bucket->findOne($query, $options);
        } catch( \MongoDB\Exception\UnsupportedException $e ) {
            \Log::error("UnsupportedException: " . $e->getMessage());
        } catch( \MongoDB\Exception\InvalidArgumentException $e ) {
            \Log::error("InvalidArgumentException: " . $e->getMessage());
        } catch( \MongoDB\Exception\RuntimeException $e ) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }

    }

    /**
     * Finds all documents from the selected GridFS bucket matching the query.
     *
     * @method find
     * @param  array|object  $query   Array or Object with query
     * @param  array|object  $options Array or Object with options
     * @return \MongoDB\Driver\Cursor          cursor of MongoDB
     * @throw UnsupportedException
     * @throw InvalidArgumentException
     * @throw RuntimeException
     */
    public function find($query, $options = null) {
        if( is_null($options) ) {
            $options = [];
        }
        try {
            return $this->bucket->find($query, $options);
        } catch( \MongoDB\Exception\UnsupportedException $e ) {
            \Log::error("UnsupportedException: " . $e->getMessage());
        } catch( \MongoDB\Exception\InvalidArgumentException $e ) {
            \Log::error("InvalidArgumentException: " . $e->getMessage());
        } catch( \MongoDB\Exception\RuntimeException $e ) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }
    }

    /**
     * Download a file from GridFS to path
     *
     * @method download
     * @param  \MongoDB\BSON\ObjectId|string   $source   ObjectId or name of file
     * @param  string   $path     Path where store file
     * @param  string|null   $revision Revision of file (by name only)
     * @return void
     * @throw \Exception
     */
    public function download( $source, string $path, $revision = NULL ) {
        if( $source instanceof \MongoDB\BSON\ObjectId ) {
            $this->downloadFileStreamById($source, $path);

        } elseif( is_string($source) ) {

            $this->downloadFileStreamByName($source, $path, $revision);

        } else {
            throw new \Exception('Invalid $source parameter: must be string or an instance of \MongoDB\BSON\ObjectId.');
        }
    }

    /**
     * Rename a single file
     *
     * @method rename
     * @param  \MongoDB\BSON\ObjectId $_id         ObjectId of file
     * @param  string              $newFilename New name of file
     * @return \MongoDB\Model\BSONDocument|null  Document of $_id
     * @throw \MongoDB\GridFS\Exception\FileNotFoundException
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    public function rename( \MongoDB\BSON\ObjectId $_id, string $newFilename) {
        try {
            $this->bucket->rename($_id, $newFilename);
        } catch( \MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("FileNotFoundException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }
        return $this->findOne(['_id' => $_id]);
    }

    /**
     * Delete a document from GridFS
     *
     * @method delete
     * @param  \MongoDB\BSON\ObjectId $_id [description]
     * @return bool                Statement of delete operation
     * @throw \MongoDB\GridFS\Exception\FileNotFoundException
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    public function delete( \MongoDB\BSON\ObjectId $_id ) {

        try {
            $this->bucket->delete($_id);
            return true;
        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("FileNotFoundException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Drop entire collection of a selected GridFS
     *
     * @method drop
     * @return void
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    public function drop() {
        try {
            $this->bucket->drop();
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }
    }

    /**
     * Increment download counter by one
     *
     * @method incrementDownload
     * @param  MongoDBBSONObjectId $_id ObjectId of Document
     * @return void
     */
    public function incrementDownload( \MongoDB\BSON\ObjectId $_id ) {
        try {
            $collection = $this->getFilesCollection();
            $collection->updateOne(['_id' => $_id], ['$inc' => [ 'metadata.downloads' => 1]]);

        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("FileNotFoundException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        } catch ( \MongoDB\Exception\UnsupportedException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }
    }

    /**
     * Returns the name of the selected GridFS Bucket
     *
     * @method getBucketName
     * @return string        Name of GridFS Bucket
     */
    public function getBucketName() {
        return $this->bucket->getBucketName();
    }

    /**
     * Return the Collection Chunks of the selected GridFS Bucket
     *
     * @method getChunksCollection
     * @return \MongoDB\Collection              Collection of Chunks
     */
    public function getChunksCollection() {
        return $this->bucket->getChunksCollection();
    }

    /**
     * Return the size of Chunks of the selected GridFS Bucket
     *
     * @method getChunkSizeBytes
     * @return integer            The chunk size of this bucket in bytes
     */
    public function getChunkSizeBytes() {
        return $this->bucket->getChunkSizeBytes();
    }

    /**
     * Return the name of the database used for selected GridFS
     *
     * @method getDatabaseName
     * @return string          The name of the database containing this bucket as a string
     */
    public function getDatabaseName() {
        return $this->bucket->getDatabaseName();
    }

    /**
     * Return the Collection File of the selected GridFS Bucket
     *
     * @method getFilesCollection
     * @return \MongoDB\Collection            A MongoDB\Collection object for the files collection.
     */
    public function getFilesCollection() {
        return $this->bucket->getFilesCollection();
    }

    /**
     * Set GridFS bucket
     *
     * @method setBucket
     * @param  string    $prefix Prefix of GridFS
     */
    private function setBucket($prefix) {

        $prefix = $prefix ? : $this->config['bucket']['prefix'];
        $options = array_merge( $this->config['bucket'], [
            'bucketName'        => $prefix,
            'readPreference'    => new \MongoDB\Driver\ReadPreference($this->config['bucket']['readPreference']),
            'readConcern'       => new \MongoDB\Driver\ReadConcern($this->config['bucket']['readConcern'])
        ]);

        // Select GridFS Bucket
        $this->bucket = $this->client->getGridFS($options);
        return $this;
    }

    /**
     * Get a file by ObjectId
     *
     * @method getFileStreamById
     * @param  MongoDBBSONObjectId $fileId   ObjectId of file
     * @return resource                      Readable stream of file
     * @throw \MongoDB\GridFS\Exception\FileNotFoundException
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    private function getFileStreamById( \MongoDB\BSON\ObjectId $fileId ) {
        try {
            $stream = $this->bucket->openDownloadStream($fileId);
        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("FileNotFoundException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }

        return $stream;
    }

    /**
     * [getFileByName description]
     *
     * @method getFileStreamByName
     * @param  string             $fileName Name of file
     * @param  string|null        $revision Revision of file
     * @return resource                     Readable stream of file
     * @throw \MongoDB\GridFS\Exception\FileNotFoundException
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    private function getFileStreamByName( string $fileName, $revision ) {

        if( is_null($revision) ) {
            $revision = '-1';
        }

        try {
            $stream = $this->bucket->openDownloadStreamByName( $fileName, ['revision' => $revision] );
        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("FileNotFoundException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }

        return $stream;

    }

    /**
     * Download a GridFS' bucket file into a file by given path
     *
     * @method downloadFileStreamByName
     * @param  string                   $fileName Name of GridFS file
     * @param  string                   $path     Path where save file
     * @param  string|null                   $revision Revision of file
     * @return void
     * @throw \MongoDB\GridFS\Exception\FileNotFoundException
     * @throw \MongoDB\GridFS\Exception\InvalidArgumentException
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    private function downloadFileStreamByName( string $fileName, string $path, $revision ) {

        if( is_null($revision) ) {
            $revision = '-1';
        }

        try {
            $obj = $this->findOne(['filename' => $fileName], ['revision' => $revision]);
            $destination = rtrim($path, '/') . '/' . $obj->filename;
            $destination = fopen($destination, 'w+b');

            $this->bucket->downloadToStreamByName( $fileName, $destination, ['revision' => $revision] );

            $obj = $this->findOne(['filename' => $fileName], ['revision' => $revision ]);

            if( $this->config['add_meta'] ) {
                $this->incrementDownload($obj->_id);
            }
        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        } catch (\MongoDB\GridFS\Exception\InvalidArgumentException $e) {
            \Log::error("InvalidArgumentException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }

    }

    /**
     * Download a GridFS' bucket file into a file by given path
     *
     * @method downloadFileStreamById
     * @param  \MongoDB\BSON\ObjectId   $_id        ObjectId of GridFS file
     * @param  string                   $path       Path where save file
     * @return void
     * @throw \MongoDB\GridFS\Exception\FileNotFoundException
     * @throw \MongoDB\GridFS\Exception\InvalidArgumentException
     * @throw \MongoDB\GridFS\Exception\RuntimeException
     */
    private function downloadFileStreamById( \MongoDB\BSON\ObjectId $_id, string $path) {
        try {
            $obj = $this->findOne(['_id' => $_id]);
            $destination = rtrim($path, '/') . '/' . $obj->filename;
            $destination = fopen($destination, 'w+b');

            $this->bucket->downloadToStream( $_id, $destination );
            if( $this->config['add_meta'] ) {
                $this->incrementDownload($_id);
            }

        } catch (\MongoDB\GridFS\Exception\FileNotFoundException $e) {
            \Log::error("FileNotFoundException: " . $e->getMessage());
        } catch (\MongoDB\GridFS\Exception\InvalidArgumentException $e) {
            \Log::error("InvalidArgumentException: " . $e->getMessage());
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            \Log::error("RuntimeException: " . $e->getMessage());
        }
    }

}
