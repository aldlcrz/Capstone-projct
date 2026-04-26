import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../models/product_model.dart';

class CartItem {
  final int id;
  final String name;
  final double price;
  final String image;
  int quantity;
  final String size;
  final String variation;
  final String? artisan;

  CartItem({
    required this.id,
    required this.name,
    required this.price,
    required this.image,
    required this.quantity,
    required this.size,
    required this.variation,
    this.artisan,
  });

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'price': price,
    'image': image,
    'quantity': quantity,
    'size': size,
    'variation': variation,
    'artisan': artisan,
  };

  factory CartItem.fromJson(Map<String, dynamic> json) => CartItem(
    id: json['id'],
    name: json['name'],
    price: json['price'].toDouble(),
    image: json['image'],
    quantity: json['quantity'],
    size: json['size'],
    variation: json['variation'],
    artisan: json['artisan'],
  );
}

class CartProvider extends ChangeNotifier {
  List<CartItem> _items = [];

  List<CartItem> get items => _items;

  CartProvider() {
    _loadCart();
  }

  Future<void> _loadCart() async {
    final prefs = await SharedPreferences.getInstance();
    final cartJson = prefs.getString('cart_data');
    if (cartJson != null) {
      final List decoded = jsonDecode(cartJson);
      _items = decoded.map((e) => CartItem.fromJson(e)).toList();
      notifyListeners();
    }
  }

  Future<void> _saveCart() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('cart_data', jsonEncode(_items.map((e) => e.toJson()).toList()));
  }

  void addToCart(ProductModel product, int quantity, {String? size, String? variation}) {
    final existingIndex = _items.indexWhere((item) => 
      item.id == product.id && 
      item.size == (size ?? 'N/A') && 
      item.variation == (variation ?? 'Original')
    );

    if (existingIndex >= 0) {
      _items[existingIndex].quantity += quantity;
    } else {
      _items.add(CartItem(
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.mainImageUrl,
        quantity: quantity,
        size: size ?? 'N/A',
        variation: variation ?? 'Original',
        artisan: product.artisan,
      ));
    }
    _saveCart();
    notifyListeners();
  }

  void setQuantity(int index, int newQuantity) {
    if (newQuantity < 1) return;
    _items[index].quantity = newQuantity;
    _saveCart();
    notifyListeners();
  }

  void removeItem(int index) {
    _items.removeAt(index);
    _saveCart();
    notifyListeners();
  }

  void clear() {
    _items.clear();
    _saveCart();
    notifyListeners();
  }
}
