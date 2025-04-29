<?php

namespace luoyy\Spatial\Support;

use LengthException;
use luoyy\Spatial\Contracts\Point;

/**
 * 多边形不用处理最后一个点与第一个点相对.
 */
class Polygon implements \JsonSerializable, \IteratorAggregate
{
    /**
     * 坐标点.
     * @var array<Point>
     */
    public array $points;

    /**
     * 配置选项.
     */
    protected array $options = [
        '_useArray' => false,
        '_useAltitude' => false,
        '_useAutoClose' => true
    ];

    /**
     * 多边形构造函数.
     * @param Point $points 点
     * @throw LengthException
     */
    public function __construct(Point ...$points)
    {
        $this->setPoints(...$points);
    }

    /**
     * 设置多边形点.
     */
    public function setPoints(Point ...$points): self
    {
        if (count($points) < 3) {
            throw new LengthException('Polygon requires at least three points.');
        }
        $this->points = $points;
        return $this;
    }

    /**
     * 添加多边形点.
     */
    public function addPoint(Point ...$points): self
    {
        array_push($this->points, ...$points);
        return $this;
    }

    /**
     * 返回迭代器.
     */
    public function getIterator(): \Traversable
    {
        yield from $this->build();
    }

    /**
     * 设置数组输出格式.
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * 是否输出数组.
     */
    public function useArray(bool $useArray = true): self
    {
        $this->options['_useArray'] = $useArray;
        return $this;
    }

    /**
     * 是否输出高度.
     */
    public function useAltitude(bool $useAltitude = true): self
    {
        $this->options['_useAltitude'] = $useAltitude;
        return $this;
    }

    /**
     * 设置是否自动闭合多边形.
     */
    public function useAutoClose(bool $useAutoClose = true): self
    {
        $this->options['_useAutoClose'] = $useAutoClose;
        return $this;
    }

    /**
     * 转换为数组.
     */
    public function toArray(): array
    {
        $data = array_map(fn($point) => (clone $point)->useArray($this->options['_useArray'])
            ->useAltitude($this->options['_useAltitude'])
            ->toArray(), $this->build());
        return $this->options['_useArray'] ? $data : ['points' => $data];
    }

    /**
     * 转换为 JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 转换为几何结构.
     */
    public function toGeometry(): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [
                array_map(fn($point) => (clone $point)->useArray(true)
                    ->useAltitude($this->options['_useAltitude'])
                    ->toArray(), $this->build())
            ]
        ];
    }

    /**
     * 构建多边形点集.
     */
    protected function build(): array
    {
        $lastIdx = array_key_last($this->points);
        if ($this->options['_useAutoClose'] && $this->points[$lastIdx] != $this->points[0]) {
            $this->addPoint($this->points[0]);
        } elseif (!$this->options['_useAutoClose'] && $this->points[$lastIdx] == $this->points[0]) {
            array_pop($this->points);
        }
        return $this->points;
    }
}
