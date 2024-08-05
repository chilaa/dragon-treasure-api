<?php

namespace App\Tests\Functional;

use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
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
                    'password' => 'pass'
                ]
            ])
            ->assertStatus(201)
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
                ],
                'headers' => [
                    'Content-type' => 'application/merge-patch+json'
                ]
            ])
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
}