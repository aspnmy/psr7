<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{

    /**
     * @var \Avoxx\Psr7\Stream
     */
    protected $stream;

    protected $temp;

    public function setUp()
    {
        $this->stream = new Stream();
    }

    public function tearDown()
    {
        if ($this->temp && file_exists($this->temp)) {
            unlink($this->temp);
        }
    }

    /**
     * ------------------------------------------
     *  CONSTRUCTOR
     * ------------------------------------------
     */

    public function testInstantiateWithStreamIdentifier()
    {
        $this->assertInstanceOf(Stream::class, $this->stream);
    }

    public function testInstantiateWithStreamResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);

        $this->assertInstanceOf(Stream::class, $stream);
    }

    public function invalidResourcesDataProvider()
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
     * @dataProvider invalidResourcesDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testInstantiateThrowsExceptionWhenStreamResourceTypeIsInvalid($resource)
    {
        new Stream($resource);
    }

    public function testToStringReturnsStreamContent()
    {
        $this->temp = 'avoxx_test';
        $message = 'foo';

        file_put_contents($this->temp, $message);

        $stream = new Stream($this->temp, 'rb');

        $this->assertEquals($message, (string) $stream);
    }

    /**
     * ------------------------------------------
     *  TO STRING
     * ------------------------------------------
     */

    public function testToStringReturnsEmptyStringWhenStreamIsNotReadable()
    {
        $this->temp = 'avoxx_test';
        $message = 'foo';

        file_put_contents($this->temp, $message);

        $stream = new Stream($this->temp, 'rb');

        $stream->detach();

        $this->assertEquals('', (string) $stream);
    }

    /**
     * ------------------------------------------
     *  CLOSE
     * ------------------------------------------
     */

    public function testCloseClosesResource()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->close();

        $this->assertFalse(is_resource($resource));
    }

    public function testCloseUnsetResource()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->close();

        $this->assertNull($stream->detach());
    }

    public function testCloseDoesNothingAfterDetach()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);
        $detached = $stream->detach();

        $stream->close();

        $this->assertTrue(is_resource($detached));
        $this->assertSame($resource, $detached);
    }

    /**
     * ------------------------------------------
     *  DETACH
     * ------------------------------------------
     */

    public function testDetachReturnsResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);

        $this->assertSame($resource, $stream->detach());
        $this->assertAttributeEquals(null, 'stream', $stream);
    }

    /**
     * ------------------------------------------
     *  SIZE
     * ------------------------------------------
     */

    public function testGetSizeReturnsStreamSize()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        $expected = fstat($resource);

        $this->assertEquals($expected['size'], $stream->getSize());
    }

    public function testGetSizeReturnsNullAfterStreamIsDetached()
    {
        $this->stream->detach();

        $this->assertNull($this->stream->getSize());
    }

    /**
     * ------------------------------------------
     *  TELL
     * ------------------------------------------
     */

    public function testTellReturnsCurrentPositionInResource()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 3);

        $this->assertEquals(3, $stream->tell());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testTellThrowsExceptionWhenResourceIsDetached()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 3);

        $stream->detach();
        $stream->tell();
    }

    /**
     * ------------------------------------------
     *  END OF FILE
     * ------------------------------------------
     */

    public function testEofReturnsTrueWhenAtEndOfStream()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 3);

        $stream->detach();

        $this->assertTrue($stream->eof());
    }

    public function testEofReturnsFalseWhenNotAtEndOfStream()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 3);

        $this->assertFalse($stream->eof());
    }

    /**
     * ------------------------------------------
     *  IS SEEKABLE
     * ------------------------------------------
     */
    public function testIsSeekableReturnsTrueForReadableStreams()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnsFalseForDetachedStreams()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->detach();

        $this->assertFalse($stream->isSeekable());
    }

    /**
     * ------------------------------------------
     *  SEEK
     * ------------------------------------------
     */

    public function testSeekAdvancesToGivenOffsetStream()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->seek(3);

        $this->assertEquals(3, $stream->tell());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSeekThrowsExceptionWhenResourceIsNotSeekable()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->detach();
        $stream->seek(3);
    }

    /**
     * ------------------------------------------
     *  REWIND
     * ------------------------------------------
     */

    public function testRewindResetsToStartOfStream()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->seek(3);
        $stream->rewind();

        $this->assertEquals(0, $stream->tell());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRewindThrowsExceptionWhenStreamIsDetached()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->detach();
        $stream->rewind();
    }

    /**
     * ------------------------------------------
     *  IS WRITABLE
     * ------------------------------------------
     */

    public function testIsWritableReturnsTrueWhenStreamIsWritable()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $this->assertTrue($stream->isWritable());
    }

    public function testIsWritableReturnsFalseIfStreamIsNotWritable()
    {
        $this->assertFalse($this->stream->isWritable());
    }

    public function testIsWritableReturnsFalseWhenStreamIsDetached()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->detach();

        $this->assertFalse($stream->isWritable());
    }

    /**
     * ------------------------------------------
     *  WRITE
     * ------------------------------------------
     */

    public function testWriteWritesContentToStream()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);
        $bytes = $stream->write('foo');

        $this->assertEquals('foo', (string) $stream);
        $this->assertEquals(3, $bytes);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWriteThrowsExceptionWhenStreamIsNotWritable()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $stream->write('foo');
    }

    /**
     * ------------------------------------------
     *  IS READABLE
     * ------------------------------------------
     */

    public function testIsReadableReturnsTrueWhenStreamIsReadable()
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testIsReadableReturnsFalseWhenStreamIsNotReadable()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb');
        $stream = new Stream($resource);

        $this->assertFalse($stream->isReadable());
    }

    public function testIsReadableReturnsFalseWhenStreamIsDetached()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb');
        $stream = new Stream($resource);

        $stream->detach();

        $this->assertFalse($stream->isReadable());
    }

    /**
     * ------------------------------------------
     *  READ
     * ------------------------------------------
     */

    public function testReadReturnsStreamContent()
    {
        $this->temp = 'avoxx_test';
        $message = 'foo';

        file_put_contents($this->temp, $message);

        $stream = new Stream($this->temp, 'rb');

        $this->assertEquals($message, $stream->read(3));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testReadThrowsExceptionWhenStreamIsDetached()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        $stream->detach();

        $stream->read(3);
    }

    public function testReadReturnsEmptyStringWhenAtEndOfFile()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb+');
        $stream = new Stream($resource);

        while (! feof($resource)) {
            fread($resource, 4096);
        }

        $this->assertEquals('', $stream->read(4096));
    }

    /**
     * ------------------------------------------
     *  CONTENTS
     * ------------------------------------------
     */

    public function testGetContentsReturnsFullStreamContents()
    {
        $this->temp = 'avoxx_test';
        $message = 'foo';

        file_put_contents($this->temp, $message);

        $stream = new Stream($this->temp, 'rb');

        $this->assertEquals($message, $stream->getContents());
    }

    public function testGetContentsReturnsStreamContentsFromCurrentPointer()
    {
        $this->temp = 'avoxx_test';
        $message = 'foobar';

        file_put_contents($this->temp, $message);

        $stream = new Stream($this->temp, 'rb');

        $stream->seek(3);
        $this->assertEquals('bar', $stream->getContents());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetContentThrowsExceptionWhenStreamIsNotReadable()
    {
        $resource = fopen($this->temp = 'avoxx_test', 'wb');
        $stream = new Stream($resource);

        $stream->getContents();
    }

    /**
     * ------------------------------------------
     *  METADATA
     * ------------------------------------------
     */

    public function testGetMetadataReturnsAllMetadataWhenNoKeyPresent()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        $expected = stream_get_meta_data($resource);

        $this->assertEquals($expected, $stream->getMetadata());
    }

    public function testGetMetadataReturnsDataForSpecifiedKey()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        $expected = stream_get_meta_data($resource);

        $this->assertEquals($expected['uri'], $stream->getMetadata('uri'));
    }

    public function testGetMetadataReturnsNullWhenNoDataExistForKey()
    {
        $this->assertNull($this->stream->getMetadata('nope'));
    }
}
