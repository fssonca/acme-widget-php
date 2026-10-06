export interface Product {
  code: string
  name: string
  unitPriceCents: number
}

export interface Catalogue {
  products: Product[]
  offerDescription: string
}

export interface BasketItem {
  code: string
  quantity: number
}

export interface Quote {
  items: (BasketItem & { lineSubtotalCents: number })[]
  subtotalCents: number
  discountCents: number
  deliveryCents: number
  totalCents: number
}

async function request<T>(path: string, init: RequestInit): Promise<T> {
  const response = await fetch(path, {
    ...init,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  })
  if (!response.ok) throw new Error(`${path} responded ${response.status}`)
  return response.json() as Promise<T>
}

export const getCatalogue = (signal: AbortSignal) =>
  request<Catalogue>('/api/catalogue', { signal })

// Prices are never calculated in the browser: the API quotes every basket change.
export const getQuote = (items: BasketItem[], signal: AbortSignal) =>
  request<Quote>('/api/basket/quote', {
    method: 'POST',
    body: JSON.stringify({ items }),
    signal,
  })

export const formatMoney = (cents: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(
    cents / 100,
  )
