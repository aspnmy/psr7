<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7\Traits;

use Avoxx\Psr7\Uri;
use AvoxxTests\Psr7\Fixtures\RequestMock;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class RequestTraitTest extends TestCase
{

    /**
     * @var \Avoxx\Psr7\Traits\RequestTrait
     */
    protected $request;

    public function setUp()
    {
        $this->request = new RequestMock();

        $reflection = new ReflectionClass($this->request);
        $property = $reflection->getProperty('uri');
        $property->setAccessible(true);
        $property->setValue($this->request, new Uri());
    }

    /**
     * ------------------------------------------
     *  REQUEST TARGET
     * ------------------------------------------
     */

    public function testGetRequestTargetReturnsSlashWhenNoUriIsPresent()
    {
        $this->assertEquals('/', $this->request->getRequestTarget());
    }

    public function testGetRequestTargetReturnsSlashWhenUriHasNoPathOrQuery()
    {
        $request = $this->request->withUri(new Uri('https://example.com'));

        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithAbsoluteUri()
    {
        $request = $this->request->withUri(new Uri('https://example.com/foo'));

        $this->assertEquals('/foo', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithAbsoluteUriAndQuery()
    {
        $request = $this->request->withUri(new Uri('https://example.com/foo?bar=baz'));

        $this->assertEquals('/foo?bar=baz', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithRelativeUri()
    {
        $request = $this->request->withUri(new Uri('/foo'));

        $this->assertEquals('/foo', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithRelativeUriAndQuery()
    {
        $request = $this->request->withUri(new Uri('/foo?bar=baz'));

        $this->assertEquals('/foo?bar=baz', $request->getRequestTarget());
    }

    public function testWithRequestTargetReturnsNewInstanceWithNewRequestTarget()
    {
        $request = $this->request->withRequestTarget('/foo/bar');

        $this->assertNotSame($request, $this->request);
        $this->assertEquals('/foo/bar', $request->getRequestTarget());
    }

    public function validRequestTargetsDataProvider()
    {
        return [
            'asterisk-form' => ['*'],
            'authority-form' => ['example.com'],
            'absolute-form' => ['https://example.com/foo'],
            'absolute-form-query' => ['https://example.com/foo?bar=baz'],
            'origin-form-path-only' => ['/foo'],
            'origin-form' => ['/foo?bar=baz'],
        ];
    }

    /**
     * @dataProvider validRequestTargetsDataProvider
     */
    public function testWithRequestTargetWithValidRequestTargets($requestTarget)
    {
        $request = $this->request->withRequestTarget($requestTarget);

        $this->assertEquals($requestTarget, $request->getRequestTarget());
    }

    public function testGetRequestTargetDoesNotCacheBetweenInstances()
    {
        $request1 = $this->request->withUri(new Uri('https://example.com/foo'));
        $requestTarget1 = $request1->getRequestTarget();
        $request2 = $request1->withUri(new Uri('https://foo.bar/baz'));

        $this->assertNotEquals($requestTarget1, $request2->getRequestTarget());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithRequestTargetThrowsExceptionForInvalidRequestTarget()
    {
        $this->request->withRequestTarget('/foo/ bar');
    }

    /**
     * ------------------------------------------
     *  METHOD
     * ------------------------------------------
     */

    public function testMethodIsEmptyByDefault()
    {
        $this->assertSame('', $this->request->getMethod());
    }


    public function testWithMethodReturnsNewInstanceWithNewMethod()
    {
        $request = $this->request->withMethod('POST');

        $this->assertNotSame($request, $this->request);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testWithMethodIsSetToNullGetMethodReturnsEmptyString()
    {
        $request = $this->request->withMethod(null);

        $this->assertSame('', $request->getMethod());
    }

    public function invalidRequestMethodDataProvider()
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.2],
            'invalid-string' => ['FOO BAR'],
            'array' => [['foo']],
            'object' => [(object) ['foo']],
        ];
    }

    /**
     * @dataProvider invalidRequestMethodDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithMethodInvalidMethodThrowsException($method)
    {
        $this->request->withMethod($method);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithMethodInvalidMethodStringThrowsException()
    {
        $this->request->withMethod('F@@');
    }

    public function validRequestMethodsDataProvider()
    {
        return [
            'head' => ['HEAD'],
            'get' => ['GET'],
            'post' => ['POST'],
            'put' => ['PUT'],
            'patch' => ['PATCH'],
            'delete' => ['DELETE'],
            'purge' => ['PURGE'],
            'options' => ['OPTIONS'],
            'trace' => ['TRACE'],
            'connect' => ['CONNECT'],
        ];
    }

    /**
     * @dataProvider validRequestMethodsDataProvider
     */
    public function testWithMethodWithValidMethodsReturnsNewInstanceWithMethod($method)
    {
        $request = $this->request->withMethod($method);

        $this->assertEquals($method, $request->getMethod());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithMethodWithNoValidMethod()
    {
        $this->request->withMethod('Nope');
    }

    /**
     * ------------------------------------------
     *  URI
     * ------------------------------------------
     */

    public function testGetUriIsInstanceOfUriInterface()
    {
        $this->assertInstanceOf(UriInterface::class, $this->request->getUri());
        $this->assertInstanceOf(Uri::class, $this->request->getUri());
    }

    public function testGetUriReturnsUnpopulatedUriByDefault()
    {
        $uri = $this->request->getUri();

        $this->assertEmpty($uri->getScheme());
        $this->assertEmpty($uri->getUserInfo());
        $this->assertEmpty($uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertEmpty($uri->getPath());
        $this->assertEmpty($uri->getQuery());
        $this->assertEmpty($uri->getFragment());
    }

    public function testWithUriReturnsNewInstanceWithNewUri()
    {
        $uriString = 'https://user:pass@example.com:8080/path?query=value#fragment';
        $uriInstance = new Uri($uriString);

        $request = $this->request->withUri($uriInstance);

        $this->assertSame($uriInstance, $request->getUri());
        $this->assertNotSame($request, $this->request);
        $this->assertEquals($uriString, (string) $uriInstance);
        $this->assertNotEquals($this->request->getRequestTarget(), $request->getRequestTarget());
    }

    public function testWithUriPreservesHost()
    {
        $uri1 = new Uri();
        $uri2 = new Uri('https://user:pass@example.com:8080/path?query=value#fragment');

        $request1 = $this->request->withUri($uri1, true);
        $request2 = $this->request->withUri($uri2, true);
        $request3 = $this->request->withHeader('Host', 'example.com');
        $request4 = $request3->withUri($uri2, true);

        $this->assertSame('', $request1->getHeaderLine('Host'));
        $this->assertSame('example.com:8080', $request2->getHeaderLine('Host'));
        $this->assertSame('example.com', $request4->getHeaderLine('Host'));
    }
}
