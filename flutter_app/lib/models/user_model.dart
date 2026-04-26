class UserModel {
  final int id;
  final String fullName;
  final String email;
  final String role; // 'admin', 'seller', 'customer'
  final String? profilePhoto;
  final String? phoneNumber;
  final String? address;
  final String status; // 'active', 'suspended', 'blocked'

  UserModel({
    required this.id,
    required this.fullName,
    required this.email,
    required this.role,
    this.profilePhoto,
    this.phoneNumber,
    this.address,
    required this.status,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? 0,
      fullName: json['fullName'] ?? '',
      email: json['email'] ?? '',
      role: json['role'] ?? 'customer',
      profilePhoto: json['profilePhoto'],
      phoneNumber: json['phoneNumber'],
      address: json['address'],
      status: json['status'] ?? 'active',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'fullName': fullName,
      'email': email,
      'role': role,
      'profilePhoto': profilePhoto,
      'phoneNumber': phoneNumber,
      'address': address,
      'status': status,
    };
  }
}
