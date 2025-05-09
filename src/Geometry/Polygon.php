<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;
use luoyy\Spatial\Utils\GeometryUtils;

/**
 * 多边形（Polygon）几何对象。
 *
 * 坐标由一个或多个 LinearRing（线性环）坐标数组组成。
 *
 * @see http://www.geojson.org/geojson-spec.html#polygon
 * @since 1.0
 */
class Polygon extends Geometry
{
    /**
     * 构造函数。
     *
     * @param array<LinearRing|array<Point|array<int|float>>> $linearRings LinearRing 对象数组或坐标数组。
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     */
    public function __construct(array $linearRings, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        $this->coordinates = GeometryUtils::normalizePolygonCoordinates($linearRings);
        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     *
     * @return TypeEnum 类型枚举，恒为 TypeEnum::POLYGON。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::POLYGON;
    }
}
