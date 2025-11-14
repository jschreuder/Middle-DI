<?php declare(strict_types=1);

namespace jschreuder\MiddleDi;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class DiCompiler implements DiCompilerInterface
{
    private const string COMPILED_EXTENSION = "__Compiled";

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

    public function compile(): static
    {
        if ($this->compiledClassExists()) {
            throw new DiCompilationException(
                "Cannot recompile already compiled container",
            );
        }

        eval(substr($this->generateCode(), 31));

        return $this;
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
        $code = "<?php declare(strict_types=1);\n";

        if ($parent->getNamespaceName()) {
            $code .=
                "namespace " .
                $parent->getNamespaceName() .
                ";\n\nuse " .
                $parent->getName() .
                ";\n";
        }

        $code .=
            "
class " .
            $parent->getShortName() .
            self::COMPILED_EXTENSION .
            " extends " .
            $parent->getShortName() .
            "
{
    private array \$__services = [];

    private function __service(string \$method, ?string \$instanceName = null)
    {
        \$suffix = \$instanceName === null ? \"\" : \".\$instanceName\";
        return \$this->__services[\$method . \$suffix] ?? (\$this->__services[\$method . \$suffix] = parent::{\$method}(\$instanceName));
    }

";

        return $code;
    }

    public function processMethod(ReflectionMethod $method): string
    {
        // Decide if the method needs to be overloaded
        if (substr($method->getName(), 0, 3) !== "get") {
            return "";
        }

        // Run validations
        $this->validateServiceDefinitionReturnType($method);
        $this->validateServiceDefinitionParameters($method);

        // Generate method-overload code
        return "\n    public function " .
            $method->getName() .
            "(?string \$instanceName = null): \\" .
            $method->getReturnType()->getName() .
            "\n    {\n        return \$this->__service(\"" .
            $method->getName() .
            "\", \$instanceName);\n    }\n";
    }

    private function validateServiceDefinitionReturnType(
        ReflectionMethod $method,
    ): void {
        // It must have a return type, and the return type must only define a single class or interface
        if (!$method->hasReturnType()) {
            throw new DiCompilationException(
                "Service definitions must have return types",
            );
        }

        $returnType = $method->getReturnType();
        if (!$returnType instanceof ReflectionNamedType) {
            throw new DiCompilationException(
                "Service definitions must define only a single class or interface returntype",
            );
        }
        if ($returnType->isBuiltin()) {
            throw new DiCompilationException(
                "Service definitions must return objects",
            );
        }
    }

    private function validateServiceDefinitionParameters(
        ReflectionMethod $method,
    ): void {
        if ($method->getNumberOfParameters() > 1) {
            throw new DiCompilationException(
                "Service definitions cannot take more than a name parameter",
            );
        }
        if ($method->getNumberOfParameters() === 1) {
            $parameter = $method->getParameters()[0];

            if (
                !is_a($parameter->getType(), ReflectionNamedType::class) ||
                $parameter->getType()->getName() !== "string"
            ) {
                throw new DiCompilationException(
                    "Service definitions are only allowed a single named nullable string argument.",
                );
            }
        }
    }

    private function generateFooter(): string
    {
        return PHP_EOL . "}" . PHP_EOL;
    }

    public function newInstance(array ...$args): mixed
    {
        $reflectedClass = new ReflectionClass($this->getCompiledName());
        return $reflectedClass->newInstance(...$args);
    }
}
