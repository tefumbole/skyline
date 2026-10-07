export const exportSettings = (template, format = 'json') => {
  return JSON.stringify({ template, exportedAt: new Date() });
};