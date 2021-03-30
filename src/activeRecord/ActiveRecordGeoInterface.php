<?php

namespace kriss\geo\activeRecord;

use kriss\geo\client\GeoClientInterface;

interface ActiveRecordGeoInterface
{
    /**
     * @return string
     */
    public static function getGeoKey();

    /**
     * @return GeoClientInterface
     */
    public static function getGeoClient();

    /**
     * 获取坐标经纬度
     * @return array|null [$lng, $lat]
     */
    public function getGeoLngLat();

    /**
     * 获取坐标点的名称
     * @return string
     */
    public function getGeoMember();
}