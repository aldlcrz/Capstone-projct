const User = require('../models/User');
const Address = require('../models/Address');
const socketUtility = require('../utils/socketUtility');

exports.getProfile = async (req, res) => {
    try {
        const user = await User.findByPk(req.user.id, {
            attributes: { exclude: ['password'] }
        });
        res.json({ user });
    } catch (err) {
        console.error('getProfile Error:', err);
        res.status(500).json({ message: 'Error fetching profile', error: err.message });
    }
};

exports.updateProfile = async (req, res) => {
    try {
        const { name, mobileNumber, gcashNumber, profilePhoto, facebookLink, instagramLink } = req.body;
        const user = await User.findByPk(req.user.id);
        if (!user) return res.status(404).json({ message: 'User not found' });
        
        const updateData = {};
        if (name) updateData.name = name;
        if (mobileNumber !== undefined) updateData.mobileNumber = mobileNumber;
        if (gcashNumber !== undefined) updateData.gcashNumber = gcashNumber;
        if (profilePhoto !== undefined) updateData.profilePhoto = profilePhoto;
        if (facebookLink !== undefined) updateData.facebookLink = facebookLink;
        if (instagramLink !== undefined) updateData.instagramLink = instagramLink;

        await user.update(updateData);

        const updatedUser = await User.findByPk(req.user.id, {
            attributes: { exclude: ['password'] }
        });

        res.json({ message: 'Profile updated successfully', user: updatedUser });
    } catch (err) {
        console.error('updateProfile Error:', err);
        res.status(500).json({ message: 'Error updating profile', error: err.message });
    }
};

exports.getAddresses = async (req, res) => {
    try {
        const addresses = await Address.findAll({
            where: { UserId: req.user.id },
            order: [['isDefault', 'DESC'], ['createdAt', 'DESC']]
        });
        res.json(addresses);
    } catch (err) {
        console.error('getAddresses Error:', { userId: req.user?.id, error: err });
        res.status(500).json({ message: 'Error fetching addresses', error: err.message });
    }
};

exports.createAddress = async (req, res) => {
    try {
        const { fullName, phoneNumber, street, barangay, city, province, postalCode, label, isDefault } = req.body;

        if (isDefault) {
            await Address.update({ isDefault: false }, { where: { UserId: req.user.id } });
        }

        const address = await Address.create({
            UserId: req.user.id,
            fullName,
            phoneNumber,
            street,
            barangay,
            city,
            province,
            postalCode,
            label,
            isDefault: isDefault || false
        });

        socketUtility.emitDashboardUpdate();

        res.json(address);
    } catch (err) {
        console.error('createAddress Error:', err);
        res.status(500).json({ message: 'Error creating address', error: err.message });
    }
};

exports.updateAddress = async (req, res) => {
    try {
        const { id } = req.params;
        const { fullName, phoneNumber, street, barangay, city, province, postalCode, label, isDefault } = req.body;

        const address = await Address.findOne({ where: { id, UserId: req.user.id } });
        if (!address) return res.status(404).json({ message: 'Address not found' });

        if (isDefault) {
            await Address.update({ isDefault: false }, { where: { UserId: req.user.id } });
        }

        await address.update({
            fullName,
            phoneNumber,
            street,
            barangay,
            city,
            province,
            postalCode,
            label,
            isDefault
        });

        socketUtility.emitDashboardUpdate();

        res.json(address);
    } catch (err) {
        console.error('updateAddress Error:', err);
        res.status(500).json({ message: 'Error updating address', error: err.message });
    }
};

exports.setDefaultAddress = async (req, res) => {
    try {
        const { id } = req.params;
        await Address.update({ isDefault: false }, { where: { UserId: req.user.id } });
        const address = await Address.findOne({ where: { id, UserId: req.user.id } });
        if (!address) return res.status(404).json({ message: 'Address not found' });

        await address.update({ isDefault: true });
        res.json({ message: 'Default address updated' });
    } catch (err) {
        console.error('setDefaultAddress Error:', err);
        res.status(500).json({ message: 'Error setting default address', error: err.message });
    }
};

exports.deleteAddress = async (req, res) => {
    try {
        const { id } = req.params;
        await Address.destroy({ where: { id, UserId: req.user.id } });

        socketUtility.emitDashboardUpdate();

        res.json({ message: 'Address removed successfully' });
    } catch (err) {
        console.error('deleteAddress Error:', err);
        res.status(500).json({ message: 'Error deleting address', error: err.message });
    }
};

