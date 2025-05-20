<?php

namespace luoyy\Spatial\Utils;

use luoyy\Spatial\Geometry\LinearRing;
use luoyy\Spatial\Geometry\Point;
use luoyy\Spatial\Geometry\Polygon;

/**
 * 几何工具类。
 *
 * 提供多边形、复合多边形等几何结构的归一化辅助方法。
 */
class GeometryUtils
{
    /**
     * 归一化 Polygon 构造参数，确保每个元素为 LinearRing 并返回其 coordinates。
     *
     * @param array<LinearRing|array<Point|array<int|float>>> $linearRings 线性环数组
     */
    public static function normalizePolygonCoordinates(array $linearRings): array
    {
        $result = [];
        foreach ($linearRings as $linearRing) {
            if (! $linearRing instanceof LinearRing) {
                $linearRing = new LinearRing($linearRing);
            }
            $result[] = $linearRing->getCoordinates();
        }
        return $result;
    }

    /**
     * 归一化 MultiPolygon 构造参数，确保每个元素为 Polygon 并返回其 coordinates。
     *
     * @param array<Polygon|array<LinearRing|array<Point|array<int|float>>>> $polygons 多边形数组
     */
    public static function normalizeMultiPolygonCoordinates(array $polygons): array
    {
        $result = [];
        foreach ($polygons as $polygon) {
            if (! $polygon instanceof Polygon) {
                $polygon = new Polygon($polygon);
            }
            $result[] = $polygon->getCoordinates();
        }
        return $result;
    }
}
