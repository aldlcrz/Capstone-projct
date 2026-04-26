import '../config/api_config.dart';

class ProductModel {
  final int id;
  final String name;
  final double price;
  final String? description;
  final String? category;
  final String? artisan;
  final int stock;
  final String? variation;
  final List<ProductImage> images;
  final List<dynamic> sizes; // Can be List<String> or List<Map<String, dynamic>>
  final double rating;
  final int reviewCount;
  final int sellerId;

  ProductModel({
    required this.id,
    required this.name,
    required this.price,
    this.description,
    this.category,
    this.artisan,
    required this.stock,
    this.variation,
    required this.images,
    required this.sizes,
    this.rating = 0.0,
    this.reviewCount = 0,
    required this.sellerId,
  });

  String get mainImageUrl {
    if (images.isNotEmpty) return images.first.url;
    return '';
  }

  int getStockForSize(String? sizeName) {
    if (sizeName == null || sizes.isEmpty) return stock;
    final sizeInfo = sizes.firstWhere(
      (s) => s is Map && (s['size'] == sizeName || s['name'] == sizeName),
      orElse: () => null,
    );
    if (sizeInfo != null && sizeInfo is Map) {
      return sizeInfo['stock'] ?? 0;
    }
    return stock;
  }

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    // Handle complex image JSON (could be string or list)
    List<ProductImage> parsedImages = [];
    final rawImages = json['image'];
    if (rawImages is String) {
      // Logic from web: split by comma or parse as JSON array
      if (rawImages.startsWith('[')) {
        // Assume JSON array of objects or strings
      } else {
        parsedImages.add(ProductImage(url: resolveBackendAssetUrl(rawImages), variation: 'Original'));
      }
    } else if (rawImages is List) {
      for (var item in rawImages) {
        if (item is Map) {
          parsedImages.add(ProductImage(
            url: resolveBackendAssetUrl(item['url'] ?? item['path']),
            variation: item['variation'] ?? 'Original',
          ));
        } else if (item is String) {
          parsedImages.add(ProductImage(url: resolveBackendAssetUrl(item), variation: 'Original'));
        }
      }
    }

    return ProductModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      price: (json['price'] ?? 0).toDouble(),
      description: json['description'],
      category: json['category'],
      artisan: json['artisan'],
      stock: json['stock'] ?? 0,
      variation: json['variation'],
      images: parsedImages,
      sizes: (json['sizes'] as List?) ?? [],
      rating: (json['rating'] ?? 0).toDouble(),
      reviewCount: json['reviewCount'] ?? 0,
      sellerId: json['sellerId'] ?? 0,
    );
  }
}

class ProductImage {
  final String url;
  final String variation;

  ProductImage({required this.url, required this.variation});
}
