type IconName = 'bag' | 'plus' | 'minus' | 'arrow' | 'tag' | 'check'
const paths: Record<IconName, string> = {
  bag: 'M5 7h14l1 14H4L5 7Zm3 0V5a4 4 0 0 1 8 0v2',
  plus: 'M12 5v14M5 12h14',
  minus: 'M5 12h14',
  arrow: 'M4 12h16m-6-6 6 6-6 6',
  tag: 'M3 3h8l10 10-8 8L3 11V3Zm4 4h.01',
  check: 'm5 12 4 4L19 6',
}

export function Icon({ name }: { name: IconName }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.7"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d={paths[name]} />
    </svg>
  )
}
