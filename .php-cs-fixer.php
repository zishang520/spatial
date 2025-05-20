<?php

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@DoctrineAnnotation' => true,
        'use_arrow_functions' => true,
        'yoda_style' => [
            'always_move_variable' => false,
            'equal' => false,
            'identical' => false,
        ],
        'align_multiline_comment' => true,
        'no_unused_imports' => true,
        'clean_namespace' => true,
        'phpdoc_no_package' => false,
        'phpdoc_separation' => false,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => false,
        'no_trailing_whitespace_in_comment' => true,
        'no_superfluous_phpdoc_tags' => true, // 移除多余的 PHPDoc 标签
        'phpdoc_order' => ['order' => ['param', 'return', 'throws']],
        'type_declaration_spaces' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space', // 统一操作符间距
        ],
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'function_typehint_space' => true,
        'cast_spaces' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'backtick_to_shell_exec' => true,
        'no_alias_language_construct_call' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
        'trim_array_spaces' => true,
        'single_line_comment_spacing' => true,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'blank_line_after_opening_tag' => false,
        'array_indentation' => true,
        'method_chaining_indentation' => true,
        'braces_position' => [
            'control_structures_opening_brace' => 'same_line',
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end', // 对应 'position_after_functions_and_oop_constructs' => 'next'
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end', // 对应 'position_after_functions_and_oop_constructs' => 'next'
            'anonymous_functions_opening_brace' => 'same_line', // 对应 'position_after_anonymous_constructs' => 'same'
            'anonymous_classes_opening_brace' => 'same_line',    //    对应 'position_after_anonymous_constructs' => 'same'
            'allow_single_line_empty_anonymous_classes' => true, // 对应 'allow_single_line_anonymous_class_with_empty_body' => true
            'allow_single_line_anonymous_functions' => true, // 对应 'allow_single_line_closure' => true
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'declare',
            ],
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'author',
            ],
        ],
        'ordered_imports' => [
            'imports_order' => [
                'class', 'function', 'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'constant_case' => [
            'case' => 'lower',
        ],
        'function_declaration' => [
            'closure_fn_spacing' => 'none',
        ],
        'class_definition' => [
            'space_before_parenthesis' => false,
        ],
        'single_space_around_construct' => [
            'constructs_contain_a_single_space' => ['yield_from'],
            'constructs_followed_by_a_single_space' => ['abstract', 'as', 'attribute', 'break', 'case', 'catch', 'class', 'clone', 'comment', 'const', 'const_import', 'continue', 'do', 'echo', 'else', 'elseif', 'enum', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'function_import', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'match', 'named_argument', 'namespace', 'new', 'open_tag_with_echo', 'php_doc', 'php_open', 'print', 'private', 'protected', 'public', 'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'type_colon', 'use', 'use_lambda', 'use_trait', 'var', 'while', 'yield', 'yield_from'],
            'constructs_preceded_by_a_single_space' => ['as', 'else', 'elseif', 'use_lambda'],
        ],
        'class_attributes_separation' => true,
        'combine_consecutive_unsets' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_static_reference' => true,
        'no_useless_else' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'not_operator_with_successor_space' => true,
        'not_operator_with_space' => false,
        'ordered_class_elements' => true,
        'php_unit_strict' => false,
        'single_quote' => true,
        'standardize_not_equals' => true,
        'multiline_comment_opening_closing' => true,
        'no_whitespace_in_blank_line' => true,     // 删除空行中的空格
        'blank_line_between_import_groups' => true, // 不同类别导入间加空行
    ])->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->in(__DIR__)
    );
