<?php

namespace Streamer;

class NetworkStream extends Stream
{
    public static function create($address, $timeout = null, $flags = null, $context = null)
    {
        $type = gettype($context);

        if($type == 'resource') {
            return new static(stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context));
        }

        return new static(stream_socket_client($address, $errno, $errstr, $timeout, $flags));
    }

    public function getName($remote = true)
    {
        return stream_socket_get_name($this->stream, $remote);
    }

}