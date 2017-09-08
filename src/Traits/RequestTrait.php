<?php

declare(strict_types=1);

namespace Avoxx\Psr7\Traits;

/*
 * AVOXX- PHP Framework Components
 *
 * @author    Merlin Christen <merloxx@avoxx.org>
 * @copyright Copyright (c) 2016 - 2017 Merlin Christen
 * @license   The MIT License (MIT)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
use Avoxx\Psr7\Uri;
use Avoxx\Psr7\Exceptions\InvalidArgumentException;
use Psr\Http\Message\UriInterface;

trait RequestTrait
{

    use MessageTrait;

    /**
     * The HTTP request-target
     *
     * @var string
     */
    private $requestTarget;

    /**
     * THe HTTP request method.
     *
     * @var string
     */
    private $method = '';

    /**
     * Available valid HTTP methods.
     *
     * @var array
     */
    private static $validMethods = [
        'HEAD',
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'PURGE',
        'OPTIONS',
        'TRACE',
        'CONNECT',
    ];

    /**
     * The uri instance.
     *
     * @var \Psr\Http\Message\UriInterface
     */
    private $uri;

    /**
     * Initialize a request instance state.
     *
     * @param string|null                                       $method
     * @param string|null|\Psr\Http\Message\UriInterface        $uri
     * @param string|resource|\Psr\Http\Message\StreamInterface $body
     * @param array                                             $headers
     *
     * @throws \InvalidArgumentException for any invalid value.
     */
    private function initialize(string $method = null, $uri = null, $body = 'php://memory', array $headers = [])
    {
        $this->method = $this->sanitizeMethod($method);
        $this->setUriInstance($uri);
        $this->setStreamInstance($body);
        $this->setHeaders($headers);
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget() : string
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }

        $path = $this->uri->getPath();

        if (empty($path)) {
            return '/';
        }

        if ($this->uri->getQuery()) {
            $path .= '?' . $this->uri->getQuery();
        }

        return $path;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     *
     * @param mixed $requestTarget
     *
     * @return static
     *
     * @throws \InvalidArgumentException for invalid request targets.
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided. Must be a string without whitespace');
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     *
     * @return static
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $method = $this->sanitizeMethod($method);

        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Validate the HTTP request method.
     *
     * @param mixed $method
     *
     * @return string
     *
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    private function sanitizeMethod($method) : string
    {
        if ($method === null) {
            return '';
        }

        if (! is_string($method)) {
            throw new InvalidArgumentException(
                'Invalid HTTP method. Must be a string, received ' .
                (is_object($method) ? get_class($method) : gettype($method))
            );
        }

        $method = strtoupper($method);

        if (! in_array($method, self::$validMethods, true)) {
            throw new InvalidArgumentException(
                'Invalid HTTP method. Must be ' .
                implode(', ', self::$validMethods)
            );

        }

        return $method;
    }

    /**
     * Set a new uri instance.
     *
     * @param string|null|\Psr\Http\Message\UriInterface $uri
     *
     * @throws \InvalidArgumentException When the provided URI is invalid.
     */
    private function setUriInstance($uri)
    {
        if ($uri instanceof UriInterface) {
            $this->uri = $uri;
        } elseif (is_string($uri)) {
            $this->uri = new Uri($uri);
        } elseif ($uri === null) {
            $this->uri = new Uri;
        } else {
            throw new InvalidArgumentException(
                'Invalid URI provided. Must be null, a string, ' .
                'or a Psr\Http\Message\UriInterface instance'
            );
        }
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @return UriInterface Returns a UriInterface instance representing the URI of the request.
     */
    public function getUri() : UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri          New request URI to use.
     * @param bool         $preserveHost Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $clone->uri = $uri;

        $host = $uri->getHost();

        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        // @todo: I'm not very happy with this solution right now :(
        if ($preserveHost) {
            if ($host !== '' && (! $this->hasHeader('Host') || $this->getHeaderLine('Host') === '')) {
                $clone->headerNames['host'] = 'Host';
                $clone->headers['Host'] = [$host];
            }
        } elseif ($host !== '') {
            $clone->headerNames['host'] = 'Host';
            $clone->headers['Host'] = [$host];
        }

        return $clone;
    }
}
