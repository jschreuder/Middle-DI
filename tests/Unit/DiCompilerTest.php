<?php

namespace Tests\Unit;

use jschreuder\MiddleDi\DiCompiler;
use jschreuder\MiddleDi\DiCachedCompiler;
use spec\jschreuder\MiddleDi\Examples\ExampleContainer;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer1;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer2;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer3;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer4;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer5;
use SplFileObject;

include __DIR__.'/../../spec/Examples/ExampleContainer.php';
include __DIR__.'/../../spec/Examples/ExampleContainerNoNamespace.php';
include __DIR__.'/../../spec/Examples/ExampleFaultyContainer.php';

$config = ['sitename' => 'My Own Website', 'comically_bad_default_password' => 'p@$$w0rd'];

// Keep track of created classes to clean up
$GLOBALS['created_classes'] = [];

beforeEach(function () use ($config) {
    $this->config = $config;
    // Create a unique container class for each test run
    $uniqueClass = 'Container_' . uniqid();
    $this->uniqueClass = $uniqueClass;
    $fullClassName = 'spec\\jschreuder\\MiddleDi\\Examples\\' . $uniqueClass;
    
    // Store for cleanup
    $GLOBALS['created_classes'][] = $fullClassName;
    
    // Create the class
    eval('namespace spec\\jschreuder\\MiddleDi\\Examples; class ' . $uniqueClass . ' extends ExampleContainer {}');
    $this->compiler = new DiCompiler($fullClassName);
});

afterEach(function () {
    // Nothing to do - PHP will handle class cleanup between requests
    // We're just tracking created classes for documentation
    $GLOBALS['created_classes'] = [];
});

test('it is initializable', function () {
    expect($this->compiler)->toBeInstanceOf(DiCompiler::class);
});

test('it has not yet compiled', function () {
    expect($this->compiler->compiledClassExists())->toBeFalse();
});

test('it can create code', function () {
    expect($this->compiler->generateCode())->toBeString();
});

test('it can compile', function () {
    expect($this->compiler->compile())->toBe($this->compiler);
    expect($this->compiler->compiledClassExists())->toBeTrue();
});

test('it cant compile twice', function () {
    $this->compiler->compile();
    expect(fn() => $this->compiler->compile())->toThrow(\RuntimeException::class);
});

test('it errors on faulty service definitions during compilation', function ($containerClass) {
    // Create a unique faulty container class
    $uniqueClass = 'Faulty_' . uniqid();
    $fullClassName = 'spec\\jschreuder\\MiddleDi\\Examples\\' . $uniqueClass;
    $GLOBALS['created_classes'][] = $fullClassName;
    
    eval('namespace spec\\jschreuder\\MiddleDi\\Examples; class ' . $uniqueClass . ' extends ' . basename(str_replace('\\', '/', $containerClass)) . ' {}');
    $this->compiler = new DiCompiler($fullClassName);
    expect(fn() => $this->compiler->compile())->toThrow(\RuntimeException::class);
})->with([
    ExampleFaultyContainer1::class,
    ExampleFaultyContainer2::class,
    ExampleFaultyContainer3::class,
    ExampleFaultyContainer4::class,
    ExampleFaultyContainer5::class,
]);

test('it can instantiate container', function () {
    $this->compiler->compile();
    expect($this->compiler->newInstance($this->config))->toBeInstanceOf(ExampleContainer::class);
});

test('it can instantiate container with no namespace', function () {
    // Create a unique no-namespace container class
    $uniqueClass = 'NoNs_' . uniqid();
    $GLOBALS['created_classes'][] = $uniqueClass;
    
    eval('class ' . $uniqueClass . ' extends \\ExampleContainerNoNamespace {}');
    $this->compiler = new DiCompiler($uniqueClass);
    $this->compiler->compile();
    expect($this->compiler->newInstance($this->config))->toBeInstanceOf('\\' . $uniqueClass);
});

test('it will create different instances', function () {
    $this->compiler->compile();
    $instance1 = $this->compiler->newInstance($this->config);
    $instance2 = $this->compiler->newInstance($this->config);
    expect($instance1)->not->toBe($instance2);
});

test('instance will treat services as services', function () {
    $this->compiler->compile();
    $instance = $this->compiler->newInstance($this->config);
    
    $service1 = $instance->getService();
    $service2 = $instance->getService();
    expect($service1)->toBe($service2);

    $secondService1 = $instance->getSecondService();
    $secondService2 = $instance->getSecondService();
    expect($secondService1)->toBe($secondService2);
});

test('instance supports named services', function () {
    $this->compiler->compile();
    $instance = $this->compiler->newInstance($this->config);
    
    $secondServiceDefault = $instance->getSecondService();
    $secondServiceNamed = $instance->getSecondService('whoop');
    expect($secondServiceDefault)->not->toBe($secondServiceNamed);
});

test('instance with config trait supports config values', function () {
    $this->compiler->compile();
    $instance = $this->compiler->newInstance($this->config);
    $user = $instance->newUser('something');
    
    expect($user->username)->toBe('something');
    expect($user->password)->toBe($instance->config('comically_bad_default_password'));
});

test('instance will treat factories as factories', function () {
    $this->compiler->compile();
    $instance = $this->compiler->newInstance($this->config);
    
    $user1 = $instance->newUser();
    $user2 = $instance->newUser();
    expect($user1)->not->toBe($user2);
}); 