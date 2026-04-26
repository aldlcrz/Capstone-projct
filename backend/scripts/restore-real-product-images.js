const { Product, sequelize } = require('../src/models');

const photoPool = [
  'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1617692855027-33b14f061079?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1611312449412-6cefac5dc3e4?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1618886487325-f665032b6351?auto=format&fit=crop&w=1200&q=80',
  'https://images.unsplash.com/photo-1585487000160-6ebcfceb0d03?auto=format&fit=crop&w=1200&q=80',
];

const byName = {
  'Barong with Calado': [
    'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1200&q=80',
  ],
  'Colored Barong': [
    'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=1200&q=80',
  ],
  'Polo Barong': [
    'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1611312449412-6cefac5dc3e4?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1618886487325-f665032b6351?auto=format&fit=crop&w=1200&q=80',
  ],
  'Short-Sleeve Barong': [
    'https://images.unsplash.com/photo-1617692855027-33b14f061079?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1585487000160-6ebcfceb0d03?auto=format&fit=crop&w=1200&q=80',
  ],
  'Wedding Barong': [
    'https://images.unsplash.com/photo-1520854221256-17451cc331bf?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80',
    'https://images.unsplash.com/photo-1606800052052-a08af7148866?auto=format&fit=crop&w=1200&q=80',
  ],
};

function buildImages(urls) {
  return urls.slice(0, 3).map((url, idx) => ({
    url,
    variation: `Variation ${idx + 1}`,
  }));
}

async function run() {
  const products = await Product.findAll({ order: [['createdAt', 'ASC']] });
  let updated = 0;
  let poolIdx = 0;

  for (const product of products) {
    const chosen = byName[product.name] || [
      photoPool[poolIdx % photoPool.length],
      photoPool[(poolIdx + 1) % photoPool.length],
      photoPool[(poolIdx + 2) % photoPool.length],
    ];

    poolIdx += 3;

    await product.update({ image: buildImages(chosen) });
    updated += 1;
    console.log(`Updated ${product.name}`);
  }

  console.log(`Done. Updated ${updated} products with real photo URLs.`);
}

run()
  .catch((err) => {
    console.error('Restore failed:', err);
    process.exitCode = 1;
  })
  .finally(async () => {
    await sequelize.close();
  });
