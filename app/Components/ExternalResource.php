<?php

use helper\RequestManager;

class ExternalResource
{

    public static function getResource($link, $rel2abs = true)
    {
        $link = self::instagramHook($link);
        if (self::get_http_response_code($link) != "404") {
            $options = [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_HEADER => 0,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3'
            ];
            $response = RequestManager::init($link)->setOptions($options)->exec(true, true);
            $errCode = isset($response['errno']) ? $response['errno'] : 1;
            header('Content-type:' . $response['content_type']);
            if ($errCode !== 0) {
                return "Не удалось загрузить страницу\n" . $link . ". Error: " . $errCode;
            }

            $host   = self::getHostFromUrl($link);
            $result = $rel2abs ? self::rel2abs($response['result'], $host) : $response['result'];

            return $result;
        } else {
            return "Ссылка недоступна " . $link;
        }
    }

    /**
     * Конвертирует относительные ссылки документа в абсолютные
     *
     * @param $file
     * @param $host
     * @return mixed
     */
    protected static function rel2abs($file, $host)
    {
        $pattern = '#(<\s*((img)|(a)|(link))\s+[^>]*((src)|(href))\s*=\s*[\"\'])(?!\/\/)(?!http)([^\"\'>]+)([\"\'>]+)#';
        $file = preg_replace($pattern, '$1'.$host.'$9$10', $file);
        return $file;
    }

    protected static function getHostFromUrl($url)
    {
        $host = parse_url($url);
        $host = $host['scheme'] . "://" . $host['host'];
        return $host;
    }

    protected static function get_http_response_code($url)
    {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }

    /**
     * Returns true if host accepts redirects through iframe
     *
     * @param $link
     *
     * @return bool
     */
    public static function hostPassthrough($link)
    {
        $passthrough = ['rutube.ru'];
        $parsed_url  = parse_url($link);
        $host        = isset($parsed_url['host']) ? strtolower($parsed_url['host']) : "";
        $result      = in_array($host, $passthrough);

        return $result;
    }

    /**
     * Redirect all instagram links to captioned embed url
     *
     * @param $link
     *
     * @return mixed
     */
    public static function instagramHook($link)
    {
        $instagram_hosts = ['instagram.com'];
        $parsed_url = parse_url($link);
        $host       = isset($parsed_url['host']) ? strtolower($parsed_url['host']) : "";
        if (in_array($host, $instagram_hosts)) {
            $link = preg_replace('/\/embed(\/captioned)?(\/)?$/i', '', $link);
            $link = $link . "/embed/captioned";
        }

        return $link;
    }

}