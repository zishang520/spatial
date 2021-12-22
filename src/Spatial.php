<?php

namespace luoyy\Spatial;

use InvalidArgumentException;
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

    public const DIRECTION_UP = 8;

    public const DIRECTION_DOWN = 2;

    public const DIRECTION_LEFT = 4;

    public const DIRECTION_RIGHT = 6;

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
        return 2 * $radius * asin(sqrt((1 - cos($point2->latitude * self::RADIAN - $point1->latitude * self::RADIAN) + (1 - cos($point2->longitude * self::RADIAN - $point1->longitude * self::RADIAN)) * cos($point2->latitude * self::RADIAN) * cos($point1->latitude * self::RADIAN)) / 2));
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
     * @param int $direction 方向 8 up 2 down 4 left 6 right
     * @param float $radius 球半径
     * @return Point 移动后的坐标点
     */
    public static function pointPanning(Point $point, int $dist, int $direction, float $radius = self::EARTH_RADIUS): Point
    {
        $range = 180 / self::PI * $dist / $radius;
        switch ($direction) {
            case self::DIRECTION_LEFT:
                return new Point($point->longitude - ($range / cos($point->latitude * self::RADIAN)), $point->latitude);
                break;
            case self::DIRECTION_RIGHT:
                return new Point($point->longitude + ($range / cos($point->latitude * self::RADIAN)), $point->latitude);
                break;
            case self::DIRECTION_UP:
                return new Point($point->longitude, $point->latitude + $range);
                break;
            case self::DIRECTION_DOWN:
                return new Point($point->longitude, $point->latitude - $range);
                break;
        }

        throw new InvalidArgumentException('Invalid pan direction.');
    }

    /**
     * 一个点按照某个角度（正北开始）和距离获得下一个点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $point 坐标点
     * @param int $dist 距离/M
     * @param int $bearing 角度 [0-360]
     * @param float $radius 球半径
     * @return Point 移动后的坐标点
     */
    public static function panning(Point $point, int $dist, int $bearing, float $radius = self::EARTH_RADIUS): Point
    {
        $scale = $dist / $radius;
        $fai = $point->latitude * self::RADIAN;
        $bear = fmod($bearing, 360) * self::RADIAN;
        $end_lat = asin(sin($fai) * cos($scale) + cos($fai) * sin($scale) * cos($bear));
        $end_lng = $point->longitude + atan2(sin($bear) * sin($scale) * cos($fai), cos($scale) - sin($fai) * sin($end_lat)) / self::RADIAN;
        return new Point(fmod($end_lng + 540, 360) - 180, $end_lat / self::RADIAN);
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
