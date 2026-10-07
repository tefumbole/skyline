// Service for TimeSheet Categories Management (mocked local storage)

const CATEGORIES_KEY = 'ab_timesheet_categories';

const getCategories = () => JSON.parse(localStorage.getItem(CATEGORIES_KEY) || '[]');
const saveCategories = (data) => localStorage.setItem(CATEGORIES_KEY, JSON.stringify(data));

// Initial categories seed
if (!localStorage.getItem(CATEGORIES_KEY)) {
    saveCategories([
        { id: 'cat-1', name: 'Development', description: 'Coding and engineering tasks', color: '#3b82f6', created_at: new Date().toISOString() },
        { id: 'cat-2', name: 'Meetings', description: 'Internal and external meetings', color: '#eab308', created_at: new Date().toISOString() },
        { id: 'cat-3', name: 'Administrative', description: 'HR, Finance, and Ops', color: '#a855f7', created_at: new Date().toISOString() }
    ]);
}

export const getAllCategories = async () => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return getCategories();
};

export const getCategoryById = async (id) => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return getCategories().find(c => c.id === id);
};

export const createCategory = async (data) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const current = getCategories();
    const newCat = {
        id: `cat-${Date.now()}`,
        ...data,
        created_at: new Date().toISOString()
    };
    current.push(newCat);
    saveCategories(current);
    return newCat;
};

export const updateCategory = async (id, data) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const current = getCategories();
    const index = current.findIndex(c => c.id === id);
    if (index === -1) throw new Error("Category not found");
    current[index] = { ...current[index], ...data };
    saveCategories(current);
    return current[index];
};

export const deleteCategory = async (id) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    let current = getCategories();
    current = current.filter(c => c.id !== id);
    saveCategories(current);
    return true;
};