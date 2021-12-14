<?php

namespace luoyy\Spatial;

use luoyy\Spatial\Support\LineString;
use luoyy\Spatial\Support\Point;
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
    protected const PI = 3.1415926535897932384626;

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
        return 2 * $radius * asin(sqrt((1 - cos($point2->latitude * self::PI / 180 - $point1->latitude * self::PI / 180) + (1 - cos($point2->longitude * self::PI / 180 - $point1->longitude * self::PI / 180)) * cos($point2->latitude * self::PI / 180) * cos($point1->latitude * self::PI / 180)) / 2));
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
        if (end($polygon->points) != $polygon->points[0]) {
            $polygon->addPoint($polygon->points[0]);
        }
        $i = $radius * self::PI / 180;
        $initial = null;
        $result = 0.0;
        foreach ($polygon->getIterator() as $point) {
            if (!is_null($initial)) {
                $result += ($initial->longitude * $i * cos($initial->latitude * self::PI / 180) * $point->latitude * $i - $point->longitude * $i * cos($point->latitude * self::PI / 180) * $initial->latitude * $i);
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
        $lngR = $range / cos($point->latitude * self::PI / 180);
        return new RangePoint($point->longitude + $lngR, $point->latitude + $range, $point->longitude - $lngR, $point->latitude - $range);
    }

    /**
     * 转换一个坐标.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point 原坐标
     * @param string $from 来源坐标 [BD09, WGS84, GCJ02]
     * @param string $to 目标坐标 [BD09, WGS84, GCJ02]
     * @return Point 目标坐标
     * @throw InvalidArgumentException
     */
    public static function transform(Point $point, string $from, string $to): Point
    {
        return Transform::transform($point, $from, $to);
    }
}
