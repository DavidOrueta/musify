import assert from 'node:assert/strict'
import test from 'node:test'
import { takeNextTrack } from '../src/playerQueue.js'

test('returns no track for an empty queue', () => {
  assert.deepEqual(takeNextTrack([]), { next: undefined, remaining: [] })
})

test('takes tracks in FIFO order and preserves the rest', () => {
  const firstTrack = { id: 1, title: 'First' }
  const secondTrack = { id: 2, title: 'Second' }
  const queue = [firstTrack, secondTrack]

  const result = takeNextTrack(queue)

  assert.equal(result.next, firstTrack)
  assert.deepEqual(result.remaining, [secondTrack])
  assert.deepEqual(queue, [firstTrack, secondTrack])
})
