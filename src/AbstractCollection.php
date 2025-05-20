<?php

namespace luoyy\Spatial;

/**
 * 抽象集合基类，统一集合相关逻辑。
 *
 * 提供类型安全的集合存储、遍历、计数等功能。
 */
abstract class AbstractCollection extends GeoJson implements \Countable, \IteratorAggregate
{
    /**
     * @var array 集合元素数组
     */
    protected array $items = [];

    /**
     * 构造函数。
     *
     * @param array $items 元素数组
     * @param string $itemClass 元素类型
     * @throws \InvalidArgumentException 元素类型不匹配时抛出
     */
    public function __construct(array $items, string $itemClass)
    {
        foreach ($items as $item) {
            if (! $item instanceof $itemClass) {
                throw new \InvalidArgumentException(static::class . " may only contain {$itemClass} objects");
            }
        }
        $this->items = array_values($items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * 获取集合元素。
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
