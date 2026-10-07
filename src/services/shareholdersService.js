// DEPRECATED: This file has been replaced by src/services/shareholderService.js
// Please update all imports to use the singular 'shareholderService.js'
// This file is kept temporarily empty to prevent immediate build crashes if imports persist.
export const getAllShareholders = async () => [];
export const getShareholderByReference = async () => null;
export const deleteShareholder = async () => false;
export const saveShareholderRegistration = async () => ({ error: "Service deprecated" });
export const getShareStats = async () => ({ total: 0, assigned: 0, remaining: 0 });