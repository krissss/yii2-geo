<?php

namespace kriss\geo\client;

interface GeoClientInterface
{
    /**
     * 设置 key
     * @param string $key
     * @return $this
     */
    public function key($key);

    /**
     * 增加坐标
     * @param string|int $member
     * @param float|string $lng
     * @param float|string $lat
     * @return $this
     */
    public function add($member, $lng, $lat);

    /**
     * 移除坐标
     * @param string|int $member
     * @return $this
     */
    public function remove($member);

    /**
     * 获取坐标
     * @param string|int ...$members
     * @return array [$member => [$lng, $lat], $member => [$lng, $lat]]
     */
    public function getPosition(...$members);

    /**
     * 获取两个坐标之间的距离
     * @param string|int $member1
     * @param string|int $member2
     * @param string $unit
     * @return float|string
     */
    public function getDistance($member1, $member2, $unit = 'm');

    /**
     * 获取范围内坐标
     * @param float|string $lng
     * @param float|string $lat
     * @param int $radius
     * @param string $unit
     * @param array $options
     * @return array
     */
    public function getMembersInRadius($lng, $lat, $radius, $unit = 'm', $options = []);
}