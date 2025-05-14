<?php

namespace luoyy\Spatial;

use luoyy\Spatial\Contracts\PointInterface;
use luoyy\Spatial\Enums\CoordinateSystemEnum;
use luoyy\Spatial\Enums\DirectionEnum;
use luoyy\Spatial\Enums\LocationEnum;
use luoyy\Spatial\Support\LineString;
use luoyy\Spatial\Support\Point;
use luoyy\Spatial\Support\PointWGS84;
use luoyy\Spatial\Support\Polygon;
use luoyy\Spatial\Support\RangePoint;

/**
 * 空间计算工具类，提供距离、面积、坐标变换等多种地理空间算法。
 */
class Spatial
{
    /**
     * 地球半径（单位：米）。
     */
    public const EARTH_RADIUS = 6378137.0;

    /**
     * 百度地球半径（单位：米）。
     */
    public const BD_EARTH_RADIUS = 6370996.81;

    /**
     * 圆周率常量。
     */
    public const PI = 3.1415926535897932384626;

    /**
     * 弧度换算常量。
     */
    public const RADIAN = self::PI / 180;

    /**
     * 计算两点之间的距离（支持海拔高程）。
     *
     * @param PointInterface $point1 坐标点1
     * @param PointInterface $point2 坐标点2
     * @param float $radius 球半径，默认地球半径
     * @return float 距离（米）
     */
    public static function distance(PointInterface $point1, PointInterface $point2, float $radius = self::EARTH_RADIUS): float
    {
        // 缓存属性，减少方法调用
        $lat1 = $point1->getLatitude();
        $lat2 = $point2->getLatitude();
        $long1 = $point1->getLongitude();
        $long2 = $point2->getLongitude();
        $alt1 = $point1->getAltitude();
        $alt2 = $point2->getAltitude();
        // 预先计算弧度
        $lat1Rad = $lat1 * self::RADIAN;
        $lat2Rad = $lat2 * self::RADIAN;
        $long1Rad = $long1 * self::RADIAN;
        $long2Rad = $long2 * self::RADIAN;
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLong = $long2Rad - $long1Rad;
        $sinDeltaLat = sin($deltaLat / 2);
        $sinDeltaLong = sin($deltaLong / 2);
        $a = $sinDeltaLat * $sinDeltaLat + cos($lat1Rad) * cos($lat2Rad) * $sinDeltaLong * $sinDeltaLong;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $radius * $c;
        $h = $alt2 - $alt1;
        if ($h == 0) {
            return $d;
        }
        return sqrt($d * $d + $h * $h);
    }

    /**
     * 计算点到线的最短距离。
     *
     * @param PointInterface $point 点坐标
     * @param LineString $lineString 线串
     * @return float 距离（米）
     */
    public static function distanceToLine(PointInterface $point, LineString $lineString): float
    {
        $distance = INF;
        $initial = null;
        foreach ($lineString->getCoordinates() as $_point) {
            if (! is_null($initial)) {
                $distance = min($distance, self::distance($point, self::closestOnSegment($point, new LineString([$initial, $_point]))));
            }
            $initial = $_point;
        }
        return $distance;
    }

    /**
     * 计算线段上距离点最近的点。
     *
     * @param Point $point 点坐标
     * @param LineString $lineString 仅包含2个点的线段
     * @return Point 最近点
     */
    public static function closestOnSegment(Point $point, LineString $lineString): Point
    {
        $points = $lineString->getCoordinates();
        $point1 = $points[0];
        $point2 = $points[1];
        // 缓存属性，减少方法调用
        $x1 = $point1[0];
        $y1 = $point1[1];
        $z1 = $point1[2] ?? 0;
        $x2 = $point2[0];
        $y2 = $point2[1];
        $z2 = $point2[2] ?? 0;
        $px = $point->getLongitude();
        $py = $point->getLatitude();
        $pz = $point->getAltitude();
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $dz = $z2 - $z1;
        $denom = $dx * $dx + $dy * $dy + $dz * $dz;
        $dot = $denom == 0 ? 0 : (($dx * ($px - $x1) + $dy * ($py - $y1) + $dz * ($pz - $z1)) / $denom);
        if ($dot <= 0) {
            $cx = $x1;
            $cy = $y1;
            $cz = $z1;
        } elseif ($dot >= 1) {
            $cx = $x2;
            $cy = $y2;
            $cz = $z2;
        } else {
            $cx = $x1 + $dot * $dx;
            $cy = $y1 + $dot * $dy;
            $cz = $z1 + $dot * $dz;
        }
        return new ($point::class)([$cx, $cy, $cz]);
    }

