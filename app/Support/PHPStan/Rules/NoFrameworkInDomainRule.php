<?php

declare(strict_types=1);

namespace App\Support\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

final class NoFrameworkInDomainRule implements Rule
{
    /** @var list<string> */
    private array $forbiddenPrefixes;

    /** @param list<string> $forbiddenPrefixes */
    public function __construct(array $forbiddenPrefixes = [])
    {
        $this->forbiddenPrefixes = $forbiddenPrefixes !== []
            ? $forbiddenPrefixes : [
                'Illuminate\\',
                'App\\Models\\',
                'App\\Http\\',
            ];
    }

    public function getNodeType(): string
    {
        return Use_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $file = str_replace('\\', '/', $scope->getFile());

        if (! str_contains($file, '/app/Core/Domain/')) {
            return [];
        }

        $errors = [];

        foreach ($node->uses as $use) {
            $import = ltrim($use->name->toString(), '\\');

            foreach ($this->forbiddenPrefixes as $prefix) {
                if (str_starts_with($import, $prefix)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf('❌ Domain layer cannot depend on framework class: %s', $import)
                    )->build();
                    break;
                }
            }
        }

        return $errors;
    }
}
