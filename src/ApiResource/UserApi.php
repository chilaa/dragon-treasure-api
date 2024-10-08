<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\DragonTreasure;
use App\Entity\User;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ApiResource(
    shortName: 'User',
//    normalizationContext: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['flameThrowingDistance']],
//    denormalizationContext: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['flameThrowingDistance']],
    paginationItemsPerPage: 5,
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: User::class)
)]
#[ApiFilter(SearchFilter::class, properties: [
    'username' => 'partial'
])]
class UserApi
{
    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public ?int $id = null;

    public ?string $email = null;

    public ?string $username = null;

    /** The string that will be hashed */
    #[ApiProperty(readable: false)]
    public ?string $password = null;

    /**
     * @var array<int, DragonTreasure>
     */
    #[ApiProperty(readable: false)]
    public array $dragonTreasures = [];

    #[ApiProperty(readable: false)]
    public ?int $flameThrowingDistance = 0;
}