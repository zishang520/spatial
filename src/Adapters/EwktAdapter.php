<?php

namespace luoyy\Spatial\Adapters;

use luoyy\Spatial\Geometry\Geometry;
use luoyy\Spatial\Geometry\GeometryCollection;
use luoyy\Spatial\Geometry\MultiLineString;
use luoyy\Spatial\Geometry\MultiPoint;
use luoyy\Spatial\Geometry\MultiPolygon;
use luoyy\Spatial\Geometry\Point;
use luoyy\Spatial\Geometry\Polygon;

/**
 * EWKT（扩展WKT）适配器。
 *
 * 支持带 SRID 的 WKT 编解码，兼容 PostGIS 等数据库格式。
 */
class EwktAdapter
{
    /**
     * 将 Geometry 对象转为 EWKT 字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param int|null $srid 空间参考ID，可选
     * @param bool $withAltitude 是否包含高程
     */
    public static function convert(Geometry|GeometryCollection $geometry, ?int $srid = null, bool $withAltitude = true): string
    {
        $srid = $srid ?? ($geometry instanceof Geometry ? $geometry->getSrid() : null);
        $wkt = WktAdapter::convert($geometry, $withAltitude);
        if ($srid !== null) {
            return "SRID={$srid};" . $wkt;
        }
        return $wkt;
    }

    /**
     * 解析 EWKT 字符串为 Geometry 对象。
     *
     * @param string $ewkt EWKT 字符串
     */
    public static function parse(string $ewkt): Point|Polygon|MultiPoint|MultiLineString|MultiPolygon|GeometryCollection
    {
        $srid = null;
        $wkt = $ewkt;
        if (stripos($ewkt, 'SRID=') === 0) {
            $parts = explode(';', $ewkt, 2);
            $srid = (int) substr($parts[0], 5);
            $wkt = $parts[1] ?? '';
        }
        $geometry = WktAdapter::parse($wkt);
        if ($srid !== null && $geometry instanceof Geometry) {
            $geometry->setSrid($srid);
        }
        return $geometry;
    }
}
