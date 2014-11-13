<?php
/**
 * Class used to manage clients name in project
 *
 * @author :  komrakov
 * @date   :  15.09.14 16:53
 */

class ClientResolver {

    /**
     * Enable multi clients architecture
     * @var bool
     */
    private $enabled = true;
    private $client;

    /**
     * Setting up default client name. See services.php for details
     * @param $client string
     */
    public function __construct ($client = '')
    {
        $this->setClient($client);
    }

    /**
     * @return bool
     */
    public function useClients(){
        return $this->enabled;
    }

    /**
     * Change client name
     * @param $new_client string
     */
    public function setClient($new_client)
    {
        if ($this->enabled)
            $this->client = (string) $new_client;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $string
     * @return string
     */
    public function addClientPrefix($string)
    {
        if ($this->enabled && $this->client){
            $string = "{$this->client}_{$string}";
        }
        return $string;
    }

    /**
     * Figure out client name from passed url
     * @param $host
     * @return string
     */
    public static function getClientFromUrl($host){

        $parts = explode('.', $host);
        if ( strstr($host, 'smart-crowd') )
            $clientName = array_shift($parts);
        else
            $clientName = count($parts) >= 3 ? array_shift($parts) : '';

        return $clientName;
    }
    
    /**
     * Generate URL to Viewer_web client
     * @param bool $toApi
     * @return string
     */
    public function getViewerUrl($toApi = true){
        return "http://" . str_replace('grandviewer', 'viewer', CDI()->config->client->url) . ($toApi ? "/api" : '');
    }

} 