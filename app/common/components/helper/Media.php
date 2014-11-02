<?php
/**
 * helper for media elements
 *
 * @author : nikolaev
 * @date : 10/20/14 2:05 PM
 */

namespace helper;

class Media
{

    /**
     * get size and width of the remote image
     * @param string $url
     * @return array
     */
    public static function getImageSize($url)
    {
        $size = [];

        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            $curl = RequestManager::init($url);
            $curl->setOptions([
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FAILONERROR    => 1,
            ]);
            $result = $curl->exec(true, true);
            if ($result['errno'] === 0) {
                $img = imagecreatefromstring($result['result']);
                if (is_resource($img)) {
                    $size[0] = imagesx($img);
                    $size[1] = imagesy($img);
                    //clear memory
                    imagedestroy($img);
                }
            } else {
                CDI()->devLog->log('Error occurred during getting size of remote image: ' . $result['error'], 'notice');
            }
        } else
            $size = @getimagesize($url);

        return $size;
    }
}