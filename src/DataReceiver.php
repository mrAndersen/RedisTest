<?php


class DataReceiver {

    const INTERNAL_SEPARATOR = ":";
    const INTERNAL_PREFIX = "data_receiver";
    const INTERNAL_KEY_PERIOD_MAP_PREFIX = "map";
    const INTERNAL_KEY_LAST_UPDATE_PREFIX = "map_updates";

    /** @var $client Predis\Client */
    private $client = null;
    private $keyPeriodMap = [];
    private $keyLastUpdates = [];
    private $keyData = [];

    //Возьмем из parameters.yml конечно же, но поскольку у нас тут нету симфони, объявим тут
    private $redisConfig = [
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379
    ];

    function __construct()
    {
        $this->client = !$this->client ?  new Predis\Client($this->redisConfig) : $this->client;
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getKeyLastUse($key)
    {
        $this->getInternalData();
        return $this->keyLastUpdates[$key];
    }

    /**
     * @param $key
     * @return bool
     */
    private function setKeyLastUse($key)
    {
        $this->getInternalData();
        $this->keyLastUpdates[$key] = microtime(true);
        return $this->setInternalData();
    }

    /**
     * @return bool
     */
    private function setInternalData()
    {
        try{
            //Внесли редис данные по ключам и их последним обращениям
            $this->client->set($this::INTERNAL_PREFIX.$this::INTERNAL_SEPARATOR.$this::INTERNAL_KEY_PERIOD_MAP_PREFIX,serialize($this->keyPeriodMap));
            $this->client->set($this::INTERNAL_PREFIX.$this::INTERNAL_SEPARATOR.$this::INTERNAL_KEY_LAST_UPDATE_PREFIX,serialize($this->keyLastUpdates));

            return true;
        }catch(Exception $e){
            return false;
        }
    }

    /**
     * @return bool
     */
    private function getInternalData()
    {
        try{
            $this->keyPeriodMap =  unserialize($this->client->get($this::INTERNAL_PREFIX.$this::INTERNAL_SEPARATOR.$this::INTERNAL_KEY_PERIOD_MAP_PREFIX));
            $this->keyLastUpdates =  unserialize($this->client->get($this::INTERNAL_PREFIX.$this::INTERNAL_SEPARATOR.$this::INTERNAL_KEY_LAST_UPDATE_PREFIX));

            return true;
        }catch(Exception $e){
            return false;
        }
    }


    /**
     * @param $key
     * @param $value
     * @param $timeoutPeriod
     * @return bool
     */
    public function set($key, $value, $timeoutPeriod)
    {
        $this->getInternalData();
        $this->client->set($key,$value);

        $this->keyData[$key] = $value;
        $this->keyPeriodMap[$key] = $timeoutPeriod;
        $this->keyLastUpdates[$key] = 0;

        return $this->setInternalData();
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     */
    public function get($key)
    {
        if($this->client->exists($key)){
            $minInterval    = $this->keyPeriodMap[$key];
            $lastUsed       = $this->getKeyLastUse($key);
            $current        = microtime(true);

            if($current - $lastUsed > $minInterval){
                $this->keyData[$key] = $this->client->get($key);
                $this->setKeyLastUse($key);
            }

            return $this->keyData[$key];
        }else{
            throw new Exception('No key found');
        }

    }

    public function getClient()
    {
        return $this->client;
    }














}