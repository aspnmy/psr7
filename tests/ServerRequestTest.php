<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\ServerRequest;
use Avoxx\Psr7\Stream;
use Avoxx\Psr7\UploadedFile;
use Avoxx\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase
{

    /**
     * @var \Avoxx\Psr7\ServerRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = new ServerRequest();
    }

    /**
     * ------------------------------------------
     *  CONSTRUCTOR
     * ------------------------------------------
     */

    public function testConstructorAllMessageParts()
    {
        $request = new ServerRequest(
            $method = 'POST',
            $uri = new Uri('https://example.com/foo'),
            $body = new  Stream(),
            $serverParams = ['foo' => 'bar'],
            $cookieParams = ['foo' => 'bar'],
            $queryParams = ['foo' => 'bar'],
            $uploadedFiles = [new UploadedFile('php://temp')],
            $headers = ['X-Foo' => ['bar']],
            $protocol = '1.0'
        );

        $this->assertEquals($method, $request->getMethod());
        $this->assertSame($uri, $request->getUri());
        $this->assertSame($body, $request->getBody());
        $this->assertEquals($serverParams, $request->getServerParams());
        $this->assertEquals($cookieParams, $request->getCookieParams());
        $this->assertEquals($queryParams, $request->getQueryParams());
        $this->assertEquals($uploadedFiles, $request->getUploadedFiles());
        $this->assertEquals($headers, $request->getHeaders());
        $this->assertNull($request->getParsedBody());
        $this->assertEquals($protocol, $request->getProtocolVersion());
        $this->assertEquals('/foo', $request->getRequestTarget());
    }

    public function testConstructorWithMethodSetToNullReturnsEmptyString()
    {
        $request = new ServerRequest();

        $this->assertEquals('', $request->getMethod());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidMethodStringThrowsException()
    {
        $request = new ServerRequest('F@@');
    }

    public function testConstructorWithUriStringReturnsNewUriInstance()
    {
        $request = new ServerRequest(null, $uri = 'https://example.com/');

        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals($uri, (string) $request->getUri());
    }

    public function testConstructorWithUriIsNullReturnsNewUriInstance()
    {
        $request = new ServerRequest(null, null);

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
        $request = new ServerRequest(null, $uri);
    }

    public function testConstructorWithBodyStringReturnsNewStreamInstance()
    {
        $request = new ServerRequest(null, null, 'php://memory');

        $this->assertInstanceOf(Stream::class, $request->getBody());
    }

    public function testConstructorWithBodyResourceReturnsNewStreamInstance()
    {
        $request = new ServerRequest(null, null, fopen('php://memory', 'r+'));

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
        $request = new ServerRequest(null, null, $body);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidUploadedFilesThrowsException()
    {
        $request = new ServerRequest(null, null, null, [], [], [], [null], []);
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
        $request = new ServerRequest(null, null, null, [], [], [], [], $headers);
    }

    public function invalidProtocolVersionDataProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
            'array' => [[1]],
            'object' => [(object) ['version' => '1.1']],
            'no-minor' => ['1'],
            'invalid-minor' => ['1.2'],
            'hotfix' => ['1.2.3'],
            'minor-too-big' => ['3.0'],
        ];
    }

    /**
     * @dataProvider invalidProtocolVersionDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithProtocolVersionThrowsExceptionWhenTypeIsInvalid($version)
    {
        $request = new ServerRequest(null, null, null, [], [], [], [], [], $version);
    }

    /**
     * ------------------------------------------
     *  SERVER PARAMS
     * ------------------------------------------
     */

    public function testGetServerParamsAreEmptyByDefault()
    {
        $this->assertEquals([], $this->request->getServerParams());
    }

    /**
     * ------------------------------------------
     *  COOKIE PARAMS
     * ------------------------------------------
     */

    public function testGetCookieParamsAreEmptyByDefault()
    {
        $this->assertEquals([], $this->request->getCookieParams());
    }

    public function testWithCookieParamsReturnsClonedInstanceWithNewCookieParams()
    {
        $cookieParams = ['foo' => 'bar'];
        $request = $this->request->withCookieParams($cookieParams);

        $this->assertNotSame($request, $this->request);
        $this->assertEquals($cookieParams, $request->getCookieParams());
    }

    /**
     * ------------------------------------------
     *  QUERY PARAMS
     * ------------------------------------------
     */

    public function testGetQueryParamsAreEmptyByDefault()
    {
        $this->assertEquals([], $this->request->getQueryParams());
    }

    public function testWIthQueryParamsReturnsClonedInstanceWithNewCookieParams()
    {
        $query = ['foo' => 'bar'];
        $request = $this->request->withQueryParams($query);

        $this->assertNotSame($request, $this->request);
        $this->assertEquals($query, $request->getQueryParams());
    }

    /**
     * ------------------------------------------
     *  UPLOADED FILES
     * ------------------------------------------
     */

    public function testGetUploadedFilesAreEmptyByDefault()
    {
        $this->assertEquals([], $this->request->getUploadedFiles());
    }

    public function testWithUploadedFilesReturnsNewInstanceWithUploadedFiles()
    {
        $uploadedFiles = [new UploadedFile('php://temp')];
        $request = $this->request->withUploadedFiles($uploadedFiles);

        $this->assertNotSame($request, $this->request);
        $this->assertEquals($uploadedFiles, $request->getUploadedFiles());
    }

    public function testWithUploadedFilesWithNestedUploadedFiles()
    {
        $uploadedFiles = [
            [
                new UploadedFile('php://temp', 0, 0),
                new UploadedFile('php://temp', 0, 0),
            ],
        ];
        $request = $this->request->withUploadedFiles($uploadedFiles);

        $this->assertSame($uploadedFiles, $request->getUploadedFiles());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithUploadedFilesWithInvalidTypeThrowsException()
    {
        $this->request->withUploadedFiles([null]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithUploadedFilesWithNestedInvalidTypesThrowsException()
    {
        $this->request->withUploadedFiles([[null], [null]]);
    }

    /**
     * ------------------------------------------
     *  PARSED BODY
     * ------------------------------------------
     */

    public function testGetParsedBodyIsEmptyByDefault()
    {
        $this->assertNull($this->request->getParsedBody());
    }

    public function testWithParsedBodyReturnsNewInstanceWithParsedBody()
    {
        $data = ['foo' => 'bar'];
        $request = $this->request->withParsedBody($data);

        $this->assertNotSame($request, $this->request);
        $this->assertEquals($data, $request->getParsedBody());
    }

    public function invalidBodyTypesDataProvider()
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
        ];
    }

    /**
     * @dataProvider invalidBodyTypesDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithParsedBodyThrowsExceptionForInvalidBodyTypes($data)
    {
        $this->request->withParsedBody($data);
    }

    /**
     * ------------------------------------------
     *  ATTRIBUTES
     * ------------------------------------------
     */

    public function testGetAttributesAreEmptyByDefault()
    {
        $this->assertEquals([], $this->request->getAttributes());
    }

    public function testGetAttributeIsEmptyByDefault()
    {
        $this->assertNull($this->request->getAttribute('foo'));
    }

    public function testGetAttributeReturnsDefaultValueWhenAttributeDoesNotExists()
    {
        $this->assertEquals('bar', $this->request->getAttribute('foo', 'bar'));
    }

    public function testWithAttributeReturnsNewInstanceWithAttributes()
    {
        $request = $this->request->withAttribute('foo', 'bar');

        $this->assertNotSame($request, $this->request);
        $this->assertEquals('bar', $request->getAttribute('foo'));
    }

    public function testWithAttributeAllowsRemovingAttributeWithNullValue()
    {
        $request = $this->request->withAttribute('foo', null);
        $request = $request->withoutAttribute('foo');

        $this->assertSame([], $request->getAttributes());
    }

    public function testWithoutAttributeReturnsNewInstanceWithoutAttribute()
    {
        $request = $this->request->withAttribute('foo', 'bar');

        $this->assertEquals('bar', $request->getAttribute('foo'));

        $request2 = $request->withoutAttribute('foo');

        $this->assertNotSame($request2, $request);
        $this->assertNull($request2->getAttribute('foo'));
    }

    public function testWithoutAttributeAllowsRemovingNonExistentAttribute()
    {
        $request = $this->request->withoutAttribute('nope');

        $this->assertSame([], $request->getAttributes());
    }
}
