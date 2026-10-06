import { useEffect, useMemo, useRef, useState } from 'react'
import { formatMoney, getCatalogue, getQuote } from './api'
import type { Catalogue, Quote } from './api'
import { Icon } from './components/Icon'
import { WidgetIllustration } from './components/WidgetIllustration'
import './App.css'

type QuoteState = {
  key: string
  status: 'loading' | 'ready' | 'error'
  value: Quote | null
}
const tone = (code: string) =>
  code === 'R01' ? 'red' : code === 'G01' ? 'green' : 'blue'

function App() {
  const [catalogue, setCatalogue] = useState<Catalogue | null>(null)
  const [catalogueError, setCatalogueError] = useState(false)
  const [catalogueAttempt, setCatalogueAttempt] = useState(0)
  const [quantities, setQuantities] = useState<Record<string, number>>({})
  const [quoteState, setQuoteState] = useState<QuoteState>({
    key: '',
    status: 'loading',
    value: null,
  })
  const [quoteAttempt, setQuoteAttempt] = useState(0)
  const catalogueRequest = useRef(0)
  const quoteRequest = useRef(0)
  const items = useMemo(
    () =>
      catalogue?.products.flatMap((product) =>
        quantities[product.code]
          ? [{ code: product.code, quantity: quantities[product.code] }]
          : [],
      ) ?? [],
    [catalogue, quantities],
  )
  const basketKey = JSON.stringify({ items, attempt: quoteAttempt })
  const count = items.reduce((total, item) => total + item.quantity, 0)
  const current =
    quoteState.key === basketKey && quoteState.status === 'ready'
      ? quoteState.value
      : null
  const quoteFailed =
    quoteState.key === basketKey && quoteState.status === 'error'
  const updating = catalogue !== null && current === null && !quoteFailed

  useEffect(() => {
    const controller = new AbortController()
    const requestId = ++catalogueRequest.current
    getCatalogue(controller.signal)
      .then((value) => {
        if (
          !controller.signal.aborted &&
          requestId === catalogueRequest.current
        )
          setCatalogue(value)
      })
      .catch(() => {
        if (
          !controller.signal.aborted &&
          requestId === catalogueRequest.current
        )
          setCatalogueError(true)
      })
    return () => controller.abort()
  }, [catalogueAttempt])

  useEffect(() => {
    if (!catalogue) return
    const controller = new AbortController()
    const requestId = ++quoteRequest.current
    getQuote(items, controller.signal)
      .then((value) => {
        if (!controller.signal.aborted && requestId === quoteRequest.current) {
          setQuoteState({ key: basketKey, status: 'ready', value })
        }
      })
      .catch(() => {
        if (!controller.signal.aborted && requestId === quoteRequest.current) {
          setQuoteState({ key: basketKey, status: 'error', value: null })
        }
      })
    return () => controller.abort()
  }, [catalogue, items, basketKey, quoteAttempt])

  function changeQuantity(code: string, change: number | 'remove') {
    setQuantities((previous) => {
      const totalUnits = Object.values(previous).reduce(
        (total, quantity) => total + quantity,
        0,
      )
      if (change === 1 && totalUnits >= 1000) return previous
      const next = { ...previous }
      const quantity = change === 'remove' ? 0 : (previous[code] ?? 0) + change
      if (quantity <= 0) delete next[code]
      else next[code] = quantity
      return next
    })
  }

  const amount = (cents: number | undefined) =>
    cents === undefined ? '—' : formatMoney(cents)

  return (
    <>
      <a className="skip-link" href="#collection">
        Skip to products
      </a>
      <header className="site-header">
        <a
          className="wordmark"
          href="#collection"
          aria-label="Acme Widget Co home"
        >
          <span className="brand-mark" aria-hidden="true">
            ✳
          </span>
          <span>
            acme<span className="brand-caption">WIDGET CO.</span>
          </span>
        </a>
        <nav aria-label="Main navigation">
          <a className="active-nav" href="#collection">
            The collection
          </a>
          <a className="basket-link" href="#basket">
            <Icon name="bag" /> Your basket{' '}
            <span className="count-pill">{count}</span>
          </a>
        </nav>
      </header>

      <main>
        <section className="intro" aria-labelledby="page-title">
          <div>
            <p className="eyebrow">GOOD THINGS COME IN SMALL PARTS</p>
            <h1 id="page-title">A few good widgets.</h1>
          </div>
          <p className="intro-copy">
            Three colors. Endless possibilities.
            <br />
            Pick your favorites. We’ll take care of the math.
          </p>
        </section>

        <div className="shop-layout">
          <section
            className="collection"
            id="collection"
            aria-labelledby="collection-title"
          >
            <div className="section-heading">
              <h2 id="collection-title">Meet the collection</h2>
              <span>03 ESSENTIALS</span>
            </div>
            {catalogueError ? (
              <div className="catalogue-message error" role="alert">
                <h3>The collection couldn’t load.</h3>
                <p>Please check your connection and try again.</p>
                <button
                  className="primary-button"
                  onClick={() => {
                    setCatalogueError(false)
                    setCatalogueAttempt((attempt) => attempt + 1)
                  }}
                >
                  Retry catalogue <Icon name="arrow" />
                </button>
              </div>
            ) : !catalogue ? (
              <div className="catalogue-message" role="status">
                Loading the collection…
              </div>
            ) : (
              <>
                <div className="product-grid">
                  {catalogue.products.map((product) => (
                    <article
                      className={`product-card ${tone(product.code)}`}
                      key={product.code}
                    >
                      <div className="product-art">
                        <span className="product-code">{product.code}</span>
                        <WidgetIllustration code={product.code} />
                        <span className="art-caption">
                          THE EVERYDAY ESSENTIAL
                        </span>
                      </div>
                      <div className="product-details">
                        <div className="product-label">
                          <span className="color-dot" />{' '}
                          {tone(product.code).toUpperCase()} EDITION
                        </div>
                        <h3>{product.name}</h3>
                        <p className="product-price">
                          {formatMoney(product.unitPriceCents)}{' '}
                          <span>/ each</span>
                        </p>
                        <button
                          className="add-button"
                          disabled={count >= 1000}
                          onClick={() => changeQuantity(product.code, 1)}
                          aria-label={`Add ${product.name}`}
                        >
                          Add to basket <Icon name="plus" />
                        </button>
                      </div>
                    </article>
                  ))}
                </div>
                <aside className="offer-note">
                  <span className="offer-icon">
                    <Icon name="tag" />
                  </span>
                  <div>
                    <p className="eyebrow">A LITTLE MORE, FOR A LITTLE LESS</p>
                    <h3>Better together.</h3>
                    <p>{catalogue.offerDescription}</p>
                  </div>
                  <span className="offer-stamp" aria-hidden="true">
                    ½<br />
                    <small>PRICE</small>
                  </span>
                </aside>
                <div className="collection-note">
                  <Icon name="check" />
                  <span>
                    Simple prices. Automatic savings. No codes needed.
                  </span>
                </div>
              </>
            )}
          </section>

          <aside
            className="basket-panel"
            id="basket"
            aria-labelledby="basket-title"
          >
            <div className="basket-heading">
              <h2 id="basket-title">
                Your basket <span className="count-pill">{count}</span>
              </h2>
              <Icon name="bag" />
            </div>
            <div className="basket-contents">
              {count === 0 ? (
                <div className="empty-basket">
                  <span className="empty-icon">
                    <Icon name="bag" />
                  </span>
                  <h3>A little empty in here.</h3>
                  <p>
                    Add a widget or two.
                    <br />
                    Good things start small.
                  </p>
                </div>
              ) : (
                <ul className="basket-lines">
                  {items.map((item) => {
                    const product = catalogue!.products.find(
                      (product) => product.code === item.code,
                    )!
                    return (
                      <li className="basket-line" key={item.code}>
                        <div className={`line-art ${tone(item.code)}`}>
                          <WidgetIllustration code={item.code} />
                        </div>
                        <div className="line-info">
                          <h3>{product.name}</h3>
                          <p>{formatMoney(product.unitPriceCents)} each</p>
                          <div className="line-actions">
                            <div className="quantity-control">
                              <button
                                aria-label={`Decrease ${product.name} quantity`}
                                onClick={() => changeQuantity(item.code, -1)}
                              >
                                <Icon name="minus" />
                              </button>
                              <span aria-label={`${product.name} quantity`}>
                                {item.quantity}
                              </span>
                              <button
                                disabled={count >= 1000}
                                aria-label={`Increase ${product.name} quantity`}
                                onClick={() => changeQuantity(item.code, 1)}
                              >
                                <Icon name="plus" />
                              </button>
                            </div>
                            <button
                              className="remove-button"
                              aria-label={`Remove ${product.name}`}
                              onClick={() =>
                                changeQuantity(item.code, 'remove')
                              }
                            >
                              Remove
                            </button>
                          </div>
                        </div>
                        <span
                          className="line-subtotal"
                          aria-label={`${product.name} gross subtotal`}
                        >
                          {amount(
                            current?.items.find(
                              (line) => line.code === item.code,
                            )?.lineSubtotalCents,
                          )}
                        </span>
                      </li>
                    )
                  })}
                </ul>
              )}
            </div>
            {count >= 1000 && (
              <p className="limit-note" role="status">
                You’ve reached the 1,000-widget basket limit.
              </p>
            )}
            <div className="basket-summary" aria-busy={updating}>
              <dl>
                <div>
                  <dt>Subtotal</dt>
                  <dd>{amount(current?.subtotalCents)}</dd>
                </div>
                <div className="savings">
                  <dt>Offer savings</dt>
                  <dd>
                    {current ? `−${formatMoney(current.discountCents)}` : '—'}
                  </dd>
                </div>
                <div className="net-subtotal">
                  <dt>After savings</dt>
                  <dd>{amount(current?.discountedSubtotalCents)}</dd>
                </div>
                <div>
                  <dt>Delivery</dt>
                  <dd>
                    {current
                      ? current.deliveryCents === 0 && count > 0
                        ? 'Free'
                        : formatMoney(current.deliveryCents)
                      : '—'}
                  </dd>
                </div>
                <div className="total-row">
                  <dt>
                    Total <span>USD</span>
                  </dt>
                  <dd data-testid="basket-total">
                    {amount(current?.totalCents)}
                  </dd>
                </div>
              </dl>
              <div className="quote-status" role="status" aria-live="polite">
                {updating
                  ? 'Updating your quote…'
                  : current
                    ? count > 0
                      ? 'All offers applied. That’s your total.'
                      : 'Ready when you are.'
                    : quoteFailed
                      ? 'Quote unavailable.'
                      : 'Waiting for the collection.'}
              </div>
              {quoteFailed && (
                <div className="quote-error" role="alert">
                  <p>Your latest quote couldn’t load.</p>
                  <button
                    onClick={() => setQuoteAttempt((attempt) => attempt + 1)}
                  >
                    Retry quote <Icon name="arrow" />
                  </button>
                </div>
              )}
            </div>
            <p className="basket-footnote">
              Delivery is based on your subtotal after savings. An empty basket
              costs nothing.
            </p>
          </aside>
        </div>
      </main>
      <footer className="site-footer">
        <span>Small parts. Thoughtfully priced.</span>
        <span>
          ACME WIDGET CO. <span className="footer-divider">/</span> USD · Basket
          quotations
        </span>
      </footer>
    </>
  )
}

export default App
