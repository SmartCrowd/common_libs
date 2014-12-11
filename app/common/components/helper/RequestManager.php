<?php
/**
 * Perform request via buffer
 *
 * User: soldatenko
 * Date: 07.02.14 15:48
 */

namespace helper;

class RequestManager
{
    private $ch;

    /**
     * initializes new curl session and returns RequestManager object
     * @param $apiUrl
     * @return RequestManager
     */
    public static function init($apiUrl)
    {
        $inst = new self();
        $inst->ch = curl_init($apiUrl);
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
        return $this;
    }

    /**
     * Performs curl request with proxy or not
     * @param bool $proxy true if it needed to use proxy
     * @param bool $rus true if it needed to use proxy based on russian server
     * @return mixed
     */
    public function exec($proxy = false, $rus = false)
    {
        if ($proxy) {
            $lang = !$rus ? 'eu' : 'ru';
            $proxies = self::getProxyList($lang);
            if (count($proxies)) {
                $proxy = $proxies[mt_rand(0, count($proxies) - 1)];
                curl_setopt($this->ch, CURLOPT_PROXY, $proxy);

                if (strstr($proxy, 'socks5'))
                    curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
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
        $proxies = self::loadFromFile(ROOT_PATH.'/private/'.$lang.'_proxy_list.txt');
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
            if (strstr($v, ';') || strstr($v, '#')){
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

}