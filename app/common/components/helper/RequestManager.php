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
        if (!isset($options[CURLOPT_RETURNTRANSFER])) {
            $options[CURLOPT_RETURNTRANSFER] = true;
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
            $lang = !$rus ? 'en' : 'ru';
            $proxies = self::getProxyList($lang);
            if (count($proxies)) {
                $proxy = $proxies[array_rand($proxies)];
                curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
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

    protected static function getProxyList($lang = 'en')
    {
        if (($proxies = CDI()->cache->getCache('proxyList', $lang)) !== false) {
            return $proxies;
        }
        $proxies = self::loadFromFile(ROOT_PATH.'/private/'.$lang.'_proxy_list.txt');
        CDI()->cache->setCache('proxyList', $lang, $proxies);
        return $proxies;
    }

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

        $array = explode($delimiter, $data);

        if (is_array($array) && count($array) > 0) {
            foreach($array as $k => $v)
            {
                if (strlen( trim($v) ) > 0)
                    $array[$k] = trim($v);
            }
            return $array;
        } else {
            CDI()->devLog->log("(!) Empty data array in file: $filename");
            return array();
        }
    }

}