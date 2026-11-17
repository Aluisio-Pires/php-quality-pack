<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

use Aluisio\PhpQualityPack\Project\Conventions;
use Aluisio\PhpQualityPack\Project\PhpVersion;

final class LintRules
{
    private const CLASS_ELEMENTS_ORDER = [
        'use_trait',
        'case',
        'constant_public',
        'constant_protected',
        'constant_private',
        'property_public',
        'property_protected',
        'property_private',
        'construct',
        'destruct',
        'magic',
        'phpunit',
        'method_abstract',
        'method_public_static',
        'method_public',
        'method_protected_static',
        'method_protected',
        'method_private_static',
        'method_private',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(PhpVersion $target, Conventions $conventions): array
    {
        $rules = [
            'fully_qualified_strict_types' => true,
            'global_namespace_import' => [
                'import_classes' => true,
                'import_constants' => true,
                'import_functions' => true,
            ],
            'lowercase_keywords' => true,
            'lowercase_static_reference' => true,
            'no_multiple_statements_per_line' => true,
            'no_superfluous_elseif' => true,
            'no_useless_else' => true,
            'ordered_class_elements' => [
                'order' => self::CLASS_ELEMENTS_ORDER,
                'sort_algorithm' => 'none',
            ],
            'ordered_interfaces' => true,
            'ordered_traits' => true,
            'protected_to_private' => true,
            'self_static_accessor' => true,
        ];

        if ($conventions->usesStrictTypesEverywhere()) {
            $rules['declare_strict_types'] = true;
        }

        if ($conventions->usesFinalClassesEverywhere()) {
            $rules['final_class'] = true;
        }

        if ($conventions->avoidsLooseComparisons()) {
            $rules['strict_comparison'] = true;
        }

        if (! $target->isAtLeast('8.0')) {
            $rules['trailing_comma_in_multiline'] = [
                'elements' => $target->isAtLeast('7.3') ? ['arrays', 'arguments'] : ['arrays'],
            ];
        }

        ksort($rules);

        return $rules;
    }
}
