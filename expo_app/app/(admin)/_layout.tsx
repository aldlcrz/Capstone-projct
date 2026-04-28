/**
 * Admin Tab Layout — Bottom navigation matching AdminLayout.js
 * Tabs: Dashboard | Users | Sellers | Products | Reports | Notifications
 */
import React from 'react';
import { Tabs } from 'expo-router';
import { View, Text, StyleSheet } from 'react-native';
import { useSocket } from '@/lib/socket';
import { Colors, FontSize } from '@/constants/theme';

function TabIcon({ emoji, label, focused, badge }: { emoji: string; label: string; focused: boolean; badge?: number }) {
  return (
    <View style={styles.tabItem}>
      <View>
        <Text style={{ fontSize: 20 }}>{emoji}</Text>
        {!!badge && badge > 0 && (
          <View style={styles.badge}><Text style={styles.badgeText}>{badge > 99 ? '99+' : badge}</Text></View>
        )}
      </View>
      <Text style={[styles.tabLabel, focused && styles.tabLabelActive]}>{label}</Text>
    </View>
  );
}

export default function AdminLayout() {
  const { unreadCount } = useSocket();
  return (
    <Tabs screenOptions={{ headerShown: false, tabBarStyle: styles.tabBar, tabBarShowLabel: false }}>
      <Tabs.Screen name="dashboard" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="📊" label="Overview" focused={focused} /> }} />
      <Tabs.Screen name="users" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="👥" label="Users" focused={focused} /> }} />
      <Tabs.Screen name="sellers" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="🏪" label="Sellers" focused={focused} /> }} />
      <Tabs.Screen name="products" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="📦" label="Products" focused={focused} /> }} />
      <Tabs.Screen name="reports" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="⚠️" label="Reports" focused={focused} /> }} />
      <Tabs.Screen name="broadcast" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="📢" label="Broadcast" focused={focused} /> }} />
      <Tabs.Screen name="notifications" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="🔔" label="Alerts" focused={focused} badge={unreadCount} /> }} />
      <Tabs.Screen name="profile" options={{ tabBarIcon: ({ focused }) => <TabIcon emoji="👤" label="Profile" focused={focused} /> }} />
      
      {/* Hidden Screens */}
      <Tabs.Screen name="messages" options={{ href: null }} />
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabBar: { backgroundColor: Colors.tabBackground, borderTopWidth: 1, borderTopColor: Colors.border, height: 70, paddingBottom: 8, paddingTop: 4 },
  tabItem: { alignItems: 'center', justifyContent: 'center' },
  tabLabel: { fontSize: 9, color: Colors.tabInactive, marginTop: 2 },
  tabLabelActive: { color: Colors.tabActive },
  badge: { position: 'absolute', top: -4, right: -8, backgroundColor: Colors.primary, borderRadius: 10, minWidth: 18, height: 18, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 3 },
  badgeText: { color: Colors.white, fontSize: 9, fontWeight: '700' },
});
