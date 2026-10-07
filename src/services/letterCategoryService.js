const CATEGORIES_KEY = 'alpha_letter_categories';

// Initialize
const init = () => {
  if (!localStorage.getItem(CATEGORIES_KEY)) {
    localStorage.setItem(CATEGORIES_KEY, JSON.stringify([
        { id: 'cat-general', name: 'General', description: 'General purpose letters', created_at: new Date().toISOString() },
        { id: 'cat-official', name: 'Official', description: 'Official company correspondence', created_at: new Date().toISOString() }
    ]));
  }
};

const getCategories = () => {
    init();
    return JSON.parse(localStorage.getItem(CATEGORIES_KEY));
};

const saveCategories = (data) => {
    localStorage.setItem(CATEGORIES_KEY, JSON.stringify(data));
};

export const getAllCategories = async () => {
    return getCategories();
};

export const getCategoryById = async (id) => {
    return getCategories().find(c => c.id === id);
};

export const createCategory = async (name, description) => {
    const categories = getCategories();
    if (categories.some(c => c.name.toLowerCase() === name.toLowerCase())) {
        throw new Error("Category with this name already exists");
    }
    
    const newCat = {
        id: `cat-${Date.now()}`,
        name,
        description,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };
    
    categories.push(newCat);
    saveCategories(categories);
    return newCat;
};

export const updateCategory = async (id, name, description) => {
    const categories = getCategories();
    const index = categories.findIndex(c => c.id === id);
    if (index === -1) throw new Error("Category not found");
    
    if (categories.some(c => c.id !== id && c.name.toLowerCase() === name.toLowerCase())) {
        throw new Error("Category name exists");
    }
    
    categories[index] = { ...categories[index], name, description, updated_at: new Date().toISOString() };
    saveCategories(categories);
    return categories[index];
};

export const deleteCategory = async (id) => {
    // Check if templates exist for this category (mock check for now, ideally check template service)
    const templates = JSON.parse(localStorage.getItem('alpha_letter_templates') || '[]');
    if (templates.some(t => t.category_id === id)) {
        throw new Error("Cannot delete category containing templates");
    }
    
    const categories = getCategories().filter(c => c.id !== id);
    saveCategories(categories);
    return true;
};