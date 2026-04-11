import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class ApiClient {
  static final ApiClient _instance = ApiClient._();
  factory ApiClient() => _instance;

  late final Dio _dio;
  static const String _tokenKey = 'token';
  static const String _userKey = 'user';

  Map<String, dynamic> _mockCurrentUser = {
    'id': 'customer-1',
    'name': 'Mock Customer',
    'email': 'customer@lumbarong.mock',
    'role': 'customer',
    'mobileNumber': '09171234567',
    'isVerified': true,
  };

  final List<Map<String, dynamic>> _mockProducts = [];
  final List<Map<String, dynamic>> _mockOrders = [];
  final List<Map<String, dynamic>> _mockAddresses = [];
  final List<Map<String, dynamic>> _mockNotifications = [];
  final Map<String, List<Map<String, dynamic>>> _mockMessages = {};

  ApiClient._() {
    _dio = Dio(
      BaseOptions(
        baseUrl: kApiBaseUrl,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    if (kMockMode) {
      _seedMockData();
      return;
    }

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString(_tokenKey);
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (err, handler) {
          return handler.next(err);
        },
      ),
    );
  }

  void _seedMockData() {
    if (_mockProducts.isNotEmpty) return;

    _mockProducts.addAll([
      {
        'id': 'product-1',
        'name': 'Heritage Barong Classic',
        'description': 'Handwoven piña-inspired formal barong.',
        'price': 2899,
        'stock': 12,
        'category': 'Formal',
        'sellerId': 'seller-1',
        'seller': {
          'id': 'seller-1',
          'name': 'Lumban Weaves Atelier',
          'shopName': 'Lumban Weaves Atelier',
        },
        'images': [
          {'url': 'https://picsum.photos/seed/lumbarong1/800/1000'},
        ],
        'ratings': [
          {'id': 1, 'rating': 5, 'review': 'Excellent quality', 'userId': 'customer-2', 'productId': 'product-1'},
          {'id': 2, 'rating': 4, 'review': 'Beautiful fabric', 'userId': 'customer-3', 'productId': 'product-1'},
        ],
        'soldCount': 20,
        'availableSizes': ['S', 'M', 'L', 'XL'],
        'availableColors': ['Ivory', 'Cream'],
        'availableDesigns': ['Classic', 'Modern'],
      },
      {
        'id': 'product-2',
        'name': 'Modern Mestizo Set',
        'description': 'Lightweight modern-fit barong for events.',
        'price': 3499,
        'stock': 8,
        'category': 'Modern',
        'sellerId': 'seller-1',
        'seller': {
          'id': 'seller-1',
          'name': 'Lumban Weaves Atelier',
          'shopName': 'Lumban Weaves Atelier',
        },
        'images': [
          {'url': 'https://picsum.photos/seed/lumbarong2/800/1000'},
        ],
        'ratings': [
          {'id': 3, 'rating': 5, 'review': 'Perfect fit', 'userId': 'customer-4', 'productId': 'product-2'},
          {'id': 4, 'rating': 5, 'review': 'Looks premium', 'userId': 'customer-5', 'productId': 'product-2'},
          {'id': 5, 'rating': 4, 'review': 'Great for events', 'userId': 'customer-6', 'productId': 'product-2'},
        ],
        'soldCount': 34,
        'availableSizes': ['M', 'L', 'XL'],
        'availableColors': ['White', 'Sand'],
        'availableDesigns': ['Mestizo'],
      },
    ]);

    _mockAddresses.addAll([
      {
        'id': 'addr-1',
        'label': 'Home',
        'isDefault': true,
        'fullName': 'Mock Customer',
        'recipientName': 'Mock Customer',
        'phone': '09171234567',
        'phoneNumber': '09171234567',
        'houseNo': '12',
        'street': 'Rizal Street',
        'barangay': 'Poblacion',
        'city': 'Lumban',
        'province': 'Laguna',
        'postalCode': '4014',
      },
    ]);

    _mockOrders.add({
      'id': 'order-1001',
      'totalAmount': 2899,
      'paymentMethod': 'GCash',
      'status': 'Pending',
      'shippingAddress': 'Mock Customer | Rizal Street, Lumban, Laguna 4014',
      'customerId': 'customer-1',
      'customer': {'id': 'customer-1', 'name': 'Mock Customer'},
      'items': [
        {
          'id': 1,
          'quantity': 1,
          'price': 2899,
          'orderId': 'order-1001',
          'productId': 'product-1',
          'product': Map<String, dynamic>.from(_mockProducts.first),
        },
      ],
      'createdAt': DateTime.now()
          .subtract(const Duration(days: 1))
          .toIso8601String(),
      'updatedAt': DateTime.now().toIso8601String(),
    });

    _mockNotifications.addAll([
      {
        'id': 'notif-1',
        'title': 'Order confirmed',
        'message': 'Your order #1001 has been confirmed by the artisan.',
        'type': 'order',
        'link': '/orders',
        'read': false,
        'createdAt': DateTime.now().subtract(const Duration(minutes: 18)).toIso8601String(),
      },
      {
        'id': 'notif-2',
        'title': 'New reply from shop',
        'message': 'Lumban Weaves Atelier replied to your message.',
        'type': 'message',
        'link': '/messages',
        'read': false,
        'createdAt': DateTime.now().subtract(const Duration(hours: 2)).toIso8601String(),
      },
      {
        'id': 'notif-3',
        'title': 'Profile reminder',
        'message': 'Keep your delivery address updated for faster checkout.',
        'type': 'system',
        'link': '/profile/addresses',
        'read': true,
        'createdAt': DateTime.now().subtract(const Duration(days: 1)).toIso8601String(),
      },
    ]);

    _mockMessages['seller-1'] = [
      {
        'id': 'msg-1',
        'senderId': 'seller-1',
        'content': 'Hello! We can customize your order.',
        'createdAt': DateTime.now()
            .subtract(const Duration(hours: 2))
            .toIso8601String(),
      },
      {
        'id': 'msg-2',
        'senderId': 'customer-1',
        'content': 'Great, I need size L in ivory.',
        'createdAt': DateTime.now()
            .subtract(const Duration(hours: 1))
            .toIso8601String(),
      },
    ];
  }

  dynamic _valueFromData(dynamic data, String key) {
    if (data is Map<String, dynamic>) return data[key];
    if (data is FormData) {
      for (final field in data.fields) {
        if (field.key == key) return field.value;
      }
    }
    return null;
  }

  Future<Response<T>> _mockResponse<T>({
    required String path,
    required String method,
    required dynamic payload,
    int statusCode = 200,
  }) async {
    await Future<void>.delayed(const Duration(milliseconds: 120));
    return Response<T>(
      requestOptions: RequestOptions(
        path: path,
        method: method,
        baseUrl: kApiBaseUrl,
      ),
      statusCode: statusCode,
      data: payload as T,
    );
  }

  DioException _mockError(String path, String method, String message) {
    return DioException(
      requestOptions: RequestOptions(
        path: path,
        method: method,
        baseUrl: kApiBaseUrl,
      ),
      response: Response(
        requestOptions: RequestOptions(
          path: path,
          method: method,
          baseUrl: kApiBaseUrl,
        ),
        statusCode: 404,
        data: {'message': message},
      ),
      type: DioExceptionType.badResponse,
      error: message,
    );
  }

  List<Map<String, dynamic>> _filteredProducts(
    Map<String, dynamic>? queryParameters,
  ) {
    Iterable<Map<String, dynamic>> list = _mockProducts;

    final search = queryParameters?['search']?.toString().toLowerCase();
    if (search != null && search.isNotEmpty) {
      list = list.where(
        (p) => p['name'].toString().toLowerCase().contains(search),
      );
    }

    final category = queryParameters?['category']?.toString().toLowerCase();
    if (category != null && category.isNotEmpty) {
      list = list.where(
        (p) => p['category'].toString().toLowerCase() == category,
      );
    }

    final seller = queryParameters?['shop']?.toString();
    if (seller != null && seller.isNotEmpty) {
      list = list.where((p) => p['sellerId']?.toString() == seller);
    }

    return list.map((p) => Map<String, dynamic>.from(p)).toList();
  }

  List<Map<String, dynamic>> _buildConversations() {
    final otherUserId = _mockCurrentUser['role'] == 'seller'
        ? 'customer-1'
        : 'seller-1';
    final otherUser = _mockCurrentUser['role'] == 'seller'
        ? {'id': 'customer-1', 'name': 'Mock Customer'}
        : {
            'id': 'seller-1',
            'name': 'Lumban Weaves Atelier',
            'shopName': 'Lumban Weaves Atelier',
          };

    final msgs = _mockMessages[otherUserId] ?? [];
    final lastMsg = msgs.isNotEmpty ? msgs.last : null;
    return [
      {
        'otherUser': otherUser,
        'lastMessage':
            lastMsg?['content']?.toString() ?? 'Start a conversation',
        'timestamp':
            lastMsg?['createdAt']?.toString() ??
            DateTime.now().toIso8601String(),
        'unreadCount': 0,
      },
    ];
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> setToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<void> setUser(Map<String, dynamic> user) async {
    if (kMockMode) {
      _mockCurrentUser = Map<String, dynamic>.from(user);
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final s = prefs.getString(_userKey);
    if (s == null) return null;
    return Map<String, dynamic>.from(jsonDecode(s) as Map);
  }

  Future<void> clearAuth() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    await prefs.remove('cart');
  }

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    if (!kMockMode) {
      return _dio.get<T>(path, queryParameters: queryParameters);
    }

    if (path == '/auth/stats') {
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: {
          'artisanCount': 12,
          'productCount': _mockProducts.length,
          'averageRating': '4.9',
        },
      );
    }

    if (path == '/products') {
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: _filteredProducts(queryParameters),
      );
    }

    if (path == '/products/seller') {
      final sellerId = _mockCurrentUser['id']?.toString();
      final list = _mockProducts
          .where((p) => p['sellerId']?.toString() == sellerId)
          .toList();
      return _mockResponse<T>(path: path, method: 'GET', payload: list);
    }

    if (path == '/products/seller-stats') {
      final sellerId = _mockCurrentUser['id']?.toString();
      final sellerProducts = _mockProducts
          .where((p) => p['sellerId']?.toString() == sellerId)
          .toList();
      final sellerOrders = _mockOrders.where((o) {
        final items = o['items'] as List? ?? [];
        return items.any(
          (item) =>
              item is Map &&
              item['product'] is Map &&
              item['product']['sellerId']?.toString() == sellerId,
        );
      }).toList();
      final revenue = sellerOrders.fold<num>(
        0,
        (sum, o) => sum + ((o['totalAmount'] as num?) ?? 0),
      );
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: {
          'revenue': revenue,
          'orders': sellerOrders.length,
          'inquiries': 3,
          'products': sellerProducts.length,
        },
      );
    }

    if (path.startsWith('/products/')) {
      final id = path.replaceFirst('/products/', '');
      final product = _mockProducts
          .where((p) => p['id']?.toString() == id)
          .cast<Map<String, dynamic>?>()
          .firstWhere(
            (p) => p != null,
            orElse: () => _mockProducts.isNotEmpty ? _mockProducts.first : null,
          );
      if (product == null) {
        throw _mockError(path, 'GET', 'Product not found');
      }
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: Map<String, dynamic>.from(product),
      );
    }

    if (path == '/categories') {
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: [
          {'id': 'formal', 'name': 'Formal'},
          {'id': 'modern', 'name': 'Modern'},
          {'id': 'traditional', 'name': 'Traditional'},
        ],
      );
    }

    if (path == '/orders') {
      final uid = _mockCurrentUser['id']?.toString();
      final list = _mockOrders
          .where((o) => o['customerId']?.toString() == uid)
          .toList();
      return _mockResponse<T>(path: path, method: 'GET', payload: list);
    }

    if (path == '/orders/seller') {
      final sellerId = _mockCurrentUser['id']?.toString();
      final list = _mockOrders
          .where((o) {
            final items = o['items'] as List? ?? [];
            return items.any(
              (item) =>
                  item is Map &&
                  item['product'] is Map &&
                  item['product']['sellerId']?.toString() == sellerId,
            );
          })
          .map((o) => Map<String, dynamic>.from(o))
          .toList();
      return _mockResponse<T>(path: path, method: 'GET', payload: list);
    }

    if (path == '/users/profile') {
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: {'user': Map<String, dynamic>.from(_mockCurrentUser)},
      );
    }

    if (path == '/users/addresses') {
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: _mockAddresses,
      );
    }

    if (path == '/chat/conversations') {
      return _mockResponse<T>(
        path: path,
        method: 'GET',
        payload: _buildConversations(),
      );
    }

    if (path == '/notifications') {
      final list = _mockNotifications
          .map((n) => Map<String, dynamic>.from(n))
          .toList()
        ..sort((a, b) =>
            (b['createdAt']?.toString() ?? '').compareTo(a['createdAt']?.toString() ?? ''));
      return _mockResponse<T>(path: path, method: 'GET', payload: list);
    }

    if (path.startsWith('/chat/messages/')) {
      final otherUserId = path.replaceFirst('/chat/messages/', '');
      final msgs = _mockMessages[otherUserId] ?? [];
      return _mockResponse<T>(path: path, method: 'GET', payload: msgs);
    }

    throw _mockError(path, 'GET', 'Mock route not implemented: $path');
  }

  Future<Response<T>> post<T>(String path, {dynamic data}) async {
    if (!kMockMode) {
      return _dio.post<T>(path, data: data);
    }

    if (path == '/auth/login' || path == '/auth/register') {
      final email = (_valueFromData(data, 'email') ?? 'customer@lumbarong.mock')
          .toString();
      final name = (_valueFromData(data, 'name') ?? 'Mock User').toString();
      final role = email.contains('admin')
          ? 'admin'
          : email.contains('seller')
          ? 'seller'
          : 'customer';
      _mockCurrentUser = {
        'id': role == 'seller'
            ? 'seller-1'
            : role == 'admin'
            ? 'admin-1'
            : 'customer-1',
        'name': role == 'customer' ? name : 'Lumban Weaves Atelier',
        'email': email,
        'role': role,
        'mobileNumber': '09171234567',
        'isVerified': true,
        if (role == 'seller') 'shopName': 'Lumban Weaves Atelier',
      };
      return _mockResponse<T>(
        path: path,
        method: 'POST',
        payload: {
          'token': 'mock-token-${DateTime.now().millisecondsSinceEpoch}',
          'user': _mockCurrentUser,
          'message': path == '/auth/register'
              ? 'Registration successful'
              : 'Login successful',
        },
      );
    }

    if (path == '/orders') {
      final newId = 'order-${DateTime.now().millisecondsSinceEpoch}';
      final itemsRaw = _valueFromData(data, 'items');
      final List<Map<String, dynamic>> items = [];
      if (itemsRaw is List) {
        for (final item in itemsRaw) {
          if (item is Map) {
            final productId = item['product']?.toString() ?? '';
            final product = _mockProducts
                .where((p) => p['id']?.toString() == productId)
                .cast<Map<String, dynamic>?>()
                .firstWhere(
                  (p) => p != null,
                  orElse: () =>
                      _mockProducts.isNotEmpty ? _mockProducts.first : null,
                );
            items.add({
              'id': items.length + 1,
              'quantity':
                  int.tryParse(item['quantity']?.toString() ?? '1') ?? 1,
              'price': double.tryParse(item['price']?.toString() ?? '0') ?? 0,
              'orderId': newId,
              'productId': productId,
              'product':
                  product ?? Map<String, dynamic>.from(_mockProducts.first),
              'color': item['color']?.toString(),
              'design': item['design']?.toString(),
              'size': item['size']?.toString(),
            });
          }
        }
      }
      final order = {
        'id': newId,
        'totalAmount':
            double.tryParse(
              (_valueFromData(data, 'totalAmount') ?? '0').toString(),
            ) ??
            0,
        'paymentMethod': (_valueFromData(data, 'paymentMethod') ?? 'GCash')
            .toString(),
        'status': 'Pending',
        'shippingAddress': (_valueFromData(data, 'shippingAddress') ?? '')
            .toString(),
        'customerId': _mockCurrentUser['id']?.toString() ?? 'customer-1',
        'customer': {
          'id': _mockCurrentUser['id']?.toString() ?? 'customer-1',
          'name': _mockCurrentUser['name']?.toString() ?? 'Mock Customer',
        },
        'items': items,
        'createdAt': DateTime.now().toIso8601String(),
        'updatedAt': DateTime.now().toIso8601String(),
      };
      _mockOrders.insert(0, order);
      return _mockResponse<T>(
        path: path,
        method: 'POST',
        payload: order,
        statusCode: 201,
      );
    }

    if (path == '/chat/send') {
      final receiverId = (_valueFromData(data, 'receiverId') ?? '').toString();
      final content = (_valueFromData(data, 'content') ?? '').toString();
      final list = _mockMessages.putIfAbsent(receiverId, () => []);
      list.add({
        'id': 'msg-${DateTime.now().microsecondsSinceEpoch}',
        'senderId': _mockCurrentUser['id']?.toString() ?? 'customer-1',
        'content': content,
        'createdAt': DateTime.now().toIso8601String(),
      });
      return _mockResponse<T>(
        path: path,
        method: 'POST',
        payload: {'success': true},
      );
    }

    throw _mockError(path, 'POST', 'Mock route not implemented: $path');
  }

  Future<Response<T>> put<T>(String path, {dynamic data}) async {
    if (!kMockMode) {
      return _dio.put<T>(path, data: data);
    }

    if (path == '/users/profile') {
      final name = (_valueFromData(data, 'name') ?? _mockCurrentUser['name'])
          .toString();
      final mobile =
          (_valueFromData(data, 'mobileNumber') ??
                  _mockCurrentUser['mobileNumber'])
              .toString();
      _mockCurrentUser['name'] = name;
      _mockCurrentUser['mobileNumber'] = mobile;
      return _mockResponse<T>(
        path: path,
        method: 'PUT',
        payload: {'user': Map<String, dynamic>.from(_mockCurrentUser)},
      );
    }

    if (path == '/users/change-password') {
      return _mockResponse<T>(
        path: path,
        method: 'PUT',
        payload: {'success': true},
      );
    }

    if (path.startsWith('/notifications/') && path.endsWith('/read')) {
      final id = path.split('/')[2];
      final index = _mockNotifications.indexWhere((n) => n['id']?.toString() == id);
      if (index != -1) {
        _mockNotifications[index] = {
          ..._mockNotifications[index],
          'read': true,
        };
      }
      return _mockResponse<T>(
        path: path,
        method: 'PUT',
        payload: {'success': true},
      );
    }

    if (path.startsWith('/orders/') && path.endsWith('/status')) {
      final id = path.split('/')[2];
      final status = (_valueFromData(data, 'status') ?? 'Pending').toString();
      for (final o in _mockOrders) {
        if (o['id']?.toString() == id) {
          o['status'] = status;
          o['updatedAt'] = DateTime.now().toIso8601String();
          break;
        }
      }
      return _mockResponse<T>(
        path: path,
        method: 'PUT',
        payload: {'success': true},
      );
    }

    throw _mockError(path, 'PUT', 'Mock route not implemented: $path');
  }

  Future<Response<T>> delete<T>(String path) async {
    if (!kMockMode) {
      return _dio.delete<T>(path);
    }

    if (path.startsWith('/products/')) {
      final id = path.replaceFirst('/products/', '');
      _mockProducts.removeWhere((p) => p['id']?.toString() == id);
      return _mockResponse<T>(
        path: path,
        method: 'DELETE',
        payload: {'success': true},
      );
    }

    throw _mockError(path, 'DELETE', 'Mock route not implemented: $path');
  }

  Future<Response<T>> patch<T>(String path, {dynamic data}) async {
    if (!kMockMode) {
      return _dio.patch<T>(path, data: data);
    }

    if (path.startsWith('/orders/') && path.endsWith('/cancel')) {
      final id = path.split('/')[2];
      for (final o in _mockOrders) {
        if (o['id']?.toString() == id) {
          o['status'] = 'Cancelled';
          o['updatedAt'] = DateTime.now().toIso8601String();
          break;
        }
      }
      return _mockResponse<T>(
        path: path,
        method: 'PATCH',
        payload: {'success': true},
      );
    }

    if (path.startsWith('/products/') && path.endsWith('/stock')) {
      final id = path.split('/')[2];
      final newStock =
          int.tryParse((_valueFromData(data, 'stock') ?? '0').toString()) ?? 0;
      for (final p in _mockProducts) {
        if (p['id']?.toString() == id) {
          p['stock'] = newStock;
          break;
        }
      }
      return _mockResponse<T>(
        path: path,
        method: 'PATCH',
        payload: {'success': true},
      );
    }

    throw _mockError(path, 'PATCH', 'Mock route not implemented: $path');
  }

  Future<Response<T>> postMultipart<T>(
    String path, {
    required FormData data,
  }) async {
    if (!kMockMode) {
      return _dio.post<T>(path, data: data);
    }

    if (path == '/upload') {
      return _mockResponse<T>(
        path: path,
        method: 'POST',
        payload: {'url': 'https://picsum.photos/seed/uploaded/800/1000'},
      );
    }

    if (path == '/products') {
      final newId = 'product-${DateTime.now().millisecondsSinceEpoch}';
      final product = {
        'id': newId,
        'name': (_valueFromData(data, 'name') ?? 'New Product').toString(),
        'description': (_valueFromData(data, 'description') ?? '').toString(),
        'price':
            double.tryParse(
              (_valueFromData(data, 'price') ?? '0').toString(),
            ) ??
            0,
        'stock':
            int.tryParse((_valueFromData(data, 'stock') ?? '0').toString()) ??
            0,
        'category': (_valueFromData(data, 'category') ?? 'Formal').toString(),
        'sellerId': _mockCurrentUser['id']?.toString() ?? 'seller-1',
        'seller': {
          'id': _mockCurrentUser['id']?.toString() ?? 'seller-1',
          'name': _mockCurrentUser['name']?.toString() ?? 'Mock Seller',
          'shopName':
              _mockCurrentUser['shopName']?.toString() ?? 'Mock Seller Shop',
        },
        'images': [
          {'url': 'https://picsum.photos/seed/$newId/800/1000'},
        ],
      };
      _mockProducts.insert(0, product);
      return _mockResponse<T>(
        path: path,
        method: 'POST',
        payload: product,
        statusCode: 201,
      );
    }

    throw _mockError(path, 'POST', 'Mock route not implemented: $path');
  }

  Future<Response<T>> putMultipart<T>(
    String path, {
    required FormData data,
  }) async {
    if (!kMockMode) {
      return _dio.put<T>(path, data: data);
    }

    if (path.startsWith('/products/')) {
      final id = path.replaceFirst('/products/', '');
      for (final p in _mockProducts) {
        if (p['id']?.toString() == id) {
          p['name'] = (_valueFromData(data, 'name') ?? p['name']).toString();
          p['description'] =
              (_valueFromData(data, 'description') ?? p['description'])
                  .toString();
          p['price'] =
              double.tryParse(
                (_valueFromData(data, 'price') ?? p['price']).toString(),
              ) ??
              p['price'];
          p['stock'] =
              int.tryParse(
                (_valueFromData(data, 'stock') ?? p['stock']).toString(),
              ) ??
              p['stock'];
          p['category'] = (_valueFromData(data, 'category') ?? p['category'])
              .toString();
          return _mockResponse<T>(path: path, method: 'PUT', payload: p);
        }
      }
      throw _mockError(path, 'PUT', 'Product not found');
    }

    throw _mockError(path, 'PUT', 'Mock route not implemented: $path');
  }
}
