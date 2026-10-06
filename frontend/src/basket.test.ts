import { describe, expect, it } from 'vitest'
import { changeQuantity, countUnits, MAX_QUANTITY, toItems } from './basket'

describe('basket quantities', () => {
  it('adds and removes units without mutating the previous state', () => {
    const before = { R01: 1 }
    const after = changeQuantity(before, 'R01', 1)

    expect(after).toEqual({ R01: 2 })
    expect(before).toEqual({ R01: 1 })
  })

  it('drops a product when its quantity reaches zero', () => {
    expect(changeQuantity({ R01: 2, B01: 1 }, 'R01', -2)).toEqual({ B01: 1 })
  })

  it('caps each product at the API limit', () => {
    expect(changeQuantity({ R01: MAX_QUANTITY }, 'R01', 1)).toEqual({
      R01: MAX_QUANTITY,
    })
  })

  it('converts quantities to API items and counts units', () => {
    const quantities = { R01: 2, B01: 3 }

    expect(toItems(quantities)).toEqual([
      { code: 'R01', quantity: 2 },
      { code: 'B01', quantity: 3 },
    ])
    expect(countUnits(quantities)).toBe(5)
  })
})
