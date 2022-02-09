<?php

namespace luoyy\Spatial\Enums;

use luoyy\Spatial\Support\PointBD09;
use luoyy\Spatial\Support\PointGCJ02;
use luoyy\Spatial\Support\PointWGS84;

enum PointEnum: string
{
    case BD09 = PointBD09::class;
    case GCJ02 = PointGCJ02::class;
    case WGS84 = PointWGS84::class;
}
