import { fireEvent, render, screen, within } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App'

const catalogue = {
  products: [
    { code: 'R01', name: 'Red Widget', unitPriceCents: 3295 },
    { code: 'B01', name: 'Blue Widget', unitPriceCents: 795 },
  ],
  offers: ['Buy one red widget, get the second half price.'],
}

const twoReds = {
  items: [{ code: 'R01', quantity: 2, lineSubtotalCents: 6590 }],
  subtotalCents: 6590,
  discountCents: 1648,
  deliveryCents: 495,
  totalCents: 5437,
}

const emptyQuote = {
  items: [],
  subtotalCents: 0,
  discountCents: 0,
  deliveryCents: 0,
  totalCents: 0,
}

const json = (body: unknown, status = 200) =>
  Promise.resolve(new Response(JSON.stringify(body), { status }))

describe('App', () => {
  let quoteResponse: () => Promise<Response>

  beforeEach(() => {
    quoteResponse = () => json(emptyQuote)
    vi.stubGlobal(
      'fetch',
      vi.fn((path: string, init?: RequestInit) => {
        if (path === '/api/catalogue') return json(catalogue)
        const { items } = JSON.parse(String(init?.body))
        return items.length === 0 ? json(emptyQuote) : quoteResponse()
      }),
    )
  })

  it('shows the totals the API quotes for the basket', async () => {
    quoteResponse = () => json(twoReds)
    render(<App />)

    const add = await screen.findByRole('button', { name: 'Add Red Widget' })
    fireEvent.click(add)
    fireEvent.click(add)

    expect(await screen.findByText('$54.37')).toBeTruthy()
    expect(screen.getByText('−$16.48')).toBeTruthy()
    expect(fetch).toHaveBeenLastCalledWith(
      '/api/basket/quote',
      expect.objectContaining({
        body: JSON.stringify({ items: [{ code: 'R01', quantity: 2 }] }),
      }),
    )
  })

  it('lists every offer the API reports', async () => {
    render(<App />)

    expect(
      await screen.findByText('Buy one red widget, get the second half price.'),
    ).toBeTruthy()
  })

  it('removes a product from the basket', async () => {
    render(<App />)

    fireEvent.click(
      await screen.findByRole('button', { name: 'Add Blue Widget' }),
    )
    const basket = screen.getByRole('complementary', { name: /your basket/i })
    fireEvent.click(within(basket).getByRole('button', { name: 'Remove' }))

    expect(within(basket).getByText('Your basket is empty.')).toBeTruthy()
  })

  it('hides amounts and offers a retry when quoting fails', async () => {
    quoteResponse = () => json({ message: 'Server error' }, 500)
    render(<App />)

    fireEvent.click(
      await screen.findByRole('button', { name: 'Add Red Widget' }),
    )

    expect(await screen.findByRole('alert')).toBeTruthy()
    expect(screen.getByTestId('basket-total').textContent).toBe('—')

    quoteResponse = () => json(twoReds)
    fireEvent.click(screen.getByRole('button', { name: /try again/i }))
    expect(await screen.findByText('$54.37')).toBeTruthy()
  })
})
