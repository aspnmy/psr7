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
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{

    /**
     * The client-provided full path to the file.
     *
     * @var string
     */
    private $file;

    /**
     * The stream instance.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    private $stream;

    /**
     * The size of the file in bytes.
     *
     * @var int
     */
    private $size;

    /**
     * The PHP UPLOAD_ERROR_* constant provided by the uploader.
     *
     * @var int
     */
    private $error;

    /**
     * The client-provided file name.
     *
     * @var null|string
     */
    private $clientFilename;

    /**
     * The client-provided media type of the file.
     *
     * @var null|string
     */
    private $clientMediaType;

    /**
     * Indicates if the uploaded file has already been moved.
     *
     * @var bool
     */
    private $moved = false;

    /**
     * Indicates if the upload is from a SAPI environment.
     *
     * @var bool
     */
    private $sapi;

    /**
     * Create a new uploaded file instance.
     *
     * @param string|resource|\Psr\Http\Message\StreamInterface $file
     * @param int                                               $size
     * @param int                                               $error
     * @param string|null                                       $clientFilename
     * @param string|null                                       $clientMediaType
     * @param bool                                              $sapi
     *
     * @throws \InvalidArgumentException if the file isn't a string, resource or Stream instance.
     */
    public function __construct(
        $file,
        int $size = 0,
        int $error = UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null,
        bool $sapi = false
    )
    {
        $this->setFileAndStream($file);
        $this->size = $size;

        if ($error < 0 || $error > 8) {
            throw new InvalidArgumentException('The error status must be an UPLOAD_ERR_* constant');
        }

        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        $this->sapi = $sapi;
    }

    /**
     * Set the file name and stream instance.
     *
     * @param string|resource|\Psr\Http\Message\StreamInterface $file
     *
     * @throws \InvalidArgumentException if the file isn't a string, resource or Stream instance.
     */
    private function setFileAndStream($file)
    {
        if (is_string($file)) {
            $this->file = $file;
            $this->stream = new Stream($file, 'wb+');
        } elseif (is_resource($file)) {
            $this->stream = new Stream($file);
            $this->file = $this->stream->getMetadata('uri');
        } elseif ($file instanceof StreamInterface) {
            $this->file = $file->getMetadata('uri');
            $this->stream = $file;
        } else {
            throw new InvalidArgumentException(
                'The file must be a string, resource or instance of Psr\Http\Message\StreamInterface'
            );
        }
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return \Psr\Http\Message\StreamInterface Stream representation of the uploaded file.
     *
     * @throws \RuntimeException in cases when no stream is available or can be created.
     */
    public function getStream() : StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream as it was moved');
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     *
     * @param string $targetPath Path to which to move the uploaded file.
     *
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on the second or subsequent call to the
     *                           method.
     */
    public function moveTo($targetPath)
    {
        if (empty($targetPath) || ! is_string($targetPath)) {
            throw new InvalidArgumentException('The target path must be a non-empty string');
        }

        $targetIsStream = strpos($targetPath, '://') > 0;

        if (! $targetIsStream && ! is_writable(dirname($targetPath))) {
            throw new InvalidArgumentException('The upload target path ' . $targetPath . ' is not writable');
        }

        if ($this->moved) {
            throw new RuntimeException('The uploaded file was already moved');
        }

        if ($targetIsStream) {
            if (! copy($this->file, $targetPath)) {
                throw new RuntimeException('The file ' . $this->file . ' could not be moved to ' . $targetPath);
            }

            if (! unlink($this->file)) {
                throw new RuntimeException('The file ' . $this->file . ' could not be removed');
            }
        } elseif ($this->sapi) {
            if (! move_uploaded_file($this->file, $targetPath)) {
                throw new RuntimeException('The file ' . $this->file . '"could not be moved to ' . $targetPath);
            }
        } elseif (! rename($this->file, $targetPath)) {
            throw new RuntimeException('The file ' . $this->file . ' could not be moved to ' . $targetPath);
        }

        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     *
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     *
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError() : int
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none was provided.
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}
