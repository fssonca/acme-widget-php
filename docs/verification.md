# Verification

Checks were executed on 2026-10-06 using the project tool images. The native ARM64 baseline is `be22545`. Frontend and browser checks were repeated after the accessible quantity/subtotal labels were updated; the same PHP and frontend gates also passed on AMD64 under emulation, as detailed below.

## Application checks

| Check | Result |
| --- | --- |
| Complete PHP suite | 152 tests, 365 assertions; no warnings |
| Pure domain PHPUnit | 101 tests, 170 assertions; no Laravel boot |
| Pint | Passed, 43 PHP files |
| PHPStan/Larastan | Level 8; no errors, baseline, or ignores |
| Composer manifest | `composer validate --strict` passed; locked dependencies installed and platform checked in Docker |
| Frontend | Strict TypeScript, lint with zero warnings/errors, production build passed |
| HTTP smoke through Apache | Root redirect, index, JS/CSS MIME types, favicon, health, catalogue, four totals, empty basket, duplicate rows, limits, malformed JSON and 422 errors passed |
| Runtime contents | No `.env`, Composer, build-only Composer environment flag, Node, PHPUnit, or Boost; configuration cached at startup; Apache worker can write storage and bootstrap/cache |
| Clean checkout | No host `.env`, `vendor`, `node_modules`, or compiled assets; `docker compose up --build --wait` passed with only the app service running |
| Stop/start | `docker compose down`, then the same build/start command and HTTP smoke passed again |
| Failed readiness | Removing the compiled index made Compose launch fail as unhealthy, while `/up` still returned 200; normal application restored afterward |
| CI commands | Check/start/smoke commands passed locally; workflow YAML parsed successfully |

See the [README](../README.md#checks) for the commands and [HTTP smoke script](../frontend/scripts/smoke.mjs) for the integration checks. A clean checkout verifies that the project requires no host application dependencies or generated assets; it does not establish a build with empty image/dependency caches.

## Browser checks

The built application was exercised with Playwright, Google Chrome, and axe:

| Check | Result |
| --- | --- |
| Acceptance baskets | $37.85, $54.37, $60.85, and $98.27 reproduced with Add buttons |
| Quantity controls | Increment, decrement, last-unit removal, and keyboard Add passed |
| Accessible labels | Product-specific quantity and gross-subtotal text is present in the browser accessibility tree |
| Network failures | Catalogue and quote retries recovered; a failed quote hid previous amounts |
| Response ordering | Delayed old successes and errors arrived after a newer successful quote and did not overwrite it; updating amounts were hidden |
| Responsive/accessibility | 1440 px desktop and 390 px mobile; no mobile overflow or uncaught JavaScript errors; axe WCAG 2 A/AA and 2.1 AA scans found no violations in the tested filled-basket states |
| Screenshot | Actual application with two reds totaling $54.37, saved as `docs/screenshot.png` |

These browser tools are verification dependencies, separate from the application. Automated accessibility scans cover the tested states and do not replace a full accessibility audit.

## Build portability and CI

Official image manifests include linux/arm64 and linux/amd64. Native execution has been tested on ARM64. An AMD64 build and execution were also verified under Docker Desktop emulation on ARM64.

A new, isolated `docker-container` BuildKit builder was used with no imported cache. Its first application build used `--platform linux/amd64 --pull --no-cache --load`: it fetched the pinned base-image layers, installed locked Composer/npm dependencies, and compiled the interface. This establishes a cold build for that builder without deleting shared Docker caches. The tools targets were then built in the same builder, reusing the base layers fetched during the application build.

| AMD64 check under emulation | Result |
| --- | --- |
| Cold application build | Passed in an isolated builder with no existing image/build/dependency cache |
| Runtime startup | Healthy; PHP reports `x86_64`, version 8.4.26 |
| Complete and pure-domain PHP suites | 152 tests / 365 assertions; 101 tests / 170 assertions |
| Pint, PHPStan level 8, Composer validation | Passed |
| TypeScript, lint, production build | Passed; zero lint warnings/errors |
| HTTP smoke | Assets, catalogue, all four totals, limits, and error contracts passed |
| Browser accessibility tree | Quantity and gross-subtotal labels present |

The application image build can be reproduced with an isolated builder:

```sh
docker buildx create --name acme-cold --driver docker-container --bootstrap
docker buildx build --builder acme-cold --platform linux/amd64 \
  --pull --no-cache --load --target app --tag acme-widget:amd64 .
docker buildx rm acme-cold
```

Native AMD64 execution and hosted GitHub Actions remain unverified. Emulation verifies the AMD64 image/toolchain but does not establish a successful run on a hosted native AMD64 runner.

The [workflow](../.github/workflows/ci.yml) builds the tool images, runs the checks, starts the application, verifies HTTP, and cleans up containers even after failure. A local command rehearsal is separate from an actual hosted workflow run.
