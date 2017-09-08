<?php

namespace Avoxx\Psr7;

require_once __DIR__ . '/../vendor/autoload.php';

// We write our own move_uploaded_file() function
// because by default this function can't be executed in other scope than HTTP POST
// @see https://pierrerambaud.com/blog/php/2012-12-29-testing-upload-file-with-php
function move_uploaded_file($filename, $destination)
{
    return copy($filename, $destination);
}
