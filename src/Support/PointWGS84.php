<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

/**
 * WGS84 坐标系点。
 * 继承自 Point，构造时自动指定 WGS84 坐标系。
 */
class PointWGS84 extends Point
{
    public function getCoordinateSystem(): CoordinateSystemEnum
    {
        return CoordinateSystemEnum::WGS84;
    }
}
