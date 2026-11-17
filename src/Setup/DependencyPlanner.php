<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Project\Project;

final class DependencyPlanner
{
    /**
     * @return list<Requirement>
     */
    public function plan(Project $project): array
    {
        $runtime = $project->runtimePhp();

        $requirements = array_merge([
            $this->linter($project),
            new Requirement('pestphp/pest', '^1.23 || ^2.0 || ^3.0 || ^4.0 || ^5.0', '7.3'),
            new Requirement('pestphp/pest-plugin-type-coverage', '^2.0 || ^3.0 || ^4.0 || ^5.0', '8.1', true),
        ], $project->framework()->requirements());

        return array_values(array_filter(
            $requirements,
            static fn (Requirement $requirement): bool => $requirement->supports($runtime)
                && ! self::isPresent($project, $requirement->package()),
        ));
    }

    private static function isPresent(Project $project, string $package): bool
    {
        if ($package === 'friendsofphp/php-cs-fixer' && self::isPresent($project, 'laravel/pint')) {
            return true;
        }

        return $project->manifest()->requires($package) || $project->installed()->has($package);
    }

    private function linter(Project $project): Requirement
    {
        if (self::isPresent($project, 'friendsofphp/php-cs-fixer') || ! $project->runtimePhp()->isAtLeast('8.0')) {
            return new Requirement('friendsofphp/php-cs-fixer', '^3.0', '7.4');
        }

        return new Requirement('laravel/pint', '^1.0', '8.0');
    }
}
