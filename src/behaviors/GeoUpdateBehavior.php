<?php

namespace kriss\geo\behaviors;

use kriss\geo\activeRecord\ActiveRecordGeoInterface;
use LogicException;
use yii\base\Behavior;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;

/**
 * 配合 ActiveRecordGeoTrait
 * in behaviors()
 * [
 *     'geo' => [
 *          'class' => GeoUpdateBehavior::class,
 *          'updateTriggerAttributes' => ['my_lng_attribute', 'my_lat_attribute'],
 *          'deleteAttributes' => ['status' => [99, 10], 'is_deleted' => true],
 *      ],
 * ]
 */
class GeoUpdateBehavior extends Behavior
{
    /**
     * 更新数据时触发更新 geo 数据时的字段
     * 为 null 时任意字段更新均触发
     * @var array|null
     */
    public $updateTriggerAttributes = [];
    /**
     * 定义为删除的字段
     * ['status' => [10, 99]]
     * @var array
     */
    public $deleteAttributes = [];
    /**
     * @see ActiveRecordGeoTrait::updateGeoInfo()
     * @var string|callable
     */
    public $updateGeoInfo = 'updateGeoInfo';
    /**
     * @see ActiveRecordGeoTrait::deleteGeoInfo()
     * @var string|callable
     */
    public $deleteGeoInfo = 'deleteGeoInfo';

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'update',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'update',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'delete',
        ];
    }

    public function update($event)
    {
        if (!$owner = $this->getValidOwner()) {
            return;
        }

        // 检查数据变化
        if ($this->updateTriggerAttributes !== null) {
            $updateTriggerAttributes = array_unique(array_merge($this->updateTriggerAttributes, array_keys($this->deleteAttributes)));
            if (count($updateTriggerAttributes) === 0) {
                throw new LogicException('updateTriggerAttributes can not be empty array');
            }
            if ($event instanceof AfterSaveEvent) {
                if (!array_intersect($updateTriggerAttributes, array_keys($event->changedAttributes))) {
                    // 非检测字段数据变更
                    return;
                }
            }
        }

        // 数据是否是删除
        foreach ($this->deleteAttributes as $attribute => $value) {
            if (!$owner->hasAttribute($attribute)) {
                continue;
            }
            if (in_array($owner->getAttribute($attribute), (array)$value)) {
                // 触发删除操作
                $this->delete($event, false);
                return;
            }
        }

        $this->callMethod($this->updateGeoInfo, $event);
    }

    public function delete($event, $checkOwner = true)
    {
        if ($checkOwner && !$this->getValidOwner()) {
            return;
        }

        $this->callMethod($this->deleteGeoInfo, $event);
    }

    /**
     * @return BaseActiveRecord|null
     */
    protected function getValidOwner()
    {
        if (!$this->owner instanceof ActiveRecordGeoInterface) {
            return null;
        }
        if (!$this->owner instanceof BaseActiveRecord) {
            throw new LogicException(static::class . ' must set in ' . BaseActiveRecord::class);
        }

        return $this->owner;
    }

    /**
     * @param $method
     * @param $event
     */
    protected function callMethod($method, $event)
    {
        if (is_callable($method)) {
            call_user_func($method, $this->owner, $event);
            return;
        }
        if (!$this->owner->hasMethod($method)) {
            throw new LogicException(get_class($this->owner) . ' has no method named: ' . $method);
        }
        $this->owner->{$method}($event);
    }
}