import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../config/app_theme.dart';

class LandingScreen extends StatelessWidget {
  const LandingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: const BoxDecoration(
          color: AppTheme.background,
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Spacer(),
            // Logo / Branding
            Text(
              'LumBarong',
              style: Theme.of(context).textTheme.displayLarge?.copyWith(
                color: AppTheme.primary,
                fontSize: 48,
                letterSpacing: 2,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Authentic Filipino Artisanry',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: AppTheme.textMuted,
                letterSpacing: 4,
                fontWeight: FontWeight.w600,
              ),
            ),
            const Spacer(),
            // Action Buttons
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40),
              child: Column(
                children: [
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => context.push('/login'),
                      child: const Text('SIGN IN'),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton(
                      onPressed: () => context.push('/register'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        side: const BorderSide(color: AppTheme.primary),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      child: const Text(
                        'CREATE ACCOUNT',
                        style: TextStyle(color: AppTheme.primary, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 48),
          ],
        ),
      ),
    );
  }
}
