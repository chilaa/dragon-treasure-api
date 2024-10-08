<?php

namespace App\Tests\Functional;

use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\Json;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserResourceTest extends ApiTestCase
{
    use ResetDatabase;

    public function testPostToCreateUser(): void
    {
        $this->browser()
            ->post('/api/users', [
                'json' => [
                    'username' => 'Smaug',
                    'email' => 'test@test.com',
                    'password' => 'pass',
                    'flameThrowingDistance' => 999
                ]
            ])
            ->assertStatus(201)
            ->use(function (Json $json) {
                $json->assertMissing('password');
                $json->assertMissing('id');
            })
            ->post('/login', [
                'json' => [
                    'email' => 'test@test.com',
                    'password' => 'pass'
                ]
            ])
            ->assertSuccessful();
    }

    public function testPatchToUpdateUser(): void
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->patch('/api/users/'.$user->getId(), [
                'json' => [
                    'username' => 'Toothless',
                    'id' => 55
                ],
                'headers' => [
                    'Content-type' => 'application/merge-patch+json'
                ]
            ])
            ->dump()
            ->assertStatus(200);
    }

    public function testTreasuresCannotBeStolen(): void
    {
        $user = UserFactory::createOne();
        $otherUser = UserFactory::createOne();
        $dragonTreasure = DragonTreasureFactory::createOne([
            'owner' => $otherUser
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/users/'.$otherUser->getId(), [
                'json' => [
                    'username' => 'Toothless',
                    'ownedTreasures' => [
                        '/api/treasures' => $dragonTreasure->getId(),
                    ]
                ],
                'headers' => [
                    'Content-type' => 'application/merge-patch+json'
                ]
            ])
            ->assertStatus(422);
    }

    public function testUnpublishedTreasuresNotReturned()
    {
        $user = UserFactory::createOne();
        DragonTreasureFactory::createOne([
            'isPublished' => false,
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs(UserFactory::createOne())
            ->get('/api/users/'.$user->getId())
            ->assertStatus(200)
            ->assertJsonMatches('length("dragonTreasures")', 0);
    }
}