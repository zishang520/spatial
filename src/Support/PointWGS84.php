<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Enums\PointEnum;

class PointWGS84 extends ContractsPoint
{
    public const COORDINATE_SYSTEM = PointEnum::WGS84;
}
