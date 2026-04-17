<?php

namespace Database\Seeders;

use App\Models\WaCarakaMenu;
use Illuminate\Database\Seeder;

/**
 * WaCarakaMenuSeeder
 *
 * Seeds the 16 chatbot menu entries from wamehehe's `format` table.
 * Menu structure follows the original proses_message() logic.
 */
class WaCarakaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'command'     => '1',
                'label'       => 'Menu Utama',
                'type'        => 'direct',
                'sort_order'  => 0,
                'description' => 'Default greeting / menu utama',
                'response_text' => "*SiNofita PA Semarang*\n_Assalamualaikum wr. wb._\n\nSelamat datang di Layanan Informasi WhatsApp *Pengadilan Agama Semarang*\n\nSilahkan ketik angka sesuai informasi yang Anda butuhkan:\n\n*2*. Syarat Perceraian\n*3*. Biaya Perkara\n*4*. Alur Pendaftaran\n*5*. Daftar Jenis Perkara\n*6*. Jadwal Pelayanan\n*7*. Cek Status Perkara\n*8*. Cek Jadwal Sidang\n*9*. Cek Akta Cerai\n*10*. Cek Biaya Panjar\n*11*. Cek Pembayaran\n*12*. Pengaduan\n*13*. Konsultasi\n*14*. Alamat Kantor\n*15*. Info Website\n*16*. Info Layanan Online\n\nKetik angka pilihan Anda:",
            ],
            [
                'command'     => '2',
                'label'       => 'Syarat Perceraian',
                'type'        => 'direct',
                'sort_order'  => 2,
                'description' => 'Informasi syarat perceraian',
                'response_text' => "*Syarat Perceraian*\n\nBerikut adalah persyaratan pengajuan perceraian di Pengadilan Agama Semarang:\n\n1. Surat Gugatan / Permohonan\n2. Fotocopy KTP\n3. Fotocopy Buku Nikah\n4. Fotocopy Kartu Keluarga\n5. Surat Keterangan dari Kelurahan\n6. Biaya panjar perkara\n\nUntuk informasi lebih lanjut, silahkan hubungi Meja Informasi PA Semarang.",
            ],
            [
                'command'     => '3',
                'label'       => 'Biaya Perkara',
                'type'        => 'direct',
                'sort_order'  => 3,
                'description' => 'Informasi biaya perkara',
                'response_text' => "*Biaya Perkara*\n\nBiaya perkara berbeda-beda tergantung jenis perkara dan radius tempat tinggal para pihak.\n\nSilahkan cek estimasi biaya melalui website:\nhttps://pa-semarang.go.id\n\natau hubungi Meja Informasi PA Semarang.",
            ],
            [
                'command'     => '4',
                'label'       => 'Alur Pendaftaran',
                'type'        => 'direct',
                'sort_order'  => 4,
                'description' => 'Alur pendaftaran perkara',
                'response_text' => "*Alur Pendaftaran Perkara*\n\n1. Datang ke Meja Informasi\n2. Konsultasi dan pembuatan surat gugatan/permohonan\n3. Pendaftaran perkara di PTSP\n4. Pembayaran biaya panjar\n5. Menunggu jadwal sidang\n\nPendaftaran juga bisa dilakukan secara online melalui e-Court:\nhttps://ecourt.mahkamahagung.go.id",
            ],
            [
                'command'       => '5',
                'label'         => 'Daftar Jenis Perkara',
                'type'          => 'prompt',
                'sort_order'    => 5,
                'description'   => 'Pilih jenis perkara untuk info detail',
                'prompt_text'   => "*Daftar Jenis Perkara*\n\nSilahkan ketik jenis perkara yang ingin Anda ketahui:\n\na. Cerai Gugat\nb. Cerai Talak\nc. Dispensasi Kawin\nd. Isbat Nikah\ne. Penetapan Ahli Waris\nf. Wali Adhol\ng. Harta Bersama\n\nKetik huruf pilihan Anda:",
                'response_text' => null,
            ],
            [
                'command'     => '6',
                'label'       => 'Jadwal Pelayanan',
                'type'        => 'direct',
                'sort_order'  => 6,
                'description' => 'Informasi jadwal pelayanan kantor',
                'response_text' => "*Jadwal Pelayanan*\n\nPengadilan Agama Semarang\nJl. Hanoman Raya No.18, Semarang\n\nSenin - Kamis: 08.00 - 16.00 WIB\nJumat: 08.00 - 16.30 WIB\n\n_Istirahat:_\nSenin-Kamis: 12.00 - 13.00 WIB\nJumat: 11.30 - 13.00 WIB",
            ],
            [
                'command'        => '7',
                'label'          => 'Cek Status Perkara',
                'type'           => 'input',
                'sort_order'     => 7,
                'description'    => 'Cek status perkara dari SIPP',
                'prompt_text'    => "*Cek Status Perkara*\n\nMasukkan nomor perkara Anda.\nContoh: _1234/Pdt.G/2024/PA.Smg_",
                'response_text'  => null,
                'response_query' => null,
                'sipp_query_type'=> 'status_perkara',
            ],
            [
                'command'        => '8',
                'label'          => 'Cek Jadwal Sidang',
                'type'           => 'input',
                'sort_order'     => 8,
                'description'    => 'Cek jadwal sidang dari SIPP',
                'prompt_text'    => "*Cek Jadwal Sidang*\n\nMasukkan nomor perkara Anda.\nContoh: _1234/Pdt.G/2024/PA.Smg_",
                'response_text'  => null,
                'response_query' => null,
                'sipp_query_type'=> 'jadwal_sidang',
            ],
            [
                'command'        => '9',
                'label'          => 'Cek Akta Cerai',
                'type'           => 'input',
                'sort_order'     => 9,
                'description'    => 'Cek akta cerai dari SIPP',
                'prompt_text'    => "*Cek Akta Cerai*\n\nMasukkan nomor perkara Anda.\nContoh: _1234/Pdt.G/2024/PA.Smg_",
                'response_text'  => null,
                'response_query' => null,
                'sipp_query_type'=> 'akta_cerai',
            ],
            [
                'command'        => '10',
                'label'          => 'Cek Biaya Panjar',
                'type'           => 'input',
                'sort_order'     => 10,
                'description'    => 'Cek biaya panjar dari SIPP',
                'prompt_text'    => "*Cek Biaya Panjar*\n\nMasukkan nomor perkara Anda.\nContoh: _1234/Pdt.G/2024/PA.Smg_",
                'response_text'  => null,
                'response_query' => null,
                'sipp_query_type'=> 'biaya_panjar',
            ],
            [
                'command'        => '11',
                'label'          => 'Cek Pembayaran',
                'type'           => 'input',
                'sort_order'     => 11,
                'description'    => 'Cek riwayat pembayaran dari SIPP',
                'prompt_text'    => "*Cek Pembayaran*\n\nMasukkan nomor perkara Anda.\nContoh: _1234/Pdt.G/2024/PA.Smg_",
                'response_text'  => null,
                'response_query' => null,
                'sipp_query_type'=> 'pembayaran',
            ],
            [
                'command'              => '12',
                'label'                => 'Pengaduan',
                'type'                 => 'input',
                'sort_order'           => 12,
                'description'          => 'Penerimaan pengaduan dari masyarakat',
                'prompt_text'          => 'Silahkan ketik aduan Anda. Pastikan informasi yang Anda diberikan sedapat mungkin memenuhi unsur *Apa*, *Dimana*, *Kapan*, *Siapa* dan *Bagaimana*:',
                'response_text'        => 'Terima kasih. Aduan Anda telah kami terima dan akan segera ditindaklanjuti oleh petugas kami. _Wassalamualaikum wr. wb._',
                'creates_ticket_type'  => 'pengaduan',
            ],
            [
                'command'              => '13',
                'label'                => 'Konsultasi',
                'type'                 => 'input',
                'sort_order'           => 13,
                'description'          => 'Penerimaan konsultasi dari masyarakat',
                'prompt_text'          => 'Silahkan ketik permasalahan Anda. Pastikan informasi yang Anda berikan benar dan jelas',
                'response_text'        => 'Terima kasih. Pertanyaan Anda telah kami terima dan akan segera ditindaklanjuti oleh petugas kami. _Wassalamualaikum wr. wb._',
                'creates_ticket_type'  => 'konsultasi',
            ],
            [
                'command'     => '14',
                'label'       => 'Alamat Kantor',
                'type'        => 'direct',
                'sort_order'  => 14,
                'description' => 'Alamat kantor PA Semarang',
                'response_text' => "*Alamat Kantor*\n\nPengadilan Agama Semarang Kelas IA\nJl. Hanoman Raya No.18\nPerumnas Tlogosari, Semarang 50196\n\nTelp: (024) 6710109\nFax: (024) 6710109\nEmail: info@pa-semarang.go.id\nWebsite: https://pa-semarang.go.id",
            ],
            [
                'command'     => '15',
                'label'       => 'Info Website',
                'type'        => 'direct',
                'sort_order'  => 15,
                'description' => 'Informasi website resmi',
                'response_text' => "*Website Resmi*\n\n🌐 Website: https://pa-semarang.go.id\n📱 SIPP: https://sipp.pa-semarang.go.id\n💼 e-Court: https://ecourt.mahkamahagung.go.id\n📊 LumpiaPasar: https://lumpiapasar.pasemarang.go.id",
            ],
            [
                'command'     => '16',
                'label'       => 'Info Layanan Online',
                'type'        => 'direct',
                'sort_order'  => 16,
                'description' => 'Informasi layanan online',
                'response_text' => "*Layanan Online*\n\n1. *e-Court* — Pendaftaran perkara online\n   https://ecourt.mahkamahagung.go.id\n\n2. *e-Litigasi* — Persidangan online\n   https://ecourt.mahkamahagung.go.id\n\n3. *SIPP* — Penelusuran perkara\n   https://sipp.pa-semarang.go.id\n\n4. *LumpiaPasar* — Layanan informasi perkara\n   https://lumpiapasar.pasemarang.go.id",
            ],
        ];

        foreach ($menus as $menuData) {
            WaCarakaMenu::updateOrCreate(
                ['command' => $menuData['command']],
                $menuData,
            );
        }

        $this->command->info('✅ Seeded ' . count($menus) . ' WA Caraka chatbot menus.');
    }
}
