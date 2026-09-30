export function takeNextTrack(queue) {
  const [next, ...remaining] = queue
  return { next, remaining }
  }
