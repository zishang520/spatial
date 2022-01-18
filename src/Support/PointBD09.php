<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Transform;

class PointBD09 extends ContractsPoint
{
    public const COORDINATE_SYSTEM = Transform::BD09;
}
