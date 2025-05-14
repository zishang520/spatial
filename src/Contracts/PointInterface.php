<?php

namespace luoyy\Spatial\Contracts;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

interface PointInterface
{
    /**
     * 获取坐标系。
     */
    public function getCoordinateSystem(): CoordinateSystemEnum;

    /**
     * 获取经度。
     */
    public function getLongitude(): float|int;

    /**
     * 获取纬度。
     */
    public function getLatitude(): float|int;

    /**
     * 获取高度（可选）。
     */
    public function getAltitude(): float|int;
}
