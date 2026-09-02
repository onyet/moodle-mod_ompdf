<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Indonesian language strings for mod_ompdf.
 *
 * @package    mod_ompdf
 * @copyright 2026 Dian Mukti Wibowo <onyetcorp@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'OMPDF';
$string['modulenameplural'] = 'OMPDFs';
$string['modulename_help'] = 'Folder plugin berbasis PDF.js dengan fitur keamanan dan analitik tingkat enterprise.';

$string['ompdf:addinstance'] = 'Tambah OMPDF baru';
$string['ompdf:view'] = 'Lihat OMPDF';

$string['pluginadministration'] = 'Administrasi OMPDF';
$string['pluginname'] = 'OMPDF';

$string['ompdf_defaults_heading'] = 'Nilai Default Pengaturan OMPDF';
$string['ompdf_defaults_text'] = 'Nilai default yang digunakan pada pengaturan OMPDF saat membuat instance baru.';
$string['ompdf_options_heading'] = 'Opsi OMPDF';
$string['ompdf_options_text'] = 'Mengubah cara kerja dan tampilan OMPDF.';

$string['filearea_pdfs'] = 'PDF';
$string['pdf_fieldset'] = 'PDF';
$string['pdfs'] = 'Berkas PDF';
$string['pdfs_help'] = 'Unggah berkas PDF di sini.';

$string['display'] = 'Tampilkan isi folder';
$string['display_help'] = 'Pilih apakah isi folder ditampilkan di halaman terpisah atau inline pada halaman kursus.';
$string['displaypage'] = 'Di halaman terpisah';
$string['displayinline'] = 'Inline pada halaman kursus';
$string['downloadlinktext'] = 'unduh';
$string['noautocompletioninline'] = 'Penyelesaian otomatis tidak dapat digunakan bersamaan dengan opsi display inline.';
$string['showexpanded'] = 'Tampilkan sub-folder terbuka';
$string['showexpanded_help'] = 'Jika diaktifkan, sub-folder akan terbuka secara default.';
$string['openinnewtab'] = 'Buka PDF di tab/jendela baru';
$string['openinnewtab_help'] = 'Jika diaktifkan, berkas PDF akan terbuka di tab atau jendela baru.';
$string['security_hdr'] = 'Keamanan & Proteksi DRM';
$string['readonly_protection'] = 'Aktifkan Proteksi Read-Only (Nonaktifkan Unduh & Cetak)';
$string['readonly_protection_help'] = 'Jika diaktifkan, siswa hanya dapat membaca dokumen. Tombol unduh, cetak, simpan, salin teks, klik kanan, dan perekaman layar (Anti-OBS) akan diblokir.';
$string['showdownloadlinks'] = 'Tampilkan tautan untuk mengunduh PDF';
$string['showdownloadlinks_help'] = 'Jika diaktifkan, tautan unduh langsung akan disertakan.';
$string['eventviewall'] = 'Lihat Semua';

$string['enable_encryption'] = 'Aktifkan Enkripsi URL AES-256';
$string['enable_encryption_help'] = 'Jika diaktifkan, URL target PDF akan dienkripsi dengan token AES-256.';
$string['disable_print_save'] = 'Nonaktifkan Cetak, Simpan & Klik Kanan (Proteksi DRM)';
$string['disable_print_save_help'] = 'Jika diaktifkan, pintasan keyboard cetak/simpan dan klik kanan akan dinonaktifkan di dalam PDF viewer.';
$string['enable_watermark'] = 'Aktifkan Watermark Pengguna Dinamis';
$string['enable_watermark_help'] = 'Jika diaktifkan, watermark transparan berisi nama pengguna, IP, dan tanggal akan ditampilkan di atas halaman PDF.';
