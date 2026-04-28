/**
 * Admin Broadcast — Send system-wide notifications to all users
 * Mirrors web admin broadcast functionality via socket.emit
 * Route: app/(admin)/broadcast.tsx
 */
import React, { useState } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, Alert,
} from 'react-native';
import { api, getApiErrorMessage } from '@/lib/api';
import { useSocket } from '@/lib/socket';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

const BROADCAST_TYPES = [
  { id: 'system',      emoji: '📢', label: 'System',      desc: 'General system messages' },
  { id: 'maintenance', emoji: '🔧', label: 'Maintenance',  desc: 'Maintenance announcements' },
  { id: 'promotion',   emoji: '🎉', label: 'Promotion',    desc: 'Promotions & events' },
  { id: 'alert',       emoji: '⚠️', label: 'Alert',        desc: 'Urgent alerts' },
];

interface SentBroadcast {
  title: string; message: string; type: string; sentAt: Date;
}

export default function AdminBroadcast() {
  const [title, setTitle] = useState('');
  const [message, setMessage] = useState('');
  const [type, setType] = useState('system');
  const [loading, setLoading] = useState(false);
  const [history, setHistory] = useState<SentBroadcast[]>([]);
  const { socket } = useSocket();

  const handleSend = async () => {
    if (!title.trim()) { Alert.alert('Required', 'Please enter a broadcast title.'); return; }
    if (!message.trim()) { Alert.alert('Required', 'Please enter a broadcast message.'); return; }
    if (message.trim().length < 10) { Alert.alert('Too short', 'Message must be at least 10 characters.'); return; }

    Alert.alert(
      'Send Broadcast',
      `Send "${title}" to ALL users on the platform?`,
      [
        { text: 'Cancel' },
        {
          text: 'Send Now', style: 'destructive', onPress: async () => {
            setLoading(true);
            try {
              await api.post('/admin/broadcast', {
                title: title.trim(),
                message: message.trim(),
                type,
              });

              // Also emit via socket for immediate delivery
              socket?.emit('admin_broadcast', {
                title: title.trim(),
                message: message.trim(),
                type,
                timestamp: new Date().toISOString(),
              });

              // Add to local history
              setHistory(prev => [{
                title: title.trim(),
                message: message.trim(),
                type,
                sentAt: new Date(),
              }, ...prev.slice(0, 9)]);

              setTitle('');
              setMessage('');
              Alert.alert('Sent! 📢', 'Broadcast delivered to all platform users.');
            } catch (err: any) {
              Alert.alert('Error', getApiErrorMessage(err, 'Failed to send broadcast.'));
            } finally { setLoading(false); }
          }
        }
      ]
    );
  };

  const selectedType = BROADCAST_TYPES.find(t => t.id === type);

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <Text style={styles.pageTitle}>Send Broadcast</Text>
        <Text style={styles.pageSubtitle}>
          Send a real-time notification to all platform users
        </Text>

        {/* Type Picker */}
        <Text style={styles.sectionTitle}>Broadcast Type</Text>
        <View style={styles.typeGrid}>
          {BROADCAST_TYPES.map(bt => (
            <TouchableOpacity
              key={bt.id}
              style={[styles.typeCard, type === bt.id && styles.typeCardActive]}
              onPress={() => setType(bt.id)}
            >
              <Text style={styles.typeEmoji}>{bt.emoji}</Text>
              <Text style={[styles.typeLabel, type === bt.id && styles.typeLabelActive]}>
                {bt.label}
              </Text>
              <Text style={styles.typeDesc}>{bt.desc}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Compose */}
        <Text style={styles.sectionTitle}>Compose Message</Text>
        <View style={styles.composeCard}>
          <View style={styles.composeHeader}>
            <Text style={styles.composeHeaderEmoji}>{selectedType?.emoji}</Text>
            <Text style={styles.composeHeaderType}>{selectedType?.label} Broadcast</Text>
          </View>
          <Input
            label="Title *"
            value={title}
            onChangeText={setTitle}
            placeholder="e.g. Scheduled Maintenance"
            maxLength={100}
          />
          <Input
            label="Message *"
            value={message}
            onChangeText={setMessage}
            placeholder="Write your broadcast message here..."
            multiline
            style={{ height: 120 }}
            maxLength={500}
          />
          <Text style={styles.charCount}>{message.length}/500</Text>
        </View>

        <Button
          label={`📢 Broadcast to All Users`}
          variant="primary"
          size="lg"
          fullWidth
          loading={loading}
          onPress={handleSend}
          style={{ marginTop: Spacing.xl }}
        />

        {/* History */}
        {history.length > 0 && (
          <>
            <Text style={[styles.sectionTitle, { marginTop: Spacing.xxxl }]}>
              Recently Sent
            </Text>
            {history.map((h, i) => (
              <View key={i} style={styles.historyCard}>
                <View style={styles.historyHeader}>
                  <Text style={styles.historyType}>
                    {BROADCAST_TYPES.find(t => t.id === h.type)?.emoji} {h.type}
                  </Text>
                  <Text style={styles.historyTime}>
                    {h.sentAt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })}
                  </Text>
                </View>
                <Text style={styles.historyTitle}>{h.title}</Text>
                <Text style={styles.historyMsg} numberOfLines={2}>{h.message}</Text>
              </View>
            ))}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 100 },
  pageTitle: {
    fontSize: FontSize.xxl, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.xs,
  },
  pageSubtitle: {
    fontSize: FontSize.sm, color: Colors.textMuted,
    marginBottom: Spacing.xxl, lineHeight: 18,
  },
  sectionTitle: {
    fontSize: FontSize.lg, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.md,
    borderLeftWidth: 3, borderLeftColor: Colors.primary, paddingLeft: Spacing.md,
  },
  typeGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.sm, marginBottom: Spacing.xl },
  typeCard: {
    width: '47%', backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.lg, borderWidth: 1.5, borderColor: Colors.border,
    alignItems: 'center',
  },
  typeCardActive: { borderColor: Colors.primary, backgroundColor: `${Colors.primary}11` },
  typeEmoji: { fontSize: 28, marginBottom: Spacing.xs },
  typeLabel: {
    fontSize: FontSize.md, fontWeight: FontWeight.bold,
    color: Colors.textMuted, marginBottom: 2,
  },
  typeLabelActive: { color: Colors.primary },
  typeDesc: { fontSize: FontSize.xs, color: Colors.textMuted, textAlign: 'center' },
  composeCard: {
    backgroundColor: Colors.bgDark, borderRadius: Radius.lg,
    padding: Spacing.xl, borderWidth: 1, borderColor: Colors.border,
  },
  composeHeader: {
    flexDirection: 'row', alignItems: 'center', gap: Spacing.sm,
    marginBottom: Spacing.xl, paddingBottom: Spacing.lg,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  composeHeaderEmoji: { fontSize: 24 },
  composeHeaderType: {
    fontSize: FontSize.md, fontWeight: FontWeight.bold,
    color: Colors.primary,
  },
  charCount: {
    fontSize: FontSize.xs, color: Colors.textMuted,
    textAlign: 'right', marginTop: -Spacing.sm,
  },
  historyCard: {
    backgroundColor: Colors.bgDark, borderRadius: Radius.md,
    padding: Spacing.lg, marginBottom: Spacing.md,
    borderWidth: 1, borderColor: Colors.border,
    borderLeftWidth: 3, borderLeftColor: Colors.primary,
  },
  historyHeader: {
    flexDirection: 'row', justifyContent: 'space-between',
    marginBottom: Spacing.xs,
  },
  historyType: { fontSize: FontSize.xs, color: Colors.textMuted, textTransform: 'capitalize' },
  historyTime: { fontSize: FontSize.xs, color: Colors.textMuted },
  historyTitle: {
    fontSize: FontSize.md, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: 4,
  },
  historyMsg: { fontSize: FontSize.sm, color: Colors.textSecondary, lineHeight: 18 },
});
