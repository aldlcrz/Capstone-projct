import 'package:flutter/foundation.dart';

/// Enable this to run the app with local mock API responses.
/// Set to false when backend is available.
const bool kMockMode = false;

/// --- API CONFIGURATION ---
/// Change the [hostIp] below to match your machine's IP or use localhost
/// For local development: 'localhost' or '127.0.0.1'
/// For physical device on same network: your machine's IP address (e.g., '192.168.1.5')
/// For Android emulator with localhost backend: '10.0.2.2'
const String _hostIp = 'localhost';

/// API base URL Configuration
///
/// Automatically detects environment and sets appropriate URL.
String get kApiBaseUrl {
  // Version: 1.0.5 (Real Backend Integration)
  if (kIsWeb) {
    return 'http://localhost:5000/api/v1';
  }

  return 'http://$_hostIp:5000/api/v1';
}

/// Socket.io connection URL (without /api/v1 path)
String get kSocketUrl {
  if (kIsWeb) {
    return 'http://localhost:5000';
  }
  return 'http://$_hostIp:5000';
}
