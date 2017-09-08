<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\Traits\MessageTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class MessageTraitTest extends TestCase
{
    /**
     * @var \Avoxx\Psr7\Traits\MessageTrait
     */
    protected $message;

    public function setUp()
    {
        $this->message = $this->getMockForTrait(MessageTrait::class);
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
        $this->message->foo = 'bar';
    }

    /**
     * ------------------------------------------
     *  PROTOCOL VERSION
     * ------------------------------------------
     */

    public function testGetProtocolVersionReturnsDefaultProtocolVersion()
    {
        $this->assertEquals('1.1', $this->message->getProtocolVersion());
    }

    public function testWithProtocolVersionReturnsClonedInstanceWithProtocolVersionValue()
    {
        $message = $this->message->withProtocolVersion('1.0');

        $this->assertNotSame($message, $this->message);
        $this->assertEquals('1.0', $message->getProtocolVersion());
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
    public function testWithProtocolVersionThrowsExceptionWhenTypeIsInvalid($version)
    {
        $this->message->withProtocolVersion($version);
    }

    /**
     * ------------------------------------------
     *  HEADERS
     * ------------------------------------------
     */

    public function testGetHeadersReturnsCaseSensitiveHeaderValuesAsArray()
    {
        $message = $this->message->withHeader('X-Foo', ['foo', 'bar']);

        $this->assertEquals(['X-Foo' => ['foo', 'bar']], $message->getHeaders());
    }

    public function testGetHeadersKeepsCaseSensitivityWithWhichHeaderWasFirstRegistered()
    {
        $message = $this->message
            ->withHeader('X-Foo', ['foo', 'bar'])
            ->withAddedHeader('x-foo', 'baz');

        $this->assertEquals(['X-Foo' => ['foo', 'bar', 'baz']], $message->getHeaders());
    }

    public function testHasHeaderReturnsBoolean()
    {
        $message = $this->message->withHeader('X-Foo', ['foo', 'bar']);

        $this->assertTrue($message->hasHeader('X-Foo'));
        $this->assertFalse($message->hasHeader('X-Bar'));
    }

    public function testHasHeaderReturnsTrueWhenNoValueIsPresent()
    {
        $message = $this->message->withHeader('X-Foo', []);

        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    public function testGetHeaderReturnsHeaderValuesAsArray()
    {
        $message = $this->message->withHeader('X-Foo', ['foo', 'bar']);

        $this->assertEquals(['foo', 'bar'], $message->getHeader('X-Foo'));
    }

    public function testGetHeaderReturnsEmptyArrayWhenNoHeaderValuesAvailable()
    {
        $this->assertEquals([], $this->message->getHeader('X-Nope'));
    }

    public function testGetHeaderLineReturnsHeaderValueAsCommaConcatenatedString()
    {
        $message = $this->message->withHeader('X-Foo', ['foo', 'bar']);

        $this->assertEquals('foo, bar', $message->getHeaderLine('X-Foo'));
    }

    public function testGetHeaderLineReturnsEmptyStringWhenHeaderValueIsNotAvailable()
    {
        $this->assertEquals('', $this->message->getHeaderLine('X-Nope'));
    }

    public function testWithHeaderReturnsClonedInstance()
    {
        $message = $this->message->withHeader('X-Foo', ['foo', 'bar']);

        $this->assertNotSame($this->message, $message);
    }

    public function testWithHeaderConvertsStringValueToArray()
    {
        $message = $this->message->withHeader('X-Foo', 'foo');

        $this->assertEquals(['foo'], $message->getHeader('X-Foo'));
    }

    public function testWithHeaderReplacesDifferentCapitalization()
    {
        $message1 = $this->message->withHeader('X-Foo', 'foo');
        $message2 = $message1->withHeader('X-foo', 'bar');

        $this->assertEquals(['bar'], $message2->getHeader('x-foo'));
        $this->assertEquals(['X-foo' => ['bar']], $message2->getHeaders());
    }

    public function testWithHeaderAllowsContinuations()
    {
        $value = "foo,\r\n bar";
        $message1 = $this->message->withHeader('X-Foo', $value);

        $this->assertEquals($value, $message1->getHeaderLine('X-Foo'));
    }

    public function testWithHeaderAllowsIntegers()
    {
        $value = 123;
        $message1 = $this->message->withHeader('X-Foo', [$value]);

        $this->assertSame(['X-Foo' => [(string) $value]], $message1->getHeaders());
    }

    public function testWithHeaderAllowsFloats()
    {
        $value = 1.2;
        $message1 = $this->message->withHeader('X-Foo', [$value]);

        $this->assertSame(['X-Foo' => [(string) $value]], $message1->getHeaders());
    }

    public function invalidHeadersDataProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [['foo' => ['bar']]],
            'object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidHeadersDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithHeaderThrowsExceptionWhenHeaderValueTypeIsInvalid($value)
    {
        $this->message->withHeader('X-Foo', $value);
    }

    /**
     * @dataProvider invalidHeadersDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithHeaderThrowsExceptionWhenHeaderNameTypeIsInvalid($name)
    {
        $this->message->withHeader($name, 'foo');
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
    public function testWithHeaderThrowsExceptionOnCRLFInjection($name, $value)
    {
        $this->message->withHeader($name, $value);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithHeaderThrowsExceptionOnCRLFInjectionWithHexCharacter()
    {
        $this->message->withHeader('injected', "foo \0d bar");
    }

    public function testWithAddedHeaderReturnsClonedInstanceWithAddedHeaderValues()
    {
        $message1 = $this->message->withHeader('X-Foo', 'foo');
        $message2 = $message1->withAddedHeader('X-Foo', 'bar');

        $this->assertNotSame($message1, $message2);
        $this->assertEquals('foo, bar', $message2->getHeaderLine('X-Foo'));
    }

    public function testWithAddedHeaderAppendsToExistingHeader()
    {
        $message1 = $this->message->withHeader('X-Foo', 'foo');
        $message2 = $message1->withAddedHeader('X-Foo', 'bar');

        $this->assertEquals('foo, bar', $message2->getHeaderLine('X-Foo'));
    }

    public function testWithAddedHeaderAddsNonExistingHeaders()
    {
        $message1 = $this->message->withAddedHeader('X-Foo', 'foo');

        $this->assertTrue($message1->hasHeader('X-Foo'));
    }

    public function testWithAddedHeaderAllowsContinuations()
    {
        $value = "foo,\r\n bar";
        $message1 = $this->message->withAddedHeader('X-Foo', $value);

        $this->assertEquals($value, $message1->getHeaderLine('X-Foo'));
    }

    public function testWithAddedHeaderAllowsIntegers()
    {
        $value = 123;
        $message1 = $this->message
            ->withHeader('X-Foo', 'foo')
            ->withAddedHeader('X-Foo', [$value]);

        $this->assertSame(['X-Foo' => ['foo', (string) $value]], $message1->getHeaders());
    }

    public function testWithAddedHeaderAllowsFloats()
    {
        $value = 1.2;
        $message1 = $this->message
            ->withHeader('X-Foo', 'foo')
            ->withAddedHeader('X-Foo', [$value]);

        $this->assertSame(['X-Foo' => ['foo', (string) $value]], $message1->getHeaders());
    }

    /**
     * @dataProvider invalidHeadersDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithAddedHeaderThrowsExceptionWhenHeaderValueTypeIsInvalid($value)
    {
        $this->message->withHeader('X-Foo', 'foo')->withAddedHeader('X-Foo', $value);
    }

    /**
     * @dataProvider invalidHeadersDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithAddedHeaderThrowsExceptionWhenHeaderNameTypeIsInvalid($name)
    {
        $this->message->withHeader('X-Foo', 'foo')->withAddedHeader($name, 'foo');
    }

    /**
     * @dataProvider headersCRLFInjectionDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithAddedHeaderThrowsExceptionOnCRLFInjection($name, $value)
    {
        $this->message->withHeader('X-Foo', 'foo')->withAddedHeader($name, $value);
    }

    public function testWithoutHeaderRemovesHeader()
    {
        $message1 = $this->message->withHeader('X-Foo', 'foo');
        $message2 = $message1->withoutHeader('X-foo');

        $this->assertTrue($message1->hasHeader('X-foo'));
        $this->assertNotSame($message1, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
    }

    public function testWithoutHeaderIsCaseInsensitive()
    {
        $message1 = $this->message
            ->withHeader('X-Foo', 'foo')
            ->withAddedHeader('X-foo', 'bar')
            ->withAddedHeader('X-FOO', 'baz');
        $message2 = $message1->withoutHeader('x-foo');

        $this->assertFalse($message2->hasHeader('X-Foo'));
        $this->assertCount(0, $message2->getHeaders());
    }

    public function testWithoutHeaderDoesNothingIfHeaderDoesNotExists()
    {
        $message = $this->message->withoutHeader('X-Nope');

        $this->assertFalse($message->hasHeader('X-Nope'));
    }

    /**
     * ------------------------------------------
     *  BODY
     * ------------------------------------------
     */

    public function testWithBodyReturnsClonedInstanceWithStream()
    {
        $stream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $message = $this->message->withBody($stream);

        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }
}
