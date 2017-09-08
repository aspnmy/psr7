<?php

declare(strict_types=1);

namespace AvoxxTests\Psr7;

use Avoxx\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{

    /**
     * @var \Avoxx\Psr7\Uri
     */
    protected $uri;

    public function setUp()
    {
        $this->uri = new Uri('https://user:pass@example.com:8080/path?query=value#fragment');
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
        $this->uri->foo = 'bar';
    }

    /**
     * ------------------------------------------
     *  CONSTRUCTOR
     * ------------------------------------------
     */

    public function tesConstructorAndGetMethods()
    {
        $this->assertEquals('https', $this->uri->getScheme());
        $this->assertEquals('user:pass', $this->uri->getUserInfo());
        $this->assertEquals('example.com', $this->uri->getHost());
        $this->assertEquals(8080, $this->uri->getPort());
        $this->assertEquals('user:pass@example.com:8080', $this->uri->getAuthority());
        $this->assertEquals('/path', $this->uri->getPath());
        $this->assertEquals('query=value', $this->uri->getQuery());
        $this->assertEquals('fragment', $this->uri->getFragment());
    }

    public function invalidSchemeDataProvider()
    {
        return [
            'ftp' => ['ftp'],
            'git' => ['git'],
            'mailto' => ['mailto'],
            'ssh' => ['ssh'],
            'telnet' => ['telnet'],
        ];
    }

    /**
     * @dataProvider invalidSchemeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidSchemeThrowsException($scheme)
    {
        new Uri($scheme . '://user:pass@example.com:8080/path?query=value#fragment');
    }

    /**
     * ------------------------------------------
     *  SCHEME
     * ------------------------------------------
     */

    public function testGetSchemeNoSchemePresentReturnsEmptyScheme()
    {
        $this->assertEquals('', (new Uri())->getScheme());
    }

    public function testWithSchemeReturnsNewInstance()
    {
        $uri = $this->uri->withScheme('http');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('http://user:pass@example.com:8080/path?query=value#fragment', (string) $uri);
    }

    public function testWithSchemeParameterCaseInsensitively()
    {
        $uri = $this->uri->withScheme('HTTPS:');

        $this->assertEquals('https', $uri->getScheme());
    }

    public function testWithSchemeTrimsDelimiter()
    {
        $uri = $this->uri->withScheme('https://');

        $this->assertEquals('https', $uri->getScheme());
    }

    /**
     * @dataProvider invalidSchemeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithSchemeInvalidSchemeThrowsException($scheme)
    {
        $this->uri->withScheme($scheme);
    }

    public function invalidTypeDataProvider()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [['/foo']],
            'object' => [(object) ['/foo']],
        ];
    }

    /**
     * @dataProvider invalidTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithSchemeInvalidTypeThrowsException($scheme)
    {
        $this->uri->withScheme($scheme);
    }

    /**
     * ------------------------------------------
     *  USER INFO
     * ------------------------------------------
     */

    public function testGetUserInfoNoUserInfoPresentReturnsEmptyString()
    {
        $this->assertEquals('', (new Uri())->getUserInfo());
    }

    public function testGetUserReturnsProvidedUser()
    {
        $uri = new Uri('https://user@example.com');

        $this->assertEquals('user', $uri->getUserInfo());
    }

    public function testGetUserAndPasswordReturnsProvidedUserAndPassword()
    {
        $this->assertEquals('user:pass', $this->uri->getUserInfo());
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUser()
    {
        $uri = $this->uri->withUserInfo('merlin');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('merlin', $uri->getUserInfo());
        $this->assertEquals('https://merlin@example.com:8080/path?query=value#fragment', (string) $uri);
    }

    public function testWithUserInfoAndPasswordReturnsNewInstanceWithProvidedUserAndPassword()
    {
        $uri = $this->uri->withUserInfo('merlin', 'newPassword');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('merlin:newPassword', $uri->getUserInfo());
        $this->assertEquals('https://merlin:newPassword@example.com:8080/path?query=value#fragment', (string) $uri);
    }

    /**
     * ------------------------------------------
     *  HOST
     * ------------------------------------------
     */

    public function testGetHostNoHostPresentReturnsEmptyString()
    {
        $this->assertEquals('', (new Uri())->getHost());
    }

    public function testWithHostReturnsNewInstanceWithProvidedHost()
    {
        $uri = $this->uri->withHost('another.url.com');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('another.url.com', $uri->getHost());
        $this->assertEquals('https://user:pass@another.url.com:8080/path?query=value#fragment', (string) $uri);
    }

    /**
     * @dataProvider invalidTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithHostInvalidTypeThrowsException($host)
    {
        $this->uri->withHost($host);
    }

    /**
     * ------------------------------------------
     *  PORT
     * ------------------------------------------
     */

    public function testGetPortNoPortPresentReturnsNull()
    {
        $this->assertNull((new Uri())->getPort());
    }

    public function testGetPortNoPortPresentButSchemeIsPresentReturnsNullForDefaultPort()
    {
        $http = new Uri('http://example.com');
        $https = new Uri('https://example.com');

        $this->assertNull($http->getPort());
        $this->assertNull($https->getPort());
    }

    public function testGetPortReturnsPortWhenPortIsPresent()
    {
        $this->assertEquals(8080, $this->uri->getPort());
    }

    public function validPortDataProvider()
    {
        return [
            'int' => [8080],
            'string' => ['8080'],
        ];
    }

    /**
     * @dataProvider validPortDataProvider
     */
    public function testWithPortReturnsNewInstanceWithProvidedPort($port)
    {
        $uri = $this->uri->withPort($port);

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals($port, $uri->getPort());
        $this->assertTrue(is_int($uri->getPort()));
        $this->assertEquals('https://user:pass@example.com:' . $port . '/path?query=value#fragment', (string) $uri);
    }

    public function testWithPortNullReturnsNewInstanceWithPortNull()
    {
        $uri = $this->uri->withPort(null);

        $this->assertNotSame($uri, $this->uri);
        $this->assertNull($uri->getPort());
        $this->assertEquals('https://user:pass@example.com/path?query=value#fragment', (string) $uri);
    }

    public function testWithPortEmptyStringReturnsNewInstanceWithPortNull()
    {
        $uri = $this->uri->withPort('');

        $this->assertNotSame($uri, $this->uri);
        $this->assertNull($uri->getPort());
        $this->assertEquals('https://user:pass@example.com/path?query=value#fragment', (string) $uri);
    }

    public function invalidPortDataProvider()
    {
        return [
            'true' => [true],
            'false' => [false],
            'string' => ['string'],
            'array' => [[8080]],
            'object' => [(object) [8080]],
            'zero' => [0],
            'too-small' => [-1],
            'too-big' => [65536],
        ];
    }

    /**
     * @dataProvider invalidPortDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithPortWithInvalidPortThrowsException($port)
    {
        $this->uri->withPort($port);
    }

    /**
     * ------------------------------------------
     *  AUTHORITY
     * ------------------------------------------
     */

    public function testGetAuthorityNoAuthorityPresentReturnsEmptyString()
    {
        $this->assertEquals('', (new Uri())->getAuthority());
    }

    public function testGetAuthorityStandardPortForSchemeReturnAuthorityWithoutPort()
    {
        $uri = new Uri('https://user:pass@example.com/path?query=value#fragment');

        $this->assertEquals('user:pass@example.com', $uri->getAuthority());
    }

    /**
     * ------------------------------------------
     *  PATH
     * ------------------------------------------
     */

    public function testWithPathReturnsNewInstanceWithProvidedPath()
    {
        $uri = $this->uri->withPath('/foo');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('/foo', $uri->getPath());
        $this->assertEquals('https://user:pass@example.com:8080/foo?query=value#fragment', (string) $uri);
    }

    public function testGetPathNoPathPresentReturnsEmptyString()
    {
        $this->assertEquals('', (new Uri())->getPath());
    }

    public function validPathDataProvider()
    {
        return [
            'empty' => [''],
            'absolute' => ['/'],
            'no-starting-slash' => ['foo'],
            'with-starting-slash' => ['/foo'],
            'with-trailing-slash' => ['foo/'],
        ];
    }

    /**
     * @dataProvider validPathDataProvider
     */
    public function testWithPathValidPathsReturnsNewInstanceWithProvidedPath($path)
    {
        $uri = $this->uri->withPath($path);

        $this->assertEquals($path, $uri->getPath());
    }

    public function invalidPathDataProvider()
    {
        return [
                'query' => ['?query=value'],
                'fragment' => ['#fragment'],
            ] + $this->invalidTypeDataProvider();
    }

    /**
     * @dataProvider invalidPathDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithPathInvalidValidTypeThrowsException($path)
    {
        $this->uri->withPath($path);
    }

    public function testWithPathIsProperlyEncoding()
    {
        $uri = $this->uri->withPath('/foo^bar');

        $this->assertEquals('/foo%5Ebar', $uri->getPath());
    }

    public function testWithPathNoDoubleEncoding()
    {
        $uri = $this->uri->withPath('/foo%5Ebar');

        $this->assertEquals('/foo%5Ebar', $uri->getPath());
    }

    /**
     * ------------------------------------------
     *  QUERY
     * ------------------------------------------
     */

    public function testGetQueryNoQueryPresentReturnsEmptyString()
    {
        $this->assertEquals('', (new Uri())->getQuery());
    }

    public function testWithQueryReturnsNewInstanceWithProvidedQuery()
    {
        $uri = $this->uri->withQuery('foo=bar');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('foo=bar', $uri->getQuery());
        $this->assertEquals('https://user:pass@example.com:8080/path?foo=bar#fragment', (string) $uri);
    }

    public function testWithQueryRemovesQueryPrefix()
    {
        $uri = $this->uri->withQuery('?foo=bar');

        $this->assertEquals('foo=bar', $uri->getQuery());
    }

    public function invalidQueryDataProvider()
    {
        return [
                'fragment' => ['foo=bar#fragment'],
            ] + $this->invalidTypeDataProvider();
    }

    /**
     * @dataProvider invalidQueryDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithQueryInvalidTypeThrowsException($query)
    {
        $this->uri->withQuery($query);
    }

    public function queryEncodingDataProvider()
    {
        return [
            'key-only' => ['k^ey', 'k%5Eey'],
            'key-value' => ['k^ey=valu`', 'k%5Eey=valu%60'],
            'array-key-only' => ['key[]', 'key%5B%5D'],
            'array-key-value' => ['key[]=valu`', 'key%5B%5D=valu%60'],
            'complex' => ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
        ];
    }

    /**
     * @dataProvider queryEncodingDataProvider
     */
    public function testGetQueryIsProperlyEncoded($query, $expected)
    {
        $uri = $this->uri->withQuery($query);

        $this->assertEquals($expected, $uri->getQuery());
    }

    /**
     * @dataProvider queryEncodingDataProvider
     */
    public function testGetQueryNoDoubleEncoding($query, $expected)
    {
        $uri = $this->uri->withQuery($expected);

        $this->assertEquals($expected, $uri->getQuery());
    }

    /**
     * ------------------------------------------
     *  FRAGMENT
     * ------------------------------------------
     */

    public function testGetFragmentNoFragmentPresentReturnsEmptyString()
    {
        $this->assertEquals('', (new Uri())->getFragment());
    }

    public function testWithFragmentReturnsNewInstanceWithProvidedFragment()
    {
        $uri = $this->uri->withFragment('foo');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('foo', $uri->getFragment());
        $this->assertEquals('https://user:pass@example.com:8080/path?query=value#foo', (string) $uri);
    }

    public function testWithFragmentRemovesFragmentPrefix()
    {
        $uri = $this->uri->withFragment('#foo');

        $this->assertEquals('foo', $uri->getFragment());
    }

    /**
     * @dataProvider invalidTypeDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testWithFragmentInvalidTypeThrowsException($fragment)
    {
        $this->uri->withFragment($fragment);
    }

    public function testGetFragmentIsProperlyEncoded()
    {
        $uri = $this->uri->withFragment('p^th?key^=`foo#b@r');

        $this->assertEquals('p%5Eth?key%5E=%60foo%23b@r', $uri->getFragment());
    }

    public function testGetFragmentNoDoubleEncoding()
    {
        $uri = $this->uri->withFragment('p%5Eth?key%5E=%60foo%23b@r');

        $this->assertEquals('p%5Eth?key%5E=%60foo%23b@r', $uri->getFragment());
    }

    /**
     * ------------------------------------------
     *  TO STRING
     * ------------------------------------------
     */

    public function testToString()
    {
        $this->assertEquals('https://user:pass@example.com:8080/path?query=value#fragment', (string) $this->uri);
    }
}