exports.updateFcmToken = async (req, res) => {
    try {
        const { fcmToken } = req.body;
        const user = await User.findByPk(req.user.id);
        if (!user) return res.status(404).json({ message: 'User not found' });

        await user.update({ fcmToken });
        res.json({ message: 'Push token updated successfully' });
    } catch (err) {
        console.error('updateFcmToken Error:', err);
        res.status(500).json({ message: 'Error updating push token', error: err.message });
    }
};

const bcrypt = require('bcryptjs');

exports.changePassword = async (req, res) => {
    try {
        const { oldPassword, newPassword } = req.body;

        if (!oldPassword || !newPassword) {
            return res.status(400).json({ message: 'Old password and new password are required' });
        }

        if (newPassword.length < 6 || newPassword.length > 32) {
            return res.status(400).json({ message: 'New password must be between 6 and 32 characters long' });
        }

        const user = await User.findByPk(req.user.id);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        // Verify old password
        const isMatch = await bcrypt.compare(oldPassword, user.password);
        if (!isMatch) {
            return res.status(400).json({ message: 'Incorrect old password' });
        }

        // Hash new password
        user.password = await bcrypt.hash(newPassword, 10);
        user.passwordChangedAt = new Date();
        await user.save();

        res.json({ message: 'Password changed successfully' });
    } catch (err) {
        console.error('changePassword Error:', err);
        res.status(500).json({ message: 'Error changing password', error: err.message });
    }
};

exports.getSellerInfo = async (req, res) => {
    try {
        const { id } = req.params;
        const seller = await User.findByPk(id, {
            attributes: ['id', 'name', 'createdAt', 'role', 'facebookLink', 'instagramLink']
        });
        
        if (!seller || seller.role !== 'seller') {
            return res.status(404).json({ message: 'Seller not found or user is not a seller' });
        }

        const monthsJoined = seller.createdAt 
            ? Math.floor((new Date() - new Date(seller.createdAt)) / (1000 * 60 * 60 * 24 * 30)) 
            : 12;

        res.json({
            id: seller.id,
            shopName: seller.name || "Lumban Artisan",
            location: "Lumban, Laguna",
            rating: 4.9,
            joined: (monthsJoined < 1 ? "Just Joined" : `${monthsJoined} Months Ago`),
            responseRate: "98%",
            description: "A legacy of fine hand-embroidery passed down through generations. Our workshop specializes in traditional Pina and Jusi Barongs with modern silhouettes.",
            facebookLink: seller.facebookLink,
            instagramLink: seller.instagramLink
        });
    } catch (err) {
        console.error('getSellerInfo Error:', err);
        res.status(500).json({ message: 'Error fetching seller info', error: err.message });
    }
};

exports.toggleFollow = async (req, res) => {
    try {
        const { id } = req.params;
        const customerId = req.user.id;
        
        if (id === customerId) return res.status(400).json({ message: "You cannot follow yourself" });

        const seller = await User.findByPk(id);
        const customer = await User.findByPk(customerId);

        if (!seller || seller.role !== 'seller') {
            return res.status(404).json({ message: 'Seller not found' });
        }

        let sellerFollowers = seller.followers ? seller.followers : [];
        if (typeof sellerFollowers === 'string') sellerFollowers = JSON.parse(sellerFollowers);
        let customerFollowing = customer.following ? customer.following : [];
        if (typeof customerFollowing === 'string') customerFollowing = JSON.parse(customerFollowing);

        const isFollowing = sellerFollowers.includes(customerId);

        if (isFollowing) {
            sellerFollowers = sellerFollowers.filter(uid => uid !== customerId);
            customerFollowing = customerFollowing.filter(uid => uid !== id);
        } else {
            sellerFollowers.push(customerId);
            customerFollowing.push(id);
        }

        seller.followers = sellerFollowers;
        customer.following = customerFollowing;

        await seller.save();
        await customer.save();

        res.json({ isFollowing: !isFollowing });
    } catch (err) {
        console.error('toggleFollow Error:', err);
        res.status(500).json({ message: 'Error toggling follow status', error: err.message });
    }
};
