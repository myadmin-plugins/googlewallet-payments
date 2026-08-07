<?php

declare(strict_types=1);

namespace Detain\MyAdminGooglewallet\Tests;

use Detain\MyAdminGooglewallet\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Detain\MyAdminGooglewallet\Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the $name static property is set correctly.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('Googlewallet Plugin', Plugin::$name);
    }

    /**
     * Test that the $description static property is a non-empty string.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('Googlewallet', Plugin::$description);
    }

    /**
     * Test that the $help static property exists and is a string.
     */
    public function testHelpProperty(): void
    {
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Test that the $type static property is set to 'plugin'.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('plugin', Plugin::$type);
    }

    /**
     * Test that the class has exactly four expected static properties.
     */
    public function testStaticPropertyCount(): void
    {
        $staticProperties = $this->reflection->getProperties(\ReflectionProperty::IS_STATIC);
        $propertyNames = array_map(fn($p) => $p->getName(), $staticProperties);

        $this->assertContains('name', $propertyNames);
        $this->assertContains('description', $propertyNames);
        $this->assertContains('help', $propertyNames);
        $this->assertContains('type', $propertyNames);
        $this->assertCount(4, $staticProperties);
    }

    /**
     * Test that all static properties are declared as public.
     */
    public function testStaticPropertiesArePublic(): void
    {
        $staticProperties = $this->reflection->getProperties(\ReflectionProperty::IS_STATIC);
        foreach ($staticProperties as $property) {
            $this->assertTrue($property->isPublic(), "Property \${$property->getName()} should be public");
        }
    }

    /**
     * Test that every hook registration in getHooks() names a real handler.
     *
     * getHooks() also carries commented-out registrations (ui.menu). Those are
     * not dispatched today, but they are the intended wiring for this plugin, so
     * a rename or removal of the handler they name is a latent break that only
     * surfaces when someone re-enables the line. This reads the registrations
     * out of the method body - live and commented alike - and asserts each one
     * still points at a public static method on the plugin class.
     */
    public function testGetHooksRegistrationsNameLiveHandlers(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $lines = file((string) $method->getFileName());
        $this->assertNotFalse($lines, 'Plugin source should be readable');
        $body = implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        preg_match_all(
            "/'([^']+)'\s*=>\s*\[\s*(?:__CLASS__|self::class|static::class|[A-Za-z_\\\\]+::class)\s*,\s*'([^']+)'\s*\]/",
            $body,
            $registrations,
            PREG_SET_ORDER
        );

        $this->assertNotEmpty(
            $registrations,
            'getHooks() should declare at least one "event.name" => [__CLASS__, "method"] registration'
        );

        foreach ($registrations as $registration) {
            [, $eventName, $handlerName] = $registration;

            $this->assertNotSame('', trim($eventName), 'Hook event names must not be empty');
            $this->assertTrue(
                $this->reflection->hasMethod($handlerName),
                "getHooks() registers '{$eventName}' => {$handlerName}() but that method does not exist on ".Plugin::class
            );

            $handler = $this->reflection->getMethod($handlerName);
            $this->assertTrue($handler->isPublic(), "Handler {$handlerName}() for '{$eventName}' must be public");
            $this->assertTrue($handler->isStatic(), "Handler {$handlerName}() for '{$eventName}' must be static");
        }
    }

    /**
     * Test that getHooks contains the system.settings key.
     */
    public function testGetHooksContainsSystemSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('system.settings', $hooks);
    }

    /**
     * Test that the system.settings hook points to getSettings method on the Plugin class.
     */
    public function testSystemSettingsHookCallable(): void
    {
        $hooks = Plugin::getHooks();
        $callback = $hooks['system.settings'];

        $this->assertIsArray($callback);
        $this->assertCount(2, $callback);
        $this->assertSame(Plugin::class, $callback[0]);
        $this->assertSame('getSettings', $callback[1]);
    }

    /**
     * Test that getHooks does not include commented-out ui.menu hook.
     */
    public function testGetHooksDoesNotContainUiMenu(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayNotHasKey('ui.menu', $hooks);
    }

    /**
     * Test that all hook callbacks reference existing static methods.
     */
    public function testAllHookCallbacksReferenceExistingMethods(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $callback) {
            $this->assertTrue(
                $this->reflection->hasMethod($callback[1]),
                "Method {$callback[1]} referenced by hook '{$event}' does not exist"
            );
            $method = $this->reflection->getMethod($callback[1]);
            $this->assertTrue($method->isStatic(), "Method {$callback[1]} should be static");
        }
    }

    /**
     * Test that the getMenu method exists and is static.
     */
    public function testGetMenuMethodExists(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getMenu'));
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that getMenu accepts a GenericEvent parameter.
     */
    public function testGetMenuSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('event', $parameters[0]->getName());

        $type = $parameters[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getSettings method exists and is static.
     */
    public function testGetSettingsMethodExists(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getSettings'));
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that getSettings accepts a GenericEvent parameter.
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('event', $parameters[0]->getName());

        $type = $parameters[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getRequirements method exists and is static.
     */
    public function testGetRequirementsMethodExists(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getRequirements'));
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that getRequirements accepts a GenericEvent parameter.
     */
    public function testGetRequirementsSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('event', $parameters[0]->getName());

        $type = $parameters[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that every source getRequirements() registers is a file that exists.
     *
     * function_requirements() require()s the registered source the first time the
     * named function or class is asked for, so a registration naming a file that
     * is not on disk is a fatal waiting for its first caller - it cannot be caught
     * by anything that only inspects the method signature. This drives the real
     * handler with a stub loader, collects every (function, source) pair it
     * registers, and resolves each source to an absolute path before asserting the
     * file is there. Sources are written relative to core's include/ but the ones
     * a plugin registers for itself climb back out through vendor/ into this very
     * package, so they resolve against the package root in a standalone checkout.
     *
     * getRequirements() registers nothing today, so the loop body does not run and
     * the test passes on the assertion below it. That is the correct result for an
     * empty table: the check exists to hold the line if a registration is ever
     * added back.
     */
    public function testEveryRegisteredRequirementSourceResolvesToAnExistingFile(): void
    {
        $loader = new class () {
            /** @var array<int, array{0: string, 1: string}> */
            public array $registered = [];

            public function add_requirement($function, $source, $methods = false): void
            {
                $this->registered[] = [(string) $function, (string) $source];
            }
        };

        Plugin::getRequirements(new GenericEvent($loader));

        $packageDir = dirname(__DIR__);
        $selfPrefix = '/vendor/detain/myadmin-googlewallet-payments/';
        $coreInclude = dirname($packageDir, 3).'/include';

        foreach ($loader->registered as [$function, $source]) {
            $this->assertNotSame('', trim($source), "Requirement '{$function}' registers an empty source");

            $position = strpos($source, $selfPrefix);
            if ($position !== false) {
                $resolved = $packageDir.'/'.ltrim(substr($source, $position + strlen($selfPrefix)), '/');
            } else {
                $this->assertDirectoryExists(
                    $coreInclude,
                    "Requirement '{$function}' registers '{$source}', which points outside this package; "
                    ."resolving it needs a core checkout at {$coreInclude}"
                );
                $resolved = $coreInclude.'/'.ltrim($source, '/');
            }

            $this->assertFileExists(
                $resolved,
                "getRequirements() registers '{$function}' => '{$source}' but that file does not exist (resolved to {$resolved})"
            );
        }

        $this->assertIsArray($loader->registered, 'getRequirements() must not corrupt the loader requirement table');
    }

    /**
     * Test that the constructor takes no parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that the constructor is public.
     */
    public function testConstructorIsPublic(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
    }

    /**
     * Test that the Plugin class is in the correct namespace.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminGooglewallet', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the Plugin class is not abstract.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the Plugin class is not final.
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test the complete set of public methods on the Plugin class.
     */
    public function testPublicMethodsList(): void
    {
        $methods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);

        $expectedMethods = ['__construct', 'getHooks', 'getMenu', 'getRequirements', 'getSettings'];
        foreach ($expectedMethods as $expected) {
            $this->assertContains($expected, $methodNames, "Public method '{$expected}' should exist");
        }
    }

    /**
     * Test that getHooks return values are valid callable arrays.
     */
    public function testGetHooksReturnValuesAreValidCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertIsString($eventName, 'Hook event name should be a string');
            $this->assertIsArray($callback, "Hook callback for '{$eventName}' should be an array");
            $this->assertCount(2, $callback, "Hook callback for '{$eventName}' should have exactly 2 elements");
            $this->assertIsString($callback[0], "Hook callback class for '{$eventName}' should be a string");
            $this->assertIsString($callback[1], "Hook callback method for '{$eventName}' should be a string");
        }
    }

    /**
     * Test that all event handler methods have void return type or no return type.
     */
    public function testEventHandlerReturnTypes(): void
    {
        $eventHandlers = ['getMenu', 'getSettings', 'getRequirements'];
        foreach ($eventHandlers as $handlerName) {
            $method = $this->reflection->getMethod($handlerName);
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                $this->assertSame('void', $returnType->getName(), "Handler {$handlerName} should return void");
            } else {
                $this->assertNull($returnType, "Handler {$handlerName} has no return type (implicitly void)");
            }
        }
    }

    /**
     * Test that every hook getHooks() returns is actually dispatchable.
     *
     * The host copies each entry straight into a Symfony EventDispatcher
     * listener, so an entry only does something if its event name is a non-empty
     * string and its handler resolves to a public static method that PHP will
     * accept as a callable.
     */
    public function testGetHooksAreDispatchableCallables(): void
    {
        $hooks = Plugin::getHooks();
        $validated = [];

        foreach ($hooks as $eventName => $handler) {
            $this->assertIsString($eventName, 'Hook event names must be strings');
            $this->assertNotSame('', trim($eventName), 'Hook event names must not be empty');

            $this->assertIsArray($handler, "Handler for '{$eventName}' must be a [class, method] pair");
            $this->assertArrayHasKey(0, $handler, "Handler for '{$eventName}' is missing its class");
            $this->assertArrayHasKey(1, $handler, "Handler for '{$eventName}' is missing its method");
            $this->assertTrue(class_exists($handler[0]), "Handler class {$handler[0]} for '{$eventName}' does not exist");
            $this->assertTrue(
                method_exists($handler[0], $handler[1]),
                "Handler {$handler[0]}::{$handler[1]}() for '{$eventName}' does not exist"
            );

            $method = new \ReflectionMethod($handler[0], $handler[1]);
            $this->assertTrue($method->isPublic(), "Handler {$handler[1]}() for '{$eventName}' must be public");
            $this->assertTrue($method->isStatic(), "Handler {$handler[1]}() for '{$eventName}' must be static");
            $this->assertTrue(is_callable($handler), "Handler for '{$eventName}' must be callable as given");

            $validated[] = $eventName;
        }

        // A payment plugin that registers nothing cannot expose its settings,
        // and this also proves the loop above covered every entry.
        $this->assertNotEmpty($hooks, 'Plugin must register at least one hook');
        $this->assertSame(array_keys($hooks), $validated, 'Every hook returned by getHooks() must be validated');
    }

    /**
     * Test that multiple Plugin instances are independent.
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        $plugin1 = new Plugin();
        $plugin2 = new Plugin();

        $this->assertNotSame($plugin1, $plugin2);
        $this->assertInstanceOf(Plugin::class, $plugin1);
        $this->assertInstanceOf(Plugin::class, $plugin2);
    }

    /**
     * Test that the class uses Symfony GenericEvent via imports.
     */
    public function testClassDependsOnGenericEvent(): void
    {
        $source = file_get_contents($this->reflection->getFileName());
        $this->assertStringContainsString(
            'use Symfony\\Component\\EventDispatcher\\GenericEvent',
            $source
        );
    }

    /**
     * Test that getHooks is a static method (can be called without instantiation).
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }
}
