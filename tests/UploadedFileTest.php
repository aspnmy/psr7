<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\Stream;
use Avoxx\Psr7\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{

    protected $temp;

    public function tearDown()
    {
        if (is_scalar($this->temp) && file_exists($this->temp)) {
            unlink($this->temp);
        }
    }

    /**
     * ------------------------------------------
     *  CONSTRUCTOR
     * ------------------------------------------
     */

    public function testConstructorAndGetMethods()
    {
        $this->temp = uniqid('avoxx_test_');
        $upload = new UploadedFile($this->temp, 4096, UPLOAD_ERR_OK, 'testfile', 'image/png');

        $this->assertEquals(4096, $upload->getSize());
        $this->assertEquals(0, $upload->getError());
        $this->assertEquals('testfile', $upload->getClientFilename());
        $this->assertEquals('image/png', $upload->getClientMediaType());
    }

    public function invalidStreamOrFileDataProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
            'array' => [['resource']],
            'object' => [(object) ['resource']],
        ];
    }

    /**
     * @dataProvider invalidStreamOrFileDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorThrowsExceptionOnInvalidStreamOrFile($fileOrStream)
    {
        new UploadedFile($fileOrStream);
    }

    public function invalidErrorDataProvider()
    {
        return [
            'to_low' => [-1],
            'to_high' => [9],
        ];
    }

    /**
     * @dataProvider invalidErrorDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructThrowsExceptionWhenErrorIntegerIsToLowOrToHigh($error)
    {
        $this->temp = uniqid('avoxx_test_');

        new UploadedFile($this->temp, 0, $error);
    }

    /**
     * ------------------------------------------
     *  STREAM
     * ------------------------------------------
     */

    public function testGetStreamReturnsOriginalStreamObject()
    {
        $stream = new Stream();
        $upload = new UploadedFile($stream);

        $this->assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream()
    {
        $this->temp = uniqid('avoxx_test_');
        $stream = fopen($this->temp, 'wb+');
        $upload = new UploadedFile($stream);

        $uploadStream = $upload->getStream()->detach();

        $this->assertSame($stream, $uploadStream);
    }

    public function testGetStreamReturnsStreamForFile()
    {
        $this->temp = uniqid('avoxx_test_');
        $stream = fopen($this->temp, 'wb+');
        $upload = new UploadedFile($stream);
        $uploadStream = $upload->getStream();

        $this->assertAttributeEquals($stream, 'stream', $uploadStream);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetStreamThrowsExceptionAfterFileWasMoved()
    {
        $this->temp = uniqid('avoxx_test_');
        $path = sys_get_temp_dir() . '/' . $this->temp;
        $upload = new UploadedFile($this->temp);
        $upload->moveTo($path);
        $upload->getStream();
    }

    /**
     * ------------------------------------------
     *  MOVE TO
     * ------------------------------------------
     */

    public function testMoveToMovesFileToDesignatedPath()
    {
        $this->temp = uniqid('avoxx_test_');
        $path = sys_get_temp_dir() . '/' . $this->temp;
        $stream = new Stream($this->temp, 'wb+');
        $stream->write('Foo');
        $upload = new UploadedFile($stream);

        $this->assertFileNotExists($path);

        $upload->moveTo($path);

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        unlink($path);

        $this->assertEquals((string) $stream, $contents);
    }

    public function testMoveWithSapiTrueMovesFileToDesignatedPath()
    {
        $this->temp = uniqid('avoxx_test_');
        $path = sys_get_temp_dir() . '/' . $this->temp;
        $stream = new Stream($this->temp, 'wb+');
        $stream->write('Foo');
        $upload = new UploadedFile($stream, 0, 0, null, null, true);

        $this->assertFileNotExists($path);

        $upload->moveTo($path);

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        unlink($path);

        $this->assertEquals((string) $stream, $contents);
    }

    public function testMoveToStream()
    {
        $this->temp = uniqid('avoxx_test_');
        $upload = new UploadedFile($this->temp);

        $this->assertFileExists($this->temp);

        $upload->moveTo('php://temp');

        $this->assertFileNotExists($this->temp);

    }

    public function invalidTargetPathDataProvider()
    {
        return [
            'empty' => [''],
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
            'array' => [['resource']],
            'object' => [(object) ['resource']],
        ];
    }

    /**
     * @dataProvider invalidTargetPathDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testMoveToThrowsExceptionWhenTargetPathIsInvalid($targetPath)
    {
        $this->temp = uniqid('avoxx_test_');
        $upload = new UploadedFile($this->temp);

        $upload->moveTo($targetPath);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMoveToThrowsExceptionWhenTargetPathIsNotWritable()
    {
        $this->temp = uniqid('avoxx_test_');
        $upload = new UploadedFile($this->temp);
        $upload->moveTo('some_random_dir/' . $this->temp);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMoveToThrowsExceptionWhenFileWasAlreadyMoved()
    {
        $this->temp = uniqid('avoxx_test_');
        $path = sys_get_temp_dir() . '/' . $this->temp;
        $upload = new UploadedFile(fopen($this->temp, 'wb+'));
        $upload->moveTo($path);

        $this->assertFileExists($path);

        unlink($path);

        $upload->moveTo($path);
    }
}
