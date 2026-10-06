import { useState } from 'react'
import { getCatalogue, getQuote } from './api'
import {
  changeQuantity,
  countUnits,
  MAX_QUANTITY,
  toItems,
  type Quantities,
} from './basket'
import { BasketPanel } from './components/BasketPanel'
import { Icon } from './components/Icon'
import { ProductCard } from './components/ProductCard'
import { useRequest } from './hooks/useRequest'
import './App.css'

export default function App() {
  const catalogue = useRequest('catalogue', getCatalogue)
  const [quantities, setQuantities] = useState<Quantities>({})
  const items = toItems(quantities)
  // Re-quote whenever the basket contents change, once products are known.
  const quote = useRequest(
    catalogue.data ? JSON.stringify(items) : null,
    (signal) => getQuote(items, signal),
  )
  const change = (code: string, delta: number) =>
    setQuantities((current) => changeQuantity(current, code, delta))

  return (
    <>
      <header className="site-header">
        <span className="wordmark">
          <span className="brand-mark" aria-hidden="true">
            ✳
          </span>
          <span>
            acme<small>WIDGET CO.</small>
          </span>
        </span>
        <a className="basket-link" href="#basket">
          <Icon name="bag" /> Basket{' '}
          <span className="count-pill">{countUnits(quantities)}</span>
        </a>
      </header>

      <main>
        <h1>A few good widgets.</h1>
        <div className="shop-layout">
          <section aria-label="Products">
            {catalogue.failed ? (
              <div className="message" role="alert">
                <p>The products couldn’t load.</p>
                <button className="primary-button" onClick={catalogue.retry}>
                  Try again <Icon name="arrow" />
                </button>
              </div>
            ) : !catalogue.data ? (
              <p className="message" role="status">
                Loading products…
              </p>
            ) : (
              <>
                <div className="product-grid">
                  {catalogue.data.products.map((product) => (
                    <ProductCard
                      key={product.code}
                      product={product}
                      canAdd={(quantities[product.code] ?? 0) < MAX_QUANTITY}
                      onAdd={() => change(product.code, 1)}
                    />
                  ))}
                </div>
                {catalogue.data.offers.map((offer) => (
                  <p className="offer-note" key={offer}>
                    <Icon name="tag" /> {offer}
                  </p>
                ))}
              </>
            )}
          </section>
          <BasketPanel
            products={catalogue.data?.products ?? []}
            quantities={quantities}
            quote={quote}
            onChange={change}
          />
        </div>
      </main>
    </>
  )
}
