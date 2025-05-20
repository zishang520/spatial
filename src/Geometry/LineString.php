<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

/**
 * 线串（LineString）几何对象。
 *
 * 坐标由至少两个点组成。
 *
 * @see http://www.geojson.org/geojson-spec.html#linestring
 * @since 1.0
 */
class LineString extends MultiPoint
{
    /**
     * 构造函数。
     *
     * @param array<Point|array<float|int>> $positions 点数组或坐标数组
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     * @throws \InvalidArgumentException 如果点数少于2
     */
    public function __construct(array $positions, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        if (\count($positions) < 2) {
            throw new \InvalidArgumentException('LineString requires at least two positions');
        }
        parent::__construct($positions, ...$args);
    }

    /**
     * 获取类型。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::LINE_STRING;
    }
}
