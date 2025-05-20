<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\Contracts\PointInterface;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\CoordinateSystemEnum;
use luoyy\Spatial\Geometry\Point as GeometryPoint;
use luoyy\Spatial\Spatial;
use luoyy\Spatial\Transform;

/**
 * WGS84 坐标系点（支持可选边界框、参考系参数）。
 */
abstract class Point extends GeometryPoint implements PointInterface
{
    /**
     * 构造函数。
     *
     * @param array<float|int> $position 坐标数组，至少包含经度和纬度
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系或边界框。
     */
    public function __construct(array $position, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        parent::__construct($position, ...$args);
    }

    /**
     * 坐标点.
     * @copyright (c) zishang520 All Rights Reserved
     * @throw \RangeException
     */
    public static function make(array $position, CoordinateReferenceSystem|BoundingBox ...$args): static
    {
        return (new \ReflectionClass(static::class))->newInstanceArgs([$position, ...$args]);
    }

    /**
     * 获取坐标系统类型。
     */
    abstract public function getCoordinateSystem(): CoordinateSystemEnum;

    /**
     * 坐标系转换。
     *
     * @param CoordinateSystemEnum $coordinateSystemEnum 目标坐标系
     */
    public function transform(CoordinateSystemEnum $coordinateSystemEnum): Point
    {
        return Transform::transform($this, $coordinateSystemEnum);
    }

    /**
     * 按距离和方位移动点。
     *
     * @param float $dist 距离（单位：米）
     * @param float $bearing 方位角（单位：度）
     * @param float $radius 半径（单位：米），默认地球半径
     */
    public function move(float $dist, float $bearing, float $radius = Spatial::EARTH_RADIUS): Point
    {
        return Spatial::move($this, $dist, $bearing, $radius);
    }
}
