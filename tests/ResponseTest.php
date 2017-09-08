<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\Response;
use Avoxx\Psr7\Stream;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    /**
     * @var \Avoxx\Psr7\Response
     */
    protected $response;

    public function setUp()
    {
        $this->response = new Response();
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
        $this->response->foo = 'bar';
    }

    /**
     * ------------------------------------------
     *  CONSTRUCTOR
     * ------------------------------------------
     */

    public function testConstructorWithStreamInstanceAsBody()
    {
        $stream = new Stream;
        $response = new Response($stream);

        $this->assertSame($stream, $response->getBody());
    }

    public function testConstructorWithStringAsBody()
    {
        $response = new Response('php://memory');

        $this->assertInstanceOf(Stream::class, $response->getBody());
    }

    public function testConstructorWithResourceAsBody()
    {
        $response = new Response(fopen('php://memory', 'r+'));

        $this->assertInstanceOf(Stream::class, $response->getBody());
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
    public function testInvalidBodyTypeThrowsException($body)
    {
        $response = new Response($body);
    }

    public function testConstructorWithStatusCode()
    {
        $response = new Response(null, 404);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    public function invalidStatusCodeTypeDataProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'string' => ['foo'],
            'float' => [100.1],
            'array' => [[100]],
            'object' => [(object) [100]],
        ];
    }

    /**
     * @dataProvider invalidStatusCodeTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidStatusCodeTypeThrowsException($statusCode)
    {
        $response = new Response(null, $statusCode);
    }

    public function testConstructorHeadersReturnsHeaderValues()
    {
        $headers = ['X-foo' => ['bar']];
        $response = new Response(null, 200, $headers);

        $this->assertEquals($headers, $response->getHeaders());
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
    public function testConstructorInvalidHeaderTypeThrowsException($headers)
    {
        $response = new Response(null, 200, $headers);
    }

    public function headersCRLFInjectionDataProvider()
    {
        return [
            'name-cr' => ["X-Foo\r-Bar", 'foo'],
            'name-lf' => ["X-Foo\n-Bar", 'foo'],
            'name-crlf' => ["X-Foo\r\n-Bar", 'foo'],
            'name-double-crlf' => ["X-Foo\r\n\r\n-Bar", 'foo'],
            'value-cr' => ['X-Foo', "foo\rbar"],
            'value-lf' => ['X-Foo', "foo\nbar"],
            'value-crlf' => ['X-Foo', "foo\r\nbar"],
            'value-double-crlf' => ['X-Foo', "foo\r\n\r\nbar"],
            'value-cr-array' => ['X-Foo', ["foo\rbar"]],
            'value-lf-array' => ['X-Foo', ["foo\nbar"]],
            'value-crlf-array' => ['X-Foo', ["foo\r\nbar"]],
            'value-double-crlf-array' => ['X-Foo', ["foo\r\n\r\nbar"]],
        ];
    }

    /**
     * @dataProvider headersCRLFInjectionDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorThrowsExceptionOnCRLFInjection($name, $value)
    {
        $response = new Response(null, 200, [$name => $value]);
    }

    /**
     * ------------------------------------------
     *  STATUS CODE
     * ------------------------------------------
     */

    public function testGetStatusCodeReturns200ByDefault()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testWithStatusCodeReturnsClonedInstanceWithNewStatusCode()
    {
        $responseClone = $this->response->withStatus(404);

        $this->assertNotSame($responseClone, $this->response);
        $this->assertEquals(404, $responseClone->getStatusCode());
    }

    public function testWithStatusCodeCanSetStatusCodeNumberAsString()
    {
        $responseClone = $this->response->withStatus('404');

        $this->assertEquals('404', $responseClone->getStatusCode());
    }

    /**
     * @dataProvider invalidStatusCodeTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidStatusCodeTypeThrowsException($statusCode)
    {
        $this->response->withStatus($statusCode);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStatusCodeTooLowThrowsException()
    {
        $this->response->withStatus(99);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStatusCodeTooHighThrowsException()
    {
        $this->response->withStatus(600);
    }

    public function invalidStatusTypeDataProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'float' => [100.1],
            'array' => [[100]],
            'object' => [(object) [100]],
        ];
    }

    /**
     * @dataProvider invalidStatusTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithStatusCodeWithInvalidReasonPhraseThrowsException($status)
    {
        $this->response->withStatus(404, $status);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithStatusThrowsExceptionForUnrecognisedCodeWithoutReasonPhrase()
    {
        $this->response->withStatus(103);
    }

    /**
     * ------------------------------------------
     *  REASON PHRASE
     * ------------------------------------------
     */

    public function testGetReasonPhraseReturnsDefaultReasonPhrase()
    {
        $this->assertEquals('OK', $this->response->getReasonPhrase());
    }

    public function testClonedInstanceReturnsDefaultReasonPhrase()
    {
        $responseClone = $this->response->withStatus(404);

        $this->assertEquals('Not Found', $responseClone->getReasonPhrase());
    }

    public function testGetReasonPhraseCanSetCustomReasonPhraseForRecognisedStatusCodes()
    {
        $responseClone = $this->response->withStatus(404, 'Foo');

        $this->assertEquals('Foo', $responseClone->getReasonPhrase());
    }

    public function testGetReasonPhraseCanSetCustomReasonPhraseForUnrecognisedStatusCodes()
    {
        $responseClone = $this->response->withStatus(103, 'Foo');

        $this->assertEquals('Foo', $responseClone->getReasonPhrase());
    }
}
