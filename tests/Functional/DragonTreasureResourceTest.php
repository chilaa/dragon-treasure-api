<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Factory\ApiTokenFactory;
use App\Factory\DragonTreasureFactory;
use App\Factory\NotificationFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends ApiTestCase
{
    use ResetDatabase;

    public function testCollectionOfTreasures(): void
    {
        DragonTreasureFactory::createMany(5, [
            'isPublished' => true
        ]);

        DragonTreasureFactory::createOne([
            'isPublished' => false
        ]);

        $json = $this->browser()
            ->get('/api/treasures')
            ->assertJson()
            ->assertJsonMatches('"hydra:totalItems"', 5)
            ->json();

        $this->assertSame(array_keys($json->decoded()['hydra:member'][0]), [
            "@id",
            "@type",
            "name",
            "description",
            "value",
            "coolFactor",
            "owner",
            "shortDescription",
            "plunderedAtAgo",
            "isMine"
        ]);
    }

    public function testGetOneUnpublishedTreasure404s(): void
    {
        $dragonTreasure = DragonTreasureFactory::createOne([
            'isPublished' => false
        ]);

        $this->browser()
            ->get('/api/treasures/'.$dragonTreasure->getId())
            ->assertStatus(404);
    }

    public function testPostToCreateTreasureWithValidation(): void
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->post('/api/treasures', [
                'json' => [],
            ])
            ->assertStatus(422)
            ->post('/api/treasures', HttpOptions::json([
                'name' => "A shiny golden piece",
                'description' => 'It sparkles when I wave it in the air.',
                'value' => 1000,
                'coolFactor' => 5
            ]))
            ->assertStatus(201)
            ->assertJsonMatches('name', 'A shiny golden piece');
    }

    public function testPostToCreateTreasureWithApiKey(): void
    {
        $token = ApiTokenFactory::createOne([
            'scopes' => [ApiToken::SCOPE_TREASURE_CREATE]
        ]);

        $this->browser()
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer '.$token->getToken()
                ]
            ])
            ->assertStatus(422);
    }

    public function testPostToCreateTreasureDeniedWithoutScope(): void
    {
        $token = ApiTokenFactory::createOne([
            'scopes' => [ApiToken::SCOPE_TREASURE_EDIT]
        ]);

        $this->browser()
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer '.$token->getToken()
                ]
            ])
            ->assertStatus(403);
    }

    public function testPatchUnpublishedWorks(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $user,
            'isPublished' => false
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 4321
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 4321);
    }

    public function testPatchToUpdateTreasure(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 4321
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 4321);

        $user2 = UserFactory::createOne();

        $this->browser()
            ->actingAs($user2)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 55555
                ]
            ])
            ->assertStatus(403);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'owner' => '/api/users/'.$user2->getId(),
                ]
            ])
            ->assertStatus(422);
    }

    public function testAdminCanPatchToEditTreasure(): void
    {
        $admin = UserFactory::new()->asAdmin()->create();
        $treasure = DragonTreasureFactory::createOne([
            'isPublished' => true
        ]);

        $this->browser()
            ->actingAs($admin)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 4132
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 4132)
            ->assertJsonMatches('isPublished', true);

    }

    public function testOwnerCanSeeIsPublishedAndIsMineFields(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'isPublished' => true,
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 4132
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 4132)
            ->assertJsonMatches('isPublished', true)
            ->assertJsonMatches('isMine', true);

    }


    public function testPublishTreasure(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'isPublished' => false,
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'isPublished' => true
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('isPublished', true);

        NotificationFactory::repository()->assert()->count(1);
    }
}