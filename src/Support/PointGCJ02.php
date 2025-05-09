<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

/**
 * GCJ02 坐标系点。
 * 继承自 GeometryPoint，构造时自动指定 GCJ02 坐标系。
 */
class PointGCJ02 extends Point
{
    public function getCoordinateSystem(): CoordinateSystemEnum
    {
        return CoordinateSystemEnum::GCJ02;
    }
}
