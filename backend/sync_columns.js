const { User, sequelize } = require('./backend/src/models');
const { DataTypes } = require('sequelize');

async function syncNewColumns() {
  try {
    const queryInterface = sequelize.getQueryInterface();
    const tableInfo = await queryInterface.describeTable('Users');
    
    if (!tableInfo.resetPasswordCode) {
      console.log('Adding resetPasswordCode column...');
      await queryInterface.addColumn('Users', 'resetPasswordCode', {
        type: DataTypes.STRING,
        allowNull: true,
      });
    }
    
    if (!tableInfo.resetPasswordExpires) {
      console.log('Adding resetPasswordExpires column...');
      await queryInterface.addColumn('Users', 'resetPasswordExpires', {
        type: DataTypes.DATE,
        allowNull: true,
      });
    }
    
    console.log('✅ Database columns synchronized successfully.');
    process.exit(0);
  } catch (error) {
    console.error('❌ Error synchronizing columns:', error);
    process.exit(1);
  }
}

syncNewColumns();
