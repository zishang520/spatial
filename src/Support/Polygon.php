<?php

namespace luoyy\Spatial\Support;

use IteratorAggregate;
use JsonSerializable;
use LengthException;
use Traversable;

/**
 * 多边形不用处理最后一个点与第一个点相对.
 */
class Polygon implements JsonSerializable, IteratorAggregate
{
    /**
     * 坐标点.
     * @var Point[]
     */
    public $points;

    /**
     * 多边形.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $points 点
     * @throw LengthException
     */
    public function __construct(Point ...$points)
    {
        $this->setPoints(...$points);
    }

    public function setPoints(Point ...$points): self
    {
        if (count($points) < 3) {
            throw new LengthException('Polygon requires at least three points.');
        }
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
