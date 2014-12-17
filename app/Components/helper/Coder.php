<?php
/**
 * Base helper for code and decode raw messages
 *
 * @author : ignat
 * @date : 29.07.14 10:45
 */

namespace helper;

use \models\Feeds;

abstract class Coder {

    private static $compressLevel = 9;
    private static $profitStringLength = 200;

    /**
     * @param string $str
     * @return string
     */
    public static function encode($str){
        return base64_encode(gzcompress($str, self::$compressLevel));
    }

    /**
     * @param string $str
     * @return string
     */
    public static function decode($str){
        return gzuncompress(base64_decode($str));
    }

    public static function compressFeedMessage(Feeds $message)
    {
        $message->text    = self::encode($message->text);
        $message->compressed = 1;
        return $message;
    }

    public static function compressionProfit($string)
    {
        return (strlen($string) > self::$profitStringLength);
    }

} 