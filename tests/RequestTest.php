<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\Request;
use Avoxx\Psr7\Stream;
use Avoxx\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    /**
     * @var \Avoxx\Psr7\Request
     */
    protected $request;

    public function setUp()
    {
        $this->request = new Request();
    }

    /**
     * ------------------------------------------
     *  IMMUTABILITY
     * ------------------------------------------
     */

    /**
     * @expectedException \Exception
     */
    public function testImmutability()
    {
        $this->request->foo = 'bar';
    }

    /**
     * ------------------------------------------
     *  CONSTRUCTOR
     * ------------------------------------------
     */

    public function testConstructorAllMessageParts()
    {
        $method = 'POST';
        $uri = new Uri('https://example.com/foo');
        $body = new  Stream();
        $headers = ['X-Foo' => ['bar']];
        $request = new Request($method, $uri, $body, $headers);

        $this->assertEquals($method, $request->getMethod());
        $this->assertSame($uri, $request->getUri());
        $this->assertSame($body, $request->getBody());
        $this->assertEquals($headers, $request->getHeaders());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('/foo', $request->getRequestTarget());
    }

    public function testConstructorWithMethodSetToNullReturnsEmptyString()
    {
        $request = new Request();

        $this->assertEquals('', $request->getMethod());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidMethodStringThrowsException()
    {
        $request = new Request('F@@');
    }

    public function testConstructorWithUriStringReturnsNewUriInstance()
    {
        $request = new Request(null, $uri = 'https://example.com/');

        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals($uri, (string) $request->getUri());
    }

    public function testConstructorWithUriIsNullReturnsNewUriInstance()
    {
        $request = new Request(null, null);

        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals('/', (string) $request->getUri());
    }

    public function invalidUriTypeDataProvider()
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
            'array' => [['foo']],
            'object' => [(object) ['foo']],
        ];
    }

    /**
     * @dataProvider invalidUriTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorThrowsExceptionForInvalidUriType($uri)
    {
        $request = new Request(null, $uri);
    }

    public function testConstructorWithBodyStringReturnsNewStreamInstance()
    {
        $request = new Request(null, null, 'php://memory');

        $this->assertInstanceOf(Stream::class, $request->getBody());
    }

    public function testConstructorWithBodyResourceReturnsNewStreamInstance()
    {
        $request = new Request(null, null, fopen('php://memory', 'r+'));

        $this->assertInstanceOf(Stream::class, $request->getBody());
    }

    public function invalidBodyTypeDataProvider()
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
            'array' => [['foo']],
            'object' => [(object) ['foo']],
        ];
    }

    /**
     * @dataProvider invalidBodyTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidBodyTypeThrowsException($body)
    {
        $request = new Request(null, null, $body);
    }

    public function invalidHeadersDataProvider()
    {
        return [
            'null' => [['foo' => null]],
            'true' => [['foo' => true]],
            'false' => [['foo' => false]],
            'array' => [['foo' => ['foo' => ['bar']]]],
            'object' => [['foo' => (object) ['foo' => 'bar']]],
        ];
    }

    /**
     * @dataProvider invalidHeadersDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidHeaderTypeThrowsException($headers)
    {
        $request = new Request(null, null, null, $headers);
    }
}
