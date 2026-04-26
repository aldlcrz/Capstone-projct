import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  // Color Palette (Mirrored from web globals.css)
  static const Color primary = Color(0xFFC0420A); // Rust
  static const Color accent = Color(0xFFD4A96A);  // Gold/Brass
  static const Color background = Color(0xFFF9F6F2); // Cream
  static const Color surface = Colors.white;
  static const Color charcoal = Color(0xFF1C1209);
  static const Color textMuted = Color(0xFF9C8876);
  static const Color border = Color(0xFFF0EBE3);

  static ThemeData get theme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: primary,
        primary: primary,
        secondary: accent,
        surface: background,
        onPrimary: Colors.white,
        onSurface: charcoal,
      ),
      scaffoldBackgroundColor: background,
      
      // Typography
      textTheme: GoogleFonts.interTextTheme().copyWith(
        displayLarge: GoogleFonts.playfairDisplay(
          color: charcoal,
          fontWeight: FontWeight.bold,
        ),
        displayMedium: GoogleFonts.playfairDisplay(
          color: charcoal,
          fontWeight: FontWeight.w600,
        ),
        bodyLarge: GoogleFonts.inter(color: charcoal),
        bodyMedium: GoogleFonts.inter(color: charcoal),
      ),

      // Components
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        iconTheme: IconThemeData(color: charcoal),
      ),
      
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          textStyle: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 0.5),
        ),
      ),

      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: primary, width: 1.5),
        ),
      ),
    );
  }
}
