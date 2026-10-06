import { useId } from 'react'

export function WidgetIllustration({ code }: { code: string }) {
  const id = useId()
  const color =
    code === 'R01' ? '#c44a3d' : code === 'G01' ? '#50846c' : '#5185bb'
  const dark =
    code === 'R01' ? '#833023' : code === 'G01' ? '#2c5445' : '#2c527e'

  return (
    <svg viewBox="0 0 260 210" fill="none" aria-hidden="true">
      <defs>
        <linearGradient
          id={id}
          x1="90"
          y1="50"
          x2="185"
          y2="172"
          gradientUnits="userSpaceOnUse"
        >
          <stop stopColor={color} />
          <stop offset="1" stopColor={dark} />
        </linearGradient>
      </defs>
      <ellipse cx="135" cy="181" rx="72" ry="10" fill={dark} opacity=".13" />
      <path
        d="m58 90 66-38a16 16 0 0 1 16 0l64 37a16 16 0 0 1 8 14v40a16 16 0 0 1-8 14l-66 38a16 16 0 0 1-16 0l-64-37a16 16 0 0 1-8-14v-40a16 16 0 0 1 8-14Z"
        fill={`url(#${id})`}
      />
      <path
        d="m58 90 66-38a16 16 0 0 1 16 0l64 37c11 6 11 15 0 21l-66 38a16 16 0 0 1-16 0l-64-37c-11-6-11-15 0-21Z"
        fill={color}
      />
      <path d="m130 148 0 45" stroke="white" opacity=".12" strokeWidth="2" />
      <ellipse cx="131" cy="96" rx="39" ry="22" fill={dark} />
      <ellipse cx="131" cy="99" rx="27" ry="15" fill="#172b30" opacity=".8" />
      <path
        d="M92 96v-35c0-13 17-23 39-23s39 10 39 23v35c0 13-17 23-39 23s-39-10-39-23Z"
        fill={`url(#${id})`}
      />
      <ellipse
        cx="131"
        cy="61"
        rx="39"
        ry="23"
        fill={color}
        stroke="white"
        strokeOpacity=".2"
      />
      <ellipse cx="131" cy="61" rx="21" ry="12" fill={dark} />
      <path
        d="M110 61c0-7 9-12 21-12s21 5 21 12v6c-6-5-13-7-21-7s-15 2-21 7Z"
        fill="#172b30"
        opacity=".85"
      />
      <ellipse cx="78" cy="100" rx="6" ry="3.5" fill={dark} />
      <ellipse cx="184" cy="100" rx="6" ry="3.5" fill={dark} />
      <ellipse cx="131" cy="132" rx="6" ry="3.5" fill={dark} />
      <path
        d="m65 125 40 23M65 136l25 14"
        stroke="white"
        strokeOpacity=".16"
        strokeWidth="2"
        strokeLinecap="round"
      />
    </svg>
  )
}
