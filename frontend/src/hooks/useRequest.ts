import { useEffect, useEffectEvent, useState } from 'react'

interface Result<T> {
  key: string
  data?: T
  failed?: boolean
}

// Loads data for `key` (null skips loading). A new key or retry aborts the previous request, so slow responses never overwrite newer ones.
export function useRequest<T>(
  key: string | null,
  load: (signal: AbortSignal) => Promise<T>,
) {
  const [attempt, setAttempt] = useState(0)
  const [result, setResult] = useState<Result<T>>({ key: '' })
  const requestKey = key === null ? null : `${key}#${attempt}`
  const loadLatest = useEffectEvent(load) // Only `key` decides when to reload.

  useEffect(() => {
    if (requestKey === null) return
    const controller = new AbortController()
    loadLatest(controller.signal).then(
      (data) =>
        !controller.signal.aborted && setResult({ key: requestKey, data }),
      () =>
        !controller.signal.aborted &&
        setResult({ key: requestKey, failed: true }),
    )
    return () => controller.abort()
  }, [requestKey])

  const settled = result.key === requestKey
  return {
    data: result.data, // The last successful response; it may be stale while `loading`.
    loading: requestKey !== null && !settled,
    failed: settled && result.failed === true,
    retry: () => setAttempt((count) => count + 1),
  }
}
