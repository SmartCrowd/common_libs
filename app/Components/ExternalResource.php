<?php

use helper\RequestManager;

class ExternalResource
{

    private static $base_href_exception_domains = ['https://vk.com'];

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

            if ($errCode !== 0) {
                return "Не удалось загрузить страницу\n" . $link . ". Error: " . $errCode;
            }

            $result = $rel2abs ? self::rel2abs($response['result'], $link) : $response['result'];

            if (strpos($response['content_type'], 'charset')) {
                header('Content-type:' . $response['content_type']);
            } else {
                if(preg_match("/<meta[^>]+charset=[']?(.*?)[']?[\/\s>]/i", $result, $matches)) {
                    header('Content-type:' . $response['content_type'] . '; charset=' . $matches[1]);
                }
            }

            return $result;
        } else {
            return "Ссылка недоступна " . $link;
        }
    }

    /**
     *  Задание базового URL для относительных URL
     *
     * @param $file
     * @param $url
     * @return mixed
     */
    protected static function rel2abs($file, $url)
    {
        $full_domain = self::getHostFromUrl($url);

        $pattern = '#(<\s*((img)|(a)|(link))\s+[^>]*((src)|(href))\s*=\s*[\"\'])(?!\/\/)(?!http)([^\"\'>]+)([\"\'>]+)#';
        $file = preg_replace($pattern, '$1'.$full_domain.'$9$10', $file);

        if (!in_array($full_domain, self::$base_href_exception_domains) && !preg_match('/(<base[^>]* href="(.*)">)/', $file)) {
            $file = preg_replace('/(<head[^>]*>)/', '$1<base href="'.$full_domain.'"/>', $file);
        }

        return $file;
    }

    protected static function getHostFromUrl($url, $full = true)
    {
        $host = parse_url($url);
        $host = $full ? $host['scheme'] . "://" . $host['host'] : $host['host'];

        return $host;
    }

    protected static function get_http_response_code($url)
    {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
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
