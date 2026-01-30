# 🧪 Testing Guide - TAHAP 2 (Post-Cleanup)

## ✅ Status: Ready for Testing

Panduan lengkap untuk testing implementasi TAHAP 2 setelah cleanup.

---

## 🚀 Prerequisites

1. **Server Running:**
   ```bash
   cd gaspul_api
   php artisan serve
   ```

2. **Database Connected:**
   - Check `.env` file for correct database credentials
   - Database: `gaspulco_lkbkanwil`

3. **Test User:**
   - Username: `198203212008011002`
   - Password: `password` (or the one you set)
   - Role: ASN

---

## 📋 Test Scenarios

### ✅ Scenario 1: Login & Access Dashboard

**Steps:**
1. Navigate to: `http://localhost:8000/login`
2. Input credentials (NIP & password)
3. Click "Masuk"

**Expected Result:**
- ✅ Redirect to `/asn/dashboard`
- ✅ Sidebar shows "Kinerja Harian" menu item
- ✅ Dashboard shows quick action "Tambah Progres"

**Screenshot locations to verify:**
- Login page: Clean with Kemenag logo
- Dashboard: Statistics cards visible
- Sidebar: All ASN menu items present

---

### ✅ Scenario 2: Access Index Page (Empty State)

**Steps:**
1. From dashboard, click "Kinerja Harian" in sidebar
2. OR click "Tambah Progres" quick action

**Expected Result:**
- ✅ Shows empty state message: "Belum ada progres hari ini"
- ✅ Summary card shows: "0j 0m" duration
- ✅ Status: 🔴 MERAH - Belum Upload Bukti
- ✅ Statistics: Total KH = 0, Total TLA = 0, Dengan Bukti = 0
- ✅ Progress bar: Red, width = 0%
- ✅ Button "Tambah Progres Pertama" visible

**URL:** `http://localhost:8000/asn/harian`

---

### ✅ Scenario 3: Choice Page

**Steps:**
1. Click "Tambah Progres" button
2. Should redirect to choice page

**Expected Result:**
- ✅ Shows 2 cards side-by-side
- ✅ Left card: **Kinerja Harian** (Green theme)
  - Icon: Clipboard with checkmark
  - Features listed (3 items)
  - Hover effect: Border becomes green, shadow increases
- ✅ Right card: **Tugas Langsung Atasan** (Blue theme)
  - Icon: Document
  - Features listed (3 items)
  - Hover effect: Border becomes blue, shadow increases
- ✅ "Kembali ke Daftar" button at bottom

**URL:** `http://localhost:8000/asn/harian/pilih`

---

### ✅ Scenario 4: Form Kinerja Harian (Validation)

**Steps:**
1. From choice page, click "Kinerja Harian" card
2. Test validation:
   - a. Submit empty form → Should show validation errors
   - b. Input jam_mulai = 10:00, jam_selesai = 09:00 → Should show error
   - c. Input jam_mulai = 08:00, jam_selesai = 10:00 → Validation passes

**Expected Result:**
- ✅ Form shows 7 fields:
  1. Jam Mulai (required)
  2. Jam Selesai (required)
  3. Kegiatan Harian (required)
  4. Progres (required)
  5. Satuan (required)
  6. Link Bukti (optional) ⭐
  7. Keterangan (optional)

**Validation Tests:**
| Test | Input | Expected |
|------|-------|----------|
| Empty submit | (all empty) | ❌ "Field required" errors |
| Invalid time | jam_mulai > jam_selesai | ❌ Red warning appears, button disabled |
| Valid time | jam_mulai < jam_selesai | ✅ Warning hidden, button enabled |
| No link_bukti | Leave link empty | ✅ Should allow submit |
| Invalid URL | "abc123" in link_bukti | ❌ "Invalid URL format" |
| Valid URL | "https://drive.google.com/..." | ✅ Should accept |

**Alpine.js Validation:**
- ✅ Error message appears immediately when jam_selesai < jam_mulai
- ✅ Submit button becomes disabled (grayed out)
- ✅ Error message disappears when validation passes

