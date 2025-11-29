
### Tests

-   Use Pest PHP (not PHPUnit)
-   One test file per feature/domain (e.g., `SchoolRegistrationAndSyllabusProcessingTest.php`)
-   Test description: readable as a sentence with `it()` (e.g., `it('creates school with valid data')`)
-   Arrange-Act-Assert pattern
-   Always test user-facing actions, not just methods
-   Test filenames must be descriptive of the feature being tested

## Pest PHP Tips

-   Use `->expectExceptionMessage()` for specific errors
-   Use `it()` to write tests as readable sentences (not `test()`)
-   Group related tests with `describe()` blocks
-   No nested `describe()` blocks
-   Use factories for test data
-   Example: `it('creates school with valid data')` reads as a complete thought
