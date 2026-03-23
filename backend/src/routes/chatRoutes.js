const express = require('express');
const router = express.Router();
const messageController = require('../controllers/messageController');
const { protect } = require('../middleware/authMiddleware');

router.get('/threads', protect, messageController.getChatThreads);
router.get('/messages/:otherId', protect, messageController.getMessages);
router.post('/send', protect, messageController.sendMessage);
router.get('/:otherId', protect, messageController.getMessages);
router.post('/', protect, messageController.sendMessage);

module.exports = router;