    /**
     * 计算线串上距离点最近的点。
     *
     * @param Point $point 点坐标
     * @param LineString $lineString 线串
     * @return Point 最近点
     */
    public static function closestOnLine(Point $point, LineString $lineString): Point
    {
        $out_point = null;
        $initial = null;
        $distance = INF;
        foreach ($lineString->getCoordinates() as $_point) {
            if (! is_null($initial)) {
                if (($d = self::distance($point, $p = self::closestOnSegment($point, new LineString([$initial, $_point])))) < $distance) {
                    $distance = $d;
                    $out_point = $p;
                }
            }
            $initial = $_point;
        }
        return $out_point;
    }

    /**
     * 计算线串长度。
     *
     * @param LineString $lineString 线串
     * @param float $radius 球半径
     * @return float 距离（米）
     */
    public static function lineDistance(LineString $lineString, float $radius = self::EARTH_RADIUS): float
    {
        $initial = null;
        $result = 0.0;
        foreach ($lineString->getCoordinates() as $point) {
            if (! is_null($initial)) {
                $result += self::distance(new PointWGS84($initial), new PointWGS84($point), $radius);
            }
            $initial = $point;
        }
        return $result;
    }

    /**
     * 计算多边形面积。
     *
     * @param Polygon $polygon 多边形
     * @param float $radius 球半径
     * @return float 面积（平方米）
     */
    public static function ringArea(Polygon $polygon, float $radius = self::EARTH_RADIUS): float
    {
        $i = $radius * self::RADIAN;
        $initial = null;
        $result = 0.0;
        foreach ($polygon->getCoordinates() as $lineStrings) {
            foreach ($lineStrings as $point) {
                if (! is_null($initial)) {
                    $result += ($initial[0] * $i * cos($initial[1] * self::RADIAN) * $point[1] * $i - $point[0] * $i * cos($point[1] * self::RADIAN) * $initial[1] * $i);
                }
                $initial = $point;
            }
        }
        return 0.5 * abs($result);
    }

    /**
     * 获取某点为中心的范围（最大最小点）。
     *
     * @param Point $point 中心点
     * @param float $dist 距离（米）
     * @param float $radius 球半径
     * @return RangePoint 范围对象
     */
    public static function pointRange(Point $point, float $dist, float $radius = self::EARTH_RADIUS): RangePoint
    {
        $range = 180 / self::PI * $dist / $radius;
        $lngR = $range / cos($point->getLatitude() * self::RADIAN);
        return new RangePoint($point->getLongitude() + $lngR, $point->getLatitude() + $range, $point->getLongitude() - $lngR, $point->getLatitude() - $range);
    }

    /**
     * 获取某点为中心的范围（指定方位）。
     *
     * @param PointInterface $point 中心点
     * @param float $dist 距离（米）
     * @param LocationEnum $location 顶点方位
     * @param float $radius 球半径
     * @return RangePoint 范围对象
     */
    public static function pointLocationRange(PointInterface $point, float $dist, LocationEnum $location, float $radius = self::EARTH_RADIUS): RangePoint
    {
        $range = 180 / self::PI * $dist / $radius;
        $lngR = $range / cos($point->getLatitude() * self::RADIAN);
        return match ($location) {
            LocationEnum::NORTHWEST => new RangePoint($point->getLongitude() + $lngR, $point->getLatitude(), $point->getLongitude(), $point->getLatitude() - $range, $point->getAltitude()),
            LocationEnum::NORTHEAST => new RangePoint($point->getLongitude(), $point->getLatitude(), $point->getLongitude() - $lngR, $point->getLatitude() - $range, $point->getAltitude()),
            LocationEnum::SOUTHEAST => new RangePoint($point->getLongitude(), $point->getLatitude() + $range, $point->getLongitude() - $lngR, $point->getLatitude(), $point->getAltitude()),
            LocationEnum::SOUTHWEST => new RangePoint($point->getLongitude() + $lngR, $point->getLatitude() + $range, $point->getLongitude(), $point->getLatitude(), $point->getAltitude()),
        };
    }

