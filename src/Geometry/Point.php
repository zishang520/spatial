<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

use function count;
use function is_float;
use function is_int;

/**
 * Point geometry object.
 *
 * Coordinates consist of a single position.
 *
 * @see http://www.geojson.org/geojson-spec.html#point
 * @since 1.0
 */
class Point extends Geometry
{
    /**
     * 构造函数。
     *
     * @param array<float|int> $position 坐标数组，至少包含经度和纬度。
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     * @throws \InvalidArgumentException 如果坐标数组元素不足或类型不正确。
     */
    public function __construct(array $position, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        if (count($position) < 2) {
            throw new \InvalidArgumentException('Position requires at least two elements');
        }

        foreach ($position as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new \InvalidArgumentException('Position elements must be integers or floats');
            }
        }

        $this->coordinates = $position;

        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     *
     * @return TypeEnum 类型枚举，恒为 TypeEnum::POINT。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::POINT;
    }

    /**
     * 获取经度。
     *
     * @return float|int 经度值。
     */
    public function getLongitude(): float|int
    {
        return $this->coordinates[0];
    }

    /**
     * 获取纬度。
     *
     * @return float|int 纬度值。
     */
    public function getLatitude(): float|int
    {
        return $this->coordinates[1];
    }

    /**
     * 获取高度（可选）。
     *
     * @return float|int 高度值，若未设置则为 0。
     */
    public function getAltitude(): float|int
    {
        return $this->coordinates[2] ?? 0;
    }
}
