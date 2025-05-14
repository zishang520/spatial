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
 * GPX 适配器。
 *
 * 提供 Geometry 对象与 GPX XML 字符串的互转能力。
 */
class GpxAdapter
{
    /**
     * 将 Geometry 对象转为 GPX XML 字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param bool $withAltitude 是否包含高程
     * @param string|null $namespace 命名空间前缀
     * @return string GPX XML 字符串
     */
    public static function convert(Geometry|GeometryCollection $geometry, bool $withAltitude = true, ?string $namespace = null): string
    {
        $ns = $namespace ? $namespace . ':' : '';
        if ($geometry instanceof Point) {
            return self::formatGpxPoint($geometry->getCoordinates(), 'wpt', $withAltitude, $namespace);
        }
        if ($geometry instanceof LineString) {
            $trkpts = [];
            foreach ($geometry->getCoordinates() as $point) {
                $trkpts[] = self::formatGpxPoint($point, 'trkpt', $withAltitude, $namespace);
            }
            return '<' . $ns . 'trk><' . $ns . 'trkseg>' . implode('', $trkpts) . '</' . $ns . 'trkseg></' . $ns . 'trk>';
        }
        if ($geometry instanceof Polygon) {
            $rings = $geometry->getCoordinates();
            $gpx = '';
            foreach ($rings as $ring) {
                $trkpts = [];
                foreach ($ring as $point) {
                    $trkpts[] = self::formatGpxPoint($point, 'trkpt', $withAltitude, $namespace);
                }
                $gpx .= '<' . $ns . 'trk><' . $ns . 'trkseg>' . implode('', $trkpts) . '</' . $ns . 'trkseg></' . $ns . 'trk>';
            }
            return $gpx;
        }
        if ($geometry instanceof MultiPoint) {
            $gpx = '';
            foreach ($geometry->getCoordinates() as $point) {
                $gpx .= self::formatGpxPoint($point, 'wpt', $withAltitude, $namespace);
            }
            return $gpx;
        }
        if ($geometry instanceof MultiLineString) {
            $gpx = '';
            foreach ($geometry->getCoordinates() as $line) {
                $trkpts = [];
                foreach ($line as $point) {
                    $trkpts[] = self::formatGpxPoint($point, 'trkpt', $withAltitude, $namespace);
                }
                $gpx .= '<' . $ns . 'trk><' . $ns . 'trkseg>' . implode('', $trkpts) . '</' . $ns . 'trkseg></' . $ns . 'trk>';
            }
            return $gpx;
        }
        if ($geometry instanceof MultiPolygon) {
            $gpx = '';
            foreach ($geometry->getCoordinates() as $polygon) {
                foreach ($polygon as $ring) {
                    $trkpts = [];
                    foreach ($ring as $point) {
                        $trkpts[] = self::formatGpxPoint($point, 'trkpt', $withAltitude, $namespace);
                    }
                    $gpx .= '<' . $ns . 'trk><' . $ns . 'trkseg>' . implode('', $trkpts) . '</' . $ns . 'trkseg></' . $ns . 'trk>';
                }
            }
            return $gpx;
        }
        if ($geometry instanceof GeometryCollection) {
            $gpx = '';
            foreach ($geometry->getGeometries() as $geom) {
                $gpx .= self::convert($geom, $withAltitude, $namespace);
            }
            return $gpx;
        }
        return '';
    }

    /**
     * 解析 GPX XML 字符串为 Geometry 对象。
     *
     * @param string $gpx GPX XML 字符串
     * @return Geometry|GeometryCollection
     * @throws \InvalidArgumentException 格式不支持或解析失败
     */
    public static function parse(string $gpx)
    {
        $xml = simplexml_load_string($gpx);
        if (! $xml) {
            throw new \InvalidArgumentException('Invalid GPX');
        }
        // 支持带namespace的标签
        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('gpx', $namespaces[''] ?? '');
        $tag = strtolower($xml->getName());
        // 去除namespace前缀
        $tag = preg_replace('/^.*:/', '', $tag);
        switch ($tag) {
            case 'wpt':
                return new Point(self::parseGpxPoint($xml));
            case 'trk':
                $lines = [];
                foreach ($xml->trkseg as $trkseg) {
                    $coords = [];
                    foreach ($trkseg->trkpt as $trkpt) {
                        $coords[] = self::parseGpxPoint($trkpt);
                    }
                    $lines[] = $coords;
                }
                if (count($lines) === 1) {
                    return new LineString($lines[0]);
                }
                return new MultiLineString($lines);
            default:
                // 支持gpx文件根节点下多wpt、trk，带namespace
                $points = [];
                $lines = [];
                foreach ($xml->xpath('//*[local-name()="wpt"]') as $wpt) {
                    $points[] = self::parseGpxPoint($wpt);
                }
                foreach ($xml->xpath('//*[local-name()="trk"]') as $trk) {
                    foreach ($trk->trkseg as $trkseg) {
                        $coords = [];
                        foreach ($trkseg->trkpt as $trkpt) {
                            $coords[] = self::parseGpxPoint($trkpt);
                        }
                        $lines[] = $coords;
                    }
                }
                if ($points && ! $lines) {
                    return new MultiPoint($points);
                }
                if (! $points && $lines) {
                    if (count($lines) === 1) {
                        return new LineString($lines[0]);
                    }
                    return new MultiLineString($lines);
                }
                if ($points && $lines) {
                    return new GeometryCollection([
                        new MultiPoint($points),
                        count($lines) === 1 ? new LineString($lines[0]) : new MultiLineString($lines)
                    ]);
                }
                throw new \InvalidArgumentException('Unsupported GPX geometry');
        }
    }

    /**
     * 解析 GPX trkpt/wpt 节点为坐标数组。
     *
     * @param \SimpleXMLElement $xml 节点对象
     * @return array [lon, lat, ele]
     */
    private static function parseGpxPoint($xml): array
    {
        $lat = (float) $xml['lat'];
        $lon = (float) $xml['lon'];
        $ele = isset($xml->ele) ? (float) $xml->ele : 0;
        return [$lon, $lat, $ele];
    }

    /**
     * 格式化 GPX 坐标点为 trkpt/wpt 字符串。
     *
     * @param array $point 坐标数组
     * @param string $tag 标签名
     * @param bool $withAltitude 是否包含高程
     * @param string|null $namespace 命名空间前缀
     * @return string GPX XML 片段
     */
    private static function formatGpxPoint(array $point, string $tag = 'trkpt', bool $withAltitude = true, ?string $namespace = null): string
    {
        $ns = $namespace ? $namespace . ':' : '';
        $lon = $point[0] ?? 0;
        $lat = $point[1] ?? 0;
        $ele = $point[2] ?? 0;
        return $withAltitude
            ? "<{$ns}{$tag} lat=\"{$lat}\" lon=\"{$lon}\"><{$ns}ele>{$ele}</{$ns}ele></{$ns}{$tag}>"
            : "<{$ns}{$tag} lat=\"{$lat}\" lon=\"{$lon}\"></{$ns}{$tag}>";
    }
}
