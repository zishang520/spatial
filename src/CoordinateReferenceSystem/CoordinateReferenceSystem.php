<?php

namespace luoyy\Spatial\CoordinateReferenceSystem;

use luoyy\Spatial\Contracts\JsonUnserializable;
use luoyy\Spatial\Exception\UnserializationException;

/**
 * 坐标参考系（CRS）对象基类。
 *
 * @deprecated 1.1 规范已移除 CRS，GeoJSON 标准不再推荐使用 'crs' 字段。
 * @see https://www.rfc-editor.org/rfc/rfc7946#appendix-B.1
 * @see http://www.geojson.org/geojson-spec.html#coordinate-reference-system-objects
 * @since 1.0
 */
abstract class CoordinateReferenceSystem implements \JsonSerializable, JsonUnserializable
{
    /**
     * @var array CRS 属性数组
     */
    protected array $properties;

    /**
     * @var string CRS 类型
     */
    protected string $type;

    /**
     * 获取 CRS 属性。
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * 获取 CRS 类型。
     */
    abstract public function getType(): string;

    /**
     * 序列化为 GeoJSON 数组。
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->getType(),
            'properties' => $this->properties,
        ];
    }

    /**
     * 反序列化 CRS。
     *
     * @throws UnserializationException
     */
    final public static function jsonUnserialize(mixed $json): CoordinateReferenceSystem
    {
        if (! \is_array($json) && ! \is_object($json)) {
            throw UnserializationException::invalidValue('CRS', $json, 'array or object');
        }

        $json = new \ArrayObject($json);

        if (! $json->offsetExists('type')) {
            throw UnserializationException::missingProperty('CRS', 'type', 'string');
        }

        if (! $json->offsetExists('properties')) {
            throw UnserializationException::missingProperty('CRS', 'properties', 'array or object');
        }

        $type = (string) $json['type'];
        $properties = $json['properties'];

        switch ($type) {
            case 'link':
                return Linked::jsonUnserializeFromProperties($properties);

            case 'name':
                return Named::jsonUnserializeFromProperties($properties);
        }

        throw UnserializationException::unsupportedType('CRS', $type);
    }

    /**
     * 工厂方法：通过属性反序列化 CRS。
     *
     * @throws \BadMethodCallException
     */
    protected static function jsonUnserializeFromProperties(mixed $properties): CoordinateReferenceSystem
    {
        throw new \BadMethodCallException(\sprintf('%s must be overridden in a child class', __METHOD__));
    }
}