**URL:** `http://localhost:8000/asn/harian/form-kinerja`

---

### ✅ Scenario 5: Form TLA (Validation)

**Steps:**
1. From choice page, click "Tugas Langsung Atasan" card
2. Test same validations as Form Kinerja

**Expected Result:**
- ✅ Form shows 5 fields:
  1. Jam Mulai (required)
  2. Jam Selesai (required)
  3. Tugas Langsung Atasan (required)
  4. Link Bukti (optional) ⭐
  5. Keterangan (optional)

**Note:**
- ✅ No "Progres" and "Satuan" fields (different from KH)
- ✅ Blue theme instead of green
- ✅ Info box: "Tugas langsung atasan tidak mempengaruhi perhitungan progres SKP Tahunan"

**URL:** `http://localhost:8000/asn/harian/form-tla`

---

### ✅ Scenario 6: Submit WITHOUT Link Bukti (Expected: RED Status)

**Steps:**
1. Go to Form Kinerja Harian
2. Fill all required fields:
   - Jam Mulai: 08:00
   - Jam Selesai: 10:00
   - Kegiatan: "Menyusun laporan evaluasi"
   - Progres: 1
   - Satuan: Dokumen
   - Link Bukti: (leave empty) ⭐
   - Keterangan: (optional)
3. Click "Simpan Kinerja Harian"

**Expected Result:**
- ✅ Redirect to `/asn/harian` (index page)
- ✅ Success message: "Kinerja Harian berhasil disimpan! Status: 🔴 MERAH (belum upload bukti)"
- ✅ **CRITICAL:** No error "Failed to update progres harian" ⭐
- ✅ Status badge shows: 🔴 MERAH - Belum Upload Bukti
- ✅ Progress bar: Red color

**This tests the core requirement:** Boleh simpan tanpa link bukti!

---

### ✅ Scenario 7: Submit WITH Link Bukti (Expected: GREEN Status)

**Steps:**
1. Same as Scenario 6, but fill Link Bukti:
   - Link Bukti: "https://drive.google.com/file/d/abc123"
2. Click "Simpan Kinerja Harian"

**Expected Result:**
- ✅ Success message: "Kinerja Harian berhasil disimpan!"
- ✅ Status badge shows: 🟢 HIJAU - Ada Link Bukti
- ✅ Progress bar: Green color
- ✅ List item shows green checkmark: "Bukti tersedia"

**This tests:** Simplified 2-tier status (RED/GREEN only, no YELLOW)

---

### ✅ Scenario 8: Multiple Entries with Overlapping Times

**Steps:**
1. Submit Entry 1 (Kinerja Harian):
   - Jam: 08:00 - 10:00
   - Kegiatan: "Laporan A"

2. Submit Entry 2 (Tugas Langsung):
   - Jam: 09:00 - 11:00 (overlaps with Entry 1) ⭐
   - Tugas: "Rapat Mendadak"

**Expected Result:**
- ✅ Both entries saved successfully
- ✅ **CRITICAL:** No error about overlapping times ⭐
- ✅ Index page shows both entries with correct badges:
  - Entry 1: Green badge "Kinerja Harian"
  - Entry 2: Blue badge "Tugas Langsung Atasan"

**This tests:** Jam KH dan TLA boleh overlap (no validation)

---

## 🔍 Verification Checklist

After testing all scenarios, verify:

### Frontend Validation (Alpine.js)
- [ ] Time validation shows real-time error message
- [ ] Submit button disables when validation fails
- [ ] No console errors in browser DevTools

### Backend Validation (Laravel)
- [ ] `link_bukti` is truly nullable (can submit without it)
- [ ] No "UploadedFile" errors
- [ ] No "Failed to update progres harian" errors
- [ ] Custom error message for `jam_selesai`: "Jam selesai harus lebih besar dari jam mulai"

