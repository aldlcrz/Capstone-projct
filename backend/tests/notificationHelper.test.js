jest.mock('../src/models/Notification', () => ({
  create: jest.fn(),
  count: jest.fn(),
}));

jest.mock('../src/models/User', () => ({
  findByPk: jest.fn(),
}));

jest.mock('../src/utils/socketUtility', () => ({
  emitToUser: jest.fn(),
  emitNotificationCountUpdated: jest.fn(),
}));

jest.mock('../src/utils/pushHelper', () => ({
  sendPush: jest.fn(),
}));

const Notification = require('../src/models/Notification');
const User = require('../src/models/User');
const socketUtility = require('../src/utils/socketUtility');
const pushHelper = require('../src/utils/pushHelper');
const { sendNotification } = require('../src/utils/notificationHelper');

describe('notificationHelper.sendNotification', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    Notification.create.mockResolvedValue({
      id: 'notif-1',
      userId: 'seller-1',
      title: 'New Return Request',
      message: 'A customer requested a return.',
      targetRole: 'seller',
      read: false,
    });
    Notification.count.mockResolvedValue(3);
    User.findByPk.mockResolvedValue(null);
  });

  it('creates the notification and emits the unread count update', async () => {
    const notification = await sendNotification(
      'seller-1',
      'New Return Request',
      'A customer requested a return.',
      'order',
      '/seller/orders',
      'seller'
    );

    expect(Notification.create).toHaveBeenCalledWith(expect.objectContaining({
      userId: 'seller-1',
      title: 'New Return Request',
      message: 'A customer requested a return.',
      type: 'order',
      link: '/seller/orders',
      read: false,
      targetRole: 'seller',
    }));
    expect(Notification.count).toHaveBeenCalledWith({
      where: { userId: 'seller-1', read: false },
    });
    expect(socketUtility.emitToUser).toHaveBeenCalledWith(
      'seller-1',
      'new_notification',
      expect.objectContaining({ id: 'notif-1' })
    );
    expect(socketUtility.emitNotificationCountUpdated).toHaveBeenCalledWith('seller-1', 3);
    expect(pushHelper.sendPush).not.toHaveBeenCalled();
    expect(notification).toEqual(expect.objectContaining({ id: 'notif-1' }));
  });
});
