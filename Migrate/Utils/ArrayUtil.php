<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 18:30
 */

namespace Migrate\Utils;


class ArrayUtil {
    public static function get(array $array, $key) {
        return (array_key_exists($key, $array)) ? $array[$key] : null;
    }

    public static function filter(array $array) {
        $files = array();
        foreach ($array as $file) {
            if(!is_dir($file)){
                $files[] = $file;
            }
        }

        return $files;
    }
}