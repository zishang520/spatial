<?php

namespace luoyy\Spatial\Adapters;

use luoyy\Spatial\Geometry\Point;

/**
 * GeoHash 编码与解码适配器。
 *
 * 提供 Point 与 GeoHash 字符串的互转能力。
 */
class GeoHashAdapter
{
    // GeoHash 编码表
    private static $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    private static $decodeMap = null;

    /**
     * 将 Point 转为 GeoHash 字符串。
     *
     * @param Point $point 点对象
     * @param int $precision 精度（字符数，默认12）
     * @return string GeoHash 字符串
     */
    public static function convert(Point $point, int $precision = 12): string
    {
        return self::encode($point->getLatitude(), $point->getLongitude(), $precision);
    }

    /**
     * 将 GeoHash 字符串转为 Point 对象。
     *
     * @param string $geohash GeoHash 字符串
     * @return Point 点对象
     */
    public static function parse(string $geohash): Point
    {
        return new Point(self::decode($geohash));
    }

    /**
     * GeoHash 编码。
     *
     * @param float $lat 纬度
     * @param float $lon 经度
     * @param int $precision 精度（字符数，默认12）
     * @return string GeoHash 字符串
     */
    private static function encode(float $lat, float $lon, int $precision = 12): string
    {
        $latInterval = [-90.0, 90.0];
        $lonInterval = [-180.0, 180.0];
        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;
        $bits = [16, 8, 4, 2, 1];
        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lonInterval[0] + $lonInterval[1]) / 2;
                if ($lon > $mid) {
                    $ch |= $bits[$bit];
                    $lonInterval[0] = $mid;
                } else {
                    $lonInterval[1] = $mid;
                }
            } else {
                $mid = ($latInterval[0] + $latInterval[1]) / 2;
                if ($lat > $mid) {
                    $ch |= $bits[$bit];
                    $latInterval[0] = $mid;
                } else {
                    $latInterval[1] = $mid;
                }
            }
            $isEven = ! $isEven;
            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= self::$base32[$ch];
                $bit = 0;
                $ch = 0;
            }
        }
        return $geohash;
    }

    /**
     * GeoHash 解码。
     *
     * @param string $geohash GeoHash 字符串
     * @return array [lon, lat] 解码后的经纬度数组
     */
    private static function decode(string $geohash): array
    {
        if (self::$decodeMap === null) {
            self::$decodeMap = array_flip(str_split(self::$base32));
        }
        $latInterval = [-90.0, 90.0];
        $lonInterval = [-180.0, 180.0];
        $isEven = true;
        foreach (str_split($geohash) as $c) {
            $cd = self::$decodeMap[$c];
            for ($mask = 16; $mask >= 1; $mask >>= 1) {
                if ($isEven) {
                    $mid = ($lonInterval[0] + $lonInterval[1]) / 2;
                    if ($cd & $mask) {
                        $lonInterval[0] = $mid;
                    } else {
                        $lonInterval[1] = $mid;
                    }
                } else {
                    $mid = ($latInterval[0] + $latInterval[1]) / 2;
                    if ($cd & $mask) {
                        $latInterval[0] = $mid;
                    } else {
                        $latInterval[1] = $mid;
                    }
                }
                $isEven = ! $isEven;
            }
        }
        return [($lonInterval[0] + $lonInterval[1]) / 2, ($latInterval[0] + $latInterval[1]) / 2];
    }
}
