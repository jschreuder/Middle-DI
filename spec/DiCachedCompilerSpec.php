<?php

namespace spec\jschreuder\MiddleDi;

use PhpSpec\ObjectBehavior;
use jschreuder\MiddleDi\DiCachedCompiler;
use jschreuder\MiddleDi\DiCompilerInterface;
use stdClass;
use RuntimeException;

class DiCachedCompilerSpec extends ObjectBehavior
{
    private $parentDiCompiler;
    private string $location = __DIR__ . '/../cache/test.compiled_cached.php';
    private int $maxAge = 3;

    private string $compiledCodeExample = '<?php

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

    public function let(DiCompilerInterface $parentDiCompiler)
    {
        if (file_exists($this->location)) {
            unlink($this->location);
        }
        $this->parentDiCompiler = $parentDiCompiler;
        $this->beConstructedWith($this->parentDiCompiler, $this->location, $this->maxAge);
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

    public function it_can_compile()
    {
        $this->parentDiCompiler->compiledClassExists()->willReturn(false);
        $this->parentDiCompiler->generateCode()->willReturn(str_replace('{{SUFFIX}}', '_itcancompile', $this->compiledCodeExample));
        $this->compile();
        $this->parentDiCompiler->compiledClassExists()->willReturn(class_exists('ExampleCompiledClass__itcancompile'));
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
