<?php
namespace assets;

/**
 * Phalcon assetsManager helper functions
 *
 * @author :  komrakov
 * @date   :  01.12.14 10:28
 */

use Phalcon\Assets\Collection;
use Phalcon\Assets\Manager;

class AssetsManager extends Manager {

    public function collection($name, $targetPath = "", $targetUri = "")
    {
        $targetUri  = empty($targetUri) ? $targetPath : $targetUri;
        $collection = parent::collection($name);
        $collection = empty($targetPath) ? $collection->join(false) : $collection->join(true)->setTargetPath($targetPath)->setTargetUri($targetUri);

        return $collection;
    }

    public function addCssDir(Collection &$collection, $path, $filter = false, $recursive = false) {
        $files = self::getFilesFromDir($path, 'css', $recursive);

        foreach ($files as $file) {
            $collection->addCss($file, true, $filter);
        }
    }

    public static function addJsDir(Collection &$collection, $path, $filter = false, $recursive = false) {
        $files = self::getFilesFromDir($path, 'js', $recursive);

        foreach ($files as $file) {
            $collection->addJs($file, true, $filter);
        }
    }

    /**
     * Gets all files in directory filtered by file extension. Return unordered array of filepaths
     *
     * @param string $path
     * @param string $extension
     * @param bool   $recursive Use recursive directory iterator. False by default
     *
     * @return array
     */
    public static function getFilesFromDir($path, $extension, $recursive = false)
    {
        $objects = $recursive ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) : new \IteratorIterator(new \DirectoryIterator($path));

        $files = [];
        foreach($objects as $name => $object){
            if ($object->getExtension() == $extension) {
                $files[] = $object->getPathname();
            }
        }

        return $files;
    }

} 