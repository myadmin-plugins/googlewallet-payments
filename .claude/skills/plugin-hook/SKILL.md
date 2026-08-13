---
name: plugin-hook
description: Adds a new Symfony EventDispatcher hook to src/Plugin.php. Use when the user says 'add hook', 'register event', 'listen to event', or needs to extend getHooks(). Adds the event key to getHooks() return array and creates the corresponding static handler method with a GenericEvent $event parameter. Do NOT use for modifying existing hooks or editing commented-out hooks. For a plugin's contract or behavioral tests (tests/ContractTest.php, the shared harness, composer myadmin:scaffold-tests) use the plugin-contract-tests skill instead — this skill's reflection-only guidance predates that harness.
---
<!-- myadmin-contract-harness-notice -->
> ### ⚠️ Read this before the rest of the file
>
> This package is on the **shared plugin contract harness**. Parts of the guidance below
> predate it and are now wrong in one specific way:
>
> **Any instruction here that a plugin's `getHooks()` / `getSettings()` / `getActivate()` /
> `getDeactivate()` / `getQueue()` must not be *called* — that only its existence, visibility
> or parameter count may be checked through `ReflectionClass` — no longer applies.** That rule
> existed because those methods reference bare constants (`PRORATE_BILLING` and friends) that
> only a live MyAdmin request defines, so calling them from a test used to fatal. The harness
> defines them first. It then executes the handlers for real, in a process of its own.
>
> A reflection-only assertion passes whether or not the thing works: `getActivate()` can exist,
> be public, be static, take one argument, and still fatal the moment it runs. Three real
> production bugs in this fleet were sitting behind assertions of exactly that shape.
>
> **Use the `plugin-contract-tests` skill** for anything touching `tests/ContractTest.php`,
> the contract inspectors, or `composer myadmin:scaffold-tests`.
>
> **Everything else in this file is still accurate and still applies** — this package's own
> classes, its API wrappers, its fixtures, its bootstrap, and the reasons certain classes must
> not be constructed. Nothing below has been removed.

# plugin-hook

## Critical

- Every handler method MUST accept exactly one parameter: `GenericEvent $event` — no other signature is valid.
- Use `__CLASS__` (not `Plugin::class` or a string) as the class reference inside `getHooks()`.
- Indentation is **tabs**, not spaces — enforced by `.scrutinizer.yml`. One violation will fail CI.
- Never add a hook entry in `getHooks()` without creating the corresponding method, and vice versa.
- The full test suite must pass after every change.

## Instructions

1. **Identify the event name and handler method name.**
   - Event names use dot notation: `system.settings`, `plugin.requirements`.
   - Method names are camelCase: `getSettings`, `getMenu`, `getRequirements`.
   - Verify no existing key in `getHooks()` already uses this event name before proceeding.

2. **Add the event entry to `getHooks()` in `src/Plugin.php`.**
   - Open `src/Plugin.php`. Locate the `getHooks()` return array (line ~31).
   - Append a new key/value pair inside the existing `return [...]` block:
   ```php
   public static function getHooks()
   {
   	return [
   		'system.settings' => [__CLASS__, 'getSettings'],
   		'your.event.name' => [__CLASS__, 'yourHandlerMethod'],  // add here
   	];
   }
   ```
   - Verify the array uses tabs and trailing commas match the surrounding style.

3. **Add the static handler method to `src/Plugin.php`.**
   - Place the new method after the last existing handler, before the closing `}`.
   - Use this exact docblock + signature pattern (tabs, no return type declared):
   ```php
   	/**
   	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
   	 */
   	public static function yourHandlerMethod(GenericEvent $event)
   	{
   		$subject = $event->getSubject();
   		// your logic here
   	}
   ```
   - Verify `use Symfony\Component\EventDispatcher\GenericEvent;` is already present at the top of the file (it is in the base file — do not duplicate it).

4. **Run the test suite.**
   ```bash
   phpunit
   ```
   All existing tests (especially `testAllHookCallbacksReferenceExistingMethods` and `testPublicMethodsList`) must pass. If `testPublicMethodsList` hardcodes expected method names, update that test to include your new method name.

## Examples

**User says:** "Add a hook for `plugin.requirements` that calls `getRequirements`"

**Actions taken:**

1. In `getHooks()`, add: `'plugin.requirements' => [__CLASS__, 'getRequirements'],`
2. Add method:
```php
	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		$loader = $event->getSubject();
		$loader->add_requirement('class.Googlewallet', '/../vendor/detain/myadmin-googlewallet-payments/src/Googlewallet.php');
	}
```
3. Run the test suite — all tests pass.

**Result:** `Plugin::getHooks()` returns `['system.settings' => [...], 'plugin.requirements' => [Plugin::class, 'getRequirements']]`.

## Common Issues

- **`testAllHookCallbacksReferenceExistingMethods` fails:** The method name in `getHooks()` doesn't match the actual method name. Check for typos — the callback string must exactly match the `public static function` name.
- **`testPublicMethodsList` fails with "Public method 'yourMethod' should exist":** The test hardcodes expected methods at line ~287 of `tests/PluginTest.php`. Add your new method name to the `$expectedMethods` array in that test.
- **Scrutinizer reports indentation errors:** You used spaces instead of tabs. Run `cat -A src/Plugin.php | grep '^ '` to find space-indented lines and replace with tabs.
- **`testEventHandlerReturnTypes` fails:** You declared an explicit non-`void` return type on the handler. Either omit the return type entirely or declare `: void`.
- **`testGetHooksReturnType` fails:** You changed `getHooks()` signature. It must be declared `public static function getHooks()` with no explicit return type hint other than `array` (the test asserts `array` via `@return array` reflection — keep the existing docblock).
