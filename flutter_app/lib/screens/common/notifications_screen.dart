import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../providers/notification_provider.dart';
import '../../config/app_theme.dart';

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        title: const Text('Notifications', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          TextButton(
            onPressed: () => context.read<NotificationProvider>().clearAll(),
            child: const Text('Clear All', style: TextStyle(color: AppTheme.primary, fontSize: 12)),
          ),
        ],
      ),
      body: Consumer<NotificationProvider>(
        builder: (context, provider, child) {
          final notifications = provider.notifications;

          if (notifications.isEmpty) {
            return _buildEmptyState();
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: notifications.length,
            separatorBuilder: (context, index) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final notification = notifications[index];
              return _buildNotificationCard(context, provider, notification);
            },
          );
        },
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(LucideIcons.bellOff, size: 64, color: AppTheme.textMuted.withValues(alpha: 0.3)),
          const SizedBox(height: 16),
          const Text(
            'All caught up!',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.charcoal),
          ),
          const SizedBox(height: 8),
          const Text(
            'No new alerts from the LumBarong studio.',
            style: TextStyle(color: AppTheme.textMuted),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationCard(BuildContext context, NotificationProvider provider, NotificationModel notification) {
    return InkWell(
      onTap: () => provider.markAsRead(notification.id),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: notification.isRead ? Colors.white.withValues(alpha: 0.5) : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: notification.isRead ? AppTheme.border : AppTheme.primary.withValues(alpha: 0.2),
          ),
          boxShadow: notification.isRead 
              ? null 
              : [BoxShadow(color: AppTheme.primary.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 4))],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildIcon(notification),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        notification.title.toUpperCase(),
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 1,
                          color: notification.isRead ? AppTheme.textMuted : AppTheme.primary,
                        ),
                      ),
                      Text(
                        DateFormat('hh:mm a').format(notification.createdAt),
                        style: const TextStyle(fontSize: 10, color: AppTheme.textMuted),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    notification.message,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: notification.isRead ? FontWeight.normal : FontWeight.bold,
                      color: AppTheme.charcoal,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildIcon(NotificationModel notification) {
    IconData icon;
    Color color;

    if (notification.title.toLowerCase().contains('order')) {
      icon = LucideIcons.shoppingBag;
      color = Colors.blue;
    } else if (notification.title.toLowerCase().contains('success')) {
      icon = LucideIcons.checkCircle;
      color = Colors.green;
    } else {
      icon = LucideIcons.bell;
      color = AppTheme.primary;
    }

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        shape: BoxShape.circle,
      ),
      child: Icon(icon, size: 18, color: color),
    );
  }
}
