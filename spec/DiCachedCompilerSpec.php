<?php

namespace spec\jschreuder\MiddleDi;

use PhpSpec\ObjectBehavior;
use jschreuder\MiddleDi\DiCachedCompiler;
use jschreuder\MiddleDi\DiCompilerInterface;
use SplFileObject;
use stdClass;
use RuntimeException;

class DiCachedCompilerSpec extends ObjectBehavior
{
    private $parentDiCompiler;
    private $file;
    private int $maxAge = 300;

    private string $compiledCodeExample = '<?php declare(strict_types=1);

class ExampleCompiledClass_{{SUFFIX}} extends stdClass
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = is_null($instanceName) ? \'\' : \'.\' . $instanceName;
        return $this->__services[$method . $suffix] ?? ($this->__services[$method . $suffix] = parent::{$method}($instanceName));
    }
}
';

    public function let(DiCompilerInterface $parentDiCompiler, SplFileObject $file)
    {
        $this->parentDiCompiler = $parentDiCompiler;
        $this->file = $file;
        $this->beConstructedWith($this->parentDiCompiler, $this->file, $this->maxAge);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(DiCachedCompiler::class);
    }

    public function it_can_check_compilation_status()
    {
        $this->parentDiCompiler->compiledClassExists()->willReturn(false);
        $this->compiledClassExists()->shouldBe(false);
    }

    public function it_can_compile_without_cache()
    {
        $preCompiledFile = __DIR__ . '/Examples/ExampleCachedContainer.php';
        $code = file_get_contents($preCompiledFile);
        $this->file->isFile()->willReturn(false);
        $this->file->ftruncate(0)->shouldBeCalled();
        $this->file->fwrite($code)->shouldBeCalled();
        $this->file->getPath()->willReturn($preCompiledFile);

        $this->parentDiCompiler->compiledClassExists()->willReturn(false);
        $this->parentDiCompiler->generateCode()->willReturn($code);

        $this->compile();
        $this->parentDiCompiler->compiledClassExists()->willReturn(class_exists('spec\\jschreuder\\MiddleDi\\Examples\\ExampleCachedContainer_Compiled'));
        $this->compiledClassExists()->shouldBe(true);
    }

    public function it_can_compile_with_expired_cache()
    {
        $preCompiledFile = __DIR__ . '/Examples/ExampleCachedContainer.php';
        $code = file_get_contents($preCompiledFile);
        $this->file->isFile()->willReturn(true);
        $this->file->getMTime()->willReturn(time()-3000);
        $this->file->ftruncate(0)->shouldBeCalled();
        $this->file->fwrite($code)->shouldBeCalled();
        $this->file->getPath()->willReturn($preCompiledFile);

        $this->parentDiCompiler->compiledClassExists()->willReturn(false);
        $this->parentDiCompiler->generateCode()->willReturn($code);

        // It will attempt include again, but due to using include_once it won't error
        $this->compile();
        $this->parentDiCompiler->compiledClassExists()->willReturn(class_exists('spec\\jschreuder\\MiddleDi\\Examples\\ExampleCachedContainer_Compiled'));
        $this->compiledClassExists()->shouldBe(true);
    }

    public function it_can_compile_with_cache()
    {
        $preCompiledFile = __DIR__ . '/Examples/ExampleCachedContainer.php';
        $this->file->isFile()->willReturn(true);
        $this->file->ftruncate(0)->shouldNotBeCalled();
        $this->file->getPath()->willReturn($preCompiledFile);
        $this->file->getMTime()->willReturn(time()-30);

        $this->parentDiCompiler->compiledClassExists()->willReturn(false);

        // It will attempt include again, but due to using include_once it won't error
        $this->compile();
        $this->parentDiCompiler->compiledClassExists()->willReturn(class_exists('spec\\jschreuder\\MiddleDi\\Examples\\ExampleCachedContainer_Compiled'));
        $this->compiledClassExists()->shouldBe(true);
    }

    public function it_cant_compile_twice()
    {
        $this->parentDiCompiler->compiledClassExists()->willReturn(true);
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_can_generate_code()
    {
        $code = str_replace('{{SUFFIX}}', '_itcangenerate', $this->compiledCodeExample);
        $this->parentDiCompiler->generateCode()->willReturn($code);
        $this->generateCode()->shouldBe($code);
    }

    public function it_can_instantiate_container()
    {
        $compiledExample = new stdClass();
        $configArray = ['test' => 'something'];
        $this->parentDiCompiler->newInstance($configArray)->willReturn($compiledExample);
        $this->newInstance($configArray)->shouldBe($compiledExample);
    }
}
