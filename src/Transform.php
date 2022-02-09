<?php

namespace luoyy\Spatial;

use InvalidArgumentException;
use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Support\PointBD09;
use luoyy\Spatial\Support\PointGCJ02;
use luoyy\Spatial\Support\PointWGS84;

/**
 * 坐标转换.
 */
class Transform
{
    public const BD09 = 'BD09';

    public const WGS84 = 'WGS84';

    public const GCJ02 = 'GCJ02';

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
        return new PointGCJ02($postion * cos($offset), $postion * sin($offset));
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
            return new PointGCJ02($point->longitude, $point->latitude);
        }
        $dlat = static::transformLat(new PointWGS84($point->longitude - 105.0, $point->latitude - 35.0));
        $dlng = static::transformLng(new PointWGS84($point->longitude - 105.0, $point->latitude - 35.0));
        $radlat = $point->latitude / 180.0 * self::PI;
        $magic = sin($radlat);
        $magic = 1 - self::FLATNESS * $magic * $magic;
        $sqrtmagic = sqrt($magic);
        $dlat = ($dlat * 180.0) / ((self::EARTHS_LONG_RADIUS * (1 - self::FLATNESS)) / ($magic * $sqrtmagic) * self::PI);
        $dlng = ($dlng * 180.0) / (self::EARTHS_LONG_RADIUS / $sqrtmagic * cos($radlat) * self::PI);
        return new PointGCJ02($point->longitude + $dlng, $point->latitude + $dlat);
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
        return new PointBD09($postion * cos($offset) + 0.0065, $postion * sin($offset) + 0.006);
    }

    /**
     * GCJ02转WGS84.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Support\PointGCJ02 $point GCJ02坐标
     */
    public static function GCJ02_WGS84(PointGCJ02 $point): PointWGS84
    {
        if (!static::in_china($point)) {
            return new PointWGS84($point->longitude, $point->latitude);
        }
        $dlat = static::transformLat(new PointGCJ02($point->longitude - 105.0, $point->latitude - 35.0));
        $dlng = static::transformLng(new PointGCJ02($point->longitude - 105.0, $point->latitude - 35.0));
        $radlat = $point->latitude / 180.0 * self::PI;
        $magic = sin($radlat);
        $magic = 1 - self::FLATNESS * $magic * $magic;
        $sqrtmagic = sqrt($magic);
        $dlat = ($dlat * 180.0) / ((self::EARTHS_LONG_RADIUS * (1 - self::FLATNESS)) / ($magic * $sqrtmagic) * self::PI);
        $dlng = ($dlng * 180.0) / (self::EARTHS_LONG_RADIUS / $sqrtmagic * cos($radlat) * self::PI);
        return new PointWGS84($point->longitude * 2 - ($point->longitude + $dlng), $point->latitude * 2 - ($point->latitude + $dlat));
    }

    /**
     * 转换一个坐标.
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Contracts\Point $point 原坐标
     * @param string $from 来源坐标 [BD09, WGS84, GCJ02]
     * @param string $to 目标坐标 [BD09, WGS84, GCJ02]
     * @return \luoyy\Spatial\Contracts\Point 目标坐标
     * @throw InvalidArgumentException
     */
    public static function transform(ContractsPoint $point, string $to): ContractsPoint
    {
        if (($from = $point::COORDINATE_SYSTEM) == $to) {
            return $point;
        }
        if (!method_exists(static::class, $method = sprintf('%s_%s', $from, $to))) {
            throw new InvalidArgumentException("Conversion type [{$from}] to [{$to}] is not supported, acceptable types: BD09, WGS84, GCJ02.");
        }
        return call_user_func([static::class, $method], $point);
    }

    protected static function transformLat(ContractsPoint $point): float
    {
        $lat = -100.0 + 2.0 * $point->longitude + 3.0 * $point->latitude + 0.2 * $point->latitude * $point->latitude + 0.1 * $point->longitude * $point->latitude + 0.2 * sqrt(abs($point->longitude));
        $lat += (20.0 * sin(6.0 * $point->longitude * self::PI) + 20.0 * sin(2.0 * $point->longitude * self::PI)) * 2.0 / 3.0;
        $lat += (20.0 * sin($point->latitude * self::PI) + 40.0 * sin($point->latitude / 3.0 * self::PI)) * 2.0 / 3.0;
        $lat += (160.0 * sin($point->latitude / 12.0 * self::PI) + 320 * sin($point->latitude * self::PI / 30.0)) * 2.0 / 3.0;
        return $lat;
    }

    protected static function transformLng(ContractsPoint $point): float
    {
        $lng = 300.0 + $point->longitude + 2.0 * $point->latitude + 0.1 * $point->longitude * $point->longitude + 0.1 * $point->longitude * $point->latitude + 0.1 * sqrt(abs($point->longitude));
        $lng += (20.0 * sin(6.0 * $point->longitude * self::PI) + 20.0 * sin(2.0 * $point->longitude * self::PI)) * 2.0 / 3.0;
        $lng += (20.0 * sin($point->longitude * self::PI) + 40.0 * sin($point->longitude / 3.0 * self::PI)) * 2.0 / 3.0;
        $lng += (150.0 * sin($point->longitude / 12.0 * self::PI) + 300.0 * sin($point->longitude / 30.0 * self::PI)) * 2.0 / 3.0;
        return $lng;
    }

    protected static function in_china(ContractsPoint $point): bool
    {
        return $point->longitude > 73.66 && $point->longitude < 135.05 && $point->latitude > 3.86 && $point->latitude < 53.55;
    }
}
