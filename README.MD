
# Laravel MongoDB GridFS

[![Latest Stable Version](https://poser.pugx.org/mts88/mongogrid/v/stable)](https://packagist.org/packages/mts88/mongogrid)
[![Total Downloads](https://poser.pugx.org/mts88/mongogrid/downloads)](https://packagist.org/packages/mts88/mongogrid)
[![License](https://poser.pugx.org/mts88/mongogrid/license)](https://packagist.org/packages/mts88/mongogrid)

A library wants to help the use of GridFS of MongoDB for Laravel 5. _This library extends the original [MongoDB GridFS Bucket for PHP](https://docs.mongodb.com/php-library/current/reference/class/MongoDBGridFSBucket/), so many methods are exactly the same and others are simplified for the use._

#### Note
This is not a library for Eloquent or somenthing like this. It's a simple helper to easly use GridFS in Laravel. If you are looking for an Eloquent Model for MongoDB I suggest you [jenssegers/laravel-mongodb](https://github.com/jenssegers/laravel-mongodb).

# Table of contents
* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
	* [Bucket Prefix](#bucket-prefix)
	* [Storing File](#storing-file)
	* [Get File](#get-file)
	* [Helpers](#helpers)
* [Contact](#contact)
* [License](#license)
# Installation

### Laravel version Compatibility

| Laravel | Package |
|--|--|
| < 5.5.x | 1.0.x (not tested) |
| >= 5.5.x | 1.0.x |

### Requirements
Make sure you have the MongoDB PHP driver installed. You can find installation instructions at [http://php.net/manual/en/mongodb.installation.php](http://php.net/manual/en/mongodb.installation.php)
### Installation
1. Installation using composer:
```
composer require mts88/mongogrid
```
2. And add the service provider in  `config/app.php`:
```php
Mts88\MongoGrid\Providers\MongoGridServiceProvider,
```
4. You may also register an alias for the MongoGrid library by adding the following to the alias array in  `config/app.php`:
```php
'MongoGrid'       => Mts88\MongoGrid\Facades\MongoGrid,
```
## Configuration

Run the command below to publish the package config file  `config/gridfs.php`:
```php
php artisan vendor:publish
```
### MongoDB Connection
In config file `config/gridfs.php` set up your configuration in order to connect to MongoDB:
#### Simple Connection
```php
    'db_config' => [
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => env('DB_PORT', 27017),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'options'  => [
            'database' => 'admin' // sets the authentication database required by mongo 3
        ]
    ],
```
#### ReplicaSet Connection
In your `.env` file add `DB_REPLICA_SET` property with the name of Replica Set
```php
    'db_config' => [
        'host'     => [
            [
                'address' => 'server1',
                'port'=> 27017
            ],
            [
                'address' => 'server2',
                'port' => 27017
            ],
        ],
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'options'  => [
            'replicaSet' => env('DB_REPLICA_SET'),
            'database' => 'admin' // sets the authentication database required by mongo 3
        ]
    ],
```
#### Bucket Configuration
This is the config for GridFS Bucket:
```php
    'bucket'    =>  [
        'prefix'            =>  'fs',
        'chunkSizeBytes'    =>  261120,
        'readPreference'    =>  'primaryPreferred',
        'readConcern'       =>  'available',
    ],
```
For more details see [MongoDB GridFS Bucket](https://docs.mongodb.com/php-library/current/reference/method/MongoDBGridFSBucket__construct/).
#### Metadata
By default the library adds these metadata to each document:
 - uuid;
 - created_at;
 - updated_at;
 - downloads;

set `false` in `config/gridfs.php` file to not include this info.
```php
    'add_meta'      =>  false,
```
#### Temporary Storage
By default the library use `local` driver for storing file into MongoDB. You can change the default driver of the `Storage`:
```php
    'storage'       =>  'local',
```
To learn more see [Laravel File Storage](https://laravel.com/docs/5.6/filesystem).
## Usage
### Bucket Prefix
By default MongoGrid use the prefix in `config/gridfs.php`, but if you want you can use another custom prefix on the fly:
```php
MongoGrid::prefix('myNewPrefix')->someCoolMethod();
```
### Storing File
#### storeFile( $fileContent, $fileName, [ optional $metadata] )
Store a file using contents, file name and your metadata (optional). Returns ObjectId
```php
$fileName = 'differentName.jpg';
$fileContent = Storage::disk('local')->get('star-wars-logo.jpg');
$objectId = MongoGrid::storeFile($fileContent, $fileName);

// Or with your custom metadata

$fileName = 'differentName.jpg';
$fileContent = Storage::disk('local')->get('star-wars-logo.jpg');
$metadata = array(
	'father'	=>	'Anakin',
	'son'		=>	'Luke'
	);
$objectId = MongoGrid::storeFile($fileContent, $fileName, $metadata);
```
*Or using another prefix*
```php
$fileName = 'differentName.jpg';
$fileContent = Storage::disk('local')->get('star-wars-logo.jpg');
$objectId = MongoGrid::prefix('starWars')->storeFile($fileContent, $fileName);

// Or with your custom metadata

$fileName = 'differentName.jpg';
$fileContent = Storage::disk('local')->get('star-wars-logo.jpg');
$metadata = array(
	'father'	=>	'Anakin',
	'son'		=>	'Luke'
	);
$objectId = MongoGrid::prefix('starWars')->storeFile($fileContent, $fileName, $metadata);
```
### Get File
All method to get files from GridFS. **Revision** numbers are defined as follows:
-   0 = the original stored file
-   1 = the first revision
-   2 = the second revision
-   etc…
-   -2 = the second most recent revision
-   -1 = the most recent revision

> Defaults to -1 (i.e. the most recent revision). Revision works only on method by filename.
#### getFileContent(  $source, [optional $revision] )
You can retrive the content of file by his name or his ObjectId. You can use also the revision of the file.
```php
$fileName = 'star-wars-logo.jpg';
$content = MongoGrid::getFileContent($fileName, '-1');

// Or by ObjectId

$objectId = new \MongoDB\BSON\ObjectId;
$content = MongoGrid::getFileContent($objectId);
```
#### getFile( $source )
You can retrive the content of file by his name or his ObjectId. Returns a document of the file collection.
```php
$fileName = 'star-wars-logo.jpg';
$document = MongoGrid::getFile($fileName);

// Or by ObjectId

$objectId = new \MongoDB\BSON\ObjectId;
$document = MongoGrid::getFile($objectId);
```
#### findOne( $query, [ optional $options ] )
Finds a single document from the selected GridFS bucket matching the query.
```php
$objectId = new \MongoDB\BSON\ObjectId;
$document = MongoGrid::findOne([ '_id' => $objectId ]);
```
To learn more about `$options` see [MongoDB GridFS findOne](https://docs.mongodb.com/php-library/current/reference/method/MongoDBGridFSBucket-findOne/).
#### find( $query, [ optional $options ] )
Finds all documents from the selected GridFS bucket matching the query.
```php
$objectId = new \MongoDB\BSON\ObjectId;
$document = MongoGrid::find([ '_id' => $objectId ]);
```
#### download( $source, string $path, [optional $revision] )
Download a file from GridFS to a given path
```php
$objectId = new \MongoDB\BSON\ObjectId;
$path = 'your/awesome/path';
MongoGrid::download($objectId, $path);

//Or by filename

$fileName = 'star-wars-logo.jpg';
$path = 'your/awesome/path';
MongoGrid::download($fileName, $path, '-1');
```
**NB:** downloading a file with this method will increments downloads on metadata by itself (only if metadata are active).
### Helpers
#### rename( $_id, $newName )
Rename a file.
```php
$objectId = new \MongoDB\BSON\ObjectId;
$newName = 'star-wars-amazing-logo.jpg';
MongoGrid::rename($objectId, $newName);
```
#### delete( $_id )
Delete a file from the collection.
```php
$objectId = new \MongoDB\BSON\ObjectId;
MongoGrid::delete($objectId);
```
#### getBucketName()
 Return the Collection Chunks of the selected GridFS Bucket
```php
$bucketName = MongoGrid::getBucketName();
```
#### getChunksCollection()
 Returns the name of the selected GridFS Bucket
```php
$collection = MongoGrid::getChunksCollection();
```
#### getChunkSizeBytes()
 Return the size of Chunks of the selected GridFS Bucket
```php
$size = MongoGrid::getChunkSizeBytes();
```
#### getDatabaseName()
Return the name of the database used for selected GridFS
```php
$databaseName = MongoGrid::getDatabaseName();
```
#### getFilesCollection()
 Return the Collection File of the selected GridFS Bucket
```php
$collection = MongoGrid::getFilesCollection();
```
#### drop()
 Drop entire collections of a selected GridFS
```php
MongoGrid::drop();
```
## Contact
Open an issue on GitHub if you have any problems or suggestions.
## License
The contents of this repository is released under the  [MIT license](http://opensource.org/licenses/MIT).
