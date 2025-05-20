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
 * WKT（文本几何）适配器。
 *
 * 提供 Geometry 对象与 WKT 字符串的互转能力。
 */
class WktAdapter
{
    /**
     * 将 Geometry 对象转为 WKT 字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param bool $withAltitude 是否包含高程
     */
    public static function convert(Geometry|GeometryCollection $geometry, bool $withAltitude = true): string
    {
        if ($geometry instanceof Point) {
            return 'POINT(' . self::formatCoordinate($geometry->getCoordinates(), $withAltitude) . ')';
        }
        if ($geometry instanceof LineString) {
            $coords = [];
            foreach ($geometry->getCoordinates() as $point) {
                $coords[] = self::formatCoordinate($point, $withAltitude);
            }
            return 'LINESTRING(' . implode(', ', $coords) . ')';
        }
        if ($geometry instanceof Polygon) {
            $ringsWkt = [];
            foreach ($geometry->getCoordinates() as $ring) {
                $coords = [];
                foreach ($ring as $point) {
                    $coords[] = self::formatCoordinate($point, $withAltitude);
                }
                $ringsWkt[] = '(' . implode(', ', $coords) . ')';
            }
            return 'POLYGON(' . implode(', ', $ringsWkt) . ')';
        }
        if ($geometry instanceof MultiPoint) {
            $coords = [];
            foreach ($geometry->getCoordinates() as $point) {
                $coords[] = self::formatCoordinate($point, $withAltitude);
            }
            return 'MULTIPOINT(' . implode(', ', $coords) . ')';
        }
        if ($geometry instanceof MultiLineString) {
            $linesWkt = [];
            foreach ($geometry->getCoordinates() as $line) {
                $coords = [];
                foreach ($line as $point) {
                    $coords[] = self::formatCoordinate($point, $withAltitude);
                }
                $linesWkt[] = '(' . implode(', ', $coords) . ')';
            }
            return 'MULTILINESTRING(' . implode(', ', $linesWkt) . ')';
        }
        if ($geometry instanceof MultiPolygon) {
            $polygonsWkt = [];
            foreach ($geometry->getCoordinates() as $polygon) {
                $ringsWkt = [];
                foreach ($polygon as $ring) {
                    $coords = [];
                    foreach ($ring as $point) {
                        $coords[] = self::formatCoordinate($point, $withAltitude);
                    }
                    $ringsWkt[] = '(' . implode(', ', $coords) . ')';
                }
                $polygonsWkt[] = '(' . implode(', ', $ringsWkt) . ')';
            }
            return 'MULTIPOLYGON(' . implode(', ', $polygonsWkt) . ')';
        }
        if ($geometry instanceof GeometryCollection) {
            $wkts = [];
            foreach ($geometry->getGeometries() as $geom) {
                $wkts[] = self::convert($geom, $withAltitude);
            }
            return 'GEOMETRYCOLLECTION(' . implode(', ', $wkts) . ')';
        }
        return '';
    }

    /**
     * 解析 WKT 字符串为 Geometry 对象。
     *
     * @param string $wkt WKT 字符串
     * @throws \InvalidArgumentException 格式错误或不支持的类型
     */
    public static function parse(string $wkt): Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon|GeometryCollection
    {
        $wkt = trim($wkt);
        if (stripos($wkt, 'SRID=') === 0) {
            $parts = explode(';', $wkt, 2);
            $wkt = $parts[1] ?? '';
        }
        if (! preg_match('/^(\w+)\s*\((.*)\)$/is', $wkt, $matches)) {
            throw new \InvalidArgumentException('Invalid WKT: ' . $wkt);
        }
        $type = strtoupper($matches[1]);
        $body = $matches[2];
        switch ($type) {
            case 'POINT':
                $coords = array_map('floatval', preg_split('/\s+/', trim($body)));
                return new Point($coords);
            case 'LINESTRING':
                return new LineString(self::parseWktCoordinates($body));
            case 'POLYGON':
                $rings = [];
                preg_match_all('/\(([^\(\)]*)\)/', $body, $ringMatches);
                foreach ($ringMatches[1] as $ringStr) {
                    $rings[] = self::parseWktCoordinates($ringStr);
                }
                return new Polygon($rings);
            case 'MULTIPOINT':
                return new MultiPoint(self::parseWktCoordinates($body));
            case 'MULTILINESTRING':
                $lines = [];
                preg_match_all('/\(([^\(\)]*)\)/', $body, $lineMatches);
                foreach ($lineMatches[1] as $lineStr) {
                    $lines[] = self::parseWktCoordinates($lineStr);
                }
                return new MultiLineString($lines);
            case 'MULTIPOLYGON':
                $polygons = [];
                preg_match_all('/\(\s*\((.*?)\)\s*\)/s', $body, $polyMatches);
                foreach ($polyMatches[1] as $polyStr) {
                    $rings = [];
                    preg_match_all('/\(([^\(\)]*)\)/', $polyStr, $ringMatches);
                    foreach ($ringMatches[1] as $ringStr) {
                        $rings[] = self::parseWktCoordinates($ringStr);
                    }
                    $polygons[] = $rings;
                }
                return new MultiPolygon($polygons);
            case 'GEOMETRYCOLLECTION':
                $geoms = [];
                $depth = 0;
                $start = 0;
                for ($i = 0; $i < strlen($body); ++$i) {
                    if ($body[$i] === '(') {
                        ++$depth;
                    }
                    if ($body[$i] === ')') {
                        --$depth;
                    }
                    if ($body[$i] === ',' && $depth === 0) {
                        $geoms[] = substr($body, $start, $i - $start);
                        $start = $i + 1;
                    }
                }
                $geoms[] = substr($body, $start);
                $geoms = array_map(self::parse(...), $geoms);
                return new GeometryCollection($geoms);
            default:
                throw new \InvalidArgumentException('Unsupported WKT type: ' . $type);
        }
    }

    /**
     * 解析WKT坐标字符串为坐标数组。
     *
     * @param string $str WKT坐标字符串
     */
    private static function parseWktCoordinates(string $str): array
    {
        $str = trim($str, '() ');
        $parts = preg_split('/\s*,\s*/', $str);
        $result = [];
        foreach ($parts as $part) {
            $result[] = array_map('floatval', preg_split('/\s+/', trim($part)));
        }
        return $result;
    }

    /**
     * 格式化坐标为WKT字符串。
     *
     * @param array $point 坐标点
     * @param bool $withAltitude 是否包含高程
     */
    private static function formatCoordinate(array $point, bool $withAltitude = true): string
    {
        $lon = $point[0] ?? 0;
        $lat = $point[1] ?? 0;
        $alt = $point[2] ?? 0;
        return ($withAltitude && $alt !== 0) ? "$lon $lat $alt" : "$lon $lat";
    }
}
