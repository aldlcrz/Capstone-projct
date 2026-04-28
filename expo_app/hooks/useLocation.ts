/**
 * useLocation hook — Requests and returns current GPS coordinates
 * Uses expo-location for address auto-fill
 */
import { useState } from 'react';
import * as Location from 'expo-location';
import { Alert } from 'react-native';

interface LocationResult {
  latitude: number;
  longitude: number;
  city?: string;
  province?: string;
  street?: string;
  barangay?: string;
  postalCode?: string;
}

interface UseLocationReturn {
  location: LocationResult | null;
  loading: boolean;
  getCurrentLocation: () => Promise<LocationResult | null>;
}

export function useLocation(): UseLocationReturn {
  const [location, setLocation] = useState<LocationResult | null>(null);
  const [loading, setLoading] = useState(false);

  const getCurrentLocation = async (): Promise<LocationResult | null> => {
    setLoading(true);
    try {
      // Request foreground permission
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert(
          'Location Permission',
          'Enable location access to auto-fill your address.',
          [{ text: 'OK' }]
        );
        return null;
      }

      // Get current coords
      const coords = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.High,
      });

      const { latitude, longitude } = coords.coords;

      // Reverse geocode to get address components
      const [geocode] = await Location.reverseGeocodeAsync({ latitude, longitude });

      const result: LocationResult = {
        latitude,
        longitude,
        city: geocode?.city || geocode?.subregion || '',
        province: geocode?.region || '',
        street: geocode?.street || '',
        barangay: geocode?.district || geocode?.subregion || '',
        postalCode: geocode?.postalCode || '',
      };

      setLocation(result);
      return result;
    } catch (err) {
      Alert.alert('Error', 'Could not retrieve your location. Please enter your address manually.');
      return null;
    } finally {
      setLoading(false);
    }
  };

  return { location, loading, getCurrentLocation };
}
