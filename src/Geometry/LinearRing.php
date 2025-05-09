<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

use function count;
use function end;
use function reset;

/**
 * 线性环（LinearRing），是特殊的 LineString，首尾坐标相同且至少包含四个点。
 *
 * @see http://www.geojson.org/geojson-spec.html#linestring
 * @since 1.0
 */
class LinearRing extends LineString
{
    /**
     * 构造函数。
     *
     * @param array<Point|array<int|float>> $positions 点对象或坐标数组，至少4个，且首尾相同。
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     * @throws \InvalidArgumentException 如果点数不足或首尾不相等。
     */
    public function __construct(array $positions, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        if (count($positions) < 4) {
            throw new \InvalidArgumentException('LinearRing requires at least four positions');
        }

        $lastPosition = end($positions);
        $firstPosition = reset($positions);

        $lastPosition = $lastPosition instanceof Point ? $lastPosition->getCoordinates() : $lastPosition;
        $firstPosition = $firstPosition instanceof Point ? $firstPosition->getCoordinates() : $firstPosition;

        if ($lastPosition !== $firstPosition) {
            throw new \InvalidArgumentException('LinearRing requires the first and last positions to be equivalent');
        }

        parent::__construct($positions, ...$args);
    }

    /**
     * 获取类型。
     *
     * @return TypeEnum 类型枚举，恒为 TypeEnum::LINE_STRING。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::LINE_STRING;
    }
}
