<?php
/**
 * Helper for Models
 *
 * @author :  ignat
 * @date   :  27.06.14 12:55
 */

namespace helper;

use MongoCollection;
use Phalcon\Mvc\Collection;

abstract class Model {

    private static $defaultNamespace = 'models\\';

    /**
     * Convert regular taskId into update compatible _id object.
     * Example: _id.sn => 'twitter', 'id.target' => 'jack'
     *
     * @param $taskId array Complex property from mongo Record
     * @param string $splitter used as prefix to array keys
     * @return array
     */
    public static function convertId($taskId, $splitter = '_id'){

        $newId = array();
        if (!empty($taskId)){
            foreach($taskId as $k => $val){
                $newId[ self::convertIdString($k, $splitter)] = $val;
            }
        }
        return $newId;
    }

    /**
     * Join value and splitter prefix by dot ('.')
     *
     * @param string $idValue
     * @param string $splitter
     * @return string ($splitter.$idValue)
     */
    public static function convertIdString($idValue, $splitter = '_id'){
        return "$splitter.$idValue";
    }

    /**
     * Validate and sanitize passed string to models instance
     * @param $className
     * @return string (example: "models/Users")
     */
    private static function sanitizeNamespace($className)
    {
        if (strpos(ltrim($className, '\\'), self::$defaultNamespace) !== 0){
            $className = self::$defaultNamespace . ucfirst($className);
        }

        return $className;
    }

    /**
     * Create instance of model or
     * @param string|Collection $model name or instance of model
     * @param null|array $params - [key => values] pairs with model data
     * @return Collection|\models\Tasks|\models\Themes|\models\Projects|\models\Feeds|\models\Projects\Keys|\models\Projects\Profiles
     * @throws \Exception
     */
    public static function getModel($model, $params = null){

        if (is_string($model)){

            $model = self::sanitizeNamespace($model);
            if ( !class_exists($model) )
                throw new \Exception("Attempt to create instance of [$model] class that don't exists");

            $model = new $model();
        }

        if ($params){
            $model = self::fillModel($model, $params);
        }

        return $model;
    }

    /**
     * @param \Phalcon\Mvc\Collection $model name or instance of model
     * @param null $params
     * @return mixed
     */
    public static function fillModel($model, $params = null){

        if (is_object($model) && $params){
            foreach($params as $k => $v){
                $model->writeAttribute($k, $v);
            }
        }

        return $model;
    }

    /**
     * Get social name from passed snId string (format: "snName_snId")
     * @param string $snId
     * @return mixed|string
     */
    public static function getSocialName($snId = ''){

        $snId = explode('_', $snId);
        return (!empty($snId)) ? array_shift($snId) : '';
    }

    /**
     * checks if collection exists
     * @param string $collection_name
     * @return bool
     */
    public static function isCollectionExists($collection_name)
    {
        $collections = CDI()->mongo->getCollectionNames();
        return (array_search($collection_name,$collections) ? true : false);
    }

    /**
     * Checks Mongo index
     * @param MongoCollection $collection
     * @param array           $index
     *
     * @return bool
     */
    public static function isIndexExist(MongoCollection $collection, array $index)
    {
        foreach ($collection->getIndexInfo() as $i) {
            if ($i['key'] == $index) {

                return true;
            }
        }

        return false;
    }

    /**
     * creates a collection
     * @param string $collection_name
     * @return bool
     */
    public static function createCollection($collection_name)
    {
        return (is_object(CDI()->mongo->createCollection($collection_name)) ? true : false);
    }

    /**
     * Return string created for Feed, theme, and themes msg
     *
     * @param string $projectId
     * @param string $collectionPrefix  - ["feed", "themes", "themes_feed"]
     * @return string
     */
    public static function createCollectionId($projectId = '', $collectionPrefix = ''){

        switch(strtolower($collectionPrefix)){
            case 'themes':
                $name = "themes" . (!empty($projectId) ? '_'.$projectId : '');
                break;

            case 'themes_feed':
                $name = "themes_feed" . (!empty($projectId) ? '_'.$projectId : '');
                break;

            case 'feed':
            default:
                $name = "feed" . (!empty($projectId) ? '_'.$projectId : '');
        }
        return $name;
    }

    /**
     * Convert passed metrics array to suitable to update
     * Example: ['metrics.views' => value, 'metrics.auditory' => value, ...]
     * @param array $metrics
     * @return array
     */
    public static function prepareMetrics(array $metrics){
        foreach($metrics as $name => $value){
            $metrics["metrics.".$name] = $value;
            unset($metrics[$name]);
        }
        return $metrics;
    }

    /**
     * Helper function for sorting arrays of messages.
     * Example usage: usort($messages, ['\helper\Model', 'sortMessagesByTime']);
     *
     * @param $message_1
     * @param $message_2
     *
     * @return int
     */
    public static function sortMessagesByTime($message_1, $message_2)
    {
        $views_1 = $message_1['time'];
        $views_2 = $message_2['time'];
        if ($views_1 == $views_2) {
            return 0;
        }
        return ($views_1 < $views_2) ? +1 : -1;
    }

    /**
     * Converts $object to array if instance of Collection
     * @param $object
     *
     * @return array
     */
    public static function toArray($object)
    {
        if ($object instanceof Collection) {
            $object = $object->toArray();
        }
        return $object;
    }

    /**
     * Converts [ ['$id' => "544f40d60f808ec3258b4a72"], ['$id' => "544f40d60f808ec3258b4a72"], ... ] to [ MongoId(), MongoId(), ... ]
     * @param array $gluedDocs
     *
     * @return mixed
     */
    public static function restoreGluedDocs($gluedDocs) {
        foreach ($gluedDocs as $key => $id)
            if (!($id instanceof \MongoId) && isset($id['$id']))
                $gluedDocs[$key] = new \MongoId($id['$id']);

        return $gluedDocs;
    }

} 