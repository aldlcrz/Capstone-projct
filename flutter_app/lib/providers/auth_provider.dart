import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import '../services/api_client.dart';
import '../services/socket_service.dart';
import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';

class AuthProvider extends ChangeNotifier {
  UserModel? _user;
  bool _loading = true;

  UserModel? get user => _user;
  bool get loading => _loading;
  bool get isLoggedIn => _user != null;

  AuthProvider() {
    _init();
  }

  Future<void> _init() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString('user_data');
    if (userJson != null) {
      try {
        _user = UserModel.fromJson(jsonDecode(userJson));
        SocketService().connect(_user!.id);
      } catch (_) {
        await logout();
      }
    }
    _loading = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password, {bool rememberMe = false}) async {
    try {
      final res = await ApiClient().post('/auth/login', data: {
        'email': email,
        'password': password,
        'rememberMe': rememberMe,
      });

      if (res.statusCode == 200) {
        final data = res.data;
        final token = data['token'];
        final userMap = data['user'];

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        await prefs.setString('user_data', jsonEncode(userMap));

        _user = UserModel.fromJson(userMap);
        SocketService().connect(_user!.id);
        notifyListeners();
        return true;
      }
    } catch (e) {
      debugPrint('Login error: $e');
    }
    return false;
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String role,
    Map<String, String>? sellerData,
    Map<String, File>? files,
  }) async {
    try {
      Map<String, dynamic> data = {
        'name': name,
        'email': email,
        'password': password,
        'role': role,
      };

      if (sellerData != null) {
        data.addAll(sellerData);
      }

      FormData formData = FormData.fromMap(data);

      if (files != null) {
        for (var entry in files.entries) {
          formData.files.add(MapEntry(
            entry.key,
            await MultipartFile.fromFile(entry.value.path, filename: entry.value.path.split('/').last),
          ));
        }
      }

      final res = await ApiClient().post(
        '/auth/register',
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );

      if (res.statusCode == 201) {
        return true;
      }
    } catch (e) {
      debugPrint('Registration error: $e');
    }
    return false;
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_data');
    SocketService().disconnect();
    _user = null;
    notifyListeners();
  }
}
