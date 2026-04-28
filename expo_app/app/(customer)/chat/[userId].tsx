/**
 * Real-time Chat Thread — [userId] route
 * Mirrors web chat/messages page.js
 * - Loads message history from API
 * - Sends messages via socket emit + REST fallback
 * - Auto-scrolls to bottom
 * - Marks messages as read on open
 */
import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, FlatList, TextInput,
  TouchableOpacity, KeyboardAvoidingView, Platform, ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import { api } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { useAuthStore } from '@/store/authStore';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

interface Message {
  id: string; content: string; senderId: string;
  createdAt: string; isRead: boolean;
}

export default function ChatScreen() {
  const { userId, name } = useLocalSearchParams<{ userId: string; name: string }>();
  const [messages, setMessages] = useState<Message[]>([]);
  const [loading, setLoading] = useState(true);
  const [text, setText] = useState('');
  const [sending, setSending] = useState(false);
  const flatRef = useRef<FlatList>(null);
  const { socket } = useSocket();
  const { user } = useAuthStore();
  const myId = user?.id;

  const fetchMessages = useCallback(async () => {
    try {
      const res = await api.get(`/messages/${userId}`);
      setMessages(res.data || []);
      // Mark all as read
      await api.put(`/messages/${userId}/read`).catch(() => {});
    } catch { setMessages([]); }
    finally { setLoading(false); }
  }, [userId]);

  useEffect(() => { fetchMessages(); }, [fetchMessages]);

  useEffect(() => {
    if (!socket) return;
    const handleReceive = (msg: any) => {
      if (String(msg.senderId) === String(userId) || String(msg.receiverId) === String(userId)) {
        setMessages(prev => {
          const exists = prev.some(m => m.id === msg.id);
          return exists ? prev : [...prev, msg];
        });
        // Mark as read immediately
        api.put(`/messages/${userId}/read`).catch(() => {});
      }
    };
    socket.on('receive_message', handleReceive);
    return () => { socket.off('receive_message', handleReceive); };
  }, [socket, userId]);

  useEffect(() => {
    if (messages.length > 0) setTimeout(() => flatRef.current?.scrollToEnd({ animated: true }), 100);
  }, [messages.length]);

  const sendMessage = async () => {
    if (!text.trim() || sending) return;
    const content = text.trim();
    setText('');
    setSending(true);

    // Optimistic update
    const tempMsg: Message = { id: `temp_${Date.now()}`, content, senderId: myId || '', createdAt: new Date().toISOString(), isRead: false };
    setMessages(prev => [...prev, tempMsg]);

    try {
      if (socket?.connected) {
        socket.emit('send_message', { receiverId: userId, content });
      }
      // REST fallback for persistence
      await api.post('/messages', { receiverId: userId, content });
    } catch {
      // Remove optimistic message on failure
      setMessages(prev => prev.filter(m => m.id !== tempMsg.id));
      setText(content);
    } finally { setSending(false); }
  };

  const renderMessage = ({ item }: { item: Message }) => {
    const isMe = String(item.senderId) === String(myId);
    return (
      <View style={[styles.msgRow, isMe && styles.msgRowMe]}>
        <View style={[styles.bubble, isMe ? styles.bubbleMe : styles.bubbleThem]}>
          <Text style={[styles.bubbleText, isMe && styles.bubbleTextMe]}>{item.content}</Text>
          <Text style={[styles.bubbleTime, isMe && styles.bubbleTimeMe]}>
            {new Date(item.createdAt).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })}
          </Text>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Text style={styles.backText}>←</Text>
        </TouchableOpacity>
        <View style={styles.headerInfo}>
          <View style={styles.headerAvatar}>
            <Text style={styles.headerAvatarText}>{(name || 'U').charAt(0).toUpperCase()}</Text>
          </View>
          <View>
            <Text style={styles.headerName}>{name || 'User'}</Text>
            <Text style={styles.headerStatus}>{socket?.connected ? '● Online' : '○ Offline'}</Text>
          </View>
        </View>
      </View>

      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        {loading ? (
          <ActivityIndicator style={{ flex: 1 }} size="large" color={Colors.primary} />
        ) : (
          <FlatList
            ref={flatRef}
            data={messages}
            keyExtractor={item => item.id}
            renderItem={renderMessage}
            contentContainerStyle={styles.messageList}
            showsVerticalScrollIndicator={false}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <Text style={styles.emptyText}>No messages yet. Say hello! 👋</Text>
              </View>
            }
          />
        )}

        {/* Input */}
        <View style={styles.inputBar}>
          <TextInput
            style={styles.input}
            value={text}
            onChangeText={setText}
            placeholder="Type a message..."
            placeholderTextColor={Colors.textMuted}
            multiline
            maxLength={1000}
            returnKeyType="send"
            onSubmitEditing={sendMessage}
          />
          <TouchableOpacity style={[styles.sendBtn, (!text.trim() || sending) && styles.sendBtnDisabled]} onPress={sendMessage} disabled={!text.trim() || sending}>
            <Text style={styles.sendBtnText}>➤</Text>
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  header: { flexDirection: 'row', alignItems: 'center', padding: Spacing.lg, borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: Colors.bgDark },
  backBtn: { marginRight: Spacing.md, padding: Spacing.xs },
  backText: { fontSize: FontSize.xl, color: Colors.textPrimary, fontWeight: FontWeight.bold },
  headerInfo: { flexDirection: 'row', alignItems: 'center', gap: Spacing.md },
  headerAvatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center' },
  headerAvatarText: { color: Colors.white, fontSize: FontSize.lg, fontWeight: FontWeight.bold },
  headerName: { fontSize: FontSize.lg, fontWeight: FontWeight.bold, color: Colors.textPrimary },
  headerStatus: { fontSize: FontSize.xs, color: Colors.success },
  messageList: { padding: Spacing.lg, paddingBottom: Spacing.md },
  msgRow: { flexDirection: 'row', marginBottom: Spacing.md },
  msgRowMe: { justifyContent: 'flex-end' },
  bubble: { maxWidth: '78%', borderRadius: Radius.lg, padding: Spacing.md },
  bubbleMe: { backgroundColor: Colors.primary, borderBottomRightRadius: 4 },
  bubbleThem: { backgroundColor: Colors.bgDark, borderWidth: 1, borderColor: Colors.border, borderBottomLeftRadius: 4 },
  bubbleText: { fontSize: FontSize.md, color: Colors.textSecondary, lineHeight: 20 },
  bubbleTextMe: { color: Colors.white },
  bubbleTime: { fontSize: FontSize.xs, color: Colors.textMuted, marginTop: 4, textAlign: 'right' },
  bubbleTimeMe: { color: 'rgba(255,255,255,0.6)' },
  emptyState: { flex: 1, alignItems: 'center', paddingTop: 80 },
  emptyText: { color: Colors.textMuted, fontSize: FontSize.md },
  inputBar: { flexDirection: 'row', alignItems: 'flex-end', padding: Spacing.lg, borderTopWidth: 1, borderTopColor: Colors.border, backgroundColor: Colors.bgDark, gap: Spacing.md },
  input: { flex: 1, backgroundColor: Colors.bgMedium, color: Colors.textPrimary, borderRadius: Radius.full, paddingHorizontal: Spacing.lg, paddingVertical: Spacing.md, fontSize: FontSize.md, maxHeight: 100, borderWidth: 1, borderColor: Colors.border },
  sendBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: Colors.primary, justifyContent: 'center', alignItems: 'center' },
  sendBtnDisabled: { opacity: 0.4 },
  sendBtnText: { color: Colors.white, fontSize: FontSize.lg, fontWeight: FontWeight.bold },
});
