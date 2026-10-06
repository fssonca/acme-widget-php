export interface Product {
  code: string
  name: string
  unitPriceCents: number
}

export interface Catalogue {
  currency: 'USD'
  products: Product[]
  offerDescription: string
}

export interface BasketItem {
  code: string
  quantity: number
}

export interface Quote {
  currency: 'USD'
  items: (Product & BasketItem & { lineSubtotalCents: number })[]
  subtotalCents: number
  discountCents: number
  discountedSubtotalCents: number
  deliveryCents: number
  totalCents: number
}

async function request<T>(path: string, options: RequestInit): Promise<T> {
  const response = await fetch(path, {
    ...options,
    headers: { Accept: 'application/json', ...options.headers },
  })

  if (!response.ok) {
    throw new Error('We could not load the latest prices. Please try again.')
  }

  return response.json() as Promise<T>
}

export const getCatalogue = (signal: AbortSignal) =>
  request<Catalogue>('/api/catalogue', { signal })

export const getQuote = (items: BasketItem[], signal: AbortSignal) =>
  request<Quote>('/api/basket/quote', {
    method: 'POST',
    signal,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ items }),
  })

export const formatMoney = (cents: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(
    cents / 100,
  )
