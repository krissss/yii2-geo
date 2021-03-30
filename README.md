Yii2 Geo
======================
Yii2 Geo

Installation
------------

```
composer require kriss/yii2-geo
```

Usage
-----

1. 设置 geoClient，目前支持 `RedisGeoClient`

```config
'components' => [
    // others
    'geoClient' => [
        'class' => 'kriss\geo\client\RedisGeoClient',
        //'redis' => 'redis', // 使用已有的 redis 组件
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'database' => 0,
        ],
        //'prefix' => 'geo_', // key 前缀
    ]
    // others
],
```

2. 设置 ActiveRecord

假设有模型如下：

```php
use yii\db\ActiveRecord;

class User extends ActiveRecord 
{
}
```

修改为：

```php
use yii\db\ActiveRecord;
use kriss\geo\activeRecord\ActiveRecordGeoInterface;
use kriss\geo\activeRecord\ActiveRecordGeoTrait;

class User extends ActiveRecord implements ActiveRecordGeoInterface
{
    use ActiveRecordGeoTrait;
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // 自动更新经纬度数据
        $behaviors['geo'] = [
            'class' => GeoUpdateBehavior::class,
            'updateTriggerAttributes' => ['lng', 'lat'], // 触发更新的字段
            'deleteAttributes' => ['is_deleted' => true], // 删除的字段和值
        ];
        
        return $behaviors;
    }
    
    /**
     * @inheritDoc
     */
    public function getGeoLngLat()
    {
        return [$this->lng, $this->lat]; // 替换成自己的经纬度字段
    }
}
```

3. 使用

自动 geo 信息更新

```php
$model = new User();
$model->lnt = 116.306726;
$model->lat = 40.067816;
$model->save(); // 触发新增 geo 信息

$model->lnt = 117.306726;
$model->save(); // 触发更新 geo 信息

$model->name = 'aaa';
$model->save(); // 由于修改的字段为配置为触发，因此不会触发更新 geo 信息

$model->is_deleted = 1;
$model->save(); // 触发删除 geo 信息

$model->delete(); // 触发删除 geo 信息
```

获取 geo 相关信息

```php
// 获取 GeoClient
$client = User::getGeoClient();
// 获取经纬度
$positions = $client->getPosition(1); // 有值时：[1 => ["116.30672782659531", "41.067817101970839"]]，无值时：[1 => []]
$positions = $client->getPosition(1, 2, 3); // 同时获取多个值
// 计算距离
$distance = $client->getDistance(33, 34); // "111226.0989"，单位默认为米
$distance = $client->getDistance(33, 34, 'km'); // "111.2261"，单位千米
// 获取范围内的
$members = $client->getMembersInRadius('116.427411','39.985579', 10, 'km'); // 10千米内的坐标：['34', '35']
$members = $client->getMembersInRadius('116.427411','39.985579', 10, 'km', ['sort' => 'ASC']); // 10千米内的坐标，并按照距离远近排序：['35', '34']
$members = $client->getMembersInRadius('116.427411','39.985579', 10, 'km', ['with' => 'DIST']); // 10千米内的坐标，并附带距离值：[['34' => '13.7595'], ['34' => '13.7591']]
$members = $client->getMembersInRadius('116.427411','39.985579', 10, 'km', ['count' => 1]); // 10千米内的坐标，最多返回1个：['34']
```