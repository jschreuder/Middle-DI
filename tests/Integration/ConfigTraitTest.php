<?php

namespace Tests\Integration;

use ArrayAccess;
use OutOfBoundsException;
use jschreuder\MiddleDi\ConfigTrait;

// Test class that uses ConfigTrait
class ConfigTraitTestClass
{
    use ConfigTrait;
}

// Test class that uses ConfigTrait with ArrayAccess
class ArrayAccessConfig implements ArrayAccess
{
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}

beforeEach(function () {
    $this->config = [
        'database' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'nested' => [
            'key' => 'value',
        ],
        'boolean' => true,
        'null_value' => null,
    ];
});

test('ConfigTrait can be instantiated with array config', function () {
    $instance = new ConfigTraitTestClass($this->config);
    expect($instance)->toBeInstanceOf(ConfigTraitTestClass::class);
});

test('ConfigTrait can be instantiated with ArrayAccess config', function () {
    $arrayAccess = new ArrayAccessConfig($this->config);
    $instance = new ConfigTraitTestClass($arrayAccess);
    expect($instance)->toBeInstanceOf(ConfigTraitTestClass::class);
});

test('ConfigTrait returns config values from array', function () {
    $instance = new ConfigTraitTestClass($this->config);

    expect($instance->config('database'))->toBe('mysql');
    expect($instance->config('host'))->toBe('localhost');
    expect($instance->config('port'))->toBe(3306);
    expect($instance->config('username'))->toBe('root');
});

test('ConfigTrait returns nested arrays', function () {
    $instance = new ConfigTraitTestClass($this->config);

    $nested = $instance->config('nested');
    expect($nested)->toBeArray();
    expect($nested['key'])->toBe('value');
});

test('ConfigTrait returns boolean values', function () {
    $instance = new ConfigTraitTestClass($this->config);

    expect($instance->config('boolean'))->toBeTrue();
});

test('ConfigTrait returns null values', function () {
    $instance = new ConfigTraitTestClass($this->config);

    expect($instance->config('null_value'))->toBeNull();
});

test('ConfigTrait returns config values from ArrayAccess', function () {
    $arrayAccess = new ArrayAccessConfig($this->config);
    $instance = new ConfigTraitTestClass($arrayAccess);

    expect($instance->config('database'))->toBe('mysql');
    expect($instance->config('host'))->toBe('localhost');
    expect($instance->config('port'))->toBe(3306);
});

test('ConfigTrait throws OutOfBoundsException for missing config key', function () {
    $instance = new ConfigTraitTestClass($this->config);

    expect(fn() => $instance->config('non_existent_key'))
        ->toThrow(OutOfBoundsException::class);
});

test('OutOfBoundsException message includes the missing key', function () {
    $instance = new ConfigTraitTestClass($this->config);

    expect(fn() => $instance->config('missing_value'))
        ->toThrow(OutOfBoundsException::class, 'No such config value: missing_value');
});

test('ConfigTrait works with empty config array', function () {
    $instance = new ConfigTraitTestClass([]);

    expect(fn() => $instance->config('any_key'))
        ->toThrow(OutOfBoundsException::class);
});

test('ConfigTrait works with empty ArrayAccess config', function () {
    $arrayAccess = new ArrayAccessConfig([]);
    $instance = new ConfigTraitTestClass($arrayAccess);

    expect(fn() => $instance->config('any_key'))
        ->toThrow(OutOfBoundsException::class);
});

test('ConfigTrait preserves config value types', function () {
    $config = [
        'string' => 'value',
        'integer' => 42,
        'float' => 3.14,
        'array' => [1, 2, 3],
        'object' => new \stdClass(),
    ];

    $instance = new ConfigTraitTestClass($config);

    expect($instance->config('string'))->toBeString();
    expect($instance->config('integer'))->toBeInt();
    expect($instance->config('float'))->toBeFloat();
    expect($instance->config('array'))->toBeArray();
    expect($instance->config('object'))->toBeInstanceOf(\stdClass::class);
});

test('ConfigTrait multiple accesses return same value', function () {
    $instance = new ConfigTraitTestClass($this->config);

    $value1 = $instance->config('database');
    $value2 = $instance->config('database');
    $value3 = $instance->config('database');

    expect($value1)->toBe($value2);
    expect($value2)->toBe($value3);
});

test('ConfigTrait works with numeric keys in array', function () {
    $config = [
        0 => 'first',
        1 => 'second',
        2 => 'third',
    ];

    $instance = new ConfigTraitTestClass($config);

    expect($instance->config('0'))->toBe('first');
    expect($instance->config('1'))->toBe('second');
    expect($instance->config('2'))->toBe('third');
});
