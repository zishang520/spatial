<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

/**
 * Point geometry object.
 *
 * Coordinates consist of a single position.
 *
 * @see https://www.geojson.org/geojson-spec.html#point
 */
class Point extends Geometry
{
    /**
     * 构造函数。
     *
     * @param array<float|int> $position 坐标数组，至少包含经度和纬度
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     * @throws \InvalidArgumentException 如果坐标数组元素不足或类型不正确
     */
    public function __construct(array $position, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        if (\count($position) < 2) {
            throw new \InvalidArgumentException('Position requires at least two elements');
        }

        $this->coordinates = \array_map(
            static function ($value): float|int {
                if (! is_numeric($value) || is_nan((float) $value)) {
                    throw new \InvalidArgumentException('Coordinate elements must be valid integers or floating-point numbers.');
                }
                return is_int($value) || is_float($value) ? $value : (strpos($value, '.') !== false ? (float) $value : (int) $value);
            },
            $position
        );

        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::POINT;
    }

    /**
     * 获取经度。
     */
    public function getLongitude(): float|int
    {
        return $this->coordinates[0];
    }

    /**
     * 获取纬度。
     */
    public function getLatitude(): float|int
    {
        return $this->coordinates[1];
    }

    /**
     * 获取高度（可选）。
     */
    public function getAltitude(): float|int
    {
        return $this->coordinates[2] ?? 0;
    }
}
