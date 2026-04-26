import 'package:flutter/foundation.dart';
import '../models/product_model.dart';
import '../services/api_client.dart';

class ProductService {
  static final ProductService _instance = ProductService._internal();
  factory ProductService() => _instance;
  ProductService._internal();

  Future<List<ProductModel>> getProducts() async {
    try {
      final res = await ApiClient().get('/products');
      if (res.statusCode == 200) {
        final List data = res.data;
        return data.map((e) => ProductModel.fromJson(e)).toList();
      }
    } catch (e) {
      debugPrint('Error fetching products: $e');
    }
    return [];
  }

  Future<List<String>> getCategories() async {
    try {
      final res = await ApiClient().get('/categories');
      if (res.statusCode == 200) {
        final List data = res.data;
        return data.map((e) => (e['name'] ?? e).toString()).toList();
      }
    } catch (e) {
      debugPrint('Error fetching categories: $e');
    }
    return [];
  }
}
