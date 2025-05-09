<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

/**
 * BD09 坐标系点。
 * 继承自 GeometryPoint，构造时自动指定 BD09 坐标系。
 */
class PointBD09 extends Point
{
    public function getCoordinateSystem(): CoordinateSystemEnum
    {
        return CoordinateSystemEnum::BD09;
    }
}
