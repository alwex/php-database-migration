<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 01/03/15
 * Time: 01:45
 */

namespace Migrate\Utils;

class InputStreamUtil
{

    public static function type($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
