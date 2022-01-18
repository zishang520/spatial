<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point as ContractsPoint;
use luoyy\Spatial\Transform;

class Point extends ContractsPoint
{
    protected const COORDINATE_SYSTEM = Transform::WGS84;
}
