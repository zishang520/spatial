<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;
use luoyy\Spatial\Utils\GeometryUtils;

/**
 * 多多边形（MultiPolygon）几何对象。
 *
 * 坐标由多个 Polygon 的坐标数组组成。
 *
 * @see http://www.geojson.org/geojson-spec.html#multipolygon
 * @since 1.0
 */
class MultiPolygon extends Geometry
{
    /**
     * 构造函数。
     *
     * @param array<Polygon|array<LinearRing|array<Point|array<int|float>>>> $polygons Polygon 对象数组或坐标数组。
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     */
    public function __construct(array $polygons, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        $this->coordinates = GeometryUtils::normalizeMultiPolygonCoordinates($polygons);
        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     *
     * @return TypeEnum 类型枚举，恒为 TypeEnum::MULTI_POLYGON。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::MULTI_POLYGON;
    }
}
