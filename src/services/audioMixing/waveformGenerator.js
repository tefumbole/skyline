export const generateWaveform = async (audioFile) => {
  return new Array(100).fill(0).map(() => Math.random());
};