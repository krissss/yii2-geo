<?php

namespace kriss\geo\client;

use LogicException;
use yii\base\BaseObject;
use yii\di\Instance;
use yii\redis\Connection;

class RedisGeoClient extends BaseObject implements GeoClientInterface
{
    /**
     * @var Connection
     */
    public $redis;
    /**
     * @var string
     */
    public $prefix = 'geo_';

    public function init()
    {
        parent::init();

        $this->redis = Instance::ensure($this->redis, Connection::class);
    }

    private $key;

    /**
     * @inheritDoc
     */
    public function key($key)
    {
        $new = clone $this;

        $new->key = $this->prefix . $key;
        return $new;
    }

    /**
     * @return string
     */
    private function getKey()
    {
        if (!$this->key) {
            throw new LogicException('key must be set first');
        }
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function add($member, $lng, $lat)
    {
        $this->redis->geoadd($this->getKey(), $lng, $lat, $member);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function remove($member)
    {
        $this->redis->zrem($this->getKey(), $member);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(...$members)
    {
        $data = $this->redis->geopos($this->getKey(), ...$members);
        $result = [];
        foreach ($members as $index => $member) {
            $result[$member] = $data[$index];
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getDistance($member1, $member2, $unit = 'm')
    {
        return $this->redis->geodist($this->getKey(), $member1, $member2, $unit);
    }

    /**
     * @inheritDoc
     */
    public function getMembersInRadius($lng, $lat, $radius, $unit = 'm', $options = [])
    {
        $options = array_merge([
            'with' => [], // DIST/COORD/HASH
            'sort' => null, // ASC/DESC
            'count' => null, // 数量
        ], $options);
        $config = [];
        if ($options['with']) {
            foreach ((array)$options['with'] as $with) {
                $config[] = 'WITH' . strtoupper($with);
            }
        }
        if ($options['sort']) {
            $config[] = strtoupper($options['sort']);
        }
        if ($options['count']) {
            $config[] = 'COUNT';
            $config[] = $options['count'];
        }

        return $this->redis->georadius($this->getKey(), $lng, $lat, $radius, $unit, ...$config);
    }
}