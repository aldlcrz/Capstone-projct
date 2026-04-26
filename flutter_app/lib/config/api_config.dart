import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;

/// --- API CONFIGURATION ---
/// This mirrors the logic in the web version's lib/api.js
const bool kMockMode = false;

String get _hostIp {
  if (kIsWeb) return 'localhost';
  try {
    // Android Emulator special IP to reach host machine
    if (Platform.isAndroid) return '10.0.2.2';
  } catch (_) {}
  return 'localhost';
}

/// API base URL
String get kApiBaseUrl => 'http://$_hostIp:5000/api/v1';

/// Socket.io connection URL
String get kSocketUrl => 'http://$_hostIp:5000';

/// Asset resolution logic to match the web version's lib/productImages.js
String resolveBackendAssetUrl(String? path) {
  if (path == null || path.trim().isEmpty) return '';
  
  final raw = path.trim();
  
  // If it's already a full URL, return it
  if (raw.startsWith('http')) return raw;
  
  // If it's a data URI or blob, return as is
  if (raw.startsWith('data:') || raw.startsWith('blob:')) return raw;
  
  // Normalize path
  final normalized = raw.replaceAll('\\', '/').replaceFirst(RegExp(r'^\.?/'), '');
  
  // Return resolved URL from backend
  return 'http://$_hostIp:5000/$normalized';
}
