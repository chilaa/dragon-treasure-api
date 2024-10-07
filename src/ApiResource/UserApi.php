<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\DragonTreasure;
use App\Entity\User;
use App\State\EntityToDtoStateProvider;

#[ApiResource(
    shortName: 'User',
//    operations: [
//        new Get(),
//    ],
    paginationItemsPerPage: 5,
    provider: EntityToDtoStateProvider::class,
    stateOptions: new Options(entityClass: User::class)
)]
#[ApiFilter(SearchFilter::class, properties: [
    'username' => 'partial'
])]
class UserApi
{
    public ?int $id = null;

    public ?string $email = null;

    public ?string $username = null;

    /**
     * @var array<int, DragonTreasure>
     */
    public array $dragonTreasures = [];
    public ?int $flameThrowingDistance = null;
}