<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

use function array_map;

/**
 * 多点（MultiPoint）几何对象。
 *
 * 坐标由多个点的位置数组组成。
 *
 * @see http://www.geojson.org/geojson-spec.html#multipoint
 * @since 1.0
 */
class MultiPoint extends Geometry
{
    /**
     * 构造函数。
     *
     * @param array<Point|array<float|int>> $positions 点对象数组或坐标数组。
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     */
    public function __construct(array $positions, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        $this->coordinates = array_map(static fn($point) => ((! $point instanceof Point) ? (new Point($point)) : $point)->getCoordinates(), $positions);
        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     *
     * @return TypeEnum 类型枚举，恒为 TypeEnum::MULTI_POINT。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::MULTI_POINT;
    }
}
