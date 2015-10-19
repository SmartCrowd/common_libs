<?php
/**
 * Perform request via buffer
 *
 * User: soldatenko
 * Date: 07.02.14 15:48
 */

namespace helper;
use Etechnika\IdnaConvert\IdnaConvert;

/**
 * Class RequestManager
 * @package helper
 *
 * Use proxy file names in format XX_proxy.list
 * @example ru_proxy.list, eu_proxy_list
 *
 */
class RequestManager
{
    private $ch;
    private $options = []; //for debug

    /**
     * initializes new curl session and returns RequestManager object
     * @param $apiUrl
     * @return RequestManager
     */
    public static function init($apiUrl)
    {
        $inst = new self();
        $inst->ch = curl_init( self::encodeUrl($apiUrl) );
        return $inst;
    }

    /**
     * sets options for curl request
     * @param array $options array of curl options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $default = [
            CURLOPT_RETURNTRANSFER => 1,    // to return data in "result" field
            CURLOPT_FRESH_CONNECT  => 0     // to use cached channels
        ];
        foreach($default as $key => $value){
            if (!isset($options[$key]))
                $options[$key] = $value;
        }
        curl_setopt_array($this->ch, $options);
        $this->options = $options;
        return $this;
    }

    /**
     * Performs curl request with proxy or not
     * @param bool $useProxy true if it needed to use proxy
     * @param bool $rus true if it needed to use proxy based on russian server
     * @return mixed
     */
    public function exec($useProxy = false, $rus = false)
    {
        if ($useProxy && $proxy = self::getProxy($rus)) {
            curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
            $this->options[CURLOPT_PROXY] = $proxy;
            if (strstr($proxy, 'socks5')) {
                curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
            }
        }
        $res = curl_exec($this->ch);
        $content = curl_getinfo($this->ch);
        $content['errno'] = curl_errno($this->ch);
        $content['error'] = curl_error($this->ch);
        $content['result'] = $res;
        curl_close($this->ch);
        return $content;
    }

    /**
     * @param string $lang - available: [eu, ru]
     * @return array
     */
    public static function getProxyList($lang = 'eu')
    {
        if (($proxies = CDI()->cache->getCache('proxyList', $lang)) !== false) {
            return $proxies;
        }

        if (empty(CDI()->config->custom_proxy_list)) {
            $path_to_proxy = __DIR__.'/data/'.$lang.'_proxy_list.txt';
        } else {
            $path_to_proxy = CDI()->config->custom_proxy_list;
        }

        $proxies = self::loadFromFile($path_to_proxy);

        if (count($proxies))
            CDI()->cache->setCache('proxyList', $lang, $proxies);

        return $proxies;
    }

    /**
     * Load proxy from file
     * @param string $filename
     * @param string $delimiter
     * @return array
     */
    protected static function loadFromFile($filename, $delimiter = "\n")
    {
        $fp = @fopen($filename, "r");

        if (!$fp) {
            CDI()->devLog->log("(!) Failed to open file: $filename");
            return array();
        }

        $data = @fread($fp, filesize($filename) );
        fclose($fp);

        if (strlen($data) < 1) {
            CDI()->devLog->log("(!) Empty file: $filename");
            return array();
        }

        $proxy = explode($delimiter, trim($data));
        foreach($proxy as $k => $v){
            if (strpos($v, ';') === 0 || strpos($v, '#') === 0){
                unset($proxy[$k]);
            }
        }

        return $proxy;
    }

    /**
     * Checks if curl error code matches codes to restart operation
     * @param int $error
     * @return bool
     */
    public static function restartCheck($error)
    {
        $errors_match = [CURLE_COULDNT_RESOLVE_PROXY, CURLE_OPERATION_TIMEOUTED, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_CONNECT];
        return (isset($error) && in_array((int)$error, $errors_match));
    }

    /**
     * Function to convert Cyrillic domains in international symbols (default encoding utf-8)
     * @param $link
     * @return mixed
     */
    public static function encodeUrl($link)
    {
        $domain = parse_url($link, PHP_URL_HOST);
        $encoded_domain = IdnaConvert::encodeString($domain);
        return str_replace($domain, $encoded_domain, $link);
    }

    /**
     * gets a single proxy string
     * @param bool $rus
     * @return array
     */
    public static function getProxy($rus = false)
    {
        $lang = !$rus ? 'eu' : 'ru';
        $proxy = '';
        $proxies = self::getProxyList($lang);
        if (count($proxies)) {
            $proxy = $proxies[mt_rand(0, count($proxies) - 1)];
        }
        return $proxy;
    }

    /**
     * sets random user agent string to options array
     * @return $this
     */
    public function setRandomUserAgent()
    {
        $user_agents = self::getUserAgentsList();
        if (count($user_agents)) {
            $agent = $user_agents[mt_rand(0, count($user_agents) - 1)];
            curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
            $this->options[CURLOPT_USERAGENT] = $agent;
        }

        return $this;
    }

    /**
     * gets list of user_agents from file or from cache
     * @return array|mixed
     */
    public static function getUserAgentsList()
    {
        if (($agents = CDI()->cache->getKey('userAgentsList')) !== false) {
            return Cache::unpack($agents);
        }
        $agents = self::loadFromFile(__DIR__.'/data/user_agent_list.txt');
        if (count($agents))
            CDI()->cache->setKey('userAgentsList', Cache::pack($agents));
        return $agents;
    }

}
