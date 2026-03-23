const Address = require('../models/Address');

exports.getAddresses = async (req, res) => {
  try {
    const addresses = await Address.findAll({
      where: { userId: req.user.id },
      order: [['isDefault', 'DESC'], ['createdAt', 'DESC']]
    });
    res.json(addresses);
  } catch (error) {
    res.status(500).json({ message: 'Failed to fetch addresses' });
  }
};

exports.createAddress = async (req, res) => {
  try {
    const { 
      recipientName, phone, houseNo, street, barangay, city, province, postalCode, isDefault 
    } = req.body;

    // Strict Integrity Guards
    if (!recipientName || !phone || !barangay || !city || !province) {
      return res.status(400).json({ message: 'Essential registry fields are missing' });
    }

    if (recipientName.length > 50) return res.status(400).json({ message: 'Recipient name too long' });
    
    const phoneDigits = phone.replace(/\D/g, "");
    if (!/^09\d{9}$/.test(phoneDigits)) {
      return res.status(400).json({ message: 'Invalid 11-digit Philippine mobile number' });
    }

    if (postalCode && !/^\d{4}$/.test(postalCode.replace(/\D/g, ""))) {
      return res.status(400).json({ message: 'Postal code must be 4 digits' });
    }

    if (isDefault) {
      await Address.update({ isDefault: false }, { where: { userId: req.user.id } });
    }

    const address = await Address.create({
      recipientName,
      phone: phoneDigits,
      houseNo: houseNo || null,
      street: street || null,
      barangay,
      city,
      province,
      postalCode: postalCode ? postalCode.replace(/\D/g, "") : null,
      userId: req.user.id,
      latitude: req.body.latitude || null,
      longitude: req.body.longitude || null,
      isDefault: isDefault || false
    });

    res.status(201).json(address);
  } catch (error) {
    res.status(400).json({ message: error.message });
  }
};

exports.updateAddress = async (req, res) => {
  try {
    const address = await Address.findOne({
      where: { id: req.params.id, userId: req.user.id }
    });

    if (!address) return res.status(404).json({ message: 'Address not found' });

    const { 
      recipientName, phone, houseNo, street, barangay, city, province, postalCode, isDefault 
    } = req.body;

    if (recipientName && recipientName.length > 50) return res.status(400).json({ message: 'Recipient name too long' });
    
    if (phone) {
      const phoneDigits = phone.replace(/\D/g, "");
      if (!/^09\d{9}$/.test(phoneDigits)) {
        return res.status(400).json({ message: 'Invalid 11-digit Philippine mobile number' });
      }
      req.body.phone = phoneDigits;
    }

    if (postalCode) {
      const pcDigits = postalCode.replace(/\D/g, "");
      if (!/^\d{4}$/.test(pcDigits)) {
        return res.status(400).json({ message: 'Postal code must be 4 digits' });
      }
      req.body.postalCode = pcDigits;
    }

    if (isDefault && !address.isDefault) {
      await Address.update({ isDefault: false }, { where: { userId: req.user.id } });
    }

    await address.update(req.body);
    res.json(address);
  } catch (error) {
    res.status(400).json({ message: error.message });
  }
};

exports.deleteAddress = async (req, res) => {
  try {
    const address = await Address.findOne({
      where: { id: req.params.id, userId: req.user.id }
    });

    if (!address) return res.status(404).json({ message: 'Address not found' });

    await address.destroy();
    res.json({ message: 'Address deleted' });
  } catch (error) {
    res.status(500).json({ message: 'Failed to delete address' });
  }
};

exports.setDefaultAddress = async (req, res) => {
  try {
    await Address.update({ isDefault: false }, { where: { userId: req.user.id } });
    const address = await Address.update({ isDefault: true }, {
      where: { id: req.params.id, userId: req.user.id }
    });
    res.json({ message: 'Default address updated' });
  } catch (error) {
    res.status(500).json({ message: 'Failed to update default address' });
  }
};
