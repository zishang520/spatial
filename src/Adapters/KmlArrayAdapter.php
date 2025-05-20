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
 * KML 数组适配器。
 *
 * 提供 Geometry 对象与 KML 结构化数组的互转能力。
 */
class KmlArrayAdapter
{
    /**
     * 将 Geometry 对象转为 KML 结构化数组。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param bool $withAltitude 是否包含高程
     * @param string|null $namespace 命名空间前缀
     */
    public static function convert(Geometry|GeometryCollection $geometry, bool $withAltitude = true, ?string $namespace = null): array
    {
        $ns = $namespace ? $namespace . ':' : '';
        if ($geometry instanceof Point) {
            return [
                $ns . 'Point' => [
                    $ns . 'coordinates' => self::formatKmlCoordinate($geometry->getCoordinates(), $withAltitude),
                ],
            ];
        }
        if ($geometry instanceof LineString) {
            $coords = [];
            foreach ($geometry->getCoordinates() as $point) {
                $coords[] = self::formatKmlCoordinate($point, $withAltitude);
            }
            return [
                $ns . 'LineString' => [
                    $ns . 'coordinates' => implode(' ', $coords),
                ],
            ];
        }
        if ($geometry instanceof Polygon) {
            $rings = $geometry->getCoordinates();
            $ringsArr = [];
            foreach ($rings as $ring) {
                $coords = [];
                foreach ($ring as $point) {
                    $coords[] = self::formatKmlCoordinate($point, $withAltitude);
                }
                $ringsArr[] = [
                    $ns . 'LinearRing' => [
                        $ns . 'coordinates' => implode(' ', $coords),
                    ],
                ];
            }
            $arr = [
                $ns . 'Polygon' => [
                    $ns . 'outerBoundaryIs' => $ringsArr[0],
                ],
            ];
            if (count($ringsArr) > 1) {
                $arr[$ns . 'Polygon'][$ns . 'innerBoundaryIs'] = array_slice($ringsArr, 1);
            }
            return $arr;
        }
        if ($geometry instanceof MultiPoint) {
            $points = $geometry->getCoordinates();
            $arr = [];
            foreach ($points as $point) {
                $arr[] = [
                    $ns . 'Point' => [
                        $ns . 'coordinates' => self::formatKmlCoordinate($point, $withAltitude),
                    ],
                ];
            }
            return [$ns . 'MultiPoint' => $arr];
        }
        if ($geometry instanceof MultiLineString) {
            $lines = $geometry->getCoordinates();
            $arr = [];
            foreach ($lines as $line) {
                $coords = [];
                foreach ($line as $point) {
                    $coords[] = self::formatKmlCoordinate($point, $withAltitude);
                }
                $arr[] = [
                    $ns . 'LineString' => [
                        $ns . 'coordinates' => implode(' ', $coords),
                    ],
                ];
            }
            return [$ns . 'MultiLineString' => $arr];
        }
        if ($geometry instanceof MultiPolygon) {
            $polygons = $geometry->getCoordinates();
            $arr = [];
            foreach ($polygons as $polygon) {
                $ringsArr = [];
                foreach ($polygon as $ring) {
                    $coords = [];
                    foreach ($ring as $point) {
                        $coords[] = self::formatKmlCoordinate($point, $withAltitude);
                    }
                    $ringsArr[] = [
                        $ns . 'LinearRing' => [
                            $ns . 'coordinates' => implode(' ', $coords),
                        ],
                    ];
                }
                $polyArr = [
                    $ns . 'Polygon' => [
                        $ns . 'outerBoundaryIs' => $ringsArr[0],
                    ],
                ];
                if (count($ringsArr) > 1) {
                    $polyArr[$ns . 'Polygon'][$ns . 'innerBoundaryIs'] = array_slice($ringsArr, 1);
                }
                $arr[] = $polyArr;
            }
            return [$ns . 'MultiPolygon' => $arr];
        }
        if ($geometry instanceof GeometryCollection) {
            $arr = [];
            foreach ($geometry->getGeometries() as $geom) {
                $arr[] = self::convert($geom, $withAltitude, $namespace);
            }
            return [$ns . 'GeometryCollection' => $arr];
        }
        return [];
    }

    /**
     * 将 KML 结构化数组解析为 Geometry 对象。
     *
     * @param array $kmlArr KML 数组
     * @throws \InvalidArgumentException 不支持的或未知的 KML 几何类型
     */
    public static function parse(array $kmlArr): Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon|GeometryCollection
    {
        if (isset($kmlArr['Point'])) {
            $coords = array_map('floatval', preg_split('/[ ,]+/', (string) $kmlArr['Point']['coordinates']));
            return new Point($coords);
        }
        if (isset($kmlArr['LineString'])) {
            $coords = array_map(fn($c): array => array_map('floatval', preg_split('/,/', trim($c))), preg_split('/\s+/', (string) $kmlArr['LineString']['coordinates'], -1, PREG_SPLIT_NO_EMPTY));
            return new LineString($coords);
        }
        if (isset($kmlArr['Polygon'])) {
            $rings = [];
            if (isset($kmlArr['Polygon']['outerBoundaryIs'])) {
                $outer = $kmlArr['Polygon']['outerBoundaryIs'];
                $coords = array_map(fn($c): array => array_map('floatval', preg_split('/,/', trim($c))), preg_split('/\s+/', (string) $outer['LinearRing']['coordinates'], -1, PREG_SPLIT_NO_EMPTY));
                $rings[] = $coords;
            }
            if (isset($kmlArr['Polygon']['innerBoundaryIs'])) {
                $inners = $kmlArr['Polygon']['innerBoundaryIs'];
                if (isset($inners[0])) {
                    foreach ($inners as $inner) {
                        $coords = array_map(fn($c): array => array_map('floatval', preg_split('/,/', trim($c))), preg_split('/\s+/', (string) $inner['LinearRing']['coordinates'], -1, PREG_SPLIT_NO_EMPTY));
                        $rings[] = $coords;
                    }
                } else {
                    $coords = array_map(fn($c): array => array_map('floatval', preg_split('/,/', trim($c))), preg_split('/\s+/', (string) $inners['LinearRing']['coordinates'], -1, PREG_SPLIT_NO_EMPTY));
                    $rings[] = $coords;
                }
            }
            return new Polygon($rings);
        }
        if (isset($kmlArr['MultiPoint'])) {
            $points = array_map(fn($pt): array => array_map('floatval', preg_split('/[ ,]+/', (string) $pt['Point']['coordinates'])), $kmlArr['MultiPoint']);
            return new MultiPoint($points);
        }
        if (isset($kmlArr['MultiLineString'])) {
            $lines = array_map(fn($ls): array => array_map(fn($c): array => array_map('floatval', preg_split('/,/', trim($c))), preg_split('/\s+/', (string) $ls['LineString']['coordinates'], -1, PREG_SPLIT_NO_EMPTY)), $kmlArr['MultiLineString']);
            return new MultiLineString($lines);
        }
        if (isset($kmlArr['MultiPolygon'])) {
            $polygons = array_map(fn($poly) => self::parse($poly)['coordinates'], $kmlArr['MultiPolygon']);
            return new MultiPolygon($polygons);
        }
        if (isset($kmlArr['GeometryCollection'])) {
            $geoms = array_map(self::parse(...), $kmlArr['GeometryCollection']);
            return new GeometryCollection($geoms);
        }
        throw new \InvalidArgumentException('Unsupported or unknown KML array geometry');
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
