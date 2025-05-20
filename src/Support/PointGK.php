<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\CoordinateSystemEnum;

/**
 * 高斯-克吕格(Gauss-Krüger) 投影坐标系点。
 * 继承自 GeometryPoint，构造时自动指定 GK 坐标系。
 */
class PointGK extends Point
{
    /**
     * 构造函数。
     *
     * @param array<float|int> $position 坐标数组，至少包含东坐标、北坐标、带号
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系或边界框。
     * @throws \InvalidArgumentException 如果坐标元素不足3个
     */
    public function __construct(array $position, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        if (count($position) < 3) {
            throw new \InvalidArgumentException('Position requires at least three elements');
        }
        parent::__construct($position, ...$args);
    }

    public function getCoordinateSystem(): CoordinateSystemEnum
    {
        return CoordinateSystemEnum::GK;
    }
}
