<?php

declare(strict_types=1);

namespace Avoxx\Psr7;

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
use Avoxx\Psr7\Exceptions\InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{

    /**
     * The URI scheme without "://" suffix.
     *
     * @var string
     */
    private $scheme;

    /**
     * The URI user info.
     *
     * @var string
     */
    private $userInfo;

    /**
     * The URI host.
     *
     * @var string
     */
    private $host;

    /**The URI port.
     *
     * @var int|null
     */
    private $port;

    /**
     * The URI path.
     *
     * @var string
     */
    private $path;

    /**
     * The URI query.
     *
     * @var string
     */
    private $query;

    /** The URI fragment.
     *
     * @var string
     */
    private $fragment;

    /**
     * Create a new container instance.
     *
     * @param string $uri
     *
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function __construct(string $uri = '')
    {
        $this->getUriParts($uri);
    }

    /**
     * Disable magic setter to ensure immutability.
     *
     * @param mixed $name
     * @param mixed $value
     *
     * @throws \InvalidArgumentException When a property was set to an instance from outside.
     */
    public function __set($name, $value)
    {
        throw new InvalidArgumentException('Cannot add new property "$' . $name . '" to instance of ' . __CLASS__);
    }

    /**
     * Parse the full URI string.
     *
     * @param string $uri
     *
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    private function getUriParts(string $uri)
    {
        $parts = parse_url($uri);

        $this->scheme = isset($parts['scheme']) ? $this->sanitizeScheme($parts['scheme']) : '';

        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $this->userInfo = $user . $pass;

        $this->host = $parts['host'] ?? '';

        $this->port = null;

        if (isset($parts['port'])) {
            $this->port = $parts['port'];
        } elseif ($this->scheme === 'http') {
            $this->port = 80;
        } elseif ($this->scheme === 'https') {
            $this->port = 433;
        }

        $this->path = $parts['path'] ?? '';
        $this->query = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @return string The URI scheme.
     */
    public function getScheme() : string
    {
        return $this->scheme;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     *
     * @return static A new instance with the specified scheme.
     *
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme) : Uri
    {
        $scheme = $this->sanitizeScheme($scheme);

        return $this->cloneWithProperty('scheme', $scheme);
    }

    /**
     * Sanitize the URI scheme.
     *
     * @param $scheme
     *
     * @return string
     *
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    private function sanitizeScheme($scheme) : string
    {
        if (! is_string($scheme)) {
            throw new InvalidArgumentException(
                'The URI scheme must be a string, received' .
                (is_object($scheme) ? get_class($scheme) : gettype($scheme))
            );
        }

        $scheme = strtolower($scheme);
        $scheme = rtrim($scheme, '://');

        if (! in_array($scheme, ['', 'http', 'https'], true)) {
            throw new InvalidArgumentException('The URI scheme must be \'\', http or https');
        }

        return $scheme;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo() : string
    {
        return $this->userInfo;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string      $user     The user name to use for authority.
     * @param null|string $password The password associated with $user.
     *
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null) : Uri
    {
        $userInfo = $user;

        if ($password !== null) {
            $userInfo .= ':' . $password;
        }

        return $this->cloneWithProperty('userInfo', $userInfo);
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @return string The URI host.
     */
    public function getHost() : string
    {
        return $this->host;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     *
     * @return static A new instance with the specified host.
     *
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host) : Uri
    {
        if (! is_string($host)) {
            throw new InvalidArgumentException(
                'The URI host must be a string, received ' .
                (is_object($host) ? get_class($host) : gettype($host))
            );
        }

        return $this->cloneWithProperty('host', $host);
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->port && ! $this->hasStandardPort() ? $this->port : null;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value removes the port information.
     *
     * @return static A new instance with the specified port.
     *
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port) : Uri
    {
        if ($port === null || $port === '') {
            return $this->cloneWithProperty('port', null);
        }

        $port = $this->sanitizePort($port);

        return $this->cloneWithProperty('port', $port);
    }

    /**
     * Determine if the URI use a standard port.
     *
     * @return bool
     */
    private function hasStandardPort() : bool
    {
        return ($this->scheme === 'https' && $this->port === 433) || ($this->scheme === 'http' && $this->port === 80);
    }

    /**
     * Sanitize the URI port.
     *
     * @param mixed $port
     *
     * @return int
     *
     * @throws \InvalidArgumentException for invalid ports.
     */
    private function sanitizePort($port) : int
    {
        if (is_bool($port) || is_array($port) || is_object($port) || (string) (int) $port !== (string) $port) {
            throw new InvalidArgumentException(
                'The URI port must be null or an integer, received ' .
                (is_object($port) ? get_class($port) : gettype($port))
            );
        }

        $port = (int) $port;

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('The URI port must be a valid TCP/UDP port');
        }

        return $port;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority() : string
    {
        $user = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($user ? $user . '@' : '') . $host . ($port !== null ? ':' . $port : '');
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @return string The URI path.
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     *
     * @return static A new instance with the specified path.
     *
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path) : Uri
    {
        $path = $this->sanitizePath($path);

        return $this->cloneWithProperty('path', $path);
    }

    /**
     * Sanitize the URI path.
     *
     * @param mixed $path
     *
     * @return string
     *
     * @throws \InvalidArgumentException for invalid paths.
     */
    private function sanitizePath($path) : string
    {
        if (! is_string($path)) {
            throw new InvalidArgumentException(
                'The URI path must be a string, received ' .
                (is_object($path) ? get_class($path) : gettype($path))
            );
        }

        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException('The URI path must not contain a query string');
        }

        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException('The URI path must not contain a URI fragment');
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     *
     * @return string The URI query string.
     */
    public function getQuery() : string
    {
        return $this->query;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     *
     * @return static A new instance with the specified query string.
     *
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query) : Uri
    {
        $query = $this->sanitizeQuery($query);

        return $this->cloneWithProperty('query', $query);
    }

    /**
     * Sanitize the URI query string.
     *
     * @param $query
     *
     * @return string
     *
     * @throws \InvalidArgumentException for invalid query strings.
     */
    private function sanitizeQuery($query) : string
    {
        if (! is_string($query)) {
            throw new InvalidArgumentException(
                'The URI query must be a string, received ' .
                (is_object($query) ? get_class($query) : gettype($query))
            );
        }

        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException('The URI query must not contain a URI fragment');
        }

        $query = ltrim($query, '?');
        $parts = explode('&', $query);

        foreach ($parts as $index => $part) {
            $data = explode('=', $part, 2);

            if (count($data) === 1) {
                $data[] = null;
            }

            list($key, $value) = $data;

            if ($value === null) {
                $parts[$index] = $this->sanitizeQueryOrFragment($key);
                continue;
            }

            $parts[$index] = $this->sanitizeQueryOrFragment($key) . '=' . $this->sanitizeQueryOrFragment($value);
        }

        return implode('&', $parts);
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @return string The URI fragment.
     */
    public function getFragment() : string
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     *
     * @return static A new instance with the specified fragment.
     *
     * @throws \InvalidArgumentException for invalid fragment strings.
     */
    public function withFragment($fragment)
    {
        $fragment = $this->sanitizeFragment($fragment);

        return $this->cloneWithProperty('fragment', $fragment);
    }

    /**
     * Sanitize the URI fragment.
     *
     * @param mixed $fragment
     *
     * @return string
     *
     * @throws \InvalidArgumentException for invalid fragment strings.
     */
    private function sanitizeFragment($fragment) : string
    {
        if (! is_string($fragment)) {
            throw new InvalidArgumentException(
                'The URI query must be a string, received ' .
                (is_object($fragment) ? get_class($fragment) : gettype($fragment))
            );
        }

        $fragment = ltrim($fragment, '#');
        $fragment = $this->sanitizeQueryOrFragment($fragment);

        return $fragment;
    }

    /**
     * Sanitize the URI query string or fragment.
     *
     * @param string $value
     *
     * @return string
     */
    private function sanitizeQueryOrFragment(string $value) : string
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $value
        );
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     *
     * @return string
     */
    public function __toString()
    {
        $uri = '';
        $uri .= $this->scheme ? $this->scheme . '://' : '';
        $uri .= $this->getAuthority() ?: '';
        $uri .= '/' . ltrim($this->getPath(), '/');
        $uri .= $this->getQuery() ? '?' . $this->getQuery() : '';
        $uri .= $this->getFragment() ? '#' . $this->getFragment() : '';

        return $uri;
    }

    /**
     * Clone an instance with given property.
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return static
     */
    private function cloneWithProperty(string $property, $value)
    {
        $clone = clone $this;
        $clone->$property = $value;

        return $clone;
    }
}
