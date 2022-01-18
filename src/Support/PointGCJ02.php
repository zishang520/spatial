<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Transform;

class PointGCJ02 extends ContractsPoint
{
    public const COORDINATE_SYSTEM = Transform::GCJ02;
}
