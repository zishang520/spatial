<?php

namespace luoyy\Spatial\Adapters;

use luoyy\Spatial\Geometry\Geometry;
use luoyy\Spatial\Geometry\GeometryCollection;

/**
 * EWKB（扩展WKB）适配器。
 *
 * 支持带 SRID 的 WKB 编解码，兼容 PostGIS 等数据库格式。
 */
class EwkbAdapter
{
    /**
     * 将 Geometry 对象转为 EWKB 十六进制字符串。
     *
     * @param Geometry|GeometryCollection $geometry 几何对象
     * @param int|null $srid 空间参考ID，可选
     * @param bool $withAltitude 是否包含高程
     * @return string EWKB 十六进制字符串
     */
    public static function convert(Geometry|GeometryCollection $geometry, ?int $srid = null, bool $withAltitude = true): string
    {
        $srid = $srid ?? ($geometry instanceof Geometry ? $geometry->getSrid() : null);
        $wkbHex = WkbAdapter::convert($geometry, $withAltitude);
        if ($srid !== null) {
            $bin = hex2bin($wkbHex);
            $ewkb = self::insertSridToWkb($bin, $srid);
            return strtoupper(bin2hex($ewkb));
        }
        return $wkbHex;
    }

    /**
     * 解析 EWKB 十六进制字符串为 Geometry 对象。
     *
     * @param string $ewkbHex EWKB 十六进制字符串
     * @return Geometry|GeometryCollection
     */
    public static function parse(string $ewkbHex)
    {
        $bin = hex2bin($ewkbHex);
        [$wkb, $srid] = self::extractSridAndWkb($bin);
        $geometry = WkbAdapter::parse(strtoupper(bin2hex($wkb)));
        if ($srid !== null && $geometry instanceof Geometry) {
            $geometry->setSrid($srid);
        }
        return $geometry;
    }

    /**
     * 插入SRID到WKB二进制流。
     *
     * @param string $bin WKB二进制流
     * @param int $srid SRID
     * @return string 插入SRID后的二进制流
     */
    private static function insertSridToWkb(string $bin, int $srid): string
    {
        $byteOrder = ord($bin[0]);
        $type = unpack($byteOrder ? 'V' : 'N', substr($bin, 1, 4))[1];
        $withSridType = $type | 0x20000000;
        $typeBin = pack($byteOrder ? 'V' : 'N', $withSridType);
        $sridBin = pack($byteOrder ? 'V' : 'N', $srid);
        return $bin[0] . $typeBin . $sridBin . substr($bin, 5);
    }

    /**
     * 从EWKB二进制流解析SRID和WKB。
     *
     * @param string $bin EWKB二进制流
     * @return array [WKB二进制流, SRID]
     */
    private static function extractSridAndWkb(string $bin): array
    {
        $offset = 0;
        $byteOrder = ord($bin[$offset++]);
        $type = unpack($byteOrder ? 'V' : 'N', substr($bin, $offset, 4))[1];
        $offset += 4;
        $hasSrid = ($type & 0x20000000) !== 0;
        $baseType = $type & 0xFFF;
        $srid = null;
        if ($hasSrid) {
            $srid = unpack($byteOrder ? 'V' : 'N', substr($bin, $offset, 4))[1];
            $offset += 4;
        } else {
            $offset -= 4;
        }
        $wkb = $bin[0] . pack($byteOrder ? 'V' : 'N', $baseType) . substr($bin, $offset);
        return [$wkb, $srid];
    }
}
