<?php declare(strict_types=1);

namespace spec\jschreuder\MiddleDi\Examples;

use ArrayAccess;
use jschreuder\MiddleDi\ConfigTrait;
use stdClass;

class ExampleContainer
{
    use ConfigTrait;

    public function __construct(array | ArrayAccess $config)
    {
        $this->config = $config;
    }

    public function newUser(string $username = null, string $password = null): stdClass
    {
        $user = new \stdClass();
        $user->username = $username ?: 'user_' . random_int(10000, 99999);
        $user->password = $password ?: $this->config('comically_bad_default_password');
        return $user;
    }

    public function getService(): stdClass
    {
        $obj = new \stdClass();
        $obj->admin = $this->newUser('admin', 'qwerty123');
        $obj->second_user = $this->newUser();
        return $obj;
    }

    public function getSecondService(string $named = null): stdClass
    {
        $obj = new \stdClass();
        $obj->name = $named ?? 'default';
        return $obj;
    }
}
