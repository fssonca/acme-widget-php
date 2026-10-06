import assert from 'node:assert/strict'

const base = process.argv[2] ?? 'http://app'
const url = (path) => new URL(path, base)
async function quote(body, status = 200, contentType = 'application/json') {
  const response = await fetch(url('/api/basket/quote'), {
    method: 'POST',
    headers: { 'Content-Type': contentType },
    body: typeof body === 'string' ? body : JSON.stringify(body),
    signal: AbortSignal.timeout(10_000),
  })
  assert.equal(response.status, status)
  assert.match(response.headers.get('content-type'), /application\/json/)
  return response.json()
}

const root = await fetch(url('/'), { redirect: 'manual' })
assert.equal(root.status, 302)
assert.equal(
  new URL(root.headers.get('location'), base).pathname,
  '/app/index.html',
)
const html = await fetch(url('/app/index.html'))
assert.equal(html.status, 200)
const markup = await html.text()
const assets = [
  ...markup.matchAll(/(?:src|href)="(\/app\/[^"\s]+\.(?:js|css))"/g),
]
assert.ok(assets.some((asset) => asset[1].endsWith('.js')))
assert.ok(assets.some((asset) => asset[1].endsWith('.css')))
for (const [, asset] of assets) {
  const response = await fetch(url(asset))
  assert.equal(response.status, 200)
  assert.match(
    response.headers.get('content-type'),
    asset.endsWith('.js') ? /javascript/ : /text\/css/,
  )
}
assert.equal((await fetch(url('/up'))).status, 200)
assert.equal((await fetch(url('/app/favicon.svg'))).status, 200)
const catalogueResponse = await fetch(url('/api/catalogue'))
assert.equal(catalogueResponse.status, 200)
const catalogue = await catalogueResponse.json()
assert.equal(catalogue.currency, 'USD')
assert.deepEqual(
  catalogue.products.map(({ code, unitPriceCents }) => [code, unitPriceCents]),
  [
    ['R01', 3295],
    ['G01', 2495],
    ['B01', 795],
  ],
)
const baskets = [
  [
    [
      { code: 'B01', quantity: 1 },
      { code: 'G01', quantity: 1 },
    ],
    3785,
  ],
  [[{ code: 'R01', quantity: 2 }], 5437],
  [
    [
      { code: 'R01', quantity: 1 },
      { code: 'G01', quantity: 1 },
    ],
    6085,
  ],
  [
    [
      { code: 'B01', quantity: 2 },
      { code: 'R01', quantity: 3 },
    ],
    9827,
  ],
]
for (const [items, total] of baskets) {
  const result = await quote({ items })
  assert.equal(result.totalCents, total)
  assert.equal(
    result.subtotalCents - result.discountCents,
    result.discountedSubtotalCents,
  )
  assert.equal(
    result.discountedSubtotalCents + result.deliveryCents,
    result.totalCents,
  )
}
assert.equal((await quote({ items: [] })).totalCents, 0)
assert.deepEqual(
  await quote({
    items: [
      { code: 'R01', quantity: 1 },
      { code: 'R01', quantity: 1 },
    ],
  }),
  await quote({ items: [{ code: 'R01', quantity: 2 }] }),
)
assert.equal(
  (await quote({ items: [{ code: 'B01', quantity: 1000 }] })).totalCents,
  795000,
)
for (const body of [
  {},
  { items: {} },
  { items: [{ code: 'r01', quantity: 1 }] },
  { items: [{ code: 'R01', quantity: '2' }] },
  { items: [{ code: 'R01', quantity: 1001 }] },
  {
    items: [
      { code: 'R01', quantity: 600 },
      { code: 'B01', quantity: 600 },
    ],
  },
  { items: [{ code: 'R01', quantity: 2, unitPriceCents: 1 }] },
])
  assert.ok((await quote(body, 422)).errors)
assert.equal(
  (await quote('{"items":', 400)).message,
  'Malformed JSON request body.',
)
assert.ok(
  (
    await quote(
      'items[0][code]=unknown&items[0][quantity]=1',
      422,
      'application/x-www-form-urlencoded',
    )
  ).errors,
)
console.log(
  'HTTP smoke passed: root, assets, health, catalogue, four totals, duplicates, unit limits, and JSON errors.',
)
