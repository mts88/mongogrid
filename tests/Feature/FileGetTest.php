<?php

use MongoGrid;

class FileGetTest extends TestCase
{
    public function __construct($name = null, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->createApplication();
    }

    /**
     * @dataProvider fileProvider
     */
    public function testGetContentById($id, $fileContent)
    {
        $content = MongoGrid::getFileContent($id);

        $this->assertSame( base64_encode($fileContent), base64_encode($content));
    }
    /**
     * @dataProvider fileContentProvider
     */
    public function testGetContentByName($filename, $fileContent)
    {
        $content = MongoGrid::getFileContent($filename);

        $this->assertSame( base64_encode($fileContent), base64_encode($content));

    }
    /**
     * @dataProvider fileContentProvider
     */
    public function testGetContentByNameLast($filename, $fileContent)
    {
        $content = MongoGrid::getFileContent($filename, '-1');

        $this->assertSame( base64_encode($fileContent), base64_encode($content));
    }

    /**
     * @dataProvider fileProvider
     */
    public function testGetObjectById($id, $fileContent)
    {
        $obj = MongoGrid::findOne([ '_id' => $id ]);

        $this->assertInstanceOf('\MongoDB\Model\BSONDocument', $obj);

    }

    /**
     * @dataProvider filePathProvider
     */
    public function testDownloadById($id, $path)
    {
        MongoGrid::download($id, $path);
        MongoGrid::incrementDownload($id); // double increment

        $this->assertTrue(true);

    }


    /**
     * @dataProvider fileNamePathProvider
     */
    public function testDownloadByName($name, $path)
    {

        MongoGrid::download( $name, $path, '-1');

        $this->assertTrue(true);

    }

    /**
     * @dataProvider fileProvider
     */
    public function testRenameObject($id, $fileContent)
    {
        $obj = MongoGrid::rename($id, 'star-wars-cool.jpg');

        $this->assertInstanceOf('\MongoDB\Model\BSONDocument', $obj);

    }

    /**
     * @dataProvider fileProvider
     */
    public function testDeleteFile($id, $fileContent)
    {
        MongoGrid::delete($id);

        $this->assertTrue(true);

    }

    public function fileProvider()
    {
        $path = dirname(__DIR__).'/src/star-wars-rogue-one.jpg';
        $file = file_get_contents($path);
        $file_id = MongoGrid::storeFile($file, 'star-wars-rogue-one.jpg');

        return [
            [$file_id, $file]
        ];
    }

    public function fileContentProvider()
    {
        $path = dirname(__DIR__).'/src/star-wars-rogue-one.jpg';
        $file = file_get_contents($path);

        return [
            ['star-wars-rogue-one.jpg', $file]
        ];
    }

    public function filePathProvider()
    {
        $path = dirname(__DIR__).'/src/star-wars-rogue-one.jpg';
        $file = file_get_contents($path);
        $file_id = MongoGrid::storeFile($file, 'star-wars-rogue-one.jpg');

        return [
            [$file_id, dirname(__DIR__).'/src/downloads']
        ];
    }

    public function fileNamePathProvider()
    {
        return [
            ['star-wars-rogue-one.jpg', dirname(__DIR__).'/src/downloads']
        ];
    }
}
