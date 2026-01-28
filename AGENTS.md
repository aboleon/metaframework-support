# Repository Guidelines

## Build, Test, and Development Commands
- `composer install` — install PHP dependencies.
- `vendor/bin/phpunit` — run the automated test suite.

## PHPUnit

- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Testing Guidelines
- Tests are PHPUnit/Laravel feature and unit tests under `tests/`. Mirror directory names of the code under test.
- Run `vendor/bin/phpunit` before submitting changes; add coverage for new logic and error paths when feasible.

## Commit & Pull Request Guidelines
- Commit messages: use concise, present-tense summaries. Group related changes per commit.
- Pull requests: include a short description of scope, linked issue/ticket if any, setup steps, and screenshots for UI changes. Note any migrations or breaking changes explicitly.

## Security & Configuration Tips
- Keep dependencies updated via `composer update`/`npm update` in dedicated PRs; run the test suite after upgrades.


## Programming language
- PHP with the last available version, highest standards (php version 8.5)
- Javascript with Jquery

## Package Dependencies

Local package symlinked from `../_packages/aboleon/*` (metaframework). Module composer.json files are merged via wikimedia/composer-merge-plugin.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

