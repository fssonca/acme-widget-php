import type { CSSProperties } from 'react'

export interface Accent {
  base: string
  dark: string
  text: string
  wash: string
}

// Display colours are presentation, so they live here rather than in the API; new products get the neutral accent.
const accents: Record<string, Accent> = {
  R01: { base: '#c44a3d', dark: '#833023', text: '#aa3b31', wash: '#f0e2d9' },
  G01: { base: '#50846c', dark: '#2c5445', text: '#3f6b55', wash: '#e2e8dc' },
  B01: { base: '#5185bb', dark: '#2c527e', text: '#366a98', wash: '#dde7ed' },
}
const neutral: Accent = {
  base: '#8a8f86',
  dark: '#55594f',
  text: '#55594f',
  wash: '#ebebe6',
}

export const accentFor = (code: string) => accents[code] ?? neutral

export const accentStyle = (code: string) =>
  ({
    '--accent': accentFor(code).text,
    '--wash': accentFor(code).wash,
  }) as CSSProperties
