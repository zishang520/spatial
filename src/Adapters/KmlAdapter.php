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
 * KML 适配器。
 *
 * 提供 Geometry 对象与 KML 的互转能力。
 */
class KmlAdapter
{
    /**
     * 将 Geometry 对象转为 KML 字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param bool $withAltitude 是否包含高程
     * @param string|null $namespace 命名空间前缀
     */
    public static function convert(Geometry|GeometryCollection $geometry, bool $withAltitude = true, ?string $namespace = null): string
    {
        $ns = $namespace ? $namespace . ':' : '';
        if ($geometry instanceof Point) {
            return '<' . $ns . 'Point><' . $ns . 'coordinates>' . self::formatKmlCoordinate($geometry->getCoordinates(), $withAltitude) . '</' . $ns . 'coordinates></' . $ns . 'Point>';
        }
        if ($geometry instanceof LineString) {
            $coords = [];
            foreach ($geometry->getCoordinates() as $point) {
                $coords[] = self::formatKmlCoordinate($point, $withAltitude);
            }
            return '<' . $ns . 'LineString><' . $ns . 'coordinates>' . implode(' ', $coords) . '</' . $ns . 'coordinates></' . $ns . 'LineString>';
        }
        if ($geometry instanceof Polygon) {
            $rings = $geometry->getCoordinates();
            $kml = '';
            foreach ($rings as $i => $ring) {
                $coords = [];
                foreach ($ring as $point) {
                    $coords[] = self::formatKmlCoordinate($point, $withAltitude);
                }
                $ringKml = '<' . $ns . 'LinearRing><' . $ns . 'coordinates>' . implode(' ', $coords) . '</' . $ns . 'coordinates></' . $ns . 'LinearRing>';
                if ($i === 0) {
                    $kml .= '<' . $ns . 'outerBoundaryIs>' . $ringKml . '</' . $ns . 'outerBoundaryIs>';
                } else {
                    $kml .= '<' . $ns . 'innerBoundaryIs>' . $ringKml . '</' . $ns . 'innerBoundaryIs>';
                }
            }
            return '<' . $ns . 'Polygon>' . $kml . '</' . $ns . 'Polygon>';
        }
        if ($geometry instanceof MultiPoint) {
            $points = $geometry->getCoordinates();
            $kml = '';
            foreach ($points as $point) {
                $kml .= '<' . $ns . 'Point><' . $ns . 'coordinates>' . self::formatKmlCoordinate($point, $withAltitude) . '</' . $ns . 'coordinates></' . $ns . 'Point>';
            }
            return $kml;
        }
        if ($geometry instanceof MultiLineString) {
            $lines = $geometry->getCoordinates();
            $kml = '';
            foreach ($lines as $line) {
                $coords = [];
                foreach ($line as $point) {
                    $coords[] = self::formatKmlCoordinate($point, $withAltitude);
                }
                $kml .= '<' . $ns . 'LineString><' . $ns . 'coordinates>' . implode(' ', $coords) . '</' . $ns . 'coordinates></' . $ns . 'LineString>';
            }
            return $kml;
        }
        if ($geometry instanceof MultiPolygon) {
            $polygons = $geometry->getCoordinates();
            $kml = '';
            foreach ($polygons as $polygon) {
                $ringsKml = '';
                foreach ($polygon as $i => $ring) {
                    $coords = [];
                    foreach ($ring as $point) {
                        $coords[] = self::formatKmlCoordinate($point, $withAltitude);
                    }
                    $ringKml = '<' . $ns . 'LinearRing><' . $ns . 'coordinates>' . implode(' ', $coords) . '</' . $ns . 'coordinates></' . $ns . 'LinearRing>';
                    if ($i === 0) {
                        $ringsKml .= '<' . $ns . 'outerBoundaryIs>' . $ringKml . '</' . $ns . 'outerBoundaryIs>';
                    } else {
                        $ringsKml .= '<' . $ns . 'innerBoundaryIs>' . $ringKml . '</' . $ns . 'innerBoundaryIs>';
                    }
                }
                $kml .= '<' . $ns . 'Polygon>' . $ringsKml . '</' . $ns . 'Polygon>';
            }
            return $kml;
        }
        if ($geometry instanceof GeometryCollection) {
            $kml = '';
            foreach ($geometry->getGeometries() as $geom) {
                $kml .= self::convert($geom, $withAltitude, $namespace);
            }
            return $kml;
        }
        return '';
    }

