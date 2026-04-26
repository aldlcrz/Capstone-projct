import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';

import 'config/app_theme.dart';
import 'providers/auth_provider.dart';
import 'providers/cart_provider.dart';
import 'providers/notification_provider.dart';

// Screens (To be implemented)
import 'screens/auth/landing_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/customer/home_screen.dart';
import 'screens/customer/product_detail_screen.dart';
import 'screens/customer/cart_screen.dart';
import 'screens/customer/checkout_screen.dart';
import 'screens/customer/orders_screen.dart';
import 'screens/seller/seller_dashboard_screen.dart';
import 'screens/seller/seller_orders_screen.dart';
import 'screens/common/notifications_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const LumBarongApp());
}

class LumBarongApp extends StatelessWidget {
  const LumBarongApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CartProvider()),
        ChangeNotifierProvider(create: (_) => NotificationProvider()),
      ],
      child: const LumBarongRouter(),
    );
  }
}

class LumBarongRouter extends StatefulWidget {
  const LumBarongRouter({super.key});

  @override
  State<LumBarongRouter> createState() => _LumBarongRouterState();
}

class _LumBarongRouterState extends State<LumBarongRouter> {
  late final GoRouter _router;

  @override
  void initState() {
    super.initState();
    final auth = context.read<AuthProvider>();

    _router = GoRouter(
      initialLocation: '/',
      refreshListenable: auth,
      redirect: (context, state) {
        final auth = context.read<AuthProvider>();
        if (auth.loading) return null;

        final loc = state.uri.path;
        final isAuthRoute = loc == '/login' || loc == '/register';
        final isLanding = loc == '/';

        if (!auth.isLoggedIn) {
          if (isLanding || isAuthRoute) return null;
          return '/';
        }

        final user = auth.user!;
        if (isAuthRoute || isLanding) {
          if (user.role == 'admin') return '/admin/dashboard';
          if (user.role == 'seller') return '/seller/dashboard';
          return '/home';
        }

        return null;
      },
      routes: [
        GoRoute(path: '/', builder: (c, s) => const LandingScreen()),
        GoRoute(path: '/login', builder: (c, s) => const LoginScreen()),
        GoRoute(path: '/register', builder: (c, s) => const RegisterScreen()),
        GoRoute(path: '/home', builder: (c, s) => const HomeScreen()),
        GoRoute(
          path: '/products/:id',
          builder: (c, s) => ProductDetailScreen(productId: s.pathParameters['id']!),
        ),
        GoRoute(path: '/cart', builder: (c, s) => const CartScreen()),
        GoRoute(path: '/checkout', builder: (c, s) => const CheckoutScreen()),
        GoRoute(path: '/orders', builder: (c, s) => const OrdersScreen()),
        
        // Seller Routes
        GoRoute(path: '/seller/dashboard', builder: (c, s) => const SellerDashboardScreen()),
        GoRoute(path: '/seller/orders', builder: (c, s) => const SellerOrdersScreen()),
        
        GoRoute(path: '/notifications', builder: (c, s) => const NotificationsScreen()),
        
        // Add more routes as we build screens
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'LumBarong',
      theme: AppTheme.theme,
      routerConfig: _router,
      debugShowCheckedModeBanner: false,
    );
  }
}
