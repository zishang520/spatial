<?php

namespace luoyy\Spatial;

use luoyy\Spatial\Contracts\Point as ContractsPoint;

/**
 * 根据EGM96模型进行高程计算.
 */
class EGM96Universal
{
    public const RADIAN = M_PI / 180;

    public const INTERVAL = (15 / 60) * self::RADIAN;

    public const NUM_ROWS = 721;

    public const NUM_COLS = 1440;

    private const DATA_FILE = '.data.bin';

    private static $dt = null;

    /**
     * 根据 EGM96 获取平均海平面高度。
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Contracts\Point $point 原坐标(请设置altitude属性值)
     * @return float 坐标平均海平面高度 M
     */
    public static function meanSeaLevel(ContractsPoint $point): float
    {
        $lat = self::normalizeRadians($point->latitude * self::RADIAN);
        if ($lat > M_PI || $lat < -M_PI) {
            throw new \RangeException('Invalid latitude.');
        }
        $lon = self::normalizeRadians($point->longitude * self::RADIAN);

        $topRow = floor(((M_PI / 2) - $lat) / self::INTERVAL);
        $topRow = $topRow === self::NUM_ROWS - 1 ? $topRow - 1 : $topRow;
        $bottomRow = $topRow + 1;

        $leftCol = floor(self::normalizeRadians($lon, M_PI) / self::INTERVAL);
        $rightCol = ($leftCol + 1) % self::NUM_COLS;

        $topLeft = self::getValue($topRow, $leftCol);
        $bottomLeft = self::getValue($bottomRow, $leftCol);
        $bottomRight = self::getValue($bottomRow, $rightCol);
        $topRight = self::getValue($topRow, $rightCol);

        $lonLeft = self::normalizeRadians($leftCol * self::INTERVAL);
        $latTop = (M_PI / 2) - ($topRow * self::INTERVAL);

        $leftProp = ($lon - $lonLeft) / self::INTERVAL;
        $topProp = ($latTop - $lat) / self::INTERVAL;

        return self::bilinearInterpolation($topLeft, $bottomLeft, $bottomRight, $topRight, $leftProp, $topProp);
    }

    /**
     * 将 WGS84 的椭球相对高度转换为 EGM96 相对高度。
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Contracts\Point $point 原坐标(请设置altitude属性值)
     * @return float 坐标 EGM96 相对高度 M
     */
    public static function ellipsoidToEgm96(ContractsPoint $point): float
    {
        return $point->altitude - self::meanSeaLevel($point);
    }

    /**
     * 将 EGM96 相对高度转换为 WGS84 椭球相对高度。
     * @copyright (c) zishang520 All Rights Reserved
     * @param \luoyy\Spatial\Contracts\Point $point 原坐标(请设置altitude属性值)
     * @return float 坐标 WGS84 椭球相对高度 M
     */
    public static function egm96ToEllipsoid(ContractsPoint $point): float
    {
        return $point->altitude + self::meanSeaLevel($point);
    }

    private static function bilinearInterpolation(float $topLeft, float $bottomLeft, float $bottomRight, float $topRight, float $x, float $y): float
    {
        $top = self::linearInterpolation($topLeft, $topRight, $x);
        $bottom = self::linearInterpolation($bottomLeft, $bottomRight, $x);

        return self::linearInterpolation($top, $bottom, $y);
    }

    private static function linearInterpolation(float $a, float $b, float $prop): float
    {
        return $a + (($b - $a) * $prop);
    }

    private static function normalizeRadians(float $rads, float $center = 0): float
    {
        return $rads - (2 * M_PI) * floor(($rads + M_PI - $center) / (2 * M_PI));
    }

    private static function getData(int $id): int
    {
        if (fseek(self::data(), $id * 2 + 1, SEEK_SET) !== 0) {
            throw new \OutOfBoundsException('File offset setting failed.');
        }
        if (($data = fread(self::data(), 2)) === false) {
            throw new \UnexpectedValueException('Failed to read file content.');
        }
        if (($u = unpack('s', $data)) === false) {
            throw new \UnexpectedValueException('Data unpacking failed.');
        }
        return $u[1];
    }

    private static function getValue(int $row, int $col): float
    {
        return self::getData($row * self::NUM_COLS + $col) / 100;
    }

    private static function data()
    {
        if (is_null(self::$dt)) {
            self::$dt = fopen(__DIR__ . DIRECTORY_SEPARATOR . self::DATA_FILE, 'r');
        }
        return self::$dt;
    }
}
