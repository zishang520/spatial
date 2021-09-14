<?php

namespace luoyy\Spatial\Support;

use JsonSerializable;
use RangeException;

class Point implements JsonSerializable
{
    /**
     * 经度.
     * @var float
     */
    public $longitude;

    /**
     * 纬度.
     * @var float
     */
    public $latitude;

    /**
     * 是否自动修正.
     * @var bool
     */
    protected $noAutofix = false;

    /**
     * 坐标点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param bool|null $noAutofix noAutoFix表示是否自动将经度修正到 [-180,180] 区间内，缺省为false
     * @throw RangeException
     */
    public function __construct(float $longitude, float $latitude, ?bool $noAutofix = null)
    {
        $this->noAutofix = $noAutofix ?? $this->noAutofix;
        $this->setLongitude($longitude);
        $this->setLatitude($latitude);
    }

    public function setLatitude(float $latitude): self
    {
        if (!is_finite($latitude)) {
            throw new RangeException('Latitude must be a finite value.');
        }
        if (!$this->noAutofix) {
            $latitude = max(min($latitude, 90), -90);
        }
        $this->latitude = $latitude;
        return $this;
    }

    public function setLongitude(float $longitude): self
    {
        if (!is_finite($longitude)) {
            throw new RangeException('Longitude must be a finite value.');
        }
        if (!$this->noAutofix) {
            $longitude = fmod($longitude + 180, 360) + (-180 > $longitude || $longitude === 180 ? 180 : -180);
        }
        $this->longitude = $longitude;
        return $this;
    }

    public function toArray()
    {
        return [
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
