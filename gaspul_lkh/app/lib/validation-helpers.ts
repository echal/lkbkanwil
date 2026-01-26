import {
  IndikatorTahunan,
  IndikatorTriwulan,
  RencanaKerjaBulanan,
  RencanaKerjaHarian,
} from "./types";

/**
 * Interface untuk hasil validasi
 */
export interface ValidationResult {
  isValid: boolean;
  message: string;
  details?: {
    targetParent: number;
    currentTotal: number;
    sisaTarget: number;
    inputValue: number;
  };
}

/**
 * Validasi: Total Realisasi Triwulan ≤ Target Tahunan
 */
export function validateTriwulanTarget(
  indikatorTahunan: IndikatorTahunan,
  allTriwulan: IndikatorTriwulan[],
  newRealisasi: number,
  currentTriwulanId?: string
): ValidationResult {
  // Hitung total realisasi triwulan (kecuali yang sedang diedit)
  const totalRealisasiTriwulan = allTriwulan
    .filter((tw) => tw.indikatorTahunanId === indikatorTahunan.id)
    .filter((tw) => tw.id !== currentTriwulanId)
    .reduce((sum, tw) => sum + tw.realisasiTriwulan, 0);

  const newTotal = totalRealisasiTriwulan + newRealisasi;
  const sisaTarget = indikatorTahunan.targetTahunan - totalRealisasiTriwulan;

  if (newTotal > indikatorTahunan.targetTahunan) {
    return {
      isValid: false,
      message: `Total realisasi triwulan (${newTotal}) melebihi target tahunan (${indikatorTahunan.targetTahunan}). Sisa target yang tersedia: ${sisaTarget}`,
      details: {
        targetParent: indikatorTahunan.targetTahunan,
        currentTotal: totalRealisasiTriwulan,
        sisaTarget,
        inputValue: newRealisasi,
      },
    };
  }

  return {
    isValid: true,
    message: "Validasi berhasil",
    details: {
      targetParent: indikatorTahunan.targetTahunan,
      currentTotal: totalRealisasiTriwulan,
      sisaTarget,
      inputValue: newRealisasi,
    },
  };
}

/**
 * Validasi: Total Realisasi Bulanan ≤ Target Triwulan
 */
export function validateBulananTarget(
  indikatorTriwulan: IndikatorTriwulan,
  allBulanan: RencanaKerjaBulanan[],
  newRealisasi: number,
  currentBulananId?: string
): ValidationResult {
  // Hitung total realisasi bulanan (kecuali yang sedang diedit)
  const totalRealisasiBulanan = allBulanan
    .filter((bln) => bln.indikatorTriwulanId === indikatorTriwulan.id)
    .filter((bln) => bln.id !== currentBulananId)
    .reduce((sum, bln) => sum + bln.realisasiBulanan, 0);

  const newTotal = totalRealisasiBulanan + newRealisasi;
  const sisaTarget = indikatorTriwulan.targetTriwulan - totalRealisasiBulanan;

  if (newTotal > indikatorTriwulan.targetTriwulan) {
    return {
      isValid: false,
      message: `Total realisasi bulanan (${newTotal}) melebihi target triwulan (${indikatorTriwulan.targetTriwulan}). Sisa target yang tersedia: ${sisaTarget}`,
      details: {
        targetParent: indikatorTriwulan.targetTriwulan,
        currentTotal: totalRealisasiBulanan,
        sisaTarget,
        inputValue: newRealisasi,
      },
    };
  }

  return {
    isValid: true,
    message: "Validasi berhasil",
    details: {
      targetParent: indikatorTriwulan.targetTriwulan,
      currentTotal: totalRealisasiBulanan,
      sisaTarget,
      inputValue: newRealisasi,
    },
  };
}

/**
 * Validasi: Total Kuantitas Output Harian ≤ Target Bulanan
 */
export function validateHarianTarget(
  rencanaBulanan: RencanaKerjaBulanan,
  allHarian: RencanaKerjaHarian[],
  newKuantitas: number,
  currentHarianId?: string
): ValidationResult {
  // Hitung total kuantitas output harian (kecuali yang sedang diedit)
  const totalKuantitasHarian = allHarian
    .filter((hrn) => hrn.rencanaKerjaBulananId === rencanaBulanan.id)
    .filter((hrn) => hrn.id !== currentHarianId)
    .reduce((sum, hrn) => sum + hrn.kuantitasOutput, 0);

  const newTotal = totalKuantitasHarian + newKuantitas;
  const sisaTarget = rencanaBulanan.targetBulanan - totalKuantitasHarian;

  if (newTotal > rencanaBulanan.targetBulanan) {
    return {
      isValid: false,
      message: `Total output harian (${newTotal}) melebihi target bulanan (${rencanaBulanan.targetBulanan}). Sisa target yang tersedia: ${sisaTarget}`,
      details: {
        targetParent: rencanaBulanan.targetBulanan,
        currentTotal: totalKuantitasHarian,
        sisaTarget,
        inputValue: newKuantitas,
      },
    };
  }

  return {
    isValid: true,
    message: "Validasi berhasil",
    details: {
      targetParent: rencanaBulanan.targetBulanan,
      currentTotal: totalKuantitasHarian,
      sisaTarget,
      inputValue: newKuantitas,
    },
  };
}

/**
 * Helper: Hitung total realisasi triwulan dari semua bulan
 */
export function calculateTotalRealisasiTriwulan(
  indikatorTriwulanId: string,
  allBulanan: RencanaKerjaBulanan[]
): number {
  return allBulanan
    .filter((bln) => bln.indikatorTriwulanId === indikatorTriwulanId)
    .reduce((sum, bln) => sum + bln.realisasiBulanan, 0);
}

/**
 * Helper: Hitung total realisasi bulanan dari semua hari
 */
export function calculateTotalRealisasiBulanan(
  rencanaBulananId: string,
  allHarian: RencanaKerjaHarian[]
): number {
  return allHarian
    .filter((hrn) => hrn.rencanaKerjaBulananId === rencanaBulananId)
    .reduce((sum, hrn) => sum + hrn.kuantitasOutput, 0);
}

/**
 * Helper: Format pesan error yang manusiawi
 */
export function formatValidationMessage(result: ValidationResult): string {
  if (result.isValid) {
    return result.message;
  }

  if (result.details) {
    const { targetParent, currentTotal, sisaTarget, inputValue } =
      result.details;
    return `❌ Input Anda (${inputValue}) terlalu besar!\n\n` +
      `Target Total: ${targetParent}\n` +
      `Sudah Terisi: ${currentTotal}\n` +
      `Sisa Tersedia: ${sisaTarget}\n\n` +
      `Silakan input maksimal ${sisaTarget} untuk melanjutkan.`;
  }

  return result.message;
}
