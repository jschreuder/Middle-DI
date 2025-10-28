<?php

namespace Tests\Unit;

use jschreuder\MiddleDi\DiCachedCompiler;
use jschreuder\MiddleDi\DiCompilationException;
use jschreuder\MiddleDi\DiCompilerInterface;
use Mockery\MockInterface;
use SplFileObject;

$compiledCodeExample = '<?php declare(strict_types=1);

namespace Tests\Examples;

class ExampleCompiledClass_{{SUFFIX}} extends \stdClass
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = is_null($instanceName) ? \'\' : \'.\' . $instanceName;
        return $this->__services[$method . $suffix] ?? ($this->__services[$method . $suffix] = parent::{$method}($instanceName));
    }
}
';

beforeEach(function () use ($compiledCodeExample) {
    $this->maxAge = 300;
    /** @var DiCompilerInterface|MockInterface */
    $this->parentDiCompiler = \Mockery::mock(DiCompilerInterface::class);

    // Create a proper SplFileObject mock with constructor args
    $this->file = \Mockery::mock(SplFileObject::class, ['php://memory'])->makePartial();
    $this->file->shouldReceive('isFile', 'ftruncate', 'fwrite', 'getPath')->byDefault();

    $this->compiler = new DiCachedCompiler($this->parentDiCompiler, $this->file, $this->maxAge);
    $this->compiledCodeExample = $compiledCodeExample;
});

test('it is initializable', function () {
    expect($this->compiler)->toBeInstanceOf(DiCachedCompiler::class);
});

test('it throws exception when initialized with invalid max age', function () {
    expect(fn() => new DiCachedCompiler($this->parentDiCompiler, $this->file, -10))
        ->toThrow(\OutOfRangeException::class);
});

test('it can check compilation status', function () {
    $this->parentDiCompiler->shouldReceive('compiledClassExists')->once()->andReturn(false);
    expect($this->compiler->compiledClassExists())->toBeFalse();
});

test('it can compile without cache', function () {
    $preCompiledFile = __DIR__ . '/../Examples/Cached/ExampleCachedContainer1.php';
    $code = @file_get_contents($preCompiledFile) ?: $this->compiledCodeExample;

    $this->file->shouldReceive('isFile')->once()->andReturn(false);
    $this->file->shouldReceive('ftruncate')->once()->with(0);
    $this->file->shouldReceive('fwrite')->once()->with($code);
    $this->file->shouldReceive('getPath')->once()->andReturn($preCompiledFile);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')->once()->andReturn(false);
    $this->parentDiCompiler->shouldReceive('generateCode')->once()->andReturn($code);

    expect($this->compiler->compile())->toBe($this->compiler);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')
        ->once()
        ->andReturn(true);
    expect($this->compiler->compiledClassExists())->toBeTrue();
});

test('it can compile with expired cache', function () {
    $preCompiledFile = __DIR__ . '/../Examples/Cached/ExampleCachedContainer2.php';
    $code = @file_get_contents($preCompiledFile) ?: $this->compiledCodeExample;

    $this->file->shouldReceive('isFile')->once()->andReturn(true);
    $this->file->shouldReceive('getMTime')->once()->andReturn(time() - 3000);
    $this->file->shouldReceive('ftruncate')->once()->with(0);
    $this->file->shouldReceive('fwrite')->once()->with($code);
    $this->file->shouldReceive('getPath')->once()->andReturn($preCompiledFile);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')->once()->andReturn(false);
    $this->parentDiCompiler->shouldReceive('generateCode')->once()->andReturn($code);

    expect($this->compiler->compile())->toBe($this->compiler);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')
        ->once()
        ->andReturn(true);
    expect($this->compiler->compiledClassExists())->toBeTrue();
});

test('it can compile with cache', function () {
    $preCompiledFile = __DIR__ . '/../Examples/Cached/ExampleCachedContainer3.php';

    $this->file->shouldReceive('isFile')->once()->andReturn(true);
    $this->file->shouldReceive('ftruncate')->never();
    $this->file->shouldReceive('getPath')->once()->andReturn($preCompiledFile);
    $this->file->shouldReceive('getMTime')->once()->andReturn(time() - 30);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')->once()->andReturn(false);

    expect($this->compiler->compile())->toBe($this->compiler);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')
        ->once()
        ->andReturn(true);
    expect($this->compiler->compiledClassExists())->toBeTrue();
});

test('it can compile with unexpireable cache', function () {
    $preCompiledFile = __DIR__ . '/../Examples/Cached/ExampleCachedContainer4.php';
    $file = \Mockery::mock(SplFileObject::class, [$preCompiledFile])->makePartial();
    $file->shouldReceive('isFile')->once()->andReturn(true);
    $file->shouldReceive('ftruncate')->never();
    $file->shouldReceive('getPath')->once()->andReturn($preCompiledFile);

    $compiler = new DiCachedCompiler($this->parentDiCompiler, $file, 0);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')->once()->andReturn(false);

    expect($compiler->compile())->toBe($compiler);

    $this->parentDiCompiler->shouldReceive('compiledClassExists')
        ->once()
        ->andReturn(true);
    expect($compiler->compiledClassExists())->toBeTrue();
});

test('it cant compile twice', function () {
    $this->parentDiCompiler->shouldReceive('compiledClassExists')->once()->andReturn(true);
    expect(fn() => $this->compiler->compile())->toThrow(DiCompilationException::class);
});

test('it can generate code', function () {
    $code = str_replace('{{SUFFIX}}', '_itcangenerate', $this->compiledCodeExample);
    $this->parentDiCompiler->shouldReceive('generateCode')->once()->andReturn($code);
    expect($this->compiler->generateCode())->toBe($code);
});

test('it can instantiate container', function () {
    $compiledExample = new \stdClass();
    $configArray = ['test' => 'something'];

    $this->parentDiCompiler->shouldReceive('newInstance')
        ->once()
        ->with($configArray)
        ->andReturn($compiledExample);

    expect($this->compiler->newInstance($configArray))->toBe($compiledExample);
});