<?php

namespace Streamer;

use Streamer\Exception\InvalidArgumentException;
use Streamer\Exception\LogicException;
use Streamer\Exception\RuntimeException;

class Stream
{
    protected $bufferSize = 4096;
    protected $stream;
    protected $isOpen;

    protected static $readableModes = array(
        'r', 'r+', 'w+', 'a+', 'x+', 'c+', 'rb', 'r+b', 'w+b', 'a+b', 'x+b', 'c+b', 'rt', 'r+t', 'w+t', 'a+t', 'x+t', 'c+t'
    );
    protected static $writableModes = array(
        'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'r+b', 'wb', 'w+b', 'ab', 'a+b', 'xb', 'x+b', 'cb', 'c+b', 'r+t', 'wt', 'w+t', 'at', 'a+t', 'xt', 'x+t', 'ct', 'c+t'
    );

    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('A Stream object requires a stream resource as constructor argument');
        }
        $this->stream = $stream;
        $this->isOpen = true;
    }

    public function getResource()
    {
        return $this->stream;
    }

    public function getMetadata()
    {
        return stream_get_meta_data($this->stream);
    }

    public function getMetadataForKey($key)
    {
        $metadata = $this->getMetadata();
        if (isset($metadata[$key])) {
            return $metadata[$key];
        }
    }

    public function getUri()
    {
        return $this->getMetadataForKey('uri');
    }

    public function getStreamType()
    {
        return $this->getMetadataForKey('stream_type');
    }

    public function getWrapperType()
    {
        return $this->getMetadataForKey('wrapper_type');
    }

    public function getWrapperData()
    {
        return $this->getMetadataForKey('wrapper_data');
    }

    /**
     * @return Boolean
     */
    public function isLocal()
    {
        return stream_is_local($this->stream);
    }

    /**
     * @return Boolean
     */
    public function isReadable()
    {
        return in_array($this->getMetadataForKey('mode'), self::$readableModes);
    }

    /**
     * @return Boolean
     */
    public function isWritable()
    {
        return in_array($this->getMetadataForKey('mode'), self::$writableModes);
    }

    /**
     * @return Boolean
     */
    public function isSeekable()
    {
        return $this->getMetadataForKey('seekable');
    }

    /**
     * @return Boolean
     */
    public function isOpen()
    {
        return $this->isOpen;
    }

    /**
     * @param int $bufferSize in Bytes
     */
    public function setBufferSize($bufferSize)
    {
        $this->bufferSize = $bufferSize;
    }
    
    /**
     * @return int BufferSize in Bytes
     */
    public function getBufferSize()
    {
        return $this->bufferSize;
    }
    
    /**
     * Read data from the stream.
     * Binary-safe.
     *
     * @param int $length Maximum number of bytes to read. Defaults to self::$bufferSize.
     *
     * @return string The data read from the stream
     */
    public function read($length = null)
    {
        $this->ensureReadable();
        if (null == $length) {
            $length = $this->bufferSize;
        }
        $ret = fread($this->stream, $length);
        if (false === $ret) {
            throw new RuntimeException('Cannot read stream');
        }
        
        return $ret;
    }

    /**
     * Read one line from the stream.
     *
     * Binary-safe. Reading ends when length bytes have been read, when the 
     * string specified by ending is found (which is not included in the return
     *  value), or on EOF (whichever comes first).
     *
     * @param int $length Maximum number of bytes to read. Defaults to self::$bufferSize.
     * @param string $ending Line ending to stop at. Defaults to "\n".
     *
     * @return string The data read from the stream
     */
    public function getLine($length = null, $ending = "\n")
    {
        $this->ensureReadable();
        if (null == $length) {
            $length = $this->bufferSize;
        }
        $ret = stream_get_line($this->stream, $length, $ending);
        if (false === $ret) {
            throw new RuntimeException('Cannot read stream');
        }
        
        return $ret;
    }

    /**
     * Read the remaining data from the stream until its end.
     * Binary-safe.
     *
     * @return string The data read from the stream
     */
    public function getContent()
    {
        $this->ensureReadable();
        return stream_get_contents($this->stream);
    }

    protected function ensureReadable()
    {
        if (!$this->isOpen) {
            throw new LogicException('Cannot read from a closed stream');
        }
        if (!$this->isReadable()) {
            throw new LogicException(sprintf('Cannot read on a non readable stream (current mode is %s)', $this->getMetadataForKey('mode')));
        }
    }

    /**
     * Check whether the stream is positioned at the end.
     *
     * @return Boolean
     */
    public function isEOF()
    {
        return feof($this->stream);
    }

    /**
     * Write data to the stream.
     * Binary-safe.
     *
     * @param string $string The string that is to be written.
     * @param int $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     *
     * @return int Number of bytes written
     */
    public function write($string, $length = null)
    {
        if (!$this->isOpen) {
            throw new LogicException('Cannot write on a closed stream');
        }
        if (!$this->isWritable()) {
            throw new LogicException(sprintf('Cannot write on a non-writable stream (current mode is %s)', $this->getMetadataForKey('mode')));
        }
        if (null === $length) {
            $ret = fwrite($this->stream, $string);
        } else {
            $ret = fwrite($this->stream, $string, $length);
        }
        if (false === $ret) {
            throw new RuntimeException('Cannot write on stream');
        }

        return $ret;
    }

    /**
     * Read the content of this stream and write it to another stream, by chunks of $bufferSize
     *
     * @param Stream $stream The destination stream to write to
     *
     * @return int Number of piped bytes
     */
    public function pipe(Stream $stream)
    {
        return stream_copy_to_stream($this->getResource(), $stream->getResource());
    }
    
    public function rewind()
    {
        rewind($this->stream);
    }
    
    public function close()
    {
        if (!$this->isOpen) {
            throw new LogicException('Stream is already closed');
        }
        if ($ret = fclose($this->stream)) {
            $this->isOpen = false;
        }

        return $ret;
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}