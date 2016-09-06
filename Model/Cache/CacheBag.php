<?php
namespace Brown298\DataTablesBundle\Model\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * Class CacheBag
 *
 * @package Brown298\DataTablesBundle\Model\Cache
 */
class CacheBag implements CacheBagInterface
{
    /**
     * @var string
     */
    protected $baseKey = 'data_tables';

    /**
     * @var array|mixed
     */
    protected $keys = array();

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $env;

    /**
     * @param Cache $cache
     * @param       $env
     */
    public function __construct(Cache $cache = null, $env = 'dev')
    {
        $this->cache = $cache;
        $this->env   = $env;
        $this->keys  = $this->getKeys();
    }

    /**
     * addKey
     *
     * @param $keyData
     */
    public function addKey($keyData)
    {
        $this->keys[$keyData] = $this->baseKey . '_' . $keyData;
        $this->setKeys($this->keys);
    }

    /**
     * setKeys
     *
     * @param array $keys
     */
    public function setKeys(array $keys)
    {
        $key = $this->baseKey . '_keys';
        $this->cache->save($key, serialize($keys));
        $this->keys = $keys;
    }

    /**
     * getKeys
     *
     * gets an array of the available keys
     *
     * @return array|mixed
     */
    public function getKeys()
    {
        $key = $this->baseKey . '_keys';
        if ($results = $this->cache->fetch($key)) {
            $results = unserialize($results);
        } else {
            $results = array();
            $this->setKeys($results);
        }

        return $results;
    }

    /**
     * delete
     *
     * @param $key
     */
    public function delete($key)
    {
        $name = $this->keys[$key];
        $this->cache->delete($name);
        unset($this->keys[$key]);
    }


    /**
     * fetch
     *
     * @param $key
     *
     * @return mixed
     */
    public function fetch($key)
    {
        if (!isset($this->keys[$key])) {
            return null;
        }

        return $this->cache->fetch($this->keys[$key]);
    }

    /**
     * save
     *
     * @param $key
     * @param $data
     */
    public function save($key, $data)
    {
        if (!array_key_exists($this->baseKey . '_' . $key, $this->keys)) {
            $this->addKey($key);
        }
        $this->cache->save($this->baseKey . '_' . $key, $data);
    }

    /**
     * findKeysByRegex
     *
     * @param $search
     *
     * @return array
     */
    public function findKeysByRegex($search)
    {
        $results = preg_grep('/' . $search . '/', array_keys($this->keys));

        return $results;
    }

    /**
     * clearPrefix
     *
     * @param $prefix
     */
    public function clearRegex($prefix)
    {
        $keys = $this->findKeysByRegex($prefix);
        foreach ($keys as $name => $key) {
            $this->delete($key);
        }

        $this->setKeys($this->keys);
    }

    /**
     * getKeyName
     *
     * @param       $description
     * @param array $options
     *
     * @return string
     */
    public function getKeyName($description, array $options = array())
    {
        $prefix = $this->baseKey . '_' . $this->env . '_';

        foreach($options as $name => $value) {
            if (method_exists($value,'getId')) {
                $prefix .= "{$name}_{$value->getId()}_";
            } elseif (method_exists($value,'__toString')) {
                $prefix .= "{$name}_{$value->__toString()}_";
            } elseif (is_string($value)) {
                $prefix .= "{$name}_{$value}_";
            } elseif (is_bool($value)) {
                $prefix .= "{$name}_" . ((int) $value) . '_';
            } else {
                $hash = hash('md4',serialize($value));
                $prefix .= "{$name}_{$hash}_";
            }
        }

        $key = $prefix . $description;

        return $key;
    }

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param string $baseKey
     */
    public function setBaseKey($baseKey)
    {
        $this->baseKey = $baseKey;
    }

    /**
     * @return string
     */
    public function getBaseKey()
    {
        return $this->baseKey;
    }
}
