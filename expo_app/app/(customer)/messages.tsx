/**
 * Real-time Chat — shared by customer/seller/admin
 * Lists conversations → tap to open thread
 * Socket: receive_message, send_message
 */
import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, TextInput, RefreshControl } from 'react-native';
import { router } from 'expo-router';
import { api } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Conversation {
  id: string; otherUser: { id: string; name: string; role: string };
  lastMessage?: string; lastMessageAt?: string; unreadCount: number;
}

export default function MessagesScreen() {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const { socket } = useSocket();

  const fetchConversations = useCallback(async () => {
    try {
      const res = await api.get('/messages/conversations');
      setConversations(res.data || []);
    } catch { setConversations([]); }
    finally { setLoading(false); setRefreshing(false); }
  }, []);

  useEffect(() => { fetchConversations(); }, [fetchConversations]);

  useEffect(() => {
    if (!socket) return;
    const handler = () => fetchConversations();
    socket.on('receive_message', handler);
    socket.on('message_read', handler);
    return () => { socket.off('receive_message', handler); socket.off('message_read', handler); };
  }, [socket, fetchConversations]);

  const filtered = conversations.filter(c =>
    !search || c.otherUser.name.toLowerCase().includes(search.toLowerCase())
  );

  const renderConvo = ({ item }: { item: Conversation }) => (
    <TouchableOpacity
      style={[styles.convoCard, item.unreadCount > 0 && styles.convoCardUnread]}
      onPress={() => router.push({ pathname: '/(customer)/chat/[userId]' as any, params: { userId: item.otherUser.id, name: item.otherUser.name } })}
      activeOpacity={0.85}
    >
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>{item.otherUser.name.charAt(0).toUpperCase()}</Text>
      </View>
      <View style={styles.convoInfo}>
        <View style={styles.convoHeader}>
          <Text style={[styles.convoName, item.unreadCount > 0 && styles.convoNameUnread]}>{item.otherUser.name}</Text>
          {item.lastMessageAt && <Text style={styles.convoTime}>{new Date(item.lastMessageAt).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })}</Text>}
        </View>
        <View style={styles.convoFooter}>
          <Text style={[styles.lastMsg, item.unreadCount > 0 && styles.lastMsgUnread]} numberOfLines={1}>{item.lastMessage || 'Start a conversation'}</Text>
          {item.unreadCount > 0 && (
            <View style={styles.badge}><Text style={styles.badgeText}>{item.unreadCount}</Text></View>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.pageTitle}>Messages</Text>
      </View>
      <View style={styles.searchBox}>
        <TextInput style={styles.searchInput} value={search} onChangeText={setSearch} placeholder="Search conversations..." placeholderTextColor={Colors.textMuted} />
      </View>

      <FlatList
        data={filtered}
        keyExtractor={item => item.id}
        renderItem={renderConvo}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchConversations(); }} tintColor={Colors.primary} />}
        ListEmptyComponent={
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>💬</Text>
            <Text style={styles.emptyText}>{loading ? 'Loading...' : 'No messages yet.'}</Text>
          </View>
        }
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  header: { padding: Spacing.xl, borderBottomWidth: 1, borderBottomColor: Colors.border },
  pageTitle: { fontSize: FontSize.xxl, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  searchBox: { padding: Spacing.lg },
  searchInput: { backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, fontSize: FontSize.md, borderWidth: 1, borderColor: Colors.border },
  list: { padding: Spacing.lg, paddingBottom: 100 },
  convoCard: { flexDirection: 'row', gap: Spacing.md, backgroundColor: Colors.bgDark, borderRadius: Radius.lg, padding: Spacing.lg, marginBottom: Spacing.sm, borderWidth: 1, borderColor: Colors.border },
  convoCardUnread: { borderColor: Colors.primary },
  avatar: { width: 44, height: 44, borderRadius: 22, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center' },
  avatarText: { color: Colors.white, fontSize: FontSize.lg, fontWeight: FontWeight.bold },
  convoInfo: { flex: 1 },
  convoHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 2 },
  convoName: { fontSize: FontSize.md, color: Colors.textSecondary, fontWeight: FontWeight.semibold },
  convoNameUnread: { color: Colors.textPrimary, fontWeight: FontWeight.bold },
  convoTime: { fontSize: FontSize.xs, color: Colors.textMuted },
  convoFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  lastMsg: { fontSize: FontSize.sm, color: Colors.textMuted, flex: 1 },
  lastMsgUnread: { color: Colors.textSecondary, fontWeight: FontWeight.semibold },
  badge: { backgroundColor: Colors.primary, borderRadius: 10, minWidth: 18, height: 18, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 4 },
  badgeText: { color: Colors.white, fontSize: 10, fontWeight: '700' },
  emptyState: { flex: 1, alignItems: 'center', paddingTop: 80 },
  emptyIcon: { fontSize: 56, marginBottom: Spacing.xl },
  emptyText: { fontSize: FontSize.md, color: Colors.textMuted },
});
