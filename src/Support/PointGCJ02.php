<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Enums\PointEnum;

class PointGCJ02 extends ContractsPoint
{
    public const COORDINATE_SYSTEM = PointEnum::GCJ02;
}
