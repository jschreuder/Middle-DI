<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

final class DiCompiler implements DiCompilerInterface
{
    const COMPILED_EXTENSION = '__Compiled';

    private string $parentDi;

    public function __construct(string $parentDi)
    {
        $this->parentDi = $parentDi;
    }

    private function getCompiledName(): string
    {
        return $this->parentDi . self::COMPILED_EXTENSION;
    }

    public function compiledClassExists(): bool
    {
        return class_exists($this->getCompiledName());
    }

    public function compile(): void
    {
        if ($this->compiledClassExists()) {
            throw new \RuntimeException('Cannot recompile already compiled container');
        }

        eval(substr($this->generateCode(), 32));
    }

    public function generateCode(): string
    {
        $parent = new ReflectionClass($this->parentDi);

        $code = $this->generateHeader($parent);

        $methods = $parent->getMethods();
        foreach ($methods as $method) {
            $code .= $this->processMethod($method);
        }
        return $code . $this->generateFooter();
    }

    private function generateHeader(ReflectionClass $parent)
    {
        return
'<?php declare(strict_types=1);

namespace ' . $parent->getNamespaceName() . ';

use ' . $parent->getName() . ';

class ' . $parent->getShortName() . self::COMPILED_EXTENSION . ' extends ' . $parent->getShortName() . '
{
    private array $__services = [];

    private function __service(string $method, ?string $instanceName = null)
    {
        $suffix = is_null($instanceName) ? \'\' : \'.\' . $instanceName;
        return $this->__services[$method . $suffix] ?? ($this->__services[$method . $suffix] = parent::{$method}($instanceName));
    }

';
    }

    public function processMethod(ReflectionMethod $method): string
    {
        // Decide if the method needs to be overloaded
        if (substr($method->getName(), 0, 3) !== 'get') {
            return '';
        }

        // Run validations
        $this->validateServiceDefinitionReturnType($method);
        $this->validateServiceDefinitionParameters($method);

        // Generate method-overload code
        return '
    public function ' . $method->getName() . '(?string $instanceName = null): \\' . $method->getReturnType()->getName() . '
    {
        return $this->__service(\'' . $method->getName() . '\', $instanceName);
    }
';
    }

    private function validateServiceDefinitionReturnType(ReflectionMethod $method): void
    {
        // It must have a return type, and the return type must only define a single class or interface
        if (!$method->hasReturnType()) {
            throw new RuntimeException('Service definitions must have return types');
        }

        $returnType = $method->getReturnType();
        if (!$returnType instanceof ReflectionNamedType) {
            throw new RuntimeException('Service definitions must define only a single class or interface returntype');
        }
        if ($returnType->isBuiltin()) {
            throw new RuntimeException('Service definitions must return objects');
        }
    }

    private function validateServiceDefinitionParameters(ReflectionMethod $method): void
    {
        if ($method->getNumberOfParameters() > 1) {
            throw new RuntimeException('Service definitions cannot take more than a name parameter');
        }
        if ($method->getNumberOfParameters() === 1) {
            $parameter = $method->getParameters()[0];

            if (!is_a($parameter->getType(), ReflectionNamedType::class) || $parameter->getType()->getName() !== 'string') {
                throw new \RuntimeException('Service definitions are only allowed a single named nullable string argument.');
            }
        }
    }

    private function generateFooter(): string
    {
        return PHP_EOL . '}' . PHP_EOL;
    }

    public function newInstance(array ...$args): mixed
    {
        $reflectedClass = new ReflectionClass($this->getCompiledName());
        return $reflectedClass->newInstance(...$args);
    }
}
