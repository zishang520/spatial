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
     * WGS84坐标点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @throw RangeException
     */
    public function __construct(float $longitude, float $latitude)
    {
        $this->longitude = $longitude;
        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new RangeException('The longitude range must be between -180 and 180');
        }
        $this->latitude = $latitude;
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new RangeException('The latitude range must be between -90 and 90');
        }
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new RangeException('The latitude range must be between -90 and 90');
        }
        return $this;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new RangeException('The longitude range must be between -180 and 180');
        }
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
