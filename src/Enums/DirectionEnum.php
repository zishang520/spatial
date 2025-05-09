<?php

namespace luoyy\Spatial\Enums;

/**
 * 方向枚举。
 *
 * 用于表示二维空间的常用方向。
 *
 * - LEFT：左（4）
 * - RIGHT：右（6）
 * - UP：上（8）
 * - DOWN：下（2）
 */
enum DirectionEnum: int
{
    case LEFT = 4;   // 左
    case RIGHT = 6;  // 右
    case UP = 8;     // 上
    case DOWN = 2;   // 下
}
