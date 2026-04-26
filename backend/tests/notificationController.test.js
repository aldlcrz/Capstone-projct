jest.mock('../src/models', () => ({
  Notification: {
    findAll: jest.fn(),
    update: jest.fn(),
    count: jest.fn(),
    findByPk: jest.fn(),
  },
}));

jest.mock('../src/utils/socketUtility', () => ({
  emitNotificationCountUpdated: jest.fn(),
}));

const { Notification } = require('../src/models');
const { emitNotificationCountUpdated } = require('../src/utils/socketUtility');
const notificationController = require('../src/controllers/notificationController');

const createResponse = () => {
  const res = {};
  res.status = jest.fn().mockReturnValue(res);
  res.json = jest.fn().mockReturnValue(res);
  return res;
};

describe('notificationController', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getMyNotifications', () => {
    it('returns notifications filtered to the authenticated role', async () => {
      const req = {
        user: { id: 'seller-1', role: 'seller' },
        query: { role: 'seller' },
      };
      const res = createResponse();

      Notification.findAll.mockResolvedValue([{ id: 'notif-1' }]);

      await notificationController.getMyNotifications(req, res);

      expect(Notification.findAll).toHaveBeenCalledWith({
        where: { userId: 'seller-1', targetRole: 'seller' },
        order: [['createdAt', 'DESC']],
      });
      expect(res.status).toHaveBeenCalledWith(200);
      expect(res.json).toHaveBeenCalledWith([{ id: 'notif-1' }]);
    });

    it('rejects mismatched role filters', async () => {
      const req = {
        user: { id: 'customer-1', role: 'customer' },
        query: { role: 'seller' },
      };
      const res = createResponse();

      await notificationController.getMyNotifications(req, res);

      expect(Notification.findAll).not.toHaveBeenCalled();
      expect(res.status).toHaveBeenCalledWith(400);
      expect(res.json).toHaveBeenCalledWith({ message: 'Invalid notification role filter' });
    });
  });

  describe('getUnreadCount', () => {
    it('returns unread count scoped to user and role', async () => {
      const req = {
        user: { id: 'admin-1', role: 'admin' },
        query: { role: 'admin' },
      };
      const res = createResponse();

      Notification.count.mockResolvedValue(5);

      await notificationController.getUnreadCount(req, res);

      expect(Notification.count).toHaveBeenCalledWith({
        where: { userId: 'admin-1', read: false, targetRole: 'admin' },
      });
      expect(res.status).toHaveBeenCalledWith(200);
      expect(res.json).toHaveBeenCalledWith({ unreadCount: 5 });
    });
  });

  describe('markAllRead', () => {
    it('marks notifications read and emits the remaining unread count', async () => {
      const req = {
        user: { id: 'seller-1', role: 'seller' },
        query: { role: 'seller' },
      };
      const res = createResponse();

      Notification.update.mockResolvedValue([2]);
      Notification.count.mockResolvedValue(1);

      await notificationController.markAllRead(req, res);

      expect(Notification.update).toHaveBeenCalledWith(
        { read: true },
        { where: { userId: 'seller-1', read: false, targetRole: 'seller' } }
      );
      expect(Notification.count).toHaveBeenCalledWith({
        where: { userId: 'seller-1', read: false },
      });
      expect(emitNotificationCountUpdated).toHaveBeenCalledWith('seller-1', 1);
      expect(res.status).toHaveBeenCalledWith(200);
    });
  });
});
