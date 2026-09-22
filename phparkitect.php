<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Rules\Rule;

/**
 * Architectural rules for lecturn.
 *
 * Encodes the pragmatic CQRS layering described in ARCHITECTURE.md:
 *
 *   Write: Http -> Application (Action + Command) -> Domain -> Infrastructure (Repository) -> tables
 *   Read:  Http -> Infrastructure (ReadModel) -> views
 *
 * Dependency direction flows inward toward Domain:
 *
 *   Domain         -> nothing (plain PHP)
 *   Application    -> Domain
 *   Infrastructure -> Domain, Models
 *   Http           -> Application, Infrastructure, Domain
 *   Listeners      -> Application, Infrastructure, Domain, Models   (infra tier)
 *   Models         -> Domain                                        (persistence detail)
 *
 * Listeners live in app/Listeners (not app/Infrastructure) for discoverability,
 * but are treated as an infrastructure-tier component: they may wire Http-less
 * side effects to Actions, repositories and models, same as the rest of infra.
 */
return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/app');

    $layeredArchitectureRules = Architecture::withComponents()
        ->component('Domain')->definedBy('App\Domain\*')
        ->component('Application')->definedBy('App\Application\*')
        ->component('Infrastructure')->definedBy('App\Infrastructure\*')
        ->component('Http')->definedBy('App\Http\*')
        ->component('Listeners')->definedBy('App\Listeners\*')
        ->component('Models')->definedBy('App\Models\*')

        // Domain is the stable core — depends on nothing but itself.
        ->where('Domain')->shouldNotDependOnAnyComponent()

        // Application (Actions, Commands) is write-side orchestration over the Domain.
        // No Models: Eloquent must never leak into an Action.
        ->where('Application')->mayDependOnComponents('Domain')

        // Infrastructure implements Domain contracts and maps Models <-> Entities.
        ->where('Infrastructure')->mayDependOnComponents('Domain', 'Models')

        // Models are a persistence detail; they may reference Domain (toEntity/HasDomainEntity).
        ->where('Models')->mayDependOnComponents('Domain')

        // Http is thin glue: call an Action or a ReadModel, hand results to Inertia.
        // No Models: controllers never touch Eloquent directly.
        ->where('Http')->mayDependOnComponents('Application', 'Infrastructure', 'Domain')

        // Listeners are infra-tier: they may reach Actions, repositories and models.
        ->where('Listeners')->mayDependOnComponents('Application', 'Infrastructure', 'Domain', 'Models')

        ->rules();

    // Domain must stay framework-free. Collection / LengthAwarePaginator are treated as
    // pure (see ARCHITECTURE.md) so Illuminate\Support and Illuminate\Contracts are allowed;
    // Eloquent, HTTP and facades are not.
    $domainPurityRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'Illuminate\Database',
            'Illuminate\Http',
            'Illuminate\Support\Facades',
            'App\Http',
            'App\Infrastructure',
            'App\Application',
            'App\Models',
        ]))
        ->because('the Domain layer is plain PHP: no Eloquent, HTTP or facades');

    // Application layer never queries Eloquent or touches HTTP directly.
    $applicationPurityRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Illuminate\Database',
            'Illuminate\Http',
            'App\Models',
        ]))
        ->because('Actions accept Commands and use repository interfaces, not Eloquent or HTTP');

    // Naming conventions (ARCHITECTURE.md naming table).
    $commandNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application\Commands'))
        ->should(new HaveNameMatching('*Command'))
        ->because('Commands are named after the Action they serve');

    $readModelNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\ReadModels'))
        ->should(new HaveNameMatching('*ReadModel'))
        ->because('read-side query objects are suffixed ReadModel');

    $repositoryNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\Persistence\Repositories'))
        ->should(new HaveNameMatching('Eloquent*Repository'))
        ->because('repository implementations are Eloquent{Noun}Repository');

    $viewModelNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Models\Views'))
        ->should(new HaveNameMatching('*View'))
        ->because('view-backed read models are suffixed View');

    $controllerNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Http\Controllers'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('controllers are suffixed Controller');

    $config->add(
        $classSet,
        $domainPurityRule,
        $applicationPurityRule,
        $commandNamingRule,
        $readModelNamingRule,
        $repositoryNamingRule,
        $viewModelNamingRule,
        $controllerNamingRule,
        ...$layeredArchitectureRules,
    );
};
