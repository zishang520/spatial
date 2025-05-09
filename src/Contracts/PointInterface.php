<?php

namespace luoyy\Spatial\Contracts;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

interface PointInterface
{
    /**
     * 获取坐标系。
     * @return CoordinateSystemEnum
     */
    public function getCoordinateSystem(): CoordinateSystemEnum;

    /**
     * 获取经度。
     * @return float|int
     */
    public function getLongitude(): float|int;

    /**
     * 获取纬度。
     * @return float|int
     */
    public function getLatitude(): float|int;

    /**
     * 获取高度（可选）。
     * @return float|int
     */
    public function getAltitude(): float|int;
}
