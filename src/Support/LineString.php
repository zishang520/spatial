<?php

namespace luoyy\Spatial\Support;

use IteratorAggregate;
use JsonSerializable;
use Traversable;

class LineString implements JsonSerializable, IteratorAggregate
{
    /**
     * 坐标点.
     * @var Point[]
     */
    public $points;

    /**
     * 是否输出数组.
     * @var bool
     */
    protected $useArray = false;

    /**
     * 线.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $points 点
     */
    public function __construct(Point ...$points)
    {
        $this->setPoints(...$points);
    }

    public function setPoints(Point ...$points): self
    {
        $this->points = $points;
        return $this;
    }

    public function addPoint(Point $point): self
    {
        array_push($this->points, $point);
        return $this;
    }

    public function getIterator(): Traversable
    {
        yield from $this->points;
    }

    /**
     * 是否输出数组.
     * @copyright (c) zishang520 All Rights Reserved
     */
    public function useArray(bool $useArray = true)
    {
        $this->useArray = $useArray;
        return $this;
    }

    public function toArray()
    {
        return $this->useArray ? array_map(function ($point) {return $point->useArray($this->useArray)->toArray(); }, $this->points) : ['points' => array_map(function ($point) {return $point->useArray($this->useArray)->toArray(); }, $this->points)];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
