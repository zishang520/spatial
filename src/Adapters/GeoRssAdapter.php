<?php

namespace luoyy\Spatial\Adapters;

use luoyy\Spatial\Geometry\Geometry;
use luoyy\Spatial\Geometry\GeometryCollection;
use luoyy\Spatial\Geometry\LineString;
use luoyy\Spatial\Geometry\MultiLineString;
use luoyy\Spatial\Geometry\MultiPoint;
use luoyy\Spatial\Geometry\MultiPolygon;
use luoyy\Spatial\Geometry\Point;
use luoyy\Spatial\Geometry\Polygon;

/**
 * GeoRSS 适配器。
 *
 * 提供 Geometry 对象与 GeoRSS XML 字符串的互转能力。
 */
class GeoRssAdapter
{
    /**
     * 将 Geometry 或 GeometryCollection 转为 GeoRSS XML 字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param bool $withAltitude 是否包含高程
     * @param string|null $namespace 命名空间前缀
     * @return string GeoRSS XML 字符串
     */
    public static function convert(Geometry|GeometryCollection $geometry, bool $withAltitude = true, ?string $namespace = null): string
    {
        $ns = $namespace ? $namespace . ':' : '';
        if ($geometry instanceof Point) {
            return '<' . $ns . 'point>' . self::formatGeoRssCoordinate($geometry->getCoordinates(), $withAltitude) . '</' . $ns . 'point>';
        }
        if ($geometry instanceof LineString) {
            $coords = [];
            foreach ($geometry->getCoordinates() as $point) {
                $coords[] = self::formatGeoRssCoordinate($point, $withAltitude);
            }
            return '<' . $ns . 'line>' . implode(' ', $coords) . '</' . $ns . 'line>';
        }
        if ($geometry instanceof Polygon) {
            $rings = $geometry->getCoordinates();
            // 只导出第一个环（外环）
            $coords = [];
            foreach ($rings[0] as $point) {
                $coords[] = self::formatGeoRssCoordinate($point, $withAltitude);
            }
            return '<' . $ns . 'polygon>' . implode(' ', $coords) . '</' . $ns . 'polygon>';
        }
        if ($geometry instanceof MultiPoint) {
            $georss = '';
            foreach ($geometry->getCoordinates() as $point) {
                $georss .= '<' . $ns . 'point>' . self::formatGeoRssCoordinate($point, $withAltitude) . '</' . $ns . 'point>';
            }
            return $georss;
        }
        if ($geometry instanceof MultiLineString) {
            $georss = '';
            foreach ($geometry->getCoordinates() as $line) {
                $coords = [];
                foreach ($line as $point) {
                    $coords[] = self::formatGeoRssCoordinate($point, $withAltitude);
                }
                $georss .= '<' . $ns . 'line>' . implode(' ', $coords) . '</' . $ns . 'line>';
            }
            return $georss;
        }
        if ($geometry instanceof MultiPolygon) {
            $georss = '';
            foreach ($geometry->getCoordinates() as $polygon) {
                // 只导出每个多边形的外环
                $coords = [];
                foreach ($polygon[0] as $point) {
                    $coords[] = self::formatGeoRssCoordinate($point, $withAltitude);
                }
                $georss .= '<' . $ns . 'polygon>' . implode(' ', $coords) . '</' . $ns . 'polygon>';
            }
            return $georss;
        }
        if ($geometry instanceof GeometryCollection) {
            $georss = '';
            foreach ($geometry->getGeometries() as $geom) {
                $georss .= self::convert($geom, $withAltitude);
            }
            return $georss;
        }
        return '';
    }

    /**
     * 解析 GeoRSS XML 字符串为 Geometry 对象。
     *
     * @param string $georss GeoRSS XML 字符串
     * @return Geometry|GeometryCollection
     * @throws \InvalidArgumentException 格式不支持或解析失败
     */
    public static function parse(string $georss)
    {
        $xml = simplexml_load_string($georss);
        if (!$xml) {
            throw new \InvalidArgumentException('Invalid GeoRSS');
        }
        // 支持带namespace的标签
        $tag = strtolower($xml->getName());
        $tag = preg_replace('/^.*:/', '', $tag);
        switch ($tag) {
            case 'point':
                $coords = preg_split('/\s+/', trim((string) $xml));
                return new Point([floatval($coords[1]), floatval($coords[0])]);
            case 'line':
                return new LineString(self::parseGeoRssCoordinates((string) $xml));
            case 'polygon':
                return new Polygon([self::parseGeoRssCoordinates((string) $xml)]);
            default:
                // 支持多point/line/polygon混合，带namespace
                $points = [];
                $lines = [];
                $polygons = [];
                foreach ($xml->xpath('//*[local-name()="point"]') as $child) {
                    $coords = preg_split('/\s+/', trim((string) $child));
                    $points[] = [floatval($coords[1]), floatval($coords[0])];
                }
                foreach ($xml->xpath('//*[local-name()="line"]') as $child) {
                    $lines[] = self::parseGeoRssCoordinates((string) $child);
                }
                foreach ($xml->xpath('//*[local-name()="polygon"]') as $child) {
                    $polygons[] = [self::parseGeoRssCoordinates((string) $child)];
                }
                $geoms = [];
                if ($points) {
                    $geoms[] = count($points) === 1 ? new Point($points[0]) : new MultiPoint($points);
                }
                if ($lines) {
                    $geoms[] = count($lines) === 1 ? new LineString($lines[0]) : new MultiLineString($lines);
                }
                if ($polygons) {
                    $geoms[] = count($polygons) === 1 ? new Polygon($polygons[0]) : new MultiPolygon($polygons);
                }
                if (count($geoms) === 1) {
                    return $geoms[0];
                }
                if ($geoms) {
                    return new GeometryCollection($geoms);
                }
                throw new \InvalidArgumentException('Unsupported or unknown GeoRSS geometry');
        }
    }

    /**
     * 解析 GeoRSS 坐标字符串为坐标数组。
     *
     * @param string $coords 坐标字符串
     * @return array 坐标数组
     */
    private static function parseGeoRssCoordinates(string $coords): array
    {
        $arr = preg_split('/\s+/', trim($coords));
        $pts = [];
        for ($i = 0; $i < count($arr) - 1; $i += 2) {
            $pts[] = [floatval($arr[$i + 1]), floatval($arr[$i])];
        }
        return $pts;
    }

    /**
     * 格式化 GeoRSS 坐标为字符串（lat lon）。
     *
     * @param array $point 坐标点数组
     * @param bool $withAltitude 是否包含高程
     * @return string 格式化后的字符串
     */
    private static function formatGeoRssCoordinate(array $point, bool $withAltitude = true): string
    {
        $lat = $point[1] ?? 0;
        $lon = $point[0] ?? 0;
        if ($withAltitude && isset($point[2]) && $point[2] != 0) {
            return $lat . ' ' . $lon . ' ' . $point[2];
        }
        return $lat . ' ' . $lon;
    }
}
