<?php

namespace luoyy\Spatial;

use ArrayObject;
use JsonSerializable;
use luoyy\Spatial\Contracts\JsonUnserializable;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;
use luoyy\Spatial\Exception\UnserializationException;

use function array_map;
use function is_array;
use function is_object;
use function sprintf;
use function strncmp;

/**
 * GeoJson 对象基类，所有地理对象的基础。
 * 支持类型、边界框、坐标参考系等标准属性。
 */
abstract class GeoJson implements JsonSerializable, JsonUnserializable
{
    /**
     * @var BoundingBox|null 边界框对象
     */
    protected ?BoundingBox $boundingBox = null;

    /**
     * @var CoordinateReferenceSystem|null 坐标参考系对象
     */
    protected ?CoordinateReferenceSystem $crs = null;

    /**
     * 获取类型。
     *
     * @return TypeEnum
     */
    abstract public function getType(): TypeEnum;

    /**
     * 获取边界框对象。
     *
     * @return BoundingBox|null
     */
    public function getBoundingBox(): ?BoundingBox
    {
        return $this->boundingBox;
    }

    /**
     * 获取坐标参考系对象。
     *
     * @return CoordinateReferenceSystem|null
     */
    public function getCrs(): ?CoordinateReferenceSystem
    {
        return $this->crs;
    }

    /**
     * 序列化为 GeoJSON 数组。
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $json = ['type' => $this->getType()->value];

        if (isset($this->crs)) {
            $json['crs'] = $this->crs->jsonSerialize();
        }

        if (isset($this->boundingBox)) {
            $json['bbox'] = $this->boundingBox->jsonSerialize();
        }

        return $json;
    }

    /**
     * 反序列化 GeoJson 对象。
     *
     * @param mixed $json
     * @return static
     * @throws UnserializationException
     */
    final public static function jsonUnserialize(mixed $json): static
    {
        if (!is_array($json) && !is_object($json)) {
            throw UnserializationException::invalidValue('GeoJson', $json, 'array or object');
        }

        $json = new ArrayObject($json);

        if (!$json->offsetExists('type') || !is_string($json['type'])) {
            throw UnserializationException::missingProperty('GeoJson', 'type', 'string');
        }

        $type = (string) $json['type'];
        $args = [];

        $typeEnum = TypeEnum::tryFrom($type);
        match ($typeEnum) {
            TypeEnum::LINE_STRING,
            TypeEnum::MULTI_LINE_STRING,
            TypeEnum::MULTI_POINT,
            TypeEnum::MULTI_POLYGON,
            TypeEnum::POINT,
            TypeEnum::POLYGON => (function () use ($json, $type, &$args) {
                if (!$json->offsetExists('coordinates')) {
                    throw UnserializationException::missingProperty($type, 'coordinates', 'array');
                }
                if (!is_array($json['coordinates'])) {
                    throw UnserializationException::invalidProperty($type, 'coordinates', $json['coordinates'], 'array');
                }
                $args[] = $json['coordinates'];
            })(),
            TypeEnum::FEATURE => (function () use ($json, $type, &$args) {
                $geometry = $json['geometry'] ?? null;
                $properties = $json['properties'] ?? null;
                $id = $json['id'] ?? null;
                if ($geometry !== null && !is_array($geometry) && !is_object($geometry)) {
                    throw UnserializationException::invalidProperty($type, 'geometry', $geometry, 'array or object');
                }
                if ($properties !== null && !is_array($properties) && !is_object($properties)) {
                    throw UnserializationException::invalidProperty($type, 'properties', $properties, 'array or object');
                }
                if ($id !== null && !is_int($id) && !is_string($id)) {
                    throw UnserializationException::invalidProperty($type, 'id', $id, 'int or string');
                }
                $args[] = $geometry !== null ? static::jsonUnserialize($geometry) : null;
                $args[] = $properties !== null ? (array) $properties : null;
                $args[] = $id;
            })(),
            TypeEnum::FEATURE_COLLECTION => (function () use ($json, $type, &$args) {
                if (!$json->offsetExists('features')) {
                    throw UnserializationException::missingProperty($type, 'features', 'array');
                }
                if (!is_array($json['features'])) {
                    throw UnserializationException::invalidProperty($type, 'features', $json['features'], 'array');
                }
                $args[] = array_map(static::jsonUnserialize(...), $json['features']);
            })(),
            TypeEnum::GEOMETRY_COLLECTION => (function () use ($json, $type, &$args) {
                if (!$json->offsetExists('geometries')) {
                    throw UnserializationException::missingProperty($type, 'geometries', 'array');
                }
                if (!is_array($json['geometries'])) {
                    throw UnserializationException::invalidProperty($type, 'geometries', $json['geometries'], 'array');
                }
                $args[] = array_map(static::jsonUnserialize(...), $json['geometries']);
            })(),
            default => throw UnserializationException::unsupportedType('GeoJson', $type),
        };

        if (isset($json['bbox'])) {
            $args[] = BoundingBox::jsonUnserialize($json['bbox']);
        }

        if (isset($json['crs'])) {
            $args[] = CoordinateReferenceSystem::jsonUnserialize($json['crs']);
        }

        if (!class_exists($class = sprintf('%s\%s\%s', __NAMESPACE__, (strncmp('Feature', $type, 7) === 0 ? 'Feature' : 'Geometry'), $type), true)) {
            throw UnserializationException::unsupportedType('GeoJson', $type);
        };

        return new $class(...$args);
    }

    /**
     * 设置可选构造参数（CRS、BoundingBox、坐标系统类型）。
     *
     * @param array $args
     */
    protected function setOptionalConstructorArgs(array $args): void
    {
        foreach ($args as $arg) {
            if ($arg instanceof CoordinateReferenceSystem) {
                $this->crs = $arg;
            }

            if ($arg instanceof BoundingBox) {
                $this->boundingBox = $arg;
            }
        }
    }
}
