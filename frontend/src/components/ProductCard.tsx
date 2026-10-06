import { accentStyle } from '../accents'
import { formatMoney, type Product } from '../api'
import { Icon } from './Icon'
import { WidgetIllustration } from './WidgetIllustration'

interface Props {
  product: Product
  canAdd: boolean
  onAdd: () => void
}

export function ProductCard({ product, canAdd, onAdd }: Props) {
  return (
    <article className="product-card" style={accentStyle(product.code)}>
      <div className="product-art">
        <span className="product-code">{product.code}</span>
        <WidgetIllustration code={product.code} />
      </div>
      <div className="product-details">
        <h3>{product.name}</h3>
        <p className="product-price">
          {formatMoney(product.unitPriceCents)} <span>/ each</span>
        </p>
        <button
          className="primary-button"
          disabled={!canAdd}
          onClick={onAdd}
          aria-label={`Add ${product.name}`}
        >
          Add to basket <Icon name="plus" />
        </button>
      </div>
    </article>
  )
}
