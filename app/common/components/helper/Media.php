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
            $result = $curl->exec(true);
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

    public static function getRemoteFileSize($url)
    {
        $options = [
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 10
        ];
        $result = RequestManager::init($url)->setOptions($options)->exec(true);
        if ($result['http_code'] === 200 && $result['errno'] === 0) {
            if (preg_match("/Content-Length: (\d+)/", $result['result'], $matches)) {
                $content_length = (int)$matches[1];
                return $content_length;
            }
            CDI()->devLog->log('Bad content-length, url: ' . $url, 'notice');
            return 0;
        }
        CDI()->devLog->log('Error occurred during getting content-length of remote image: ' . $result['error'] . ' url: ' . $url, 'notice');
        return 0;
    }
}