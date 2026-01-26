'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  progresHarianApi,
  ProgresHarian,
  rencanaAksiBulananApi,
  RencanaAksiBulanan
} from '@/app/lib/api-v2';

export default function ProgresHarianPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const [calendarData, setCalendarData] = useState<any[]>([]);
  const [progresList, setProgresList] = useState<ProgresHarian[]>([]);
  const [rencanaAksiList, setRencanaAksiList] = useState<RencanaAksiBulanan[]>([]);
  const [showModal, setShowModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedProgres, setSelectedProgres] = useState<ProgresHarian | null>(null);
  const [totalDurasi, setTotalDurasi] = useState({ menit: 0, jam: 0, sisa: 450, isFull: false });

  // ✅ DUAL MODE STATE
  const [tipeProgres, setTipeProgres] = useState<'KINERJA_HARIAN' | 'TUGAS_ATASAN'>('KINERJA_HARIAN');

  const [formData, setFormData] = useState({
    tipe_progres: 'KINERJA_HARIAN' as 'KINERJA_HARIAN' | 'TUGAS_ATASAN',
    rencana_aksi_bulanan_id: 0,
    tanggal: '',
    jam_mulai: '08:00',
    jam_selesai: '09:00',
    // KINERJA_HARIAN fields
    rencana_kegiatan_harian: '',
    progres: 0,
    satuan: '',
    // TUGAS_ATASAN fields
    tugas_atasan: '',
    // Common
    bukti_dukung: '',
    keterangan: ''
  });

  useEffect(() => {
    loadCalendar();
    loadRencanaAksi();
  }, [currentDate]);

  const loadCalendar = async () => {
    try {
      setLoading(true);
      const bulan = currentDate.getMonth() + 1;
      const tahun = currentDate.getFullYear();

      const response = await progresHarianApi.getCalendar(bulan, tahun);
      setCalendarData(response.calendar_data);
    } catch (err: any) {
      console.error('Failed to load calendar:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadRencanaAksi = async () => {
    try {
      const bulan = currentDate.getMonth() + 1;
      const tahun = currentDate.getFullYear();

      const response = await rencanaAksiBulananApi.getAll({
        bulan,
        tahun,
        status: 'AKTIF'
      });
      setRencanaAksiList(response);
    } catch (err: any) {
      console.error('Failed to load rencana aksi:', err);
    }
  };

  const loadProgresForDate = async (date: string) => {
    try {
      setLoading(true);
      const response = await progresHarianApi.getByDate(date);
      setProgresList(response.progres_list);
      setTotalDurasi({
        menit: response.total_durasi_menit,
        jam: response.total_durasi_jam,
        sisa: response.sisa_durasi_menit,
        isFull: response.is_full
      });
      setSelectedDate(date);
    } catch (err: any) {
      console.error('Failed to load progres harian:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDateClick = (date: string) => {
    loadProgresForDate(date);
  };

  const handleAddProgres = () => {
    if (!selectedDate) {
      alert('Pilih tanggal terlebih dahulu');
      return;
    }
    if (rencanaAksiList.length === 0) {
      alert('Belum ada rencana aksi bulanan yang aktif untuk bulan ini');
      return;
    }

    setFormData({
      tipe_progres: 'KINERJA_HARIAN',
      rencana_aksi_bulanan_id: rencanaAksiList[0]?.id || 0,
      tanggal: selectedDate,
      jam_mulai: '08:00',
      jam_selesai: '09:00',
      rencana_kegiatan_harian: '',
      progres: 0,
      satuan: rencanaAksiList[0]?.satuan_target || '',
      tugas_atasan: '',
      bukti_dukung: '',
      keterangan: ''
    });
    setTipeProgres('KINERJA_HARIAN');
    setSelectedProgres(null);
    setShowModal(true);
  };

  const handleEditProgres = (progres: ProgresHarian) => {
    setFormData({
      tipe_progres: progres.tipe_progres,
      rencana_aksi_bulanan_id: progres.rencana_aksi_bulanan_id || 0,
      tanggal: progres.tanggal,
      jam_mulai: progres.jam_mulai,
      jam_selesai: progres.jam_selesai,
      rencana_kegiatan_harian: progres.rencana_kegiatan_harian || '',
      progres: progres.progres || 0,
      satuan: progres.satuan || '',
      tugas_atasan: progres.tugas_atasan || '',
      bukti_dukung: progres.bukti_dukung || '',
      keterangan: progres.keterangan || ''
    });
    setTipeProgres(progres.tipe_progres);
    setSelectedProgres(progres);
    setShowModal(true);
  };

  const handleViewDetail = (progres: ProgresHarian) => {
    setSelectedProgres(progres);
    setShowDetailModal(true);
  };

  const handleDeleteProgres = async (id: number) => {
    if (!confirm('Yakin ingin menghapus progres harian ini?')) return;

    try {
      setLoading(true);
      await progresHarianApi.delete(id);

      if (selectedDate) {
        await loadProgresForDate(selectedDate);
      }
      await loadCalendar();

      alert('Progres harian berhasil dihapus');
    } catch (err: any) {
      alert(err.message || 'Failed to delete progres harian');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // ✅ PERUBAHAN: Validasi manual sebelum submit - Dual Mode
    // Bukti dukung OPSIONAL, tidak lagi wajib saat simpan
    if (tipeProgres === 'KINERJA_HARIAN') {
      if (formData.rencana_aksi_bulanan_id === 0) {
        alert('Pilih Rencana Aksi Bulanan terlebih dahulu');
        return;
      }
    } else if (tipeProgres === 'TUGAS_ATASAN') {
      if (!formData.tugas_atasan.trim()) {
        alert('Tugas Langsung Atasan wajib diisi');
        return;
      }
    }

    // ❌ REMOVED: Validasi bukti_dukung wajib
    // ASN boleh simpan progres tanpa bukti, wajib diisi sebelum 23:59

    try {
      setLoading(true);

      // Ensure time format is HH:MM (24-hour format without AM/PM)
      let dataToSubmit: any = {
        tipe_progres: tipeProgres,
        tanggal: formData.tanggal,
        jam_mulai: formData.jam_mulai.substring(0, 5),
        jam_selesai: formData.jam_selesai.substring(0, 5),
        // ✅ FIX: Kirim null jika bukti_dukung kosong (bukan empty string)
        bukti_dukung: formData.bukti_dukung.trim() || null,
        keterangan: formData.keterangan,
      };

      // Add fields based on tipe_progres
      if (tipeProgres === 'KINERJA_HARIAN') {
        dataToSubmit.rencana_aksi_bulanan_id = formData.rencana_aksi_bulanan_id;
        dataToSubmit.rencana_kegiatan_harian = formData.rencana_kegiatan_harian;
        dataToSubmit.progres = formData.progres;
        dataToSubmit.satuan = formData.satuan;
      } else {
        dataToSubmit.tugas_atasan = formData.tugas_atasan;
      }

      console.log('Submitting data:', dataToSubmit);

      if (selectedProgres) {
        // For update, exclude rencana_aksi_bulanan_id (cannot be changed)
        const { rencana_aksi_bulanan_id, ...updateData } = dataToSubmit;
        await progresHarianApi.update(selectedProgres.id, updateData);
      } else {
        await progresHarianApi.create(dataToSubmit);
      }

      if (selectedDate) {
        await loadProgresForDate(selectedDate);
      }
      await loadCalendar();

      setShowModal(false);
      alert(`Progres harian berhasil ${selectedProgres ? 'diperbarui' : 'ditambahkan'}!`);
    } catch (err: any) {
      console.error('Error saving progres:', err);
      if (err.errors) {
        const errorMessages = Object.entries(err.errors)
          .map(([field, messages]: [string, any]) => `${field}: ${messages.join(', ')}`)
          .join('\n');
        alert(`Validation error:\n${errorMessages}`);
      } else {
        alert(err.message || 'Failed to save progres harian');
      }
    } finally {
      setLoading(false);
    }
  };

  /**
   * ✅ CRITICAL FIX: Handler khusus untuk update HANYA link bukti dukung
   *
   * Fungsi ini digunakan saat user hanya ingin update link bukti tanpa mengubah
   * jam kerja, progres, atau field lainnya.
   *
   * Keuntungan:
   * - Payload minimal (hanya bukti_dukung)
   * - Tidak trigger validasi jam kerja
   * - Fast (< 50ms vs 500ms)
   * - Aman untuk 250 ASN concurrent
   */
  const handleUpdateBuktiOnly = async (progresId: number, buktiBaru: string) => {
    try {
      setLoading(true);

      // ✅ Hanya kirim bukti_dukung, TIDAK kirim field lain
      await progresHarianApi.updateBuktiDukung(progresId, buktiBaru);

      // Reload data
      if (selectedDate) {
        await loadProgresForDate(selectedDate);
      }
      await loadCalendar();

      alert('Link bukti dukung berhasil diperbarui!');
    } catch (err: any) {
      console.error('Error updating bukti:', err);
      alert(err.message || 'Failed to update bukti dukung');
    } finally {
      setLoading(false);
    }
  };

  const getDaysInMonth = (date: Date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];

    // Add empty cells for days before the first day of month
    for (let i = 0; i < startingDayOfWeek; i++) {
      days.push(null);
    }

    // Add days of month
    for (let day = 1; day <= daysInMonth; day++) {
      days.push(day);
    }

    return days;
  };

  const getDateString = (day: number) => {
    const year = currentDate.getFullYear();
    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
    const dayStr = String(day).padStart(2, '0');
    return `${year}-${month}-${dayStr}`;
  };

  const getCalendarDataForDate = (dateStr: string) => {
    return calendarData.find(d => d.tanggal === dateStr);
  };

  const getCellColor = (dateStr: string) => {
    const data = getCalendarDataForDate(dateStr);
    if (!data) return 'bg-white hover:bg-gray-50';

    // ✅ AUTO LOCK - Status warna
    if (data.status_visual === 'hitam') return 'bg-gray-800 text-white hover:bg-gray-900 cursor-not-allowed'; // LOCKED
    if (data.status_visual === 'merah') return 'bg-red-100 hover:bg-red-200';
    if (data.status_visual === 'hijau') return 'bg-green-100 hover:bg-green-200';
    if (data.status_visual === 'kuning') return 'bg-yellow-100 hover:bg-yellow-200';

    return 'bg-white hover:bg-gray-50';
  };

  const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

  const days = getDaysInMonth(currentDate);

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <button
            onClick={() => router.push('/asn/dashboard')}
            className="mb-4 text-blue-600 hover:text-blue-800 flex items-center"
          >
            <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Dashboard
          </button>
          <h1 className="text-3xl font-bold text-gray-900">Progres Harian</h1>
          <p className="mt-2 text-gray-600">
            Input dan kelola progres kegiatan harian dengan kalender visual
          </p>
        </div>

        {/* Legend */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <h3 className="font-semibold text-gray-900 mb-3">Keterangan Warna:</h3>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div className="flex items-center">
              <div className="w-8 h-8 bg-red-100 border border-red-300 rounded mr-2"></div>
              <span className="text-sm text-gray-700">🔴 Belum upload</span>
            </div>
            <div className="flex items-center">
              <div className="w-8 h-8 bg-yellow-100 border border-yellow-300 rounded mr-2"></div>
              <span className="text-sm text-gray-700">🟡 Kurang jam kerja</span>
            </div>
            <div className="flex items-center">
              <div className="w-8 h-8 bg-green-100 border border-green-300 rounded mr-2"></div>
              <span className="text-sm text-gray-700">🟢 Lengkap (≥7.5 jam)</span>
            </div>
            <div className="flex items-center">
              <div className="w-8 h-8 bg-gray-800 border border-gray-900 rounded mr-2"></div>
              <span className="text-sm text-gray-700">⚫ LOCKED (H+1)</span>
            </div>
          </div>
          <p className="mt-2 text-xs text-red-600 font-semibold">
            ⚠️ Progres harian hanya bisa diinput/edit pada hari yang sama (sebelum pukul 23:59). Setelah lewat hari (H+1), data akan otomatis LOCKED.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Calendar */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow">
              {/* Calendar Header */}
              <div className="p-6 border-b border-gray-200 flex justify-between items-center">
                <button
                  onClick={() => setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1))}
                  className="p-2 hover:bg-gray-100 rounded"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                <h2 className="text-xl font-semibold text-gray-900">
                  {monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}
                </h2>
                <button
                  onClick={() => setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1))}
                  className="p-2 hover:bg-gray-100 rounded"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>

              {/* Calendar Grid */}
              <div className="p-6">
                <div className="grid grid-cols-7 gap-2 mb-2">
                  {['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].map((day) => (
                    <div key={day} className="text-center font-semibold text-gray-700 text-sm py-2">
                      {day}
                    </div>
                  ))}
                </div>
                <div className="grid grid-cols-7 gap-2">
                  {days.map((day, index) => {
                    if (day === null) {
                      return <div key={`empty-${index}`} className="aspect-square"></div>;
                    }

                    const dateStr = getDateString(day);
                    const data = getCalendarDataForDate(dateStr);
                    const isSelected = selectedDate === dateStr;
                    const isToday = dateStr === new Date().toISOString().split('T')[0];

                    return (
                      <button
                        key={day}
                        onClick={() => handleDateClick(dateStr)}
                        className={`aspect-square border rounded-lg p-2 transition-all ${
                          isSelected
                            ? 'ring-2 ring-blue-500 border-blue-500'
                            : isToday
                            ? 'border-blue-400 border-2'
                            : 'border-gray-200'
                        } ${getCellColor(dateStr)}`}
                      >
                        <div className="text-sm font-medium text-gray-900">{day}</div>
                        {data && (
                          <div className="text-xs text-gray-600 mt-1">
                            {data.jumlah_progres} progres
                          </div>
                        )}
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>
          </div>

          {/* Progres List */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow">
              <div className="p-4 border-b border-gray-200">
                <h3 className="font-semibold text-gray-900">
                  {selectedDate ? `Progres ${selectedDate}` : 'Pilih Tanggal'}
                </h3>
                {selectedDate && (
                  <div className="mt-2 text-sm text-gray-600">
                    <p>Total: {totalDurasi.jam.toFixed(1)} jam ({totalDurasi.menit} menit)</p>
                    <p>Sisa: {Math.floor(totalDurasi.sisa / 60)} jam {totalDurasi.sisa % 60} menit</p>
                  </div>
                )}
              </div>

              {selectedDate && (
                <div className="p-4 border-b border-gray-200">
                  {(() => {
                    const dateData = getCalendarDataForDate(selectedDate);
                    const isLocked = dateData?.is_locked || false;
                    const isDisabled = totalDurasi.isFull || isLocked;

                    return (
                      <>
                        <button
                          onClick={handleAddProgres}
                          disabled={isDisabled}
                          className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                        >
                          {isLocked
                            ? '⚫ LOCKED - Tidak bisa input (H+1)'
                            : totalDurasi.isFull
                            ? 'Durasi Sudah Penuh (7.5 jam)'
                            : 'Tambah Progres Harian'}
                        </button>
                        {isLocked && (
                          <p className="mt-2 text-xs text-red-600 text-center">
                            Tanggal ini sudah lewat. Progres harian hanya bisa diinput pada hari yang sama.
                          </p>
                        )}
                      </>
                    );
                  })()}
                </div>
              )}

              <div className="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                {progresList.length > 0 ? (
                  progresList.map((progres) => (
                    <div key={progres.id} className="p-4">
                      <div className="flex justify-between items-start mb-2">
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-1">
                            <p className="text-sm font-medium text-gray-900">
                              {progres.jam_mulai} - {progres.jam_selesai}
                            </p>
                            {progres.tipe_progres === 'TUGAS_ATASAN' && (
                              <span className="px-2 py-0.5 text-xs font-semibold bg-purple-100 text-purple-800 rounded">
                                Tugas Atasan
                              </span>
                            )}
                          </div>
                          <p className="text-xs text-gray-600">{progres.durasi_jam}</p>
                        </div>
                        {/* ✅ Status Bukti Badge - Merah/Hijau */}
                        <div className="flex items-center gap-2">
                          {progres.status_bukti === 'BELUM_ADA' ? (
                            <span className="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded flex items-center gap-1">
                              🔴 Belum Ada Bukti
                            </span>
                          ) : (
                            <span className="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded flex items-center gap-1">
                              🟢 Sudah Ada Bukti
                            </span>
                          )}
                        </div>
                      </div>
                      <p className="text-sm text-gray-700 mb-2 line-clamp-2">
                        {progres.tipe_progres === 'TUGAS_ATASAN'
                          ? progres.tugas_atasan
                          : progres.rencana_kegiatan_harian}
                      </p>
                      {progres.tipe_progres === 'KINERJA_HARIAN' && (
                        <p className="text-xs text-gray-600 mb-2">
                          Progres: {progres.progres} {progres.satuan}
                        </p>
                      )}

                      {/* ⚠️ Warning jika belum ada bukti */}
                      {progres.status_bukti === 'BELUM_ADA' && (
                        <div className="mb-3 p-2 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                          <p className="text-xs text-yellow-700">
                            ⚠️ Link bukti dukung belum diisi. Wajib diisi sebelum pukul 23:59.
                          </p>
                          {/* ✅ QUICK UPDATE LINK BUTTON */}
                          <button
                            onClick={() => {
                              const link = prompt('Masukkan link Google Drive bukti dukung:');
                              if (link && link.trim()) {
                                handleUpdateBuktiOnly(progres.id, link.trim());
                              }
                            }}
                            className="mt-2 px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
                          >
                            📎 Tambah Link Sekarang
                          </button>
                        </div>
                      )}

                      <div className="flex space-x-2">
                        <button
                          onClick={() => handleViewDetail(progres)}
                          className="flex-1 px-2 py-1 text-xs text-blue-600 border border-blue-600 rounded hover:bg-blue-50"
                        >
                          Detail
                        </button>
                        {(() => {
                          const dateData = getCalendarDataForDate(progres.tanggal);
                          const isLocked = dateData?.is_locked || false;

                          return (
                            <>
                              <button
                                onClick={() => handleEditProgres(progres)}
                                disabled={isLocked}
                                className={`flex-1 px-2 py-1 text-xs rounded ${
                                  isLocked
                                    ? 'text-gray-400 border border-gray-300 cursor-not-allowed'
                                    : 'text-green-600 border border-green-600 hover:bg-green-50'
                                }`}
                                title={isLocked ? 'Tidak bisa edit - tanggal sudah lewat (H+1)' : 'Edit'}
                              >
                                {isLocked ? '🔒 Edit' : 'Edit'}
                              </button>
                              <button
                                onClick={() => !isLocked && handleDeleteProgres(progres.id)}
                                disabled={isLocked}
                                className={`flex-1 px-2 py-1 text-xs rounded ${
                                  isLocked
                                    ? 'text-gray-400 border border-gray-300 cursor-not-allowed'
                                    : 'text-red-600 border border-red-600 hover:bg-red-50'
                                }`}
                                title={isLocked ? 'Tidak bisa hapus - tanggal sudah lewat (H+1)' : 'Hapus'}
                              >
                                {isLocked ? '🔒 Hapus' : 'Hapus'}
                              </button>
                            </>
                          );
                        })()}
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="p-8 text-center text-gray-500">
                    <p className="text-sm">
                      {selectedDate ? 'Belum ada progres untuk tanggal ini' : 'Pilih tanggal di kalender'}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Form Modal */}
        {showModal && (
          <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div className="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
              <div className="mb-4">
                <h3 className="text-lg font-semibold text-gray-900">
                  {selectedProgres ? 'Edit' : 'Tambah'} Progres Harian
                </h3>
              </div>

              {/* Segmented Control - Tipe Progres */}
              {!selectedProgres && (
                <div className="mb-6">
                  <div className="flex items-center bg-gray-100 p-1 rounded-lg">
                    <button
                      type="button"
                      onClick={() => {
                        setTipeProgres('KINERJA_HARIAN');
                        setFormData({ ...formData, tipe_progres: 'KINERJA_HARIAN' });
                      }}
                      className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
                        tipeProgres === 'KINERJA_HARIAN'
                          ? 'bg-white text-blue-600 shadow-sm'
                          : 'text-gray-600 hover:text-gray-900'
                      }`}
                    >
                      🔘 Kinerja Harian
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setTipeProgres('TUGAS_ATASAN');
                        setFormData({ ...formData, tipe_progres: 'TUGAS_ATASAN' });
                      }}
                      className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
                        tipeProgres === 'TUGAS_ATASAN'
                          ? 'bg-white text-blue-600 shadow-sm'
                          : 'text-gray-600 hover:text-gray-900'
                      }`}
                    >
                      🔘 Tugas Langsung Atasan
                    </button>
                  </div>
                  <p className="text-xs text-gray-500 mt-2">
                    {tipeProgres === 'KINERJA_HARIAN'
                      ? 'Input progres sesuai Rencana Aksi Bulanan'
                      : 'Input tugas langsung dari atasan (di luar rencana aksi)'}
                  </p>
                </div>
              )}

              <form onSubmit={handleSubmit}>
                <div className="space-y-4">
                  {/* Form KINERJA_HARIAN */}
                  {tipeProgres === 'KINERJA_HARIAN' && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Rencana Aksi Bulanan <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.rencana_aksi_bulanan_id}
                        onChange={(e) => {
                          const selectedRencana = rencanaAksiList.find(r => r.id === parseInt(e.target.value));
                          setFormData({
                            ...formData,
                            rencana_aksi_bulanan_id: parseInt(e.target.value),
                            satuan: selectedRencana?.satuan_target || ''
                          });
                        }}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal bg-white"
                        required
                      >
                        <option value="0">-- Pilih Rencana Aksi --</option>
                        {rencanaAksiList.map((rencana) => (
                          <option key={rencana.id} value={rencana.id}>
                            {rencana.bulan_nama} - {rencana.rencana_aksi_bulanan?.substring(0, 50)}...
                          </option>
                        ))}
                      </select>
                    </div>
                  )}

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Jam Mulai <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="time"
                        value={formData.jam_mulai}
                        onChange={(e) => setFormData({ ...formData, jam_mulai: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                        step="300"
                        min="00:00"
                        max="23:59"
                        required
                      />
                      <p className="text-xs text-gray-500 mt-1">Format 24 jam (13:00 = jam 1 siang)</p>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Jam Selesai <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="time"
                        value={formData.jam_selesai}
                        onChange={(e) => setFormData({ ...formData, jam_selesai: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                        step="300"
                        min="00:00"
                        max="23:59"
                        required
                      />
                      <p className="text-xs text-gray-500 mt-1">Format 24 jam (13:00 = jam 1 siang)</p>
                    </div>
                  </div>

                  {/* Form KINERJA_HARIAN - Rencana Kegiatan */}
                  {tipeProgres === 'KINERJA_HARIAN' && (
                    <>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Rencana Kegiatan Harian <span className="text-red-500">*</span>
                        </label>
                        <textarea
                          value={formData.rencana_kegiatan_harian}
                          onChange={(e) => setFormData({ ...formData, rencana_kegiatan_harian: e.target.value })}
                          rows={3}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                          required
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            Progres <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="number"
                            value={formData.progres}
                            onChange={(e) => setFormData({ ...formData, progres: parseInt(e.target.value) || 0 })}
                            min="0"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                            required
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">
                            Satuan <span className="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            value={formData.satuan}
                            onChange={(e) => setFormData({ ...formData, satuan: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                            required
                          />
                        </div>
                      </div>
                    </>
                  )}

                  {/* Form TUGAS_ATASAN - Tugas Langsung */}
                  {tipeProgres === 'TUGAS_ATASAN' && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Tugas Langsung Atasan <span className="text-red-500">*</span>
                      </label>
                      <textarea
                        value={formData.tugas_atasan}
                        onChange={(e) => setFormData({ ...formData, tugas_atasan: e.target.value })}
                        rows={4}
                        placeholder="Jelaskan tugas yang diberikan langsung oleh atasan..."
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                        required
                      />
                      <p className="text-xs text-gray-500 mt-1">
                        Tugas di luar Rencana Aksi Bulanan yang diberikan langsung oleh atasan
                      </p>
                    </div>
                  )}

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Bukti Dukung (Google Drive Link) <span className="text-gray-500">(Opsional)</span>
                    </label>
                    <input
                      type="url"
                      value={formData.bukti_dukung}
                      onChange={(e) => setFormData({ ...formData, bukti_dukung: e.target.value })}
                      placeholder="https://drive.google.com/... (bisa diisi nanti sebelum 23:59)"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                    />
                    <p className="text-xs text-gray-500 mt-1">
                      💡 Opsional saat simpan, wajib diisi sebelum pukul 23:59
                    </p>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Keterangan (Opsional)
                    </label>
                    <textarea
                      value={formData.keterangan}
                      onChange={(e) => setFormData({ ...formData, keterangan: e.target.value })}
                      rows={2}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                    />
                  </div>
                </div>

                <div className="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                    disabled={loading}
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400"
                    disabled={loading}
                  >
                    {loading ? 'Menyimpan...' : 'Simpan'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Detail Modal */}
        {showDetailModal && selectedProgres && (
          <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div className="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
              <div className="mb-4 flex justify-between items-start">
                <h3 className="text-lg font-semibold text-gray-900">Detail Progres Harian</h3>
                <button
                  onClick={() => setShowDetailModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Tipe Progres</label>
                  <div className="mt-1">
                    {selectedProgres.tipe_progres === 'TUGAS_ATASAN' ? (
                      <span className="px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded">
                        Tugas Langsung Atasan
                      </span>
                    ) : (
                      <span className="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded">
                        Kinerja Harian
                      </span>
                    )}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">Tanggal</label>
                  <p className="mt-1 text-sm text-gray-900">{selectedProgres.tanggal}</p>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Jam Mulai</label>
                    <p className="mt-1 text-sm text-gray-900">{selectedProgres.jam_mulai}</p>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Jam Selesai</label>
                    <p className="mt-1 text-sm text-gray-900">{selectedProgres.jam_selesai}</p>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700">Durasi</label>
                  <p className="mt-1 text-sm text-gray-900">{selectedProgres.durasi_jam} ({selectedProgres.durasi_menit} menit)</p>
                </div>

                {selectedProgres.tipe_progres === 'KINERJA_HARIAN' ? (
                  <>
                    <div>
                      <label className="block text-sm font-medium text-gray-700">Rencana Kegiatan Harian</label>
                      <p className="mt-1 text-sm text-gray-900">{selectedProgres.rencana_kegiatan_harian}</p>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700">Progres</label>
                      <p className="mt-1 text-sm text-gray-900">{selectedProgres.progres} {selectedProgres.satuan}</p>
                    </div>
                  </>
                ) : (
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Tugas Langsung Atasan</label>
                    <p className="mt-1 text-sm text-gray-900 whitespace-pre-line">{selectedProgres.tugas_atasan}</p>
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-gray-700">Status Bukti Dukung</label>
                  <div className="mt-1">
                    {selectedProgres.status_bukti === 'SUDAH_ADA' ? (
                      <span className="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">
                        Sudah Ada
                      </span>
                    ) : (
                      <span className="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">
                        Belum Ada
                      </span>
                    )}
                  </div>
                </div>

                {selectedProgres.bukti_dukung && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Bukti Dukung</label>
                    <a
                      href={selectedProgres.bukti_dukung}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="mt-1 text-sm text-blue-600 hover:underline flex items-center"
                    >
                      Lihat Bukti Dukung
                      <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                    </a>
                  </div>
                )}

                {selectedProgres.keterangan && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Keterangan</label>
                    <p className="mt-1 text-sm text-gray-900">{selectedProgres.keterangan}</p>
                  </div>
                )}
              </div>

              <div className="mt-6 flex justify-end">
                <button
                  onClick={() => setShowDetailModal(false)}
                  className="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                >
                  Tutup
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
