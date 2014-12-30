<?php
/**
 * Wrapper for Mongo connection
 *
 * @author :  ignat
 * @date   :  17.09.14 14:27
 */

namespace storage;

use helper\ConfigHelper;

class MongoStorage {

    public $connection;

    public function __construct(){

        $stage  = ConfigHelper::getStageStatus();
        $config = CDI()->config->mongo->$stage;
        $host   = $config->host;
        $port   = $config->port;

        if ($stage === 'default'){
            $this->connection = new \MongoClient($this->getConnectionString($host, $port));
        } else {
            $nodes   = isset($config->nodes) ? $config->nodes : null;
            $replica = null;

            if (isset($config->replicaName) && !empty($config->replicaName))
                $replica = $config->replicaName;

            if ($nodes && $replica) {
                $this->connection = new \MongoClient(
                    $this->getConnectionString($host, $port, $nodes), ["replicaSet" => $replica]
                );
            } else {
                $this->connection = new \MongoClient($this->getConnectionString($host, $port));
            }
        }
    }

    /**
     * Get instantiated mongo connection
     * @return \MongoClient
     */
    public function getConnection(){
        return $this->connection;
    }

    /**
     * Create connection string to MongoDB
     * Example: mongodb://localhost:27017
     *
     * @param string $host
     * @param string|int $port
     * @param null|array $nodes
     * @return string
     */
    private function getConnectionString($host, $port, $nodes = null){

        $conn = ["mongodb://", $host, ':', $port];

        if (!empty($nodes)){
            foreach($nodes as $node){
                $conn[] = ",{$node['host']}:{$node['port']}";
            }
        }
        return implode('', $conn);
    }

} 