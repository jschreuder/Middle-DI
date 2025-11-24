<?php

use jschreuder\MiddleDi\DiCompiler;
use Tests\Examples\ExampleContainerWithArgs;

include_once __DIR__ . "/../Examples/ExampleContainerWithArgs.php";

beforeEach(function () {
    $uniqueClass = "ContainerWithArgs_" . uniqid();
    $this->uniqueClass = $uniqueClass;
    $this->fullClassName = "Tests\\Examples\\{$uniqueClass}";

    eval("namespace Tests\\Examples; class {$uniqueClass} extends ExampleContainerWithArgs {}");
    $this->compiler = new DiCompiler($this->fullClassName);
    $this->compiler->compile();
});

test("newInstance can accept string arguments", function () {
    $instance = $this->compiler->newInstance("test string");
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe(["test string"]);
});

test("newInstance can accept integer arguments", function () {
    $instance = $this->compiler->newInstance(42);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe([42]);
});

test("newInstance can accept float arguments", function () {
    $instance = $this->compiler->newInstance(3.14);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe([3.14]);
});

test("newInstance can accept boolean arguments", function () {
    $instance = $this->compiler->newInstance(true);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe([true]);
});

test("newInstance can accept null arguments", function () {
    $instance = $this->compiler->newInstance(null);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe([null]);
});

test("newInstance can accept object arguments", function () {
    $obj = new stdClass();
    $obj->value = "test";
    $instance = $this->compiler->newInstance($obj);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe([$obj]);
});

test("newInstance can accept mixed argument types", function () {
    $obj = new stdClass();
    $obj->data = "test";
    $instance = $this->compiler->newInstance("string", 42, 3.14, true, null, $obj);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe(["string", 42, 3.14, true, null, $obj]);
});

test("newInstance can accept multiple array arguments", function () {
    $array1 = ["key" => "value"];
    $array2 = [1, 2, 3];
    $instance = $this->compiler->newInstance($array1, $array2);
    expect($instance)->toBeInstanceOf(ExampleContainerWithArgs::class);
    expect($instance->constructorArgs)->toBe([$array1, $array2]);
});
