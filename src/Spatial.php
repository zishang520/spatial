<?php

namespace luoyy\Spatial;

use InvalidArgumentException;
use luoyy\Spatial\Contracts\Point;
use luoyy\Spatial\Enums\DirectionEnum;
use luoyy\Spatial\Enums\PointEnum;
use luoyy\Spatial\Support\LineString;
use luoyy\Spatial\Support\Polygon;
use luoyy\Spatial\Support\RangePoint;

class Spatial
{
    /**
     * 地球半径.
     */
    public const EARTH_RADIUS = 6378137.0;

    /**
     * 百度地球半径.
     */
    public const BD_EARTH_RADIUS = 6370996.81;

    /**
     * PI.
     */
    public const PI = 3.1415926535897932384626;

    public const RADIAN = self::PI / 180;

    /**
     * 计算两点之间的距离.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point1 坐标1
     * @param Point $point2 坐标2
     * @param float $radius 球半径
     * @return float 距离/M
     */
    public static function distance(Point $point1, Point $point2, float $radius = self::EARTH_RADIUS): float
    {
        return 2 * $radius * asin((float) bcsqrt((1 - cos($point2->latitude * self::RADIAN - $point1->latitude * self::RADIAN) + (1 - cos($point2->longitude * self::RADIAN - $point1->longitude * self::RADIAN)) * cos($point2->latitude * self::RADIAN) * cos($point1->latitude * self::RADIAN)) / 2, 30));
    }

    /**
     * 计算P到line的距离。单位：米.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point P点坐标
     * @param LineString $lineString 线段
     * @return float 距离/M
     */
    public static function distanceToLine(Point $point, LineString $lineString): float
    {
        $distance = INF;
        $initial = null;
        foreach ($lineString->getIterator() as $_point) {
            if (!is_null($initial)) {
                $distance = min($distance, self::distance($point, self::closestOnSegment($point, new LineString($initial, $_point))));
            }
            $initial = $_point;
        }
        return $distance;
    }

    /**
     * 计算线段上距离P最近的点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point P点坐标
     * @param LineString $lineString 只有2个点的线段
     * @return Point 最近的一个坐标
     */
    public static function closestOnSegment(Point $point, LineString $lineString): Point
    {
        $point1 = $lineString->points[0];
        $point2 = $lineString->points[1];
        $longitude = $point2->longitude - $point1->longitude;
        $latitude = $point2->latitude - $point1->latitude;
        $dot = $longitude == 0 && $latitude == 0 ? 0 : ($longitude * ($point->longitude - $point1->longitude) + $latitude * ($point->latitude - $point1->latitude)) / ($longitude * $longitude + $latitude * $latitude);
        if ($dot <= 0) {
            $longitude = $point1->longitude;
            $latitude = $point1->latitude;
        } elseif (1 <= $dot) {
            $longitude = $point2->longitude;
            $latitude = $point2->latitude;
        } else {
            $longitude = $point1->longitude + $dot * $longitude;
            $latitude = $point1->latitude + $dot * $latitude;
        }

        return (clone $point)->setLongitude($longitude)->setLatitude($latitude);
    }

    /**
     * 计算line上距离P最近的点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point P点坐标
     * @param LineString $lineString 线段
     * @return Point 最近的一个坐标
     */
    public static function closestOnLine(Point $point, LineString $lineString): Point
    {
        $out_point = null;
        $initial = null;
        $distance = INF;
        foreach ($lineString->getIterator() as $_point) {
            if (!is_null($initial)) {
                if (($d = self::distance($point, $p = self::closestOnSegment($point, new LineString($initial, $_point)))) < $distance) {
                    $distance = $d;
                    $out_point = $p;
                }
            }
            $initial = $_point;
        }
        return $out_point;
    }

    /**
     * 线段的长度.
     * @copyright (c) zishang520 All Rights Reserved
     * @param LineString $lineString 多个点组成的线
     * @param float $radius 球半径
     * @return float 距离/M
     */
    public static function lineDistance(LineString $lineString, float $radius = self::EARTH_RADIUS): float
    {
        $initial = null;
        $result = 0.0;
        foreach ($lineString->getIterator() as $point) {
            if (!is_null($initial)) {
                $result += self::distance($initial, $point, $radius);
            }
            $initial = $point;
        }
        return $result;
    }

