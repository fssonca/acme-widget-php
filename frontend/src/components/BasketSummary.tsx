import { formatMoney, type Quote } from '../api'
import { Icon } from './Icon'

interface Props {
  quote?: Quote
  loading: boolean
  failed: boolean
  onRetry: () => void
}

export function BasketSummary({ quote, loading, failed, onRetry }: Props) {
  // A failed quote hides amounts; a loading one keeps the previous amounts, dimmed.
  const show = (cents: (quote: Quote) => number, prefix = '') =>
    quote && !failed ? prefix + formatMoney(cents(quote)) : '—'

  return (
    <div className="basket-summary" aria-busy={loading} data-loading={loading}>
      <dl>
        <div>
          <dt>Subtotal</dt>
          <dd>{show((q) => q.subtotalCents)}</dd>
        </div>
        <div className="savings">
          <dt>Offer savings</dt>
          <dd>{show((q) => q.discountCents, '−')}</dd>
        </div>
        <div>
          <dt>Delivery</dt>
          <dd>
            {quote?.deliveryCents === 0 && quote.subtotalCents > 0 && !failed
              ? 'Free'
              : show((q) => q.deliveryCents)}
          </dd>
        </div>
        <div className="total-row">
          <dt>Total</dt>
          <dd data-testid="basket-total">{show((q) => q.totalCents)}</dd>
        </div>
      </dl>
      {failed && (
        <div className="quote-error" role="alert">
          <p>We couldn’t price your basket.</p>
          <button onClick={onRetry}>
            Try again <Icon name="arrow" />
          </button>
        </div>
      )}
      <p className="footnote">
        Delivery is based on your subtotal after savings.
      </p>
    </div>
  )
}
