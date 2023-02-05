<?php

namespace spec\jschreuder\MiddleDi;

use PhpSpec\ObjectBehavior;
use jschreuder\MiddleDi\DiCompiler;
use RuntimeException;
use spec\jschreuder\MiddleDi\Examples\ExampleContainer;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer1;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer2;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer3;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer4;
use spec\jschreuder\MiddleDi\Examples\ExampleFaultyContainer5;

include __DIR__.'/Examples/ExampleContainer.php';
include __DIR__.'/Examples/ExampleFaultyContainer.php';

class DiCompilerSpec extends ObjectBehavior
{
    private array $config = ['sitename' => 'My Own Website', 'comically_bad_default_password' => 'p@$$w0rd'];

    public function let()
    {
        $this->beConstructedWith(ExampleContainer::class);
    }

    public function getMatchers(): array
    {
        return [
            "havePropertyValue" => function ($subject, $property, $value) {
                if (!isset($subject->{$property}) || $subject->{$property} !== $value) {
                    throw new FailureException(
                        sprintf('the subject property "%s" is not equal to expected value', $property)
                    );
                }
                return true;
            }
        ];
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DiCompiler::class);
    }

    public function it_has_not_yet_compiled()
    {
        $this->compiledClassExists()->shouldBe(false);
    }

    public function it_can_create_code()
    {
        $this->generateCode()->shouldBeString();
    }

    public function it_can_compile()
    {
        $this->compile()->shouldBe($this);
        $this->compiledClassExists()->shouldBe(true);
    }

    public function it_cant_compile_twice()
    {
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_errors_on_faulty_service_definitions_during_compilation_1()
    {
        $this->beConstructedWith(ExampleFaultyContainer1::class);
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_errors_on_faulty_service_definitions_during_compilation_2()
    {
        $this->beConstructedWith(ExampleFaultyContainer2::class);
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_errors_on_faulty_service_definitions_during_compilation_3()
    {
        $this->beConstructedWith(ExampleFaultyContainer3::class);
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_errors_on_faulty_service_definitions_during_compilation_4()
    {
        $this->beConstructedWith(ExampleFaultyContainer4::class);
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_errors_on_faulty_service_definitions_during_compilation_5()
    {
        $this->beConstructedWith(ExampleFaultyContainer5::class);
        $this->shouldThrow(RuntimeException::class)->duringCompile();
    }

    public function it_can_instantiate_container()
    {
        $this->newInstance($this->config)->shouldBeAnInstanceOf(ExampleContainer::class);
    }

    public function it_will_create_different_instances()
    {
        $instance1 = $this->newInstance($this->config);
        $instance2 = $this->newInstance($this->config);
        $instance1->shouldNotEqual($instance2);
    }

    public function its_instance_will_treat_services_as_services()
    {
        $instance = $this->newInstance($this->config);
        $service1 = $instance->getService();
        $service2 = $instance->getService();
        $service1->shouldBe($service2);

        $instance = $this->newInstance($this->config);
        $secondService1 = $instance->getSecondService();
        $secondService2 = $instance->getSecondService();
        $secondService1->shouldBe($secondService2);
    }

    public function its_instance_supports_named_services()
    {
        $instance = $this->newInstance($this->config);
        $secondServiceDefault = $instance->getSecondService();
        $secondServiceNamed = $instance->getSecondService('whoop');
        $secondServiceDefault->shouldNotBe($secondServiceNamed);
    }

    public function its_instance_with_config_trait_supports_config_values()
    {
        $instance = $this->newInstance($this->config);
        $user = $instance->newUser('something');
        $user->shouldHavePropertyValue('username', 'something');
        $user->shouldHavePropertyValue('password', $instance->config('comically_bad_default_password'));
    }

    public function its_instance_will_treat_factories_as_factories()
    {
        $instance = $this->newInstance($this->config);
        $user1 = $instance->newUser();
        $user2 = $instance->newUser();
        $user1->shouldNotBe($user2);
    }
}
