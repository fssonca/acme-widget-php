import type { Product, Quote } from '../api'
import { countUnits, type Quantities } from '../basket'
import { BasketLineItem } from './BasketLineItem'
import { BasketSummary } from './BasketSummary'
import { Icon } from './Icon'

interface Props {
  products: Product[]
  quantities: Quantities
  quote: { data?: Quote; loading: boolean; failed: boolean; retry: () => void }
  onChange: (code: string, delta: number) => void
}

export function BasketPanel({ products, quantities, quote, onChange }: Props) {
  const lines = products.filter((product) => quantities[product.code])

  return (
    <aside className="basket-panel" id="basket" aria-labelledby="basket-title">
      <h2 id="basket-title">
        Your basket <span className="count-pill">{countUnits(quantities)}</span>
      </h2>
      {lines.length === 0 ? (
        <div className="empty-basket">
          <Icon name="bag" />
          <p>Your basket is empty.</p>
        </div>
      ) : (
        <ul className="basket-lines">
          {lines.map((product) => (
            <BasketLineItem
              key={product.code}
              product={product}
              quantity={quantities[product.code]}
              lineSubtotalCents={
                quote.data?.items.find((item) => item.code === product.code)
                  ?.lineSubtotalCents
              }
              onChange={(delta) => onChange(product.code, delta)}
            />
          ))}
        </ul>
      )}
      <BasketSummary
        quote={quote.data}
        loading={quote.loading}
        failed={quote.failed}
        onRetry={quote.retry}
      />
    </aside>
  )
}
