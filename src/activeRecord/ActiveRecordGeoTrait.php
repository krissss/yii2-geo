<?php

namespace kriss\geo\activeRecord;

use kriss\geo\client\GeoClientInterface;
use Yii;
use yii\db\ActiveRecord;

/**
 * trait for ActiveRecordGeoInterface
 * Must be set in yii/db/ActiveRecord
 * @mixin ActiveRecord|ActiveRecordGeoInterface
 */
trait ActiveRecordGeoTrait
{
    /**
     * @inheritDoc
     */
    public static function getGeoKey()
    {
        return static::tableName();
    }

    /**
     * @inheritDoc
     */
    public static function getGeoClient()
    {
        /** @var GeoClientInterface $client */
        $client = Yii::$app->get('geoClient');
        return $client->key(static::getGeoKey());
    }

    /**
     * @inheritDoc
     */
    public function getGeoMember()
    {
        if (is_array($this->primaryKey)) {
            return implode('_', array_values($this->primaryKey));
        }
        return $this->primaryKey;
    }

    /**
     * 更新 geo 信息
     */
    public function updateGeoInfo()
    {
        $position = $this->getGeoLngLat();
        $client = static::getGeoClient();
        if (!$position || count($position) !== 2) {
            $client->remove($this->getGeoMember());
            return;
        }
        list($lng, $lat) = $position;
        $client->add($this->getGeoMember(), $lng, $lat);
    }

    /**
     * 删除 geo 信息
     */
    public function deleteGeoInfo()
    {
        static::getGeoClient()->remove($this->getGeoMember());
    }
}