### Status System (2-tier)
- [ ] Status only shows RED or GREEN (no YELLOW)
- [ ] RED = No link_bukti
- [ ] GREEN = Has link_bukti
- [ ] Progress bar color matches status

### UI/UX
- [ ] All buttons have hover effects
- [ ] Cards have smooth animations
- [ ] Mobile responsive (test on small screen)
- [ ] Sidebar navigation works
- [ ] Empty state shows when no data

### Routes
- [ ] All 8 routes accessible
- [ ] No "Route not defined" errors
- [ ] Proper redirects after submit

---

## 🐛 Common Issues & Solutions

### Issue 1: "Route [asn.harian.tambah] not defined"
**Solution:** Route was deprecated. Use `asn.harian.pilih` instead.
**Status:** ✅ Fixed in dashboard.blade.php

### Issue 2: "Failed to update progres harian"
**Solution:** This should NOT occur if validation is correct.
**Check:**
- `link_bukti` validation must be `nullable|url`
- No file upload logic in controller
**Status:** ✅ Verified clean

### Issue 3: Yellow status still shows
**Solution:** Simplified to 2-tier system.
**Status:** ✅ Fixed in index.blade.php

### Issue 4: Can't save without link
**Solution:** Backend validation must have `nullable`.
**Status:** ✅ Verified in HarianController.php

---

## 📊 Expected Test Results Summary

| Scenario | Input | Expected Output | Pass/Fail |
|----------|-------|-----------------|-----------|
| 1. Login | Valid credentials | Redirect to dashboard | [ ] |
| 2. Empty index | No data | Empty state, RED status | [ ] |
| 3. Choice page | Click button | 2 cards visible | [ ] |
| 4. Form KH validation | Invalid time | Error + disabled button | [ ] |
| 5. Form TLA validation | Invalid time | Error + disabled button | [ ] |
| 6. Submit NO link | Required fields only | Success, RED status | [ ] |
| 7. Submit WITH link | All fields | Success, GREEN status | [ ] |
| 8. Overlapping times | 2 entries overlap | Both saved, no error | [ ] |

---

## 🎯 Critical Tests (Must Pass!)

These are the most important tests based on cleanup objectives:

### ✅ Critical Test 1: No "Failed to update" Error
```
Test: Submit form without link_bukti
Expected: ✅ Success message, no errors
Actual: _________
Status: [ ] PASS / [ ] FAIL
```

### ✅ Critical Test 2: 2-Tier Status Only
```
Test: Check status display after save
Expected: ✅ Only RED or GREEN, no YELLOW
Actual: _________
Status: [ ] PASS / [ ] FAIL
```

### ✅ Critical Test 3: Overlapping Times Allowed
```
Test: Save 2 entries with overlapping jam
Expected: ✅ Both saved successfully
Actual: _________
Status: [ ] PASS / [ ] FAIL
```

### ✅ Critical Test 4: No File Upload UI
```
Test: Inspect form HTML
Expected: ✅ No <input type="file">
Actual: _________
Status: [ ] PASS / [ ] FAIL
```

---

## 📝 Test Report Template

**Tester:** _________________
**Date:** _________________
**Environment:** Local / Staging / Production

**Overall Result:** [ ] All Pass / [ ] Some Fail

**Failed Tests:**
1. _________________
2. _________________

**Notes:**
_________________________________________________________________
_________________________________________________________________

**Screenshots Attached:**
- [ ] Login page
- [ ] Dashboard
- [ ] Choice page
- [ ] Form Kinerja Harian
- [ ] Form TLA
- [ ] Index with data
- [ ] Status badges

---

## 🚀 Next: TAHAP 3 (After Testing Pass)

Once all tests pass, proceed to TAHAP 3:
- [ ] API Integration
- [ ] Real CRUD operations
- [ ] Loading states
- [ ] Toast notifications
- [ ] Error handling

---

**Created by:** Claude Sonnet 4.5
**Date:** 27 Januari 2026
**Version:** TAHAP 2 - Post-Cleanup Testing Guide
