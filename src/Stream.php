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
use Avoxx\Psr7\Exceptions\RuntimeException;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{

    /**
     * The HTTP stream resource.
     *
     * @var resource
     */
    private $stream;

    /**
     * Create a new stream instance.
     *
     * @param string|resource $stream
     * @param string          $mode
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($stream = 'php://memory', string $mode = 'r')
    {
        if (is_string($stream)) {
            $stream = fopen($stream, $mode);
        }

        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException(
                'The stream must be a string stream identifier or stream resource, received ' .
                (is_object($stream) ? get_class($stream) : gettype($stream))
            );
        }

        $this->stream = $stream;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     *
     * @return string
     *
     * @throws \RuntimeException on failure.
     */
    public function __toString()
    {
        try {
            $this->rewind();

            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     */
    public function close()
    {
        $this->stream && fclose($this->stream);
        $this->detach();
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        if ($this->stream === null) {
            return null;
        }

        $resource = $this->stream;
        $this->stream = null;

        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if ($this->stream === null) {
            return null;
        }

        $stats = fstat($this->stream);

        return $stats['size'] ?? null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     *
     * @throws \RuntimeException on error.
     */
    public function tell() : int
    {
        if (! $this->stream || is_int($position = ftell($this->stream)) === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof() : bool
    {
        return ! $this->stream || feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable() : bool
    {
        return $this->stream && $this->getMetadata('seekable') === true;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical to the built-in
     *                    PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *                    offset bytes SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (! $this->isSeekable() || fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable tho seek stream position');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see  seek()
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @throws \RuntimeException on failure.
     *
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable() : bool
    {
        return $this->stream && is_writable($this->getMetadata('uri'));
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     *
     * @return int Returns the number of bytes written to the stream.
     *
     * @throws \RuntimeException on failure.
     */
    public function write($string) : int
    {
        if (! $this->isWritable() || ($write = fwrite($this->stream, $string)) === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $write;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable() : bool
    {
        if ($this->stream) {
            $mode = $this->getMetadata('mode');

            return strpos($mode, 'r') !== false || strpos($mode, '+') !== false;
        }

        return false;
    }


    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if underlying stream
     *                    call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an empty string if no bytes are available.
     *
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length) : string
    {
        if (! $this->isReadable() || ($read = fread($this->stream, $length)) === false) {
            throw new RuntimeException('Unable to read stream');
        }

        return $read;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     *
     * @throws \RuntimeException if unable to read or an error occurs while reading.
     */
    public function getContents() : string
    {
        if (! $this->isReadable() || ($contents = stream_get_contents($this->stream)) === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
