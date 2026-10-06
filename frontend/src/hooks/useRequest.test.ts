import { act, renderHook, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { useRequest } from './useRequest'

function deferred<T>() {
  let resolve!: (value: T) => void
  const promise = new Promise<T>((done) => (resolve = done))
  return { promise, resolve }
}

describe('useRequest', () => {
  it('ignores a slow response that arrives after a newer one', async () => {
    const slow = deferred<string>()
    const fast = deferred<string>()
    const { result, rerender } = renderHook(
      ({ key, request }) => useRequest(key, () => request.promise),
      { initialProps: { key: 'one red', request: slow } },
    )

    rerender({ key: 'two reds', request: fast })
    await act(async () => fast.resolve('quote for two reds'))
    await act(async () => slow.resolve('quote for one red'))

    expect(result.current.data).toBe('quote for two reds')
    expect(result.current.loading).toBe(false)
  })

  it('reports failures and loads again on retry', async () => {
    let calls = 0
    const { result } = renderHook(() =>
      useRequest('catalogue', () =>
        ++calls === 1
          ? Promise.reject(new Error('offline'))
          : Promise.resolve('ok'),
      ),
    )

    await waitFor(() => expect(result.current.failed).toBe(true))
    act(() => result.current.retry())

    await waitFor(() => expect(result.current.data).toBe('ok'))
    expect(result.current.failed).toBe(false)
  })

  it('does nothing while the key is null', () => {
    let calls = 0
    const { result } = renderHook(() => useRequest(null, async () => ++calls))

    expect(calls).toBe(0)
    expect(result.current.loading).toBe(false)
  })
})
