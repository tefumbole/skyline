export const detectInstrumentFromFilename = (filename) => {
  return {
    topMatch: 'Vocals',
    confidence: 85,
    alternatives: ['Backing Vocals', 'Lead Vocal']
  };
};