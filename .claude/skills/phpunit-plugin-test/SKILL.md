---
name: phpunit-plugin-test
description: Writes PHPUnit 9.6 tests for src/Plugin.php in tests/PluginTest.php using ReflectionClass to assert static properties, method signatures, and hook callback arrays. Use when the user says 'add test', 'write test for', 'test this method', or after adding new hooks/settings/methods to Plugin.php. Run with `vendor/bin/phpunit`. Do NOT use for integration tests or tests outside the Plugin class.
---
# PHPUnit Plugin Test

## Critical

- File must be `tests/PluginTest.php` · namespace `Detain\MyAdminGooglewallet\Tests`
- Use `declare(strict_types=1)` at the top
- Never instantiate the real host MyAdmin system — tests must work standalone
- Guard all `getSettings` tests against undefined constants — the host system defines `GOOGLE_WALLET_*` constants that won't exist in test context
- All hook callbacks must be `[ClassName::class, 'methodName']` arrays — never closures
- Run the test suite and confirm 0 failures before considering the task done

## Instructions

1. **Add imports** at the top of `tests/PluginTest.php`:
   ```php
   declare(strict_types=1);
   namespace Detain\MyAdminGooglewallet\Tests;
   use Detain\MyAdminGooglewallet\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   ```
   Verify the class under test is `Detain\MyAdminGooglewallet\Plugin` before proceeding.

2. **Set up ReflectionClass in `setUp()`**:
   ```php
   private ReflectionClass $reflection;
   protected function setUp(): void {
       $this->reflection = new ReflectionClass(Plugin::class);
   }
   ```
   This is used in every reflection-based assertion below.

3. **Test static properties** — assert exact values and visibility:
   ```php
   public function testNameProperty(): void {
       $this->assertSame('Googlewallet Plugin', Plugin::$name);
   }
   public function testTypeProperty(): void {
       $this->assertSame('plugin', Plugin::$type);
   }
   public function testStaticPropertiesArePublic(): void {
       foreach ($this->reflection->getProperties(\ReflectionProperty::IS_STATIC) as $p) {
           $this->assertTrue($p->isPublic(), "Property \${$p->getName()} should be public");
       }
   }
   ```
   Verify `Plugin::$name`, `$description`, `$help`, `$type` all exist before adding value assertions.

4. **Test `getHooks()` structure** — assert keys, callback shape, and that callbacks point to real static methods:
   ```php
   public function testAllHookCallbacksReferenceExistingMethods(): void {
       foreach (Plugin::getHooks() as $event => $callback) {
           $this->assertIsArray($callback);
           $this->assertCount(2, $callback);
           $this->assertSame(Plugin::class, $callback[0]);
           $method = $this->reflection->getMethod($callback[1]);
           $this->assertTrue($method->isStatic(), "Hook method {$callback[1]} must be static");
       }
   }
   ```
   When a hook is commented out in `getHooks()`, assert `assertArrayNotHasKey` for that event name.

5. **Test event handler signatures** — all handlers must accept exactly one `GenericEvent $event` param and return `void`:
   ```php
   foreach (['getMenu', 'getSettings', 'getRequirements'] as $handler) {
       $method = $this->reflection->getMethod($handler);
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   ```

6. **Test class-level constraints** — namespace, not abstract, not final:
   ```php
   $this->assertSame('Detain\\MyAdminGooglewallet', $this->reflection->getNamespaceName());
   $this->assertFalse($this->reflection->isAbstract());
   $this->assertFalse($this->reflection->isFinal());
   ```

7. **Run tests**: execute the phpunit test suite — all tests must pass with 0 errors.

## Examples

**User says:** "I added a `getActivate` hook to Plugin.php, add tests for it."

**Actions taken:**
1. Read `src/Plugin.php` to confirm `getActivate(GenericEvent $event)` exists as a public static method
2. Confirm `getHooks()` now returns `['system.activate' => [Plugin::class, 'getActivate']]`
3. Add to `tests/PluginTest.php`:
   ```php
   public function testGetHooksContainsSystemActivate(): void {
       $hooks = Plugin::getHooks();
       $this->assertArrayHasKey('system.activate', $hooks);
       $this->assertSame([Plugin::class, 'getActivate'], $hooks['system.activate']);
   }
   public function testGetActivateSignature(): void {
       $method = $this->reflection->getMethod('getActivate');
       $this->assertTrue($method->isStatic());
       $this->assertTrue($method->isPublic());
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   ```
4. Run the test suite — confirm pass

**Result:** Two new passing tests covering the hook registration and method signature.

## Common Issues

- **`Error: Class "GOOGLE_WALLET_ENABLED" not found`** — `getSettings` references undefined constants. Wrap with `defined('GOOGLE_WALLET_ENABLED')` in `Plugin.php` (already done for text/password settings). Do not call `getSettings` directly in tests without defining constants first.
- **`ReflectionException: Method getActivate does not exist`** — method was added to `getHooks()` return array but not to the class body. Add the missing static method to `src/Plugin.php`.
- **`Failed asserting that false is true` on `isStatic()`** — hook callback method was declared as an instance method. All event handlers must be `public static function`.
- **`PHPUnit\Framework\Error\Warning: array_map(): Argument #2 should be of type array`** — `getHooks()` returned a non-array. Check that `Plugin::getHooks()` has an explicit `return []` with `array` return type hint.
- **Test class not found** — check `phpunit.xml.dist` bootstrap and verify `testsuites` directory is `tests/`.