    /**
     * 计算面积.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Polygon $polygon 多边形（最后一点不需要与第一点相等）
     * @param float $radius 球半径
     * @return float 面积/㎡
     */
    public static function ringArea(Polygon $polygon, float $radius = self::EARTH_RADIUS): float
    {
        $i = $radius * self::RADIAN;
        $initial = null;
        $result = 0.0;
        foreach ($polygon->getIterator() as $point) {
            if (!is_null($initial)) {
                $result += ($initial->longitude * $i * cos($initial->latitude * self::RADIAN) * $point->latitude * $i - $point->longitude * $i * cos($point->latitude * self::RADIAN) * $initial->latitude * $i);
            }
            $initial = $point;
        }
        return 0.5 * abs($result);
    }

    /**
     * 某一点范围内的最大最小点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point 坐标点
     * @param int $dist 距离/M
     * @param float $radius 球半径
     * @return RangePoint 范围坐标
     */
    public static function pointRange(Point $point, int $dist, float $radius = self::EARTH_RADIUS): RangePoint
    {
        $range = 180 / self::PI * $dist / $radius;
        $lngR = $range / cos($point->latitude * self::RADIAN);
        return new RangePoint($point->longitude + $lngR, $point->latitude + $range, $point->longitude - $lngR, $point->latitude - $range);
    }

    /**
     * 平移一个点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point 坐标点
     * @param int $dist 距离/M
     * @param DirectionEnum $direction 方向 8 UP 2 DOWN 4 LEFT 6 RIGHT
     * @param float $radius 球半径
     * @return Point 移动后的坐标点
     */
    public static function pointPanning(Point $point, int $dist, DirectionEnum $direction, float $radius = self::EARTH_RADIUS): Point
    {
        $range = 180 / self::PI * $dist / $radius;
        return match ($direction) {
            DirectionEnum::LEFT => (clone $point)->setLongitude($point->longitude - ($range / cos($point->latitude * self::RADIAN)))->setLatitude($point->latitude),
            DirectionEnum::RIGHT => (clone $point)->setLongitude($point->longitude + ($range / cos($point->latitude * self::RADIAN)))->setLatitude($point->latitude),
            DirectionEnum::UP => (clone $point)->setLongitude($point->longitude)->setLatitude($point->latitude + $range),
            DirectionEnum::DOWN => (clone $point)->setLongitude($point->longitude)->setLatitude($point->latitude - $range),
        };
    }

    /**
     * 移动一个点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point 坐标点
     * @param int $dist 距离/M
     * @param int $bearing 角度 [0-360]
     * @param float $radius 球半径
     * @return Point 移动后的坐标点
     */
    public static function move(Point $point, int $dist, int $bearing, float $radius = self::EARTH_RADIUS): Point
    {
        $scale = $dist / $radius;
        $fai = $point->latitude * self::RADIAN;
        $bear = fmod($bearing, 360) * self::RADIAN;
        $end_lat = asin(sin($fai) * cos($scale) + cos($fai) * sin($scale) * cos($bear));
        $end_lng = $point->longitude + atan2(sin($bear) * sin($scale) * cos($fai), cos($scale) - sin($fai) * sin($end_lat)) / self::RADIAN;
        return (clone $point)->setLongitude(fmod($end_lng + 540, 360) - 180)->setLatitude($end_lat / self::RADIAN);
    }

    public static function panning(Point $point, int $dist, int $bearing, float $radius = self::EARTH_RADIUS): Point
    {
        return static::move($point, $dist, $bearing, $radius);
    }

    /**
     * 计算两点之间的角度.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point1 坐标1
     * @param Point $point2 坐标2
     * @return float 角度
     */
    public static function bearing(Point $point1, Point $point2): float
    {
        $fat = $point1->latitude * self::RADIAN;
        $fai2 = $point2->latitude * self::RADIAN;
        $temp = ($point2->longitude - $point1->longitude) * self::RADIAN;
        $bearing = atan2(sin($temp) * cos($fai2), cos($fat) * sin($fai2) - sin($fat) * cos($fai2) * cos($temp)) / self::RADIAN;
        return ($bearing < 0) ? $bearing + 360 : $bearing;
    }

    /**
     * 转换一个坐标.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point 原坐标
     * @param PointEnum $to 目标坐标 [BD09, WGS84, GCJ02]
     * @return Point 目标坐标
     * @throw InvalidArgumentException
     */
    public static function transform(Point $point, PointEnum $to): Point
    {
        return Transform::transform($point, $to);
    }
}
