/**
 * Leave Review Screen — for completed orders
 * Route: app/(customer)/leave-review.tsx?orderId=&productId=&productName=
 */
import React, { useState } from 'react';
import {
  View, Text, StyleSheet, SafeAreaView, ScrollView,
  TouchableOpacity, Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { useLocalSearchParams, router } from 'expo-router';
import { api, getApiErrorMessage } from '@/lib/api';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Colors, FontSize, FontWeight, Spacing, Radius } from '@/constants/theme';

export default function LeaveReviewScreen() {
  const { orderId, productId, productName } = useLocalSearchParams<{
    orderId: string; productId: string; productName: string;
  }>();

  const [rating, setRating] = useState(0);
  const [comment, setComment] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    if (rating === 0) { Alert.alert('Rating required', 'Please select a star rating.'); return; }
    if (comment.trim().length < 5) { Alert.alert('Review required', 'Please write a short review (at least 5 characters).'); return; }

    setLoading(true);
    try {
      await api.post(`/products/${productId}/reviews`, {
        orderId,
        rating,
        comment: comment.trim(),
      });
      Alert.alert('Review Submitted! ⭐', 'Thank you for your feedback.', [
        { text: 'Back to Orders', onPress: () => router.replace('/(customer)/orders' as any) },
      ]);
    } catch (err: any) {
      Alert.alert('Error', getApiErrorMessage(err, 'Failed to submit review.'));
    } finally { setLoading(false); }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.back}>← Back</Text>
          </TouchableOpacity>

          <Text style={styles.title}>Leave a Review</Text>
          <Text style={styles.productName} numberOfLines={2}>{productName || 'Product'}</Text>

          {/* Star Rating */}
          <Text style={styles.sectionLabel}>Your Rating *</Text>
          <View style={styles.starsRow}>
            {[1, 2, 3, 4, 5].map(star => (
              <TouchableOpacity key={star} onPress={() => setRating(star)} style={styles.starBtn}>
                <Text style={[styles.star, rating >= star && styles.starFilled]}>
                  {rating >= star ? '⭐' : '☆'}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
          <Text style={styles.ratingLabel}>
            {rating === 0 ? 'Tap a star' :
             rating === 1 ? 'Poor' :
             rating === 2 ? 'Fair' :
             rating === 3 ? 'Good' :
             rating === 4 ? 'Very Good' : 'Excellent!'}
          </Text>

          {/* Comment */}
          <Input
            label="Your Review *"
            value={comment}
            onChangeText={setComment}
            placeholder="Share your experience with this product..."
            multiline
            style={{ height: 120 }}
            maxLength={500}
          />
          <Text style={styles.charCount}>{comment.length}/500</Text>

          <Button
            label="Submit Review ⭐"
            variant="primary"
            size="lg"
            fullWidth
            loading={loading}
            onPress={handleSubmit}
            style={{ marginTop: Spacing.xl }}
          />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.bgDeep },
  scroll: { padding: Spacing.xl, paddingBottom: 100 },
  back: { color: Colors.accent, fontSize: FontSize.md, marginBottom: Spacing.xl },
  title: {
    fontSize: FontSize.xxl, fontWeight: FontWeight.bold,
    color: Colors.textPrimary, marginBottom: Spacing.sm,
  },
  productName: {
    fontSize: FontSize.md, color: Colors.textSecondary,
    lineHeight: 22, marginBottom: Spacing.xxxl,
  },
  sectionLabel: {
    fontSize: FontSize.sm, color: Colors.textMuted,
    fontWeight: FontWeight.bold, letterSpacing: 1,
    textTransform: 'uppercase', marginBottom: Spacing.md,
  },
  starsRow: { flexDirection: 'row', gap: Spacing.md, marginBottom: Spacing.sm },
  starBtn: { padding: Spacing.xs },
  star: { fontSize: 40, color: Colors.border },
  starFilled: { color: '#F59E0B' },
  ratingLabel: {
    fontSize: FontSize.md, fontWeight: FontWeight.semibold,
    color: Colors.textSecondary, marginBottom: Spacing.xxl, textAlign: 'center',
  },
  charCount: {
    fontSize: FontSize.xs, color: Colors.textMuted,
    textAlign: 'right', marginTop: -Spacing.sm, marginBottom: Spacing.md,
  },
});
