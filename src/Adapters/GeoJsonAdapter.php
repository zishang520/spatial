<?php

namespace luoyy\Spatial\Adapters;

use luoyy\Spatial\Geometry\Geometry;

/**
 * GeoJSON 适配器。
 *
 * 提供 Geometry 对象与 GeoJSON 数组的互转能力。
 */
class GeoJsonAdapter
{
    /**
     * 将 Geometry 对象转为 GeoJSON 数组。
     *
     * @param Geometry $geometry 几何对象
     * @return array GeoJSON 数组
     */
    public static function convert(Geometry $geometry): array
    {
        return $geometry->jsonSerialize();
    }

    /**
     * 将 GeoJSON 数组解析为 Geometry 对象。
     *
     * @param array $geojson GeoJSON 数组
     * @return Geometry 几何对象
     */
    public static function parse(array $geojson)
    {
        return Geometry::jsonUnserialize($geojson);
    }
}
