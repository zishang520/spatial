<?php

namespace luoyy\Spatial;

use JsonSerializable;
use luoyy\Spatial\Contracts\JsonUnserializable;
use luoyy\Spatial\Exception\UnserializationException;

use function count;
use function is_array;
use function is_float;
use function is_int;

/**
 * 边界框对象，表示空间范围的最小矩形。
 * 支持 GeoJSON 标准。
 */
class BoundingBox implements JsonSerializable, JsonUnserializable
{
    /**
     * @var array<float|int> 边界值数组。
     */
    protected array $bounds;

    /**
     * 构造函数。
     *
     * @param array<float|int> $bounds 边界值数组，顺序为[minX, minY, maxX, maxY] 或[minX, minY, minZ, maxX, maxY, maxZ]
     * @throws \InvalidArgumentException 参数不足或类型错误
     */
    public function __construct(array $bounds)
    {
        $count = count($bounds);

        if ($count < 4) {
            throw new \InvalidArgumentException('BoundingBox requires at least four values');
        }

        if ($count % 2) {
            throw new \InvalidArgumentException('BoundingBox requires an even number of values');
        }

        foreach ($bounds as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new \InvalidArgumentException('BoundingBox values must be integers or floats');
            }
        }

        for ($i = 0; $i < ($count / 2); $i++) {
            if ($bounds[$i] > $bounds[$i + ($count / 2)]) {
                throw new \InvalidArgumentException('BoundingBox min values must precede max values');
            }
        }

        $this->bounds = $bounds;
    }

    /**
     * 获取边界值数组。
     *
     * @return array<float|int>
     */
    public function getBounds(): array
    {
        return $this->bounds;
    }

    /**
     * 序列化为 GeoJSON 数组。
     *
     */
    public function jsonSerialize(): array
    {
        return $this->bounds;
    }

    /**
     * 反序列化 BoundingBox。
     *
     * @throws \luoyy\Spatial\Exception\UnserializationException
     */
    final public static function jsonUnserialize(mixed $json): static
    {
        if (! is_array($json)) {
            throw UnserializationException::invalidValue('BoundingBox', $json, 'array');
        }

        return new static($json);
    }
}
