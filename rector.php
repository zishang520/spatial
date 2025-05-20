<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassLike\RemoveTypedPropertyNonMockDocblockRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Doctrine\CodeQuality\Rector\Property\ImproveDoctrineCollectionDocTypeInEntityRector;
use Rector\Set\ValueObject\SetList;
// use Rector\TypeDeclaration\Rector\ClassMethod\AddTypeFromResourceDocblockRector;

return RectorConfig::configure()
    ->withImportNames(importShortClasses: false)
    ->withPhpVersion(80100) // PHP 8.1
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        SetList::PHP_81,                   // 启用 PHP 8.1 语法迁移（包括 readonly, enums 等）
        SetList::TYPE_DECLARATION,     // 自动添加参数/返回类型声明
        SetList::CODE_QUALITY,         // 提升代码质量
        SetList::DEAD_CODE,          // 移除未使用的代码
        SetList::PRIVATIZATION,        // 私有化 class 成员
        SetList::EARLY_RETURN,         // 将嵌套 if 优化为早返回风格
        SetList::NAMING,             // 改善变量/函数/类名命名
    ])->withRules([
        ImproveDoctrineCollectionDocTypeInEntityRector::class,
        RemoveUselessParamTagRector::class,
        RemoveTypedPropertyNonMockDocblockRector::class,
        // AddTypeFromResourceDocblockRector::class,
    ]);
