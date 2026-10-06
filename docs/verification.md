# Final verification

Phases 1–2 were reviewed and approved by the candidate on 2026-10-06. Phases 3–5 implement the approved domain/rounding policy through Laravel and React. Nothing is published or submitted.

## Review cleanups

- Removed the committed Boost agent instructions/configuration and `laravel/boost` dev dependency, including its unused transitive packages. AI assistance remains truthfully disclosed in the README.
- Removed unused Vite template images/icons and replaced its favicon with the app mark, served at `/app/favicon.svg`.
- Renamed `ExampleTest` to `ScaffoldTest`.
- Removed the impractical basket quantity-overflow guard; Money arithmetic retains its actual overflow checks, and HTTP limits quotations to 1000 units.
- Delivery-band validation now rejects missing keys, non-array bands, and non-integer values with `InvalidArgumentException`. The domain still contains the same ten types.
- Composer and its build-only environment flag are absent from the final application image.

## Executed checks

Checks run on 2026-10-06 using Docker Desktop linux/arm64 on Apple Silicon:

| Check | Result |
| --- | --- |
| Complete PHP suite | 152 tests, 365 assertions; no warnings |
| Pure domain PHPUnit | 101 tests, 170 assertions; no Laravel boot |
| Pint | Passed, 43 PHP files |
| PHPStan/Larastan | Level 8; no errors, baseline, or ignores |
| Composer manifest | `composer validate --strict` passed; locked dependencies installed and platform checked in Docker |
| Frontend | Strict TypeScript, lint with zero warnings/errors, production build passed |
| HTTP smoke through Apache | Root redirect, index, JS/CSS MIME types, favicon, health, catalogue, four totals, empty basket, duplicate rows, limits, malformed JSON and 422 errors passed |
| Runtime contents | No `.env`, Composer, build-only Composer environment flag, Node, PHPUnit, or Boost; config cached at startup; Apache worker can write storage and bootstrap/cache |
| Clean checkout | Detached Git worktree at `277baec`, no host `.env`, `vendor`, `node_modules`, or compiled assets; exact `docker compose up --build --wait` passed with only app running |
| Stop/start | `docker compose down`, then the same build/start command and HTTP smoke passed again |
| Failed readiness | Removed the compiled index in a temporary container override; Compose launch failed as unhealthy, while `/up` still returned 200 and the health-check process returned failure; normal app restored afterward |
| CI rehearsal | All application check/start/smoke commands passed locally; workflow YAML parsed successfully |
| Browser acceptance | $37.85, $54.37, $60.85, $98.27 reproduced with Add buttons |
| Browser controls | Increment, decrement, last-unit removal, and keyboard Add passed |
| Network failures | Catalogue retry and quote retry recovered; a failed quote hid previous amounts |
| Response ordering | Delayed old successes and old errors arrived after a newer successful quote and did not overwrite it; updating amounts were hidden |
| Responsive/accessibility | 1440 px desktop and 390 px mobile; no mobile overflow or uncaught JavaScript errors; axe WCAG 2 A/AA and 2.1 AA scans found no violations in the tested filled-basket states |
| Screenshot | Actual running app, two reds totaling $54.37, saved as `docs/screenshot.png` |

The browser checks used Playwright with installed Google Chrome and axe from a temporary verification directory. They did not add application dependencies. The screenshot is the implemented UI, not an image-generation mockup. Automated scans cover the tested states and do not replace a full accessibility audit.

All required checks above were executed against committed source. The clean worktree remains free of host dependencies/generated assets after the checks because tool commands operate inside their images. The workflow has not run on GitHub because the repository has no remote and publication remains a separate authorized step.

## Limits and environment

Official image manifests support linux/arm64 and linux/amd64; execution was tested only on ARM64. AMD64 execution and hosted GitHub Actions are untested.

The host's default Docker Desktop credential helper stalled during the earlier scaffold work. Registry/build operations used a temporary `DOCKER_CONFIG` and the existing Docker socket, leaving the user's Docker settings untouched. Later builds reused cached base images. This does not prove that default-config registry pulls now work, nor represent a cold registry-cache build. The application startup command remains exactly `docker compose up --build --wait`.

The recruiter AI-use policy remains unknown. The candidate reviewed the domain checkpoint; final API/UI review and applicable policy/disclosure decisions remain for the candidate before submission. Existing Git identity and the no-optional-coauthor attribution choice were preserved.

## Interview walkthrough

1. Quantities are the only mutable state. Calculations copy them into immutable product/quantity lines, so order and repeated quotations do not affect eligibility.
2. Money is integer cents. Each eligible second red costs `intdiv(3295, 2) = 1647`; each pair saves 1648. Two reds are 4942 merchandise + 495 delivery = 5437. Six reds are 19770 − 4944 = 14826, with free delivery.
3. Offers run before delivery. `BasketTotals` derives net and final total, preserving both accounting identities.
4. Constructor injection replaces products, delivery, or offers without framework coupling. Laravel builds those dependencies from cacheable configuration; `QuoteBasket` creates one basket per call.
5. HTTP validates containers, catalogue codes, strict integers, and aggregate unit limits. React displays the returned quote and prevents stale responses from being presented as current. No database is needed for a stateless calculation.
