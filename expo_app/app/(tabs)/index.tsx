import React, { useEffect, useState } from 'react';
import {
  StyleSheet,
  View,
  Text,
  FlatList,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  SafeAreaView,
  Alert,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { router } from 'expo-router';
import axios from 'axios';

interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  stock: number;
  images: string | string[];
}

export default function DashboardScreen() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [baseUrl, setBaseUrl] = useState('');

  useEffect(() => {
    loadProducts();
  }, []);

  const loadProducts = async () => {
    try {
      const ip = await AsyncStorage.getItem('backend_ip') || '192.168.100.5';
      const port = await AsyncStorage.getItem('backend_port') || '5000';
      const url = `http://${ip}:${port}`;
      setBaseUrl(url);

      const response = await axios.get(`${url}/api/v1/products`);
      if (response.data) {
        setProducts(response.data);
      }
    } catch (error: any) {
      console.error('Error fetching products:', error);
      Alert.alert('Fetch Error', 'Failed to retrieve shop inventory.');
    } finally {
      setLoading(false);
    }
  };

  const handleSignOut = async () => {
    await AsyncStorage.multiRemove(['auth_token', 'backend_ip', 'backend_port']);
    router.replace('/');
  };

  const renderProductItem = ({ item }: { item: Product }) => {
    // Parse image URLs
    let imageSource = 'https://via.placeholder.com/150';
    if (item.images) {
      try {
        const parsed = typeof item.images === 'string' ? JSON.parse(item.images) : item.images;
        if (Array.isArray(parsed) && parsed.length > 0) {
          imageSource = `${baseUrl}/uploads/products/${parsed[0]}`;
        } else if (typeof parsed === 'string' && parsed) {
          imageSource = `${baseUrl}/uploads/products/${parsed}`;
        }
      } catch (e) {
        if (typeof item.images === 'string' && item.images) {
          imageSource = `${baseUrl}/uploads/products/${item.images}`;
        }
      }
    }

    return (
      <View style={styles.productCard}>
        <Image source={{ uri: imageSource }} style={styles.productImage} />
        <View style={styles.productDetails}>
          <Text style={styles.productName} numberOfLines={1}>{item.name}</Text>
          <Text style={styles.productPrice}>₱{item.price}</Text>
          <Text style={styles.productStock}>Stock: {item.stock}</Text>
          
          <TouchableOpacity 
            style={styles.buyBtn}
            onPress={() => Alert.alert('Shop Info', 'Cart functionalities are under live native development.')}
          >
            <Text style={styles.buyBtnText}>Add to Cart</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Shop Barongs</Text>
        <TouchableOpacity style={styles.signOutBtn} onPress={handleSignOut}>
          <Text style={styles.signOutText}>Sign Out</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.loaderContainer}>
          <ActivityIndicator size="large" color="#C0422A" />
          <Text style={styles.loadingText}>Fetching authentic garments...</Text>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderProductItem}
          contentContainerStyle={styles.listContainer}
          showsVerticalScrollIndicator={false}
          numColumns={2}
          columnWrapperStyle={styles.row}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No products available at the moment.</Text>
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#1C1917',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#2A2623',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#D4B896',
  },
  signOutBtn: {
    backgroundColor: '#C0422A',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 15,
  },
  signOutText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: 'bold',
  },
  loaderContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    color: '#D4B896',
    opacity: 0.8,
  },
  listContainer: {
    padding: 15,
  },
  row: {
    justifyContent: 'space-between',
    marginBottom: 15,
  },
  productCard: {
    backgroundColor: '#2A2623',
    width: '48%',
    borderRadius: 15,
    overflow: 'hidden',
    elevation: 4,
  },
  productImage: {
    width: '100%',
    height: 160,
    backgroundColor: '#3E3834',
  },
  productDetails: {
    padding: 12,
  },
  productName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#F7F3EE',
    marginBottom: 4,
  },
  productPrice: {
    fontSize: 15,
    color: '#C0422A',
    fontWeight: '700',
    marginBottom: 4,
  },
  productStock: {
    fontSize: 12,
    color: '#D4B896',
    opacity: 0.6,
    marginBottom: 10,
  },
  buyBtn: {
    backgroundColor: '#D4B896',
    paddingVertical: 8,
    borderRadius: 8,
    alignItems: 'center',
  },
  buyBtnText: {
    color: '#1C1917',
    fontSize: 12,
    fontWeight: 'bold',
  },
  emptyText: {
    textAlign: 'center',
    color: '#D4B896',
    opacity: 0.5,
    marginTop: 40,
  },
});
