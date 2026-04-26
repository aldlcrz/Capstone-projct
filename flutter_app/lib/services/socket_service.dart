import 'package:socket_io_client/socket_io_client.dart' as io;
import '../config/api_config.dart';
import 'package:flutter/foundation.dart';

class SocketService extends ChangeNotifier {
  static final SocketService _instance = SocketService._internal();
  factory SocketService() => _instance;
  SocketService._internal();

  io.Socket? _socket;
  bool _isConnected = false;

  bool get isConnected => _isConnected;
  io.Socket? get socket => _socket;

  void connect(int userId) {
    if (_socket != null && _socket!.connected) return;

    _socket = io.io(kSocketUrl, <String, dynamic>{
      'transports': ['websocket'],
      'autoConnect': false,
      'query': {'userId': userId},
    });

    _socket!.connect();

    _socket!.onConnect((_) {
      _isConnected = true;
      debugPrint('Socket connected: ${_socket!.id}');
      notifyListeners();
    });

    _socket!.onDisconnect((_) {
      _isConnected = false;
      debugPrint('Socket disconnected');
      notifyListeners();
    });

    _socket!.onConnectError((err) => debugPrint('Socket Connect Error: $err'));
    _socket!.onError((err) => debugPrint('Socket Error: $err'));
  }

  void disconnect() {
    _socket?.disconnect();
    _isConnected = false;
    notifyListeners();
  }

  void emit(String event, dynamic data) {
    _socket?.emit(event, data);
  }

  void on(String event, Function(dynamic) handler) {
    _socket?.on(event, handler);
  }

  void off(String event) {
    _socket?.off(event);
  }
}
