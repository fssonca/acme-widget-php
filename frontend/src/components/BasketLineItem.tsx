import { accentStyle } from '../accents'
import { formatMoney, type Product } from '../api'
import { MAX_QUANTITY } from '../basket'
import { Icon } from './Icon'
import { WidgetIllustration } from './WidgetIllustration'

interface Props {
  product: Product
  quantity: number
  lineSubtotalCents?: number
  onChange: (delta: number) => void
}

export function BasketLineItem({
  product,
  quantity,
  lineSubtotalCents,
  onChange,
}: Props) {
  return (
    <li className="basket-line">
      <div className="line-art" style={accentStyle(product.code)}>
        <WidgetIllustration code={product.code} />
      </div>
      <div className="line-info">
        <h3>{product.name}</h3>
        <p>{formatMoney(product.unitPriceCents)} each</p>
        <div className="line-actions">
          <div className="quantity-control">
            <button
              aria-label={`Remove one ${product.name}`}
              onClick={() => onChange(-1)}
            >
              <Icon name="minus" />
            </button>
            <span aria-label={`${product.name} quantity`}>{quantity}</span>
            <button
              aria-label={`Add one ${product.name}`}
              disabled={quantity >= MAX_QUANTITY}
              onClick={() => onChange(1)}
            >
              <Icon name="plus" />
            </button>
          </div>
          <button className="link-button" onClick={() => onChange(-quantity)}>
            Remove
          </button>
        </div>
      </div>
      <span className="line-subtotal">
        {lineSubtotalCents === undefined ? '—' : formatMoney(lineSubtotalCents)}
      </span>
    </li>
  )
}
