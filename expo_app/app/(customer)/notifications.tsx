/**
 * Shared Notifications Screen — used by all 3 roles
 * Read/unread, mark as read, real-time badge update via socket
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, RefreshControl } from 'react-native';
import { api } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Notification {
  id: string; title: string; message: string; isRead: boolean;
  type: string; createdAt: string; link?: string;
}

const TYPE_ICON: Record<string, string> = {
  order: '📦', message: '💬', system: '🔔', review: '⭐', report: '⚠️', admin: '🛡️',
};

export default function NotificationsScreen() {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const { socket, setUnreadCount } = useSocket();

  const fetchNotifications = useCallback(async () => {
    try {
      const res = await api.get('/notifications');
      setNotifications(res.data || []);
    } catch { setNotifications([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchNotifications(); }, [fetchNotifications]);

  useEffect(() => {
    if (!socket) return;
    socket.on('notification_count_update', fetchNotifications);
    return () => { socket.off('notification_count_update', fetchNotifications); };
  }, [socket, fetchNotifications]);

  const markRead = async (id: string) => {
    try {
      await api.put(`/notifications/${id}/read`);
      setNotifications(prev => prev.map(n => n.id === id ? { ...n, isRead: true } : n));
      setUnreadCount(Math.max(0, notifications.filter(n => !n.isRead).length - 1));
    } catch {}
  };

  const markAllRead = async () => {
    try {
      await api.put('/notifications/read-all');
      setNotifications(prev => prev.map(n => ({ ...n, isRead: true })));
      setUnreadCount(0);
    } catch {}
  };

  const unreadCount = notifications.filter(n => !n.isRead).length;

  const renderItem = ({ item }: { item: Notification }) => (
    <TouchableOpacity
      style={[styles.notifCard, !item.isRead && styles.notifCardUnread]}
      onPress={() => !item.isRead && markRead(item.id)}
      activeOpacity={0.8}
    >
      <Text style={styles.notifIcon}>{TYPE_ICON[item.type] || '🔔'}</Text>
      <View style={styles.notifContent}>
        <View style={styles.notifHeader}>
          <Text style={[styles.notifTitle, !item.isRead && styles.notifTitleUnread]} numberOfLines={1}>{item.title}</Text>
          {!item.isRead && <View style={styles.unreadDot} />}
        </View>
        <Text style={styles.notifMessage} numberOfLines={2}>{item.message}</Text>
        <Text style={styles.notifDate}>{new Date(item.createdAt).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <View>
          <Text style={styles.pageTitle}>Notifications</Text>
          {unreadCount > 0 && <Text style={styles.unreadLabel}>{unreadCount} unread</Text>}
        </View>
        {unreadCount > 0 && (
          <TouchableOpacity style={styles.markAllBtn} onPress={markAllRead}>
            <Text style={styles.markAllText}>Mark all read</Text>
          </TouchableOpacity>
        )}
      </View>

      <FlatList
        data={notifications}
        keyExtractor={item => item.id}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchNotifications(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>🔔</Text>
            <Text style={styles.emptyText}>{loading ? 'Loading...' : 'No notifications yet.'}</Text>
          </View>
        }
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', padding: Spacing.xl, borderBottomWidth: 1, borderBottomColor: Colors.border },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  unreadLabel: { fontSize: FontSize.xs, color: Colors.primary, fontWeight: FontWeight.semibold, marginTop: 2 },
  markAllBtn: { paddingHorizontal: Spacing.md, paddingVertical: Spacing.xs, borderRadius: Radius.full, borderWidth: 1, borderColor: Colors.accent },
  markAllText: { fontSize: FontSize.xs, color: Colors.accent, fontWeight: FontWeight.semibold },
  list: { padding: Spacing.lg, paddingBottom: 100 },
  notifCard: { flexDirection: 'row', gap: Spacing.md, backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.lg, marginBottom: Spacing.sm, borderWidth: 1, borderColor: Colors.border },
  notifCardUnread: { borderColor: Colors.primary, backgroundColor: `${Colors.primary}11` },
  notifIcon: { fontSize: 24, marginTop: 2 },
  notifContent: { flex: 1 },
  notifHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 2 },
  notifTitle: { fontSize: FontSize.sm, color: Colors.textSecondary, fontWeight: FontWeight.semibold, flex: 1 },
  notifTitleUnread: { color: Colors.textPrimary, fontWeight: FontWeight.bold },
  unreadDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: Colors.primary, marginLeft: Spacing.xs },
  notifMessage: { fontSize: FontSize.sm, color: Colors.textMuted, lineHeight: 18, marginBottom: 4 },
  notifDate: { fontSize: FontSize.xs, color: Colors.textMuted },
  emptyState: { flex: 1, alignItems: 'center', paddingTop: 80 },
  emptyIcon: { fontSize: 56, marginBottom: Spacing.xl },
  emptyText: { fontSize: FontSize.md, color: Colors.textMuted },
});
