class OrderModel {
  final String id;
  final int userId;
  final List<OrderItemModel> items;
  final double total;
  final String status; // 'pending', 'confirmed', 'shipping', 'delivered', 'cancelled'
  final String shippingAddress;
  final DateTime createdAt;

  OrderModel({
    required this.id,
    required this.userId,
    required this.items,
    required this.total,
    required this.status,
    required this.shippingAddress,
    required this.createdAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id']?.toString() ?? '',
      userId: json['userId'] ?? 0,
      items: (json['items'] as List?)?.map((e) => OrderItemModel.fromJson(e)).toList() ?? [],
      total: (json['total'] ?? 0).toDouble(),
      status: json['status'] ?? 'pending',
      shippingAddress: json['shippingAddress'] ?? '',
      createdAt: json['createdAt'] != null ? DateTime.parse(json['createdAt']) : DateTime.now(),
    );
  }
}

class OrderItemModel {
  final int productId;
  final String name;
  final int quantity;
  final double price;
  final String? size;
  final String? variation;
  final String? image;

  OrderItemModel({
    required this.productId,
    required this.name,
    required this.quantity,
    required this.price,
    this.size,
    this.variation,
    this.image,
  });

  factory OrderItemModel.fromJson(Map<String, dynamic> json) {
    return OrderItemModel(
      productId: json['productId'] ?? 0,
      name: json['name'] ?? 'Product',
      quantity: json['quantity'] ?? 0,
      price: (json['price'] ?? 0).toDouble(),
      size: json['size'],
      variation: json['variation'],
      image: json['image'],
    );
  }
}