    /**
     * 解析 KML 字符串为 Geometry 对象。
     *
     * @param string $kml KML 字符串
     * @throws \InvalidArgumentException 格式错误或不支持的类型
     */
    public static function parse(string $kml): Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon|GeometryCollection
    {
        $xml = simplexml_load_string($kml);
        if (! $xml) {
            throw new \InvalidArgumentException('Invalid KML');
        }
        $ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('kml', $ns[''] ?? '');
        $tag = $xml->getName();
        switch (strtolower($tag)) {
            case 'point':
                $coords = trim((string) $xml->coordinates);
                $arr = array_map('floatval', preg_split('/[ ,]+/', $coords));
                return new Point($arr);
            case 'linestring':
                $coords = trim((string) $xml->coordinates);
                return new LineString(self::parseKmlCoordinates($coords));
            case 'polygon':
                $rings = [];
                foreach ($xml->xpath('.//outerBoundaryIs|.//innerBoundaryIs') as $b) {
                    $coords = trim((string) $b->LinearRing->coordinates);
                    $rings[] = self::parseKmlCoordinates($coords);
                }
                return new Polygon($rings);
            case 'multipoint':
                $points = [];
                foreach ($xml->xpath('.//Point') as $pt) {
                    $coords = trim((string) $pt->coordinates);
                    $points[] = array_map('floatval', preg_split('/[ ,]+/', $coords));
                }
                return new MultiPoint($points);
            case 'multilinestring':
                $lines = [];
                foreach ($xml->xpath('.//LineString') as $ls) {
                    $coords = trim((string) $ls->coordinates);
                    $lines[] = self::parseKmlCoordinates($coords);
                }
                return new MultiLineString($lines);
            case 'multipolygon':
                $polygons = [];
                foreach ($xml->xpath('.//Polygon') as $poly) {
                    $rings = [];
                    foreach ($poly->xpath('.//outerBoundaryIs|.//innerBoundaryIs') as $b) {
                        $coords = trim((string) $b->LinearRing->coordinates);
                        $rings[] = self::parseKmlCoordinates($coords);
                    }
                    $polygons[] = $rings;
                }
                return new MultiPolygon($polygons);
            case 'geometrycollection':
                $geoms = [];
                foreach ($xml->children() as $child) {
                    $geoms[] = self::parse($child->asXML());
                }
                return new GeometryCollection($geoms);
            default:
                throw new \InvalidArgumentException('Unsupported or unknown KML geometry: ' . $tag);
        }
    }

    /**
     * 解析KML坐标字符串为坐标数组。
     *
     * @param string $coords KML 坐标字符串
     */
    private static function parseKmlCoordinates(string $coords): array
    {
        return array_map(
            fn($c): array => array_map('floatval', preg_split('/,/', trim($c))),
            preg_split('/\s+/', trim($coords), -1, PREG_SPLIT_NO_EMPTY)
        );
    }

    /**
     * 格式化KML坐标字符串。
     *
     * @param array $point 坐标点
     * @param bool $withAltitude 是否包含高程
     */
    private static function formatKmlCoordinate(array $point, bool $withAltitude = true): string
    {
        $lon = $point[0] ?? 0;
        $lat = $point[1] ?? 0;
        $alt = $point[2] ?? 0;
        return $withAltitude ? ("$lon,$lat,$alt") : ("$lon,$lat");
    }
}
