<?php


namespace App;


use App\Repository\RecipesConfigurationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use App\Utils\ArraysUtils;

/**
 * Class RecipesCacheHandler
 *
 * @package App
 */
class RecipesCacheHandler {

    // CONFIG TYPES
    public const CONFIG_TYPE_THRESHOLDS = 'thresholds';
    public const CONFIG_TYPE_PRUNING = 'pruning';
    public const CONFIG_TYPE_APP = 'app-config';

    public const CACHE_EXPIRE = 31500000;


    /** @var FilesystemAdapter  */
    private $cache;
    /** @var ManagerRegistry  */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry) {
        $this->cache = new FilesystemAdapter();
        $this->managerRegistry = $managerRegistry;

        // TODO Structure method to create all db configs.
    }

    /**
     * Find a record.
     *
     * @param string $lookupKey
     * @param string $configKey
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigKey(string $lookupKey) {
        $value = $this->cache->get('cache_'.$lookupKey, function (ItemInterface $item) use ($lookupKey) {
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 365 days.
            $recipesConfigRepo = new RecipesConfigurationRepository($this->managerRegistry);
            if ($lookupKey !== '') {
                $dbValue =  $recipesConfigRepo->findBy(['configKey' => $lookupKey],[], 1);
            } else {
                $this->cache->delete('cache_'.$lookupKey);
            }
            return $dbValue;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_'.$lookupKey);
        }
        $result = !empty($value[0]) ? $value[0]->getConfigValue() : false;
        return $result;
    }

    /**
     * Clear all cache.
     */
    public function clearCache() {
        $this->cache->clear();
    }

    /**
     * Delete Cache by key.
     *
     * @param $key
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function clearCacheKey($key) {
        $this->cache->delete($key);

    }

    /**
     * Find a record.
     *
     * @param string $key
     * @param string $value
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigValue(string $value = '') {
        //$this->cache->delete('cache_'.$value);
        // Gets a cache value and sets it if it doesn't already exist.
        $value = $this->cache->get('cache_'.$value, function (ItemInterface $item) use ($value) {
            // TODO should be a config.
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 41 days.
            $recipesRepo = new recipesConfigurationRepository($this->managerRegistry);
            $dbValue = [];
            if ($value !== '') {
                $dbValue =  $recipesRepo->findBy(['configValue' => $value],[], 1);
            } else {
                $this->cache->delete('cache_'.$value);
            }
            return $dbValue;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_'.$value);
        }
        return !empty($value[0]) ? $value[0]->getConfigKey() : false;
    }

    /**
     * Get all available config keys.
     *
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllConfigKeys() {
        // Gets a cache value and sets it if it doesn't already exist.
        $value = $this->cache->get('cache_all_config_keys', function (ItemInterface $item) {
            // TODO should be a config.
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 365 days.
            $recipesRepo = new RecipesConfigurationRepository($this->managerRegistry);
            $dbValue =  $recipesRepo->findAll();
            $configKeys = [];
            foreach($dbValue as $value) {
                $configKeys[] = $value->getConfigKey();
            }
            sort($configKeys);
            return $configKeys;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_all_config_keys');
        }
        return $value;
    }

    /**
     * Get all available config keys.
     *
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllConfigs() {
        // Gets a cache value and sets it if it doesn't already exist.
        $value = $this->cache->get('cache_all_configs', function (ItemInterface $item) {
            // TODO should be a config.
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 365 days.
            $recipesRepo = new RecipesConfigurationRepository($this->managerRegistry);
            $dbValue =  $recipesRepo->findAll();
            sort($dbValue);
            return $dbValue;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_all_configs');
        }
        return $value;
    }

    /**
     * Check if a config is set.
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isConfigSetKey($key): bool {
        if ($key !== '') {
            $isSet = $this->getConfigKey($key) ?? false;
        }  else {
            $isSet = false;
        }
        return $isSet;
    }

    /**
     * Check if a config is set.
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isConfigSetValue($value = ''): bool {
        if ($value !== '') {
            $isSet = $this->getConfigValue($value) ?? false;
        }  else {
            $isSet = false;
        }
        return $isSet;
    }
}