    /**
     * 平移一个点。
     *
     * @param Point $point 坐标点
     * @param float $dist 距离（米）
     * @param DirectionEnum $direction 方向
     * @param float $radius 球半径
     * @return Point 平移后的点
     */
    public static function pointPanning(Point $point, float $dist, DirectionEnum $direction, float $radius = self::EARTH_RADIUS): Point
    {
        $range = 180 / self::PI * $dist / $radius;
        $h = $point->getAltitude();
        return match ($direction) {
            DirectionEnum::LEFT => new ($point::class)([$point->getLongitude() - ($range / cos($point->getLatitude() * self::RADIAN)), $point->getLatitude()] + ($h == 0 ? [] : [$h])),
            DirectionEnum::RIGHT => new ($point::class)([$point->getLongitude() + ($range / cos($point->getLatitude() * self::RADIAN)), $point->getLatitude()] + ($h == 0 ? [] : [$h])),
            DirectionEnum::UP => new ($point::class)([$point->getLongitude(), $point->getLatitude() + $range] + ($h == 0 ? [] : [$h])),
            DirectionEnum::DOWN => new ($point::class)([$point->getLongitude(), $point->getLatitude() - $range] + ($h == 0 ? [] : [$h])),
        };
    }

    /**
     * 按距离和方位移动点。
     *
     * @param Point $point 坐标点
     * @param float $dist 距离（米）
     * @param float $bearing 方位角（度）
     * @param float $radius 球半径
     * @return Point 移动后的点
     */
    public static function move(Point $point, float $dist, float $bearing, float $radius = self::EARTH_RADIUS): Point
    {
        $scale = $dist / $radius;
        $fai = $point->getLatitude() * self::RADIAN;
        $bear = fmod($bearing, 360) * self::RADIAN;
        $end_lat = asin(sin($fai) * cos($scale) + cos($fai) * sin($scale) * cos($bear));
        $end_lng = $point->getLongitude() + atan2(sin($bear) * sin($scale) * cos($fai), cos($scale) - sin($fai) * sin($end_lat)) / self::RADIAN;
        $h = $point->getAltitude();
        return (clone $point::class)([fmod($end_lng + 540, 360) - 180, $end_lat / self::RADIAN] + ($h == 0 ? [] : [$h]));
    }

    public static function panning(Point $point, float $dist, float $bearing, float $radius = self::EARTH_RADIUS): Point
    {
        return static::move($point, $dist, $bearing, $radius);
    }

    /**
     * 计算两点间的方位角。
     *
     * @param Point $point1 点1
     * @param Point $point2 点2
     * @return float 方位角（度）
     */
    public static function bearing(Point $point1, Point $point2): float
    {
        $fat = $point1->getLatitude() * self::RADIAN;
        $fai2 = $point2->getLatitude() * self::RADIAN;
        $temp = ($point2->getLongitude() - $point1->getLongitude()) * self::RADIAN;
        $bearing = atan2(sin($temp) * cos($fai2), cos($fat) * sin($fai2) - sin($fat) * cos($fai2) * cos($temp)) / self::RADIAN;
        return ($bearing < 0) ? $bearing + 360 : $bearing;
    }

    /**
     * 坐标系转换。
     *
     * @param Point $point 原始点
     * @param CoordinateSystemEnum $to 目标坐标系
     * @return Point 转换后的点
     * @throws \InvalidArgumentException 不支持的转换类型
     */
    public static function transform(Point $point, CoordinateSystemEnum $to): Point
    {
        return Transform::transform($point, $to);
    }

    /**
     * 获取 EGM96 平均海平面高度。
     *
     * @param Point $point 坐标点（需设置 altitude）
     * @return float 平均海平面高度（米）
     */
    public static function meanSeaLevel(Point $point): float
    {
        return EGM96Universal::meanSeaLevel($point);
    }

    /**
     * WGS84 椭球高转 EGM96 高。
     *
     * @param Point $point 坐标点（需设置 altitude）
     * @return float EGM96 高（米）
     */
    public static function ellipsoidToEgm96(Point $point): float
    {
        return EGM96Universal::ellipsoidToEgm96($point);
    }

    /**
     * EGM96 高转 WGS84 椭球高。
     *
     * @param Point $point 坐标点（需设置 altitude）
     * @return float 椭球高（米）
     */
    public static function egm96ToEllipsoid(Point $point): float
    {
        return EGM96Universal::egm96ToEllipsoid($point);
    }
}
