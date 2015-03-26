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
        $result = self::getImageHeaders($url);
        if ($result === false) {
            CDI()->devLog->log('Error occurred during getting content-length of remote image: ' . $result['error'] . ' url: ' . $url, 'notice');
            return false;
        }

        if (preg_match_all('/Content-Length: (\d+)/', $result['result'], $matches)) {
            $last_match = array_pop($matches);
            $content_length = (int)array_pop($last_match);
            return $content_length;
        }

        CDI()->devLog->log('Bad content-length, url: ' . $url, 'notice');

        return false;
    }


    public static function getImageHeaders($url)
    {
        $options = [
            CURLOPT_NOBODY         => true,
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding:gzip,deflate,sdch',
                'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                'Cache-Control:no-cache',
                'Connection:keep-alive',
                'Pragma:no-cache',
                'User-Agent:Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36',
            ]
        ];
        $result = RequestManager::init($url)->setOptions($options)->exec();
        return ($result['http_code'] === 200 && $result['errno'] === 0) ? $result : false;
    }

    public static function getContentLength($headers)
    {
        return isset($headers['download_content_length']) ? $headers['download_content_length'] : 0;
    }

    public static function getContentType($headers)
    {
        if (isset($headers['content_type']) && preg_match('/(.+)(;|$)/U',$headers['content_type'],$match) ){
            return $match[1];
        }
        return "";
    }

    public static function checkImageType($headers)
    {
        $content_type    = self::getContentType($headers);
        $available_types = ['image/gif', 'image/jpeg', 'image/png'];

        return in_array($content_type, $available_types);
    }

    public static function checkImageSize($headers)
    {
        $content_length = self::getContentLength($headers);
        $minimal_length = 3500;

        return $content_length > $minimal_length;
    }

    /**
     * Extract media elements from html
     * @param string $html
     * @return array
     */
    public static function getMediaFromHtml($html)
    {
        $album = [
            'main' => '',
            'album' => [],
            'video' => []
        ];
        preg_match_all( '/<img.*src=[\'\"](.*)[\'\"]/U', $html, $result_img );
        preg_match_all( '/<iframe.*src=[\'\"](.*)[\'\"]/U', $html, $result_video );
        foreach(array_pop($result_img) as $image){
            $image = html_entity_decode($image);
            $headers = self::getImageHeaders($image);
            if (self::checkImageType($headers) && self::checkImageSize($headers)) {
                $album['album'][] = $image;
            }
        }
        foreach(array_pop($result_video) as $video) {
            $album['video'][] = str_replace('amp;','',$video);
        }
        if (count($album['video']) > 0) {
            $album['main'] = '/img/frontend/Stamp_.jpg';
        }
        elseif (count($album['album']) > 0 && count($album['video']) == 0) {
            $album['main'] = $album['album'][0];
        }
        $album['album'] = array_unique($album['album']);
        $album['video'] = array_unique($album['video']);
        return $album;
    }

}
