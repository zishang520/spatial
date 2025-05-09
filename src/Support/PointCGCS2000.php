<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

/**
 * CGCS2000 坐标系点。
 * 继承自 GeometryPoint，构造时自动指定 CGCS2000 坐标系。
 */
class PointCGCS2000 extends Point
{
    public function getCoordinateSystem(): CoordinateSystemEnum
    {
        return CoordinateSystemEnum::CGCS2000;
    }
}
