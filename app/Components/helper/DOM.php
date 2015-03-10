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
     * Parses page with DOMDocument
     *
     * @param        $url
     * @param bool   $user_agents true if need to use random user_agents in request
     * @param string $cookie
     *
     * @return \DOMDocument
     */
    public static function getDOM($url, $user_agents = false, $cookie = "")
    {
        $curl_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        if (!empty($cookie)) {
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

    /**
     * @param string $html
     * @param string $version
     * @param string $encoding
     * @param bool   $convert_encoding
     * @param string $encoding_from
     *
     * @return \DOMDocument
     */
    public static function makeDomFromHtml($html, $version = "1.0", $encoding = "utf-8", $convert_encoding = true, $encoding_from = "")
    {
        if ($convert_encoding) {
            $encoding_from = !empty($encoding_from) ? $encoding_from : mb_detect_encoding($html);
            $html = mb_convert_encoding($html, $encoding, $encoding_from);
        }
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml version="'.$version.'" encoding="'.$encoding.'"?>' . $html);
        $dom->substituteEntities = true;

        return $dom;
    }

    /**
     * @param $selector
     * @param \DOMDocument $dom
     *
     * @return \DOMNodeList
     */
    public static function findByXPath($selector, \DOMDocument $dom)
    {
        $finder = new \DomXPath($dom);
        $nodeList = $finder->query($selector);

        return $nodeList;
    }

    /**
     * @param string $class
     * @param \DOMDocument $dom
     *
     * @return \DOMNodeList
     */
    public static function findByClass($class, \DOMDocument $dom)
    {
        return self::findByXPath("//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]", $dom);
    }

    /**
     * @param \DOMNodeList $nodeList
     * @param string       $version  New DOMDocument object version parameter
     * @param string       $encoding New DOMDocument object encoding parameter
     *
     * @return \DOMDocument
     */
    public static function nodeListToDom(\DOMNodeList $nodeList, $version = '1.0', $encoding = 'UTF-8')
    {
        $dom = new \DOMDocument($version, $encoding);

        $nb = $nodeList->length;
        for ($pos = 0; $pos < $nb; $pos++) {
            $element = $nodeList->item($pos);
            $element = $dom->importNode($element, true);
            $dom->appendChild($element);
        }

        return $dom;
    }

}