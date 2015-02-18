<?php
/**
 * Created by PhpStorm.
 * User: nikolaev
 * Date: 21.01.15
 * Time: 11:41
 */
namespace helper;

class DOM
{

    /**
     * parses page with DOMDocument
     * @param $url
     * @param bool $user_agents true if need to use random user_agents in request
     * @return \DOMDocument
     */
    public static function getDOM($url, $user_agents = false, $cookie = null)
    {
        $curl_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        if ($cookie != null) {
            $curl_options[CURLOPT_COOKIE] = $cookie;
        }
        $response = RequestManager::init($url)->setOptions($curl_options);
        if ($user_agents == true) {
            $response->setRandomUserAgent();
        }
        $response = $response->exec(true, true);
        $dom = self::makeDomFromHtml($response['result']);
        return $dom;
    }

    public static function makeDomFromHtml($html)
    {
        $html = mb_convert_encoding($html, 'utf-8', mb_detect_encoding($html));

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . $html);
        $dom->substituteEntities = true;

        return $dom;
    }

    /**
     * @param $selector
     * @param \DOMDocument $dom
     * @return \DOMNodeList
     */
    public static function findByXPath($selector, \DOMDocument $dom)
    {
        $finder = new \DomXPath($dom);
        $res = $finder->query($selector);
        return $res;
    }


}