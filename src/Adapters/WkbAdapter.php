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
 * WKB（二进制几何）适配器。
 *
 * 提供 Geometry 对象与 WKB 十六进制字符串的互转能力。
 */
class WkbAdapter
{
    /**
     * 将 Geometry 对象转为 WKB 十六进制字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param bool $withAltitude 是否包含高程
     */
    public static function convert(Geometry|GeometryCollection $geometry, bool $withAltitude = true): string
    {
        // 判断是否有Z值，仅由withAltitude控制
        $hasZ = $withAltitude && self::hasZInCoords($geometry->getCoordinates());
        $getType = function ($baseType) use ($hasZ) {
            // WKB类型编号：1=Point, 2=LineString, 3=Polygon, ...
            return $hasZ ? ($baseType + 1000) : $baseType;
        };
        $byteOrder = pack('C', 1);
        if ($geometry instanceof Point) {
            $wkbType = pack('V', $getType(1));
            $bin = $byteOrder . $wkbType . self::packPoint($geometry->getCoordinates(), $withAltitude);
            return strtoupper(bin2hex($bin));
        }
        if ($geometry instanceof LineString) {
            $coords = $geometry->getCoordinates();
            $wkbType = pack('V', $getType(2));
            $numPoints = pack('V', count($coords));
            $bin = $byteOrder . $wkbType . $numPoints;
            foreach ($coords as $coord) {
                $bin .= self::packPoint($coord, $withAltitude);
            }
            return strtoupper(bin2hex($bin));
        }
        if ($geometry instanceof Polygon) {
            $rings = $geometry->getCoordinates();
            $wkbType = pack('V', $getType(3));
            $numRings = pack('V', count($rings));
            $bin = $byteOrder . $wkbType . $numRings;
            foreach ($rings as $ring) {
                $numPoints = pack('V', count($ring));
                $bin .= $numPoints;
                foreach ($ring as $point) {
                    $bin .= self::packPoint($point, $withAltitude);
                }
            }
            return strtoupper(bin2hex($bin));
        }
        if ($geometry instanceof MultiPoint) {
            $coords = $geometry->getCoordinates();
            $wkbType = pack('V', $getType(4));
            $numPoints = pack('V', count($coords));
            $bin = $byteOrder . $wkbType . $numPoints;
            foreach ($coords as $coord) {
                $pointByteOrder = pack('C', 1);
                $pointType = pack('V', $getType(1));
                $bin .= $pointByteOrder . $pointType . self::packPoint($coord, $withAltitude);
            }
            return strtoupper(bin2hex($bin));
        }
        if ($geometry instanceof MultiLineString) {
            $lines = $geometry->getCoordinates();
            $wkbType = pack('V', $getType(5));
            $numLines = pack('V', count($lines));
            $bin = $byteOrder . $wkbType . $numLines;
            foreach ($lines as $line) {
                $lineByteOrder = pack('C', 1);
                $lineType = pack('V', $getType(2));
                $numPoints = pack('V', count($line));
                $lineBin = $lineByteOrder . $lineType . $numPoints;
                foreach ($line as $point) {
                    $lineBin .= self::packPoint($point, $withAltitude);
                }
                $bin .= $lineBin;
            }
            return strtoupper(bin2hex($bin));
        }
        if ($geometry instanceof MultiPolygon) {
            $polygons = $geometry->getCoordinates();
            $wkbType = pack('V', $getType(6));
            $numPolygons = pack('V', count($polygons));
            $bin = $byteOrder . $wkbType . $numPolygons;
            foreach ($polygons as $polygon) {
                $polyByteOrder = pack('C', 1);
                $polyType = pack('V', $getType(3));
                $numRings = pack('V', count($polygon));
                $polyBin = $polyByteOrder . $polyType . $numRings;
                foreach ($polygon as $ring) {
                    $numPoints = pack('V', count($ring));
                    $polyBin .= $numPoints;
                    foreach ($ring as $point) {
                        $polyBin .= self::packPoint($point, $withAltitude);
                    }
                }
                $bin .= $polyBin;
            }
            return strtoupper(bin2hex($bin));
        }
        if ($geometry instanceof GeometryCollection) {
            $geoms = $geometry->getGeometries();
            $wkbType = pack('V', 7);
            $numGeoms = pack('V', count($geoms));
            $bin = $byteOrder . $wkbType . $numGeoms;
            foreach ($geoms as $geom) {
                $bin .= hex2bin(self::convert($geom, $withAltitude));
            }
            return strtoupper(bin2hex($bin));
        }
        return '';
    }

    /**
     * 解析 WKB 十六进制字符串为 Geometry 对象。
     *
     * @param string $wkbHex WKB 十六进制字符串
     */
    public static function parse(string $wkbHex): Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon|GeometryCollection
    {
        $bin = hex2bin($wkbHex);
        $offset = 0;
        return self::parseGeom($bin, $offset);
    }

    private static function packPoint(array $point, bool $withAltitude = true): string
    {
        $lon = (float) ($point[0] ?? 0);
        $lat = (float) ($point[1] ?? 0);
        $alt = (float) ($point[2] ?? 0);
        return $withAltitude && isset($point[2]) ? pack('ddd', $lon, $lat, $alt) : pack('dd', $lon, $lat);
    }

    private static function readByte(string $bin, int &$offset, int $n): string
    {
        $val = substr($bin, $offset, $n);
        $offset += $n;
        return $val;
    }

    private static function readDouble(string $bin, int &$offset): float
    {
        return unpack('d', self::readByte($bin, $offset, 8))[1];
    }

    private static function readUInt32(string $bin, int &$offset, bool $le = true): int
    {
        return unpack($le ? 'V' : 'N', self::readByte($bin, $offset, 4))[1];
    }

    private static function parseGeom(string $bin, int &$offset): Point|LineString|Polygon|MultiPoint|MultiLineString|MultiPolygon|GeometryCollection
    {
        $byteOrder = ord(self::readByte($bin, $offset, 1));
        $le = $byteOrder === 1;
        $type = self::readUInt32($bin, $offset, $le);
        $hasZ = ($type >= 1000 && $type < 2000) || ($type & 0x80000000);
        $baseType = $type % 1000;
        switch ($baseType) {
            case 1: // Point
                $x = self::readDouble($bin, $offset);
                $y = self::readDouble($bin, $offset);
                $z = $hasZ ? self::readDouble($bin, $offset) : null;
                return new Point(array_filter([$x, $y, $z], fn($v): bool => $v !== null));
            case 2: // LineString
                $num = self::readUInt32($bin, $offset, $le);
                $coords = [];
                for ($i = 0; $i < $num; ++$i) {
                    $x = self::readDouble($bin, $offset);
                    $y = self::readDouble($bin, $offset);
                    $z = $hasZ ? self::readDouble($bin, $offset) : null;
                    $coords[] = array_filter([$x, $y, $z], fn($v): bool => $v !== null);
                }
                return new LineString($coords);
            case 3: // Polygon
                $numRings = self::readUInt32($bin, $offset, $le);
                $rings = [];
                for ($i = 0; $i < $numRings; ++$i) {
                    $num = self::readUInt32($bin, $offset, $le);
                    $coords = [];
                    for ($j = 0; $j < $num; ++$j) {
                        $x = self::readDouble($bin, $offset);
                        $y = self::readDouble($bin, $offset);
                        $z = $hasZ ? self::readDouble($bin, $offset) : null;
                        $coords[] = array_filter([$x, $y, $z], fn($v): bool => $v !== null);
                    }
                    $rings[] = $coords;
                }
                return new Polygon($rings);
            case 4: // MultiPoint
                $num = self::readUInt32($bin, $offset, $le);
                $points = [];
                for ($i = 0; $i < $num; ++$i) {
                    $points[] = self::parseGeom($bin, $offset);
                }
                return new MultiPoint(array_map(fn($pt): array => $pt->getCoordinates(), $points));
            case 5: // MultiLineString
                $num = self::readUInt32($bin, $offset, $le);
                $lines = [];
                for ($i = 0; $i < $num; ++$i) {
                    $lines[] = self::parseGeom($bin, $offset);
                }
                return new MultiLineString(array_map(fn($ls): array => $ls->getCoordinates(), $lines));
            case 6: // MultiPolygon
                $num = self::readUInt32($bin, $offset, $le);
                $polys = [];
                for ($i = 0; $i < $num; ++$i) {
                    $polys[] = self::parseGeom($bin, $offset);
                }
                return new MultiPolygon(array_map(fn($pg): array => $pg->getCoordinates(), $polys));
            case 7: // GeometryCollection
                $num = self::readUInt32($bin, $offset, $le);
                $geoms = [];
                for ($i = 0; $i < $num; ++$i) {
                    $geoms[] = self::parseGeom($bin, $offset);
                }
                return new GeometryCollection($geoms);
            default:
                throw new \InvalidArgumentException('Unsupported WKB type: ' . $type);
        }
    }

    /**
     * 检查坐标数组是否包含Z值。
     *
     * @param array $coords 坐标数组
     */
    private static function hasZInCoords(array $coords): bool
    {
        foreach ($coords as $coord) {
            if (is_array($coord)) {
                if (self::hasZInCoords($coord)) {
                    return true;
                }
            } elseif (isset($coords[2]) && $coords[2] !== 0) {
                return true;
            }
        }
        return false;
    }
}
