<?php

namespace Streamer;

class FileStream extends Stream
{
    public static function create($filename, $mode, $use_include_path = false, $context = null)
    {
        $type = gettype($context);

        if($type == 'resource') {
            return new self(fopen($filename, $mode, $use_include_path, $context));
        }

        return new static(fopen($filename, $mode, $use_include_path));
    }
}