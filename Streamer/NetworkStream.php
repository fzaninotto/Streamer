<?php

namespace Streamer;

class NetworkStream extends Stream
{
    public static function create($address, $timeout = null, $flags = null, $context = null)
    {
        return new static(stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context));
    }

    public function getName($remote = true)
    {
        return stream_socket_get_name($this->stream, $remote);
    }

}