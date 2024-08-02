<?php

namespace App\Tests\Functional;

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
            ->assertSuccessful()
        ;


    }
}