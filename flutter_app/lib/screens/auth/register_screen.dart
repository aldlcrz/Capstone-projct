import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:image_picker/image_picker.dart';
import '../../providers/auth_provider.dart';
import '../../config/app_theme.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  int _step = 1;
  bool _isLoading = false;

  // Form Data
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  String _role = 'customer';

  // Seller Data
  final _mobileController = TextEditingController();
  final _gcashController = TextEditingController();
  bool _isAdult = false;
  File? _indigencyFile;
  File? _validIdFile;
  File? _gcashQrFile;

  final _picker = ImagePicker();

  Future<void> _pickImage(String type) async {
    final pickedFile = await _picker.pickImage(source: ImageSource.gallery);
    if (pickedFile != null) {
      setState(() {
        if (type == 'cert') _indigencyFile = File(pickedFile.path);
        if (type == 'id') _validIdFile = File(pickedFile.path);
        if (type == 'qr') _gcashQrFile = File(pickedFile.path);
      });
    }
  }

  void _nextStep() {
    if (_step == 1) {
      if (_formKey.currentState!.validate()) {
        if (_passwordController.text != _confirmPasswordController.text) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Passwords do not match')),
          );
          return;
        }
        setState(() => _step = 2);
      }
    }
  }

  Future<void> _handleRegister() async {
    if (_role == 'seller' && (_indigencyFile == null || _validIdFile == null || _gcashQrFile == null || !_isAdult)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please provide all requirements and confirm your age.')),
      );
      return;
    }

    setState(() => _isLoading = true);
    try {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      
      // Prepare additional data for seller
      Map<String, String>? sellerData;
      Map<String, File>? files;

      if (_role == 'seller') {
        sellerData = {
          'mobileNumber': _mobileController.text,
          'gcashNumber': _gcashController.text,
          'isAdult': _isAdult.toString(),
        };
        files = {
          'indigencyCertificate': _indigencyFile!,
          'validId': _validIdFile!,
          'gcashQrCode': _gcashQrFile!,
        };
      }

      final success = await auth.register(
        name: _nameController.text,
        email: _emailController.text,
        password: _passwordController.text,
        role: _role,
        sellerData: sellerData,
        files: files,
      );

      if (success && mounted) {
        setState(() => _step = 3);
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Registration failed. Please check your information.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: AppTheme.charcoal),
          onPressed: () => _step > 1 ? setState(() => _step--) : Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          children: [
            if (_step < 3) ...[
              _buildHeader(),
              const SizedBox(height: 40),
              _buildProgress(),
              const SizedBox(height: 40),
              _step == 1 ? _buildStep1() : _buildStep2(),
            ] else
              _buildSuccess(),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      children: [
        const Text(
          'LumbaRong',
          style: TextStyle(
            fontFamily: 'Playfair Display',
            fontSize: 24,
            fontWeight: FontWeight.w900,
            fontStyle: FontStyle.italic,
            color: AppTheme.primary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'CREATE YOUR HERITAGE PROFILE',
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.bold,
            letterSpacing: 2,
            color: AppTheme.textMuted.withValues(alpha: 0.8),
          ),
        ),
      ],
    );
  }

  Widget _buildProgress() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        _stepCircle(1, _step >= 1),
        _stepLine(_step >= 2),
        _stepCircle(2, _step >= 2),
      ],
    );
  }

  Widget _stepCircle(int n, bool active) {
    return Container(
      width: 32,
      height: 32,
      decoration: BoxDecoration(
        color: active ? AppTheme.primary : Colors.white,
        shape: BoxShape.circle,
        border: Border.all(color: active ? AppTheme.primary : AppTheme.border),
        boxShadow: active ? [BoxShadow(color: AppTheme.primary.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 4))] : null,
      ),
      child: Center(
        child: Text(
          '$n',
          style: TextStyle(
            color: active ? Colors.white : AppTheme.textMuted,
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _stepLine(bool active) {
    return Container(
      width: 40,
      height: 2,
      margin: const EdgeInsets.symmetric(horizontal: 8),
      color: active ? AppTheme.primary : AppTheme.border,
    );
  }

  Widget _buildStep1() {
    return Form(
      key: _formKey,
      child: Column(
        children: [
          _buildTextField(
            controller: _nameController,
            label: 'FULL NAME',
            icon: LucideIcons.user,
            validator: (v) => v!.isEmpty ? 'Name is required' : null,
          ),
          const SizedBox(height: 20),
          _buildTextField(
            controller: _emailController,
            label: 'EMAIL ADDRESS',
            icon: LucideIcons.mail,
            keyboardType: TextInputType.emailAddress,
            validator: (v) => !v!.contains('@') ? 'Invalid email' : null,
          ),
          const SizedBox(height: 20),
          _buildTextField(
            controller: _passwordController,
            label: 'PASSWORD',
            icon: LucideIcons.lock,
            isPassword: true,
            maxLength: 20,
            validator: (v) => v!.length < 6 ? 'Too short' : null,
          ),
          const SizedBox(height: 20),
          _buildTextField(
            controller: _confirmPasswordController,
            label: 'CONFIRM PASSWORD',
            icon: LucideIcons.shieldCheck,
            isPassword: true,
            maxLength: 20,
          ),
          const SizedBox(height: 40),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: ElevatedButton(
              onPressed: _nextStep,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.charcoal,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
              ),
              child: const Text('CONTINUE', style: TextStyle(letterSpacing: 2, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStep2() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'I WANT TO JOIN AS...',
          style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1, color: AppTheme.textMuted),
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(child: _roleButton('customer', 'USER', LucideIcons.user)),
            const SizedBox(width: 16),
            Expanded(child: _roleButton('seller', 'SELLER', LucideIcons.shoppingBag)),
          ],
        ),
        if (_role == 'seller') ...[
          const SizedBox(height: 32),
          const Text(
            'SELLER VERIFICATION',
            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, letterSpacing: 1, color: AppTheme.textMuted),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _buildTextField(controller: _mobileController, label: 'MOBILE', hint: '09xx...')),
              const SizedBox(width: 16),
              Expanded(child: _buildTextField(controller: _gcashController, label: 'GCASH', hint: '09xx...')),
            ],
          ),
          const SizedBox(height: 20),
          const Text('Requirements', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _filePicker('Indigency', _indigencyFile, () => _pickImage('cert'))),
              const SizedBox(width: 8),
              Expanded(child: _filePicker('Valid ID', _validIdFile, () => _pickImage('id'))),
              const SizedBox(width: 8),
              Expanded(child: _filePicker('GCash QR', _gcashQrFile, () => _pickImage('qr'))),
            ],
          ),
          const SizedBox(height: 20),
          CheckboxListTile(
            value: _isAdult,
            onChanged: (v) => setState(() => _isAdult = v!),
            title: const Text('I am at least 18 years old', style: TextStyle(fontSize: 12)),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            activeColor: AppTheme.primary,
          ),
        ],
        const SizedBox(height: 40),
        SizedBox(
          width: double.infinity,
          height: 56,
          child: ElevatedButton(
            onPressed: _isLoading ? null : _handleRegister,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primary,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
            ),
            child: _isLoading 
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Text('SIGN UP', style: TextStyle(letterSpacing: 2, fontWeight: FontWeight.bold)),
          ),
        ),
      ],
    );
  }

  Widget _roleButton(String role, String label, IconData icon) {
    bool active = _role == role;
    return GestureDetector(
      onTap: () => setState(() => _role = role),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: active ? Colors.white : Colors.white.withValues(alpha: 0.5),
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: active ? AppTheme.primary : AppTheme.border, width: 2),
          boxShadow: active ? [BoxShadow(color: AppTheme.primary.withValues(alpha: 0.1), blurRadius: 10, offset: const Offset(0, 4))] : null,
        ),
        child: Column(
          children: [
            Icon(icon, color: active ? AppTheme.primary : AppTheme.textMuted),
            const SizedBox(height: 8),
            Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: active ? AppTheme.charcoal : AppTheme.textMuted)),
          ],
        ),
      ),
    );
  }

  Widget _filePicker(String label, File? file, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 80,
        decoration: BoxDecoration(
          color: file != null ? AppTheme.primary.withValues(alpha: 0.05) : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: file != null ? AppTheme.primary : AppTheme.border, style: BorderStyle.solid),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(file != null ? LucideIcons.checkCircle : LucideIcons.upload, size: 16, color: file != null ? AppTheme.primary : AppTheme.textMuted),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(fontSize: 8, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
          ],
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    IconData? icon,
    String? hint,
    bool isPassword = false,
    TextInputType? keyboardType,
    int? maxLength,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 16, bottom: 8),
          child: Text(label, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppTheme.textMuted)),
        ),
        TextFormField(
          controller: controller,
          obscureText: isPassword,
          keyboardType: keyboardType,
          maxLength: maxLength,
          validator: validator,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: icon != null ? Icon(icon, size: 18) : null,
            counterText: '',
          ),
        ),
      ],
    );
  }

  Widget _buildSuccess() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const SizedBox(height: 60),
        Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(color: Colors.green.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(24)),
          child: const Icon(LucideIcons.checkCircle, size: 40, color: Colors.green),
        ),
        const SizedBox(height: 32),
        const Text('Certified!', style: TextStyle(fontFamily: 'Playfair Display', fontSize: 28, fontWeight: FontWeight.bold)),
        const SizedBox(height: 16),
        Text(
          _role == 'seller' 
              ? 'Your heritage application is being reviewed. We will notify you within 24 hours.'
              : 'Welcome to LumbaRong. You may now explore our curated collection.',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppTheme.textMuted, fontStyle: FontStyle.italic),
        ),
        const SizedBox(height: 48),
        SizedBox(
          width: double.infinity,
          height: 56,
          child: ElevatedButton(
            onPressed: () => Navigator.pushReplacementNamed(context, '/'),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.charcoal, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28))),
            child: const Text('BEGIN JOURNEY', style: TextStyle(letterSpacing: 2, fontWeight: FontWeight.bold)),
          ),
        ),
      ],
    );
  }
}
