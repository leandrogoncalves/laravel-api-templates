<?php

namespace Preferred\Domain\Users\Tests\Unit;

use Preferred\Domain\Users\Entities\AuthorizedDevice;
use Preferred\Domain\Users\Entities\User;
use Tests\TestCase;

class AuthorizedDeviceTest extends TestCase
{
    /**
     * @var AuthorizedDevice
     */
    private $authorizedDevice;

    public function setUp(): void
    {
        parent::setUp();

        $user = factory(User::class)->create();
        $this->authorizedDevice = factory(AuthorizedDevice::class)->make(['user_id' => $user->id]);
    }

    public function testUserRelationship()
    {
        $this->assertNotNull($this->authorizedDevice->user->id);
    }
}
