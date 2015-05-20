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

    private function getKeyLastUse($key)
    {
        $this->getInternalData();
        return $this->keyLastUpdates[$key];
    }

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
     * @param $period
     * @return bool
     * @throws Exception
     */
    public function setInterval($key,$period)
    {
        $this->getInternalData();

        if($this->client->exists($key)){
            $this->keyPeriodMap[$key] = $period;
            $this->keyLastUpdates[$key] = 0;

            return $this->setInternalData();
        }else{
            throw new Exception('No key found');
        }

    }

    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function get($key)
    {
        $this->getInternalData();

        if(in_array($key,array_keys($this->keyLastUpdates))){
            //Значит у нас есть ограничение на ключ
            $minInterval    = $this->keyPeriodMap[$key];
            $lastUsed       = $this->getKeyLastUse($key);
            $current        = microtime(true);

            if($current - $lastUsed > $minInterval){
                //Отдаем ключ и ставим флаг о том что его юзнули
                $this->setKeyLastUse($key);
                return $this->client->get($key);
            }else{
                return 'Too many requests';
            }
        }else{
            //Значит ограничения нету, отдаем как есть
            return $this->client->get($key);
        }
    }

    public function getClient()
    {
        return $this->client;
    }














}