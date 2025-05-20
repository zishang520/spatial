<?php

namespace luoyy\Spatial\CoordinateReferenceSystem;

use luoyy\Spatial\Exception\UnserializationException;

/**
 * 命名型坐标参考系对象。
 *
 * @deprecated 1.1 规范已移除 CRS，GeoJSON 标准不再推荐使用 'crs' 字段。
 * @see http://www.geojson.org/geojson-spec.html#named-crs
 * @since 1.0
 */
class Named extends CoordinateReferenceSystem
{
    /**
     * 构造函数。
     *
     * @param string $name 坐标系名称
     */
    public function __construct(string $name)
    {
        $this->properties = ['name' => $name];
    }

    /**
     * 获取类型。
     */
    public function getType(): string
    {
        return 'name';
    }

    /**
     * 工厂方法：通过属性反序列化 Named CRS 对象。
     *
     * @throws UnserializationException
     */
    protected static function jsonUnserializeFromProperties(mixed $properties): CoordinateReferenceSystem
    {
        if (! \is_array($properties) && ! \is_object($properties)) {
            throw UnserializationException::invalidProperty('Named CRS', 'properties', $properties, 'array or object');
        }

        $properties = new \ArrayObject($properties);

        if (! $properties->offsetExists('name')) {
            throw UnserializationException::missingProperty('Named CRS', 'properties.name', 'string');
        }

        $name = (string) $properties['name'];

        return new self($name);
    }
}
