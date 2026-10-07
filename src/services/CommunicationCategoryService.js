// Mock Service for Communication Categories (localStorage based)

const COMMS_CATEGORIES_KEY = 'ab_communication_categories';

// Seed initial data
const seedData = [
  { id: 'cat-alert', name: 'Alerts', description: 'Urgent system alerts', color: '#ef4444', created_at: new Date().toISOString() },
  { id: 'cat-info', name: 'Information', description: 'General information updates', color: '#3b82f6', created_at: new Date().toISOString() },
  { id: 'cat-update', name: 'Updates', description: 'Project status updates', color: '#22c55e', created_at: new Date().toISOString() }
];

const getLocalCategories = () => {
    const data = localStorage.getItem(COMMS_CATEGORIES_KEY);
    if (!data) {
        localStorage.setItem(COMMS_CATEGORIES_KEY, JSON.stringify(seedData));
        return seedData;
    }
    return JSON.parse(data);
};

const saveLocalCategories = (data) => {
    localStorage.setItem(COMMS_CATEGORIES_KEY, JSON.stringify(data));
};

export const getAllCategories = async () => {
    await new Promise(resolve => setTimeout(resolve, 300));
    return getLocalCategories();
};

export const getCategoryById = async (id) => {
    await new Promise(resolve => setTimeout(resolve, 200));
    return getLocalCategories().find(c => c.id === id);
};

export const createCategory = async (data) => {
    await new Promise(resolve => setTimeout(resolve, 400));
    const categories = getLocalCategories();
    const newCategory = {
        id: `cat-${Date.now()}`,
        ...data,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };
    categories.push(newCategory);
    saveLocalCategories(categories);
    return newCategory;
};

export const updateCategory = async (id, data) => {
    await new Promise(resolve => setTimeout(resolve, 400));
    const categories = getLocalCategories();
    const index = categories.findIndex(c => c.id === id);
    if (index === -1) throw new Error('Category not found');
    
    categories[index] = { 
        ...categories[index], 
        ...data,
        updated_at: new Date().toISOString()
    };
    saveLocalCategories(categories);
    return categories[index];
};

export const deleteCategory = async (id) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    let categories = getLocalCategories();
    categories = categories.filter(c => c.id !== id);
    saveLocalCategories(categories);
    return true;
};