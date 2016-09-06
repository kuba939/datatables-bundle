<?php

namespace Brown298\DataTablesBundle\Model\Cache;

/**
 * Interface CacheBagInterface
 *
 * @package Brown298\DataTablesBundle\Model\Cache
 */
interface CacheBagInterface
{

    /**
     * getKeyName
     *
     * provides a unique name for the object
     *
     * @param       $description
     * @param array $options
     *
     * @return mixed
     */
    public function getKeyName($description, array $options = array());

    /**
     * fetch
     *
     * gets data from the cache identified by the key
     *
     * @param $key
     *
     * @return mixed
     */
    public function fetch($key);

    /**
     * save
     *
     * saves data to the cache
     *
     * @param $key
     * @param $data
     *
     * @return mixed
     */
    public function save($key, $data);
}
