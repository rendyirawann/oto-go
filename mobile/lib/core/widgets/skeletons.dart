import 'package:flutter/material.dart';
import 'package:skeletonizer/skeletonizer.dart';

import '../theme/app_theme.dart';

/// Kerangka daftar kartu. Tinggi kartunya bisa diatur agar menyerupai kartu
/// asli, sehingga tidak ada lompatan tata letak saat data selesai dimuat.
class ListSkeleton extends StatelessWidget {
  const ListSkeleton({
    super.key,
    this.jumlah = 5,
    this.tinggi = 84,
    this.padding = const EdgeInsets.all(AppSpacing.lg),
  });

  final int jumlah;
  final double tinggi;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    return Skeletonizer(
      enabled: true,
      child: ListView.separated(
        padding: padding,
        physics: const NeverScrollableScrollPhysics(),
        shrinkWrap: true,
        itemCount: jumlah,
        separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.md),
        itemBuilder: (context, index) => KotakTiruan(tinggi: tinggi),
      ),
    );
  }
}

/// Kerangka baris-baris harga (label kiri, angka kanan).
class BarisSkeleton extends StatelessWidget {
  const BarisSkeleton({super.key, this.jumlah = 6});

  final int jumlah;

  @override
  Widget build(BuildContext context) {
    return Skeletonizer(
      enabled: true,
      child: Column(
        children: [
          for (var i = 0; i < jumlah; i++)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              child: Row(
                children: [
                  Expanded(child: Bone.text(words: 3, fontSize: 14)),
                  const SizedBox(width: AppSpacing.xl),
                  Bone.text(words: 1, fontSize: 14),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class KotakTiruan extends StatelessWidget {
  const KotakTiruan({super.key, required this.tinggi, this.lebar = double.infinity});

  final double tinggi;
  final double lebar;

  @override
  Widget build(BuildContext context) {
    return Bone(height: tinggi, width: lebar, borderRadius: AppRadius.card);
  }
}
