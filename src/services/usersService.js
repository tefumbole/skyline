import * as userService from './userService';

// Re-export functionality to ensure compatibility with components using the plural filename
export const getUsers = userService.getAllUsers;
export const getUserById = userService.getUserById;
export const createUser = userService.createUser;
export const updateUser = userService.updateUser;
export const deleteUser = userService.deleteUser;