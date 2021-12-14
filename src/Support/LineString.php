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

    public function toArray()
    {
        return [
            'points' => array_map(function ($point) {return $point->toArray(); }, $this->points),
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
