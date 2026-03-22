const { Category } = require('../models/Product');

const defaultCategories = [
    { name: 'Barong Tagalog', description: 'Traditional Filipino formal wear for men.' },
    { name: 'Filipiniana Dresses', description: 'Elegant traditional dresses for women.' },
    { name: 'Accessories', description: 'Traditional Filipino jewelry, fans, and more.' },
    { name: 'Others', description: 'Miscellaneous heritage crafts.' }
];

const initCategories = async () => {
    try {
        for (const cat of defaultCategories) {
            await Category.findOrCreate({
                where: { name: cat.name },
                defaults: cat
            });
        }
        console.log('Default categories initialized successfully.');
    } catch (error) {
        console.error('Error initializing categories:', error);
    }
};

module.exports = initCategories;
