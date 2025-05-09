<?php

namespace luoyy\Spatial\Enums;

/**
 * 方位枚举。
 *
 * 用于表示矩形或范围的四个角方向。
 *
 * - NORTHWEST：西北（0）
 * - NORTHEAST：东北（1）
 * - SOUTHEAST：东南（2）
 * - SOUTHWEST：西南（3）
 */
enum LocationEnum: int
{
    case NORTHWEST = 0; // 西北
    case NORTHEAST = 1; // 东北
    case SOUTHEAST = 2; // 东南
    case SOUTHWEST = 3; // 西南
}
