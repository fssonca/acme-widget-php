import type { BasketItem } from './api'

export type Quantities = Record<string, number>

// Matches the API's per-product limit.
export const MAX_QUANTITY = 99

export function changeQuantity(
  quantities: Quantities,
  code: string,
  delta: number,
): Quantities {
  const quantity = Math.min((quantities[code] ?? 0) + delta, MAX_QUANTITY)
  const next = { ...quantities, [code]: quantity }
  if (quantity <= 0) delete next[code]
  return next
}

export const toItems = (quantities: Quantities): BasketItem[] =>
  Object.entries(quantities).map(([code, quantity]) => ({ code, quantity }))

export const countUnits = (quantities: Quantities) =>
  Object.values(quantities).reduce((sum, quantity) => sum + quantity, 0)
