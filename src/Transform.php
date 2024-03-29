<?php

namespace luoyy\Spatial;

use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Enums\PointEnum;
use luoyy\Spatial\Support\Point;
use luoyy\Spatial\Support\PointBD09;
use luoyy\Spatial\Support\PointGCJ02;
use luoyy\Spatial\Support\PointWGS84;

/**
 * 坐标转换.
 */
class Transform
{
    protected const X_PI = 3.14159265358979324 * 3000.0 / 180.0;

    protected const PI = 3.1415926535897932384626;

    protected const EARTHS_LONG_RADIUS = 6378245.0;

    protected const FLATNESS = 0.00669342162296594323;

    /**
     * BD09转GCJ02.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointBD09 $point BD09坐标
     */
    public static function BD09_GCJ02(PointBD09 $point): PointGCJ02
    {
        $longitude = $point->longitude - 0.0065;
        $latitude = $point->latitude - 0.006;
        $postion = sqrt($longitude * $longitude + $latitude * $latitude) - 0.00002 * sin($latitude * self::X_PI);
        $offset = atan2($latitude, $longitude) - 0.000003 * cos($longitude * self::X_PI);
        return new PointGCJ02($postion * cos($offset), $postion * sin($offset), altitude: $point->altitude);
    }

    /**
     * BD09转WGS84.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointBD09 $point BD09坐标
     */
    public static function BD09_WGS84(PointBD09 $point): PointWGS84
    {
        return static::GCJ02_WGS84(static::BD09_GCJ02($point));
    }

    /**
     * WGS84转BD09.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointWGS84 $point WGS84坐标
     */
    public static function WGS84_BD09(PointWGS84 $point): PointBD09
    {
        return static::GCJ02_BD09(static::WGS84_GCJ02($point));
    }

    /**
     * WGS84转GCJ02.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointWGS84 $point WGS84坐标
     */
    public static function WGS84_GCJ02(PointWGS84 $point): PointGCJ02
    {
        if (!static::in_china($point)) {
            return new PointGCJ02($point->longitude, $point->latitude, altitude: $point->altitude);
        }
        $offsetPoint = self::offsetPoint($point);
        return new PointGCJ02($point->longitude + $offsetPoint->longitude, $point->latitude + $offsetPoint->latitude, altitude: $point->altitude);
    }

    /**
     * GCJ02转BD09.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointGCJ02 $point CJ02坐标
     */
    public static function GCJ02_BD09(PointGCJ02 $point): PointBD09
    {
        $postion = sqrt($point->longitude * $point->longitude + $point->latitude * $point->latitude) + 0.00002 * sin($point->latitude * self::X_PI);
        $offset = atan2($point->latitude, $point->longitude) + 0.000003 * cos($point->longitude * self::X_PI);
        return new PointBD09($postion * cos($offset) + 0.0065, $postion * sin($offset) + 0.006, altitude: $point->altitude);
    }

    /**
     * GCJ02转WGS84.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointGCJ02 $point GCJ02坐标
     */
    public static function GCJ02_WGS84(PointGCJ02 $point): PointWGS84
    {
        $out = new PointWGS84($point->longitude, $point->latitude, altitude: $point->altitude);
        if (!static::in_china($point)) {
            return $out;
        }

        $gcj02_point = self::WGS84_GCJ02($out);
        [$dlng, $dlat] = [$gcj02_point->longitude - $point->longitude, $gcj02_point->latitude - $point->latitude];
        while (abs($dlng) > 1e-7 || abs($dlat) > 1e-7) {
            $gcj02_point = self::WGS84_GCJ02(new PointWGS84($out->longitude -= $dlng, $out->latitude -= $dlat));
            [$dlng, $dlat] = [$gcj02_point->longitude - $point->longitude, $gcj02_point->latitude - $point->latitude];
        }

        return $out;
    }

    /**
     * 转换一个坐标.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Contracts\Point $point 原坐标
     * @param string $from 来源坐标 [BD09, WGS84, GCJ02]
     * @param PointEnum $to 目标坐标 [BD09, WGS84, GCJ02]
     * @return \luoyy\Spatial\Contracts\Point 目标坐标
     * @throw \InvalidArgumentException
     */
    public static function transform(ContractsPoint $point, PointEnum $to): ContractsPoint
    {
        if (($from = $point::COORDINATE_SYSTEM) === $to) {
            return $point;
        }
        if (!method_exists(static::class, $method = sprintf('%s_%s', $from->name, $to->name))) {
            throw new \InvalidArgumentException("Conversion type [{$from->name}] to [{$to->name}] is not supported, acceptable types: BD09, WGS84, GCJ02.");
        }
        return call_user_func([static::class, $method], $point);
    }

    protected static function offsetPoint(ContractsPoint $point): ContractsPoint
    {
        $dlng = static::transformLongitude(new Point($point->longitude - 105.0, $point->latitude - 35.0));
        $dlat = static::transformLatitude(new Point($point->longitude - 105.0, $point->latitude - 35.0));
        $radlat = $point->latitude / 180.0 * self::PI;
        $magic = sin($radlat);
        $sqrtmagic = sqrt($magic = 1 - self::FLATNESS * $magic * $magic);

        return new Point($dlng * 180.0 / (self::EARTHS_LONG_RADIUS / $sqrtmagic * cos($radlat) * self::PI), $dlat * 180.0 / (self::EARTHS_LONG_RADIUS * (1 - self::FLATNESS) / ($magic * $sqrtmagic) * self::PI));
    }

    protected static function transformLongitude(ContractsPoint $point): float
    {
        $lng = 300.0 + $point->longitude + 2.0 * $point->latitude + 0.1 * $point->longitude * $point->longitude + 0.1 * $point->longitude * $point->latitude + 0.1 * sqrt(abs($point->longitude));
        $lng += 2.0 * (20.0 * sin(6.0 * $point->longitude * self::PI) + 20.0 * sin(2.0 * $point->longitude * self::PI)) / 3.0;
        $lng += 2.0 * (20.0 * sin($point->longitude * self::PI) + 40.0 * sin($point->longitude / 3.0 * self::PI)) / 3.0;
        $lng += 2.0 * (150.0 * sin($point->longitude / 12.0 * self::PI) + 300.0 * sin($point->longitude / 30.0 * self::PI)) / 3.0;
        return $lng;
    }

    protected static function transformLatitude(ContractsPoint $point): float
    {
        $lat = 2.0 * $point->longitude - 100.0 + 3.0 * $point->latitude + 0.2 * $point->latitude * $point->latitude + 0.1 * $point->longitude * $point->latitude + 0.2 * sqrt(abs($point->longitude));
        $lat += 2.0 * (20.0 * sin(6.0 * $point->longitude * self::PI) + 20.0 * sin(2.0 * $point->longitude * self::PI)) / 3.0;
        $lat += 2.0 * (20.0 * sin($point->latitude * self::PI) + 40.0 * sin($point->latitude / 3.0 * self::PI)) / 3.0;
        $lat += 2.0 * (160.0 * sin($point->latitude / 12.0 * self::PI) + 320.0 * sin($point->latitude * self::PI / 30.0)) / 3.0;
        return $lat;
    }

    protected static function in_china(ContractsPoint $point): bool
    {
        return $point->longitude >= 72.004 && $point->longitude <= 137.8347 && $point->latitude >= 0.8293 && $point->latitude <= 55.8271;
    }
}
