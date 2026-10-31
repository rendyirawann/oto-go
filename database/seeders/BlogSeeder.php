<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds the launch articles for the OtoGo blog.
 *
 * Idempotent: categories and posts are matched by slug, so re-running the
 * seeder refreshes the content instead of duplicating it. Price tables in the
 * cost articles are built from database/data/medan_price_template.php so they
 * always match the estimator on the landing page.
 */
class BlogSeeder extends Seeder
{
    private array $template = [];

    public function run(): void
    {
        $this->template = (array) require database_path('data/medan_price_template.php');

        $categories = [];
        foreach ([
            'perawatan-motor' => 'Perawatan Motor',
            'perawatan-mobil' => 'Perawatan Mobil',
            'doorsmeer-detailing' => 'Doorsmeer & Detailing',
            'biaya-tips-hemat' => 'Biaya & Tips Hemat',
        ] as $slug => $name) {
            $categories[$slug] = PostCategory::updateOrCreate(['slug' => $slug], ['name' => $name])->id;
        }

        foreach ($this->articles() as $article) {
            $content = HtmlSanitizer::clean($article['content']);

            Post::updateOrCreate(['slug' => $article['slug']], [
                'post_category_id' => $categories[$article['category']],
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'content' => $content,
                'cover_path' => 'assets/media/blog/covers/' . $article['slug'] . '.svg',
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => Carbon::parse($article['date'] . ' 09:00:00', 'Asia/Jakarta'),
                'reading_minutes' => Post::readingMinutes($content),
                'meta_description' => $article['meta'] ?? null,
            ]);
        }

        Post::flushCache();

        $this->command?->info('Blog: ' . count($this->articles()) . ' artikel siap.');
    }

    // ------------------------------------------------------------------
    // Helpers for template-driven tables
    // ------------------------------------------------------------------

    private function rp(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function item(string $service): array
    {
        foreach ($this->template['items'] ?? [] as $item) {
            if ($item['service'] === $service) {
                return $item;
            }
        }

        return ['prices' => []];
    }

    private function product(string $name): int
    {
        foreach ($this->template['products'] ?? [] as $product) {
            if ($product['name'] === $name) {
                return (int) $product['price'];
            }
        }

        return 0;
    }

    private function logistics(string $mode, string $type): array
    {
        foreach ($this->template['logistics'] ?? [] as $row) {
            if ($row['mode'] === $mode && $row['vehicle_type'] === $type) {
                return $row;
            }
        }

        return ['base_fee' => 0, 'included_km' => 0, 'per_km_fee' => 0, 'max_radius_km' => 0];
    }

    private function fee(string $mode, string $type, float $km): int
    {
        $row = $this->logistics($mode, $type);

        return (int) $row['base_fee'] + (int) max(0, ceil($km - $row['included_km'])) * (int) $row['per_km_fee'];
    }

    /** Car service table: one row per service, columns per car group. */
    private function carServiceTable(array $services): string
    {
        $cols = ['car_city' => 'City car', 'car_mpv' => 'Sedan / MPV', 'car_suv' => 'SUV / Pickup', 'car_premium' => 'Van / Premium'];
        $html = '<table><thead><tr><th>Jasa</th>';
        foreach ($cols as $label) {
            $html .= '<th>' . $label . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($services as $service) {
            $item = $this->item($service);
            $html .= '<tr><td>' . e($service) . '</td>';
            foreach (array_keys($cols) as $code) {
                $html .= '<td>' . (isset($item['prices'][$code]) ? $this->rp((int) $item['prices'][$code]) : '—') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function washTable(): string
    {
        $wash = $this->item('Cuci reguler (cuci salju)');
        $names = [];
        foreach ($this->template['vehicle_categories'] ?? [] as $c) {
            $names[$c['code']] = $c['name'];
        }

        $html = '<table><thead><tr><th>Kategori kendaraan</th><th>Cuci salju reguler</th></tr></thead><tbody>';
        foreach ($wash['prices'] as $code => $price) {
            $html .= '<tr><td>' . e($names[$code] ?? $code) . '</td><td>' . $this->rp((int) $price) . '</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private function logisticsTable(): string
    {
        $labels = ['pickup_delivery' => 'Antar jemput', 'home_service' => 'Layanan di rumah'];
        $html = '<table><thead><tr><th>Cara</th><th>Kendaraan</th><th>Biaya dasar</th><th>Termasuk</th><th>Per km berikutnya</th><th>Radius maks</th></tr></thead><tbody>';
        foreach ($this->template['logistics'] ?? [] as $row) {
            $html .= '<tr><td>' . $labels[$row['mode']] . '</td><td>' . ucfirst($row['vehicle_type']) . '</td><td>'
                . $this->rp((int) $row['base_fee']) . '</td><td>' . $row['included_km'] . ' km</td><td>'
                . $this->rp((int) $row['per_km_fee']) . '</td><td>' . $row['max_radius_km'] . ' km</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private function disclaimer(): string
    {
        return '<p><em>' . e($this->template['disclaimer'] ?? '') . '</em></p>';
    }

    // ------------------------------------------------------------------
    // Articles
    // ------------------------------------------------------------------

    private function articles(): array
    {
        $oilJob = $this->item('Ganti oli motor (jasa)')['prices']['motor_matic'] ?? 10000;
        $oilMatic = $this->product('Oli motor matic 0,8 L');
        $gearOil = $this->product('Oli gardan matic 120 ml');
        $brakeJob = $this->item('Ganti kampas rem motor (per roda)')['prices'];
        $brakePad = $this->product('Kampas rem motor (set)');
        $carBrake = $this->item('Servis rem 2 roda')['prices'];
        $cvtJob = $this->item('Servis CVT (matic)')['prices']['motor_matic'] ?? 60000;
        $vbelt = $this->product('V-belt matic');
        $wiper = $this->product('Wiper blade (sepasang)');
        $underbody = $this->item('Cuci kolong / underbody')['prices']['car_mpv'] ?? 30000;
        $spooring = $this->item('Spooring')['prices']['car_mpv'] ?? 120000;
        $balancing = $this->item('Balancing 4 roda')['prices']['car_mpv'] ?? 100000;
        $acService = $this->item('Servis AC (cuci evaporator)')['prices']['car_mpv'] ?? 350000;
        $batteryMotor = $this->product('Aki motor kering');
        $batteryCar = $this->product('Aki mobil NS40');
        $mpvService = $this->item('Servis berkala ringan mobil')['prices']['car_mpv'] ?? 200000;
        $carOil = $this->product('Oli mobil 4 L');
        $oilFilter = $this->product('Filter oli mobil');
        $airFilter = $this->product('Filter udara mobil');
        $mpvTotal = $mpvService + $carOil + $oilFilter;
        $mpvTotalAir = $mpvTotal + $airFilter;
        $wax = $this->item('Wax / semir body')['prices']['car_mpv'] ?? 50000;
        $vacuum = $this->item('Cuci + vakum interior')['prices']['car_mpv'] ?? 60000;
        $pickupCar6 = $this->fee('pickup_delivery', 'mobil', 6);
        $homeCar6 = $this->fee('home_service', 'mobil', 6);
        $pickupMotor4 = $this->fee('pickup_delivery', 'motor', 4.5);
        $homeMotor4 = $this->fee('home_service', 'motor', 4.5);

        return [
            [
                'slug' => 'ganti-oli-motor-matic',
                'category' => 'perawatan-motor',
                'date' => '2026-10-03',
                'title' => 'Kapan Harus Ganti Oli Motor Matic? Patokan Kilometer untuk Jalanan Medan',
                'excerpt' => 'Buku manual bilang 4.000 km, tapi macet dan panas di Medan termasuk pemakaian berat. Ini cara menentukan jadwal ganti oli yang realistis.',
                'meta' => 'Interval ganti oli motor matic untuk pemakaian harian di Medan, cara cek kondisi oli, pilihan spesifikasi, dan kisaran biayanya.',
                'content' => <<<HTML
<p>Oli mesin adalah perawatan paling murah sekaligus paling sering ditunda. Padahal pada motor matic, oli bekerja lebih keras dari yang terlihat: mesinnya kecil, putarannya tinggi, dan ruang di sekitar blok mesin tertutup bodi sehingga panas lebih lama terbuang.</p>
<p>Buku pedoman kebanyakan motor matic mencantumkan interval sekitar 4.000 km atau beberapa bulan sekali, mana yang lebih dulu tercapai. Masalahnya, angka itu ditulis untuk kondisi "normal". Lalu lintas padat, jarak pendek berulang, dan suhu siang hari yang tinggi termasuk kategori <strong>pemakaian berat</strong> di hampir semua buku manual.</p>

<h2>Patokan yang lebih realistis untuk pemakaian harian</h2>
<p>Untuk motor yang dipakai pulang-pergi kerja di dalam kota dengan banyak berhenti di lampu merah, interval <strong>2.000–3.000 km</strong> adalah patokan yang aman. Jika motor jarang dipakai, tetap ganti oli minimal setiap tiga sampai empat bulan, karena oli juga menua walau kilometernya sedikit: uap air dari kondensasi bercampur dan aditifnya perlahan melemah.</p>
<ul>
<li><strong>Dipakai ojek atau kurir</strong> (lebih dari 80 km per hari): ganti setiap 2.000 km.</li>
<li><strong>Pulang-pergi kerja di kota</strong>: 2.500–3.000 km.</li>
<li><strong>Jarang dipakai, jarak dekat</strong>: tiap 3–4 bulan, berapa pun kilometernya.</li>
</ul>

<h2>Tanda oli sudah minta diganti</h2>
<p>Kilometer adalah patokan utama, tetapi beberapa tanda ini layak jadi alarm tambahan:</p>
<ul>
<li>Suara mesin terdengar lebih kasar saat pagi atau saat langsam.</li>
<li>Oli di stik pengukur berwarna hitam pekat dan terasa encer di jari.</li>
<li>Volume oli turun di bawah batas minimum di antara dua jadwal ganti.</li>
<li>Mesin cepat terasa panas dan tarikan terasa lebih berat.</li>
</ul>
<p>Cara mengecek paling mudah: tegakkan motor dengan standar tengah di permukaan rata, tunggu beberapa menit setelah mesin mati, cabut stik oli, lap, masukkan kembali tanpa diputar, lalu lihat batasnya.</p>

<h2>Spesifikasi oli yang tepat</h2>
<p>Motor matic memakai kopling kering di dalam CVT, sehingga oli mesinnya tidak bersentuhan dengan kampas kopling. Karena itu banyak pabrikan menganjurkan oli berstandar <strong>JASO MB</strong> untuk matic, berbeda dengan motor manual yang umumnya memakai JASO MA. Kekentalan yang umum adalah SAE 10W-30 atau 10W-40; ikuti yang tercantum di buku manual motormu.</p>
<p>Perhatikan juga volumenya. Banyak matic 110–125 cc hanya membutuhkan sekitar 0,65–0,8 liter, sedangkan matic bermesin lebih besar bisa sampai 1 liter atau lebih. Mengisi berlebihan sama buruknya dengan kekurangan.</p>

<h2>Oli mineral, semi sintetis, atau full sintetis?</h2>
<p>Oli mineral paling terjangkau dan cukup untuk motor harian yang rutin diganti. Oli semi sintetis menawarkan perlindungan sedikit lebih baik terhadap panas. Oli full sintetis paling tahan terhadap suhu tinggi dan oksidasi, sehingga stabil lebih lama, tetapi harganya juga paling tinggi.</p>
<p>Yang sering disalahpahami: oli sintetis bukan alasan untuk memperpanjang jadwal ganti secara berlebihan di lalu lintas padat. Debu, sisa pembakaran, dan uap air tetap menumpuk di dalam oli apa pun jenisnya. Untuk pemakaian harian di Medan, pilih jenis yang sesuai anggaran, lalu disiplin dengan jadwalnya. Itu jauh lebih berpengaruh daripada sekadar memilih merek paling mahal.</p>

<h2>Ganti sendiri atau di bengkel?</h2>
<p>Mengganti oli matic sendiri sebenarnya tidak sulit: buka baut pembuangan, tunggu oli lama habis, ganti ring baut bila perlu, kencangkan dengan torsi yang wajar, lalu isi oli baru sesuai takaran. Tantangannya ada pada hal-hal kecil, seperti membuang oli bekas dengan benar dan memastikan baut tidak terlalu kencang sampai merusak ulir di blok mesin.</p>
<p>Di bengkel, ganti oli biasanya sekaligus memberi kesempatan mekanik melihat hal lain: kampas rem, tekanan ban, rembesan oli, atau saringan udara yang kotor. Untuk biaya jasa yang kecil, pemeriksaan sekilas ini sering menyelamatkan dari kerusakan yang lebih besar.</p>

<h2>Jangan lupa oli gardan</h2>
<p>Oli gardan (oli transmisi akhir) sering terlewat karena wadahnya kecil, biasanya hanya sekitar 100–120 ml. Patokan umum: ganti oli gardan setiap dua sampai tiga kali ganti oli mesin, atau sekitar 8.000 km. Oli gardan yang terlalu tua membuat gigi lebih cepat aus dan bisa memunculkan dengung saat motor melaju.</p>

<h2>Kisaran biaya di Medan</h2>
<p>Berdasarkan template harga pasar Medan yang kami pakai di OtoGo, jasa ganti oli motor matic berkisar <strong>{$this->rp((int) $oilJob)}</strong>. Oli matic 0,8 L generik ada di kisaran <strong>{$this->rp($oilMatic)}</strong>, dan oli gardan 120 ml sekitar <strong>{$this->rp($gearOil)}</strong>. Harga oli bermerek tertentu tentu bisa lebih tinggi.</p>
{$this->disclaimer()}

<h2>Kebiasaan kecil yang membantu</h2>
<ol>
<li>Catat kilometer setiap ganti oli. Stiker kecil di dekat speedometer cukup, atau simpan riwayatnya di aplikasi.</li>
<li>Panaskan mesin secukupnya, satu sampai dua menit sudah memadai untuk mesin injeksi.</li>
<li>Hindari menggeber gas saat mesin masih dingin.</li>
<li>Bila motor sering melewati genangan, minta mekanik memeriksa apakah ada air yang masuk ke oli; oli yang berubah keruh seperti susu adalah tanda bahaya.</li>
</ol>
<blockquote><p>Patokan sederhana: kalau ragu, lebih baik ganti sedikit lebih cepat. Selisih biayanya jauh lebih kecil daripada biaya turun mesin.</p></blockquote>
HTML,
            ],
            [
                'slug' => 'tanda-kampas-rem-habis',
                'category' => 'perawatan-motor',
                'date' => '2026-10-07',
                'title' => 'Tanda Kampas Rem Habis dan Kapan Harus Diganti',
                'excerpt' => 'Bunyi decit, tuas rem makin dalam, atau jarak berhenti yang memanjang. Kenali tandanya sebelum cakram ikut rusak.',
                'meta' => 'Ciri kampas rem motor dan mobil yang sudah tipis, cara mengecek ketebalan, risiko menunda penggantian, dan estimasi biaya di Medan.',
                'content' => <<<HTML
<p>Rem adalah satu-satunya komponen yang tugasnya menghentikan kendaraan, tetapi justru sering diperiksa paling akhir. Kampas rem habis secara perlahan, sehingga perubahan rasanya mudah tidak disadari sampai suatu hari jarak pengereman terasa terlalu panjang.</p>

<h2>Lima tanda yang paling sering muncul</h2>
<ol>
<li><strong>Bunyi decit atau gesekan logam.</strong> Decit tipis bisa karena debu, tetapi suara "kriet" kasar saat mengerem biasanya berarti material kampas sudah sangat tipis dan pelat besinya mulai menyentuh cakram.</li>
<li><strong>Tuas atau pedal rem terasa lebih dalam.</strong> Semakin tipis kampas, semakin jauh piston kaliper harus bergerak.</li>
<li><strong>Jarak berhenti memanjang.</strong> Kalau kamu mulai mengerem lebih awal dari biasanya tanpa sadar, itu sinyal.</li>
<li><strong>Cakram tergores melingkar.</strong> Goresan dalam pada piringan cakram menandakan ada gesekan logam dengan logam.</li>
<li><strong>Minyak rem turun.</strong> Pada rem cakram hidrolik, level minyak di tabung ikut turun seiring kampas menipis.</li>
</ol>

<h2>Cara mengecek ketebalan kampas</h2>
<p>Pada rem cakram, intip kampas dari celah kaliper menggunakan senter. Banyak kampas memiliki alur di tengah sebagai indikator; bila alurnya sudah hampir hilang, saatnya ganti. Sebagai patokan umum, kampas cakram yang tersisa sekitar <strong>2 mm atau kurang</strong> sebaiknya diganti.</p>
<p>Pada rem tromol, beberapa motor memiliki tanda panah di tuas rem belakang dan tanda batas di tromol. Saat panah sudah melewati batas ketika rem ditekan penuh, kampas tromol perlu diganti.</p>

<h2>Kenapa jangan ditunda</h2>
<p>Kampas rem itu murah. Yang mahal adalah komponen di sekitarnya. Kampas yang dibiarkan habis akan menggerus piringan cakram atau tromol, dan biaya mengganti cakram bisa beberapa kali lipat harga kampas. Selain itu, rem yang tidak optimal di jalan basah jelas berisiko.</p>
<p>Di musim hujan, kampas cenderung lebih cepat aus karena air dan lumpur bercampur pasir menjadi amplas alami. Setelah melewati genangan atau jalan berlumpur, coba injak rem pelan beberapa kali agar permukaannya kering kembali.</p>

<h2>Rem cakram dan rem tromol, beda perawatannya</h2>
<p>Rem cakram terbuka sehingga panasnya cepat terbuang dan kondisinya mudah dilihat. Kekurangannya, piringan cakram langsung terkena air dan pasir. Rem tromol tertutup sehingga lebih terlindung dari kotoran, tetapi debu kampas menumpuk di dalam dan perlu dibersihkan saat servis. Pada rem tromol, rasa rem yang "keras" di awal lalu tiba-tiba blong sering disebabkan debu dan kampas yang mengeras.</p>
<p>Untuk motor matic yang roda belakangnya masih tromol, minta mekanik membersihkan tromol setiap servis berkala. Untuk rem cakram, cukup pastikan piringan tidak bengkok dan pin kaliper bergerak lancar.</p>

<h2>Kebiasaan yang membuat kampas lebih awet</h2>
<ul>
<li><strong>Baca lalu lintas lebih jauh ke depan.</strong> Melepas gas lebih awal mengurangi pengereman mendadak.</li>
<li><strong>Gunakan rem depan dan belakang bersamaan</strong> pada motor, supaya beban terbagi dan tidak satu sisi saja yang cepat habis.</li>
<li><strong>Jangan menahan rem terus-menerus di turunan panjang.</strong> Panas berlebih membuat kampas mengeras dan daya cengkeramnya turun.</li>
<li><strong>Kurangi beban berlebih.</strong> Bobot ekstra berarti energi yang harus diredam rem juga lebih besar.</li>
</ul>

<h2>Setelah ganti kampas: beri waktu adaptasi</h2>
<p>Kampas baru belum langsung menempel sempurna dengan permukaan cakram atau tromol. Selama 100–200 km pertama, hindari pengereman keras yang tidak perlu dan lakukan pengereman bertahap beberapa kali di jalan yang aman. Proses ini membuat permukaan kampas dan cakram saling menyesuaikan sehingga pengereman terasa pakem dan tidak berdecit.</p>

<h2>Sekalian periksa minyak rem</h2>
<p>Minyak rem bersifat menyerap air dari udara. Seiring waktu titik didihnya turun, dan rem bisa terasa "kosong" saat panas. Rekomendasi umum adalah mengganti minyak rem sekitar setiap dua tahun, atau mengikuti buku manual. Gunakan jenis yang sesuai, misalnya DOT 3 atau DOT 4, dan jangan mencampur jenis yang berbeda.</p>

<h2>Estimasi biaya</h2>
<p>Dari template harga pasar Medan di OtoGo, jasa ganti kampas rem motor per roda berkisar:</p>
<ul>
<li>Matic / bebek: <strong>{$this->rp((int) ($brakeJob['motor_matic'] ?? 0))}</strong></li>
<li>Sport: <strong>{$this->rp((int) ($brakeJob['motor_sport'] ?? 0))}</strong></li>
<li>Motor besar: <strong>{$this->rp((int) ($brakeJob['motor_big'] ?? 0))}</strong></li>
</ul>
<p>Kampas rem motor generik per set ada di kisaran <strong>{$this->rp($brakePad)}</strong>. Untuk mobil, servis rem dua roda berkisar <strong>{$this->rp((int) ($carBrake['car_city'] ?? 0))}</strong> untuk city car sampai <strong>{$this->rp((int) ($carBrake['car_premium'] ?? 0))}</strong> untuk van atau mobil premium, belum termasuk kampasnya.</p>
{$this->disclaimer()}

<h2>Ringkasnya</h2>
<p>Minta mekanik memeriksa rem setiap kali servis berkala, perhatikan bunyi dan rasa tuas rem, dan jangan menunggu sampai terdengar suara logam. Untuk motor yang dipakai harian, pemeriksaan setiap 2.000–4.000 km sudah cukup untuk menangkap keausan sebelum merusak komponen lain.</p>
HTML,
            ],
            [
                'slug' => 'servis-cvt-motor-matic',
                'category' => 'perawatan-motor',
                'date' => '2026-10-10',
                'title' => 'Servis CVT Motor Matic: Gejala, Jadwal, dan Biayanya',
                'excerpt' => 'Getar saat mulai jalan atau tarikan yang terasa berat sering berasal dari CVT. Kenali komponennya dan kapan perlu dibersihkan.',
                'meta' => 'Penjelasan CVT motor matic, gejala CVT kotor atau aus, jadwal servis dan ganti V-belt, serta estimasi biaya di Medan.',
                'content' => <<<HTML
<p>CVT (<em>continuously variable transmission</em>) adalah alasan motor matic bisa melaju tanpa oper gigi. Di dalam rumah CVT ada dua puli yang dihubungkan V-belt. Puli depan melebar dan menyempit mengikuti putaran mesin, sehingga perbandingan putaran berubah halus dari lambat ke cepat.</p>
<p>Karena bekerja dengan gesekan, CVT menghasilkan debu dari belt dan kampas kopling. Debu inilah yang membuat performanya turun pelan-pelan bila tidak dibersihkan.</p>

<h2>Komponen yang perlu kamu kenal</h2>
<ul>
<li><strong>V-belt</strong>: sabuk karet yang memindahkan tenaga dari puli depan ke puli belakang.</li>
<li><strong>Roller</strong>: pemberat di puli depan yang bergeser keluar saat putaran naik.</li>
<li><strong>Kampas ganda (kopling sentrifugal)</strong>: mengunci ke mangkok saat putaran cukup tinggi sehingga roda mulai berputar.</li>
<li><strong>Per CVT</strong>: menentukan kapan puli belakang menyempit kembali.</li>
</ul>

<h2>Gejala CVT minta diservis</h2>
<ol>
<li>Motor <strong>bergetar atau "gredek"</strong> saat mulai jalan dari posisi diam.</li>
<li>Tarikan awal terasa berat, padahal mesin sehat.</li>
<li>Muncul <strong>bunyi decit atau berisik</strong> dari sisi kiri motor.</li>
<li>Putaran mesin naik tetapi kecepatan tertahan, seperti selip.</li>
<li>Konsumsi bensin terasa lebih boros dari biasanya.</li>
</ol>

<h2>Jadwal perawatan yang umum</h2>
<p>Sebagai patokan umum, bersihkan CVT setiap sekitar <strong>8.000–10.000 km</strong>. V-belt biasanya diperiksa pada saat yang sama dan diganti sekitar <strong>20.000–25.000 km</strong> atau lebih cepat bila sudah retak, menipis, atau berserabut. Selalu ikuti angka di buku manual motormu, karena tiap model bisa berbeda.</p>
<p>Roller juga diperiksa: roller yang aus bentuknya tidak bulat lagi (ada sisi rata), sehingga akselerasi menjadi tidak halus.</p>

<h2>Apa saja yang dikerjakan saat servis CVT</h2>
<p>Supaya kamu tahu apa yang dibayar, berikut urutan kerja servis CVT yang umum di bengkel:</p>
<ol>
<li>Membuka tutup CVT dan memeriksa kondisi umum: ada air, karat, atau komponen yang patah.</li>
<li>Melepas puli depan, puli belakang, dan V-belt.</li>
<li>Membersihkan debu belt dan kampas dengan kuas, udara bertekanan, dan cairan pembersih yang tidak meninggalkan minyak.</li>
<li>Mengukur lebar V-belt dan memeriksa retakan di sisi dalamnya.</li>
<li>Memeriksa roller, slider, dan permukaan puli dari keausan.</li>
<li>Memeriksa ketebalan kampas ganda dan permukaan mangkok.</li>
<li>Merakit kembali dengan torsi yang benar dan mencoba motor.</li>
</ol>
<p>Satu hal penting: puli dan permukaan kampas harus bebas dari oli atau gemuk. Sedikit saja kontaminasi membuat belt atau kopling selip, jadi pastikan mekanik bekerja rapi.</p>

<h2>Perlu ganti roller atau per yang lebih "racing"?</h2>
<p>Mengganti bobot roller atau kekerasan per memang bisa mengubah karakter tarikan, misalnya terasa lebih responsif di putaran bawah. Tetapi setiap perubahan ada kompromi, mulai dari konsumsi bensin sampai kecepatan puncak. Untuk pemakaian harian, komponen standar pabrik umumnya sudah paling seimbang. Kalau ingin mengubahnya, lakukan bertahap dan tanyakan alasannya kepada mekanik yang memang memahami model motormu.</p>

<h2>Musuh utama CVT: air dan lumpur</h2>
<p>Saat hujan deras, beberapa ruas jalan di Medan mudah tergenang. Menerobos genangan yang tinggi berisiko membuat air masuk ke rumah CVT lewat saluran udaranya. Akibatnya belt dan kampas bisa selip, dan bila dibiarkan, komponen logam di dalamnya berkarat.</p>
<ul>
<li>Hindari genangan yang lebih tinggi dari as roda.</li>
<li>Kalau terpaksa menerobos, jalan pelan dan stabil, jangan berhenti di tengah genangan.</li>
<li>Setelah melewati banjir, minta mekanik memeriksa CVT, terutama bila muncul selip.</li>
<li>Saat mencuci motor, jangan menyemprot air bertekanan langsung ke saluran udara CVT.</li>
</ul>

<h2>Estimasi biaya di Medan</h2>
<p>Menurut template harga pasar Medan di OtoGo, jasa servis CVT motor matic berkisar <strong>{$this->rp((int) $cvtJob)}</strong>. Bila V-belt perlu diganti, harga V-belt generik ada di kisaran <strong>{$this->rp($vbelt)}</strong>. Roller dan kampas ganda dihitung terpisah bila memang perlu diganti.</p>
{$this->disclaimer()}

<h2>Pertanyaan yang sering muncul</h2>
<h3>Apakah CVT perlu "diservis" kalau motor masih terasa normal?</h3>
<p>Ya, karena penurunan performa CVT terjadi perlahan sehingga sering tidak terasa. Pembersihan rutin mencegah debu menumpuk dan memberi kesempatan memeriksa keausan belt sebelum putus.</p>
<h3>Bolehkah memakai V-belt merek lain?</h3>
<p>Boleh, asalkan ukurannya sesuai spesifikasi motor dan kualitasnya jelas. V-belt yang ukurannya sedikit berbeda bisa mengubah karakter tarikan atau mempercepat keausan puli.</p>
<h3>Apakah bunyi "ngorok" dari CVT berbahaya?</h3>
<p>Bunyi kasar bisa berasal dari bearing, kampas ganda, atau mangkok yang aus. Sebaiknya segera diperiksa, karena komponen yang lepas di dalam rumah CVT dapat merusak bagian lain.</p>

<h2>Tips agar CVT awet</h2>
<ul>
<li>Buka gas secara halus saat mulai jalan; hentakan membuat kampas ganda cepat habis.</li>
<li>Jangan membawa beban melebihi kapasitas motor secara rutin.</li>
<li>Gabungkan servis CVT dengan jadwal ganti oli gardan supaya tidak ada yang terlewat.</li>
</ul>
<p>CVT yang bersih membuat motor terasa enteng lagi. Biayanya juga jauh lebih ringan daripada menunggu belt putus di tengah jalan.</p>
HTML,
            ],
            [
                'slug' => 'cuci-salju-vs-cuci-biasa',
                'category' => 'doorsmeer-detailing',
                'date' => '2026-10-13',
                'title' => 'Cuci Salju vs Cuci Biasa: Mana yang Lebih Aman untuk Cat Kendaraan?',
                'excerpt' => 'Busa tebal di doorsmeer bukan sekadar gaya. Ini perbedaan cara kerjanya, kapan cuci salju sepadan, dan cara cuci yang tidak meninggalkan baret halus.',
                'meta' => 'Perbedaan cuci salju (snow wash) dan cuci biasa, kelebihan dan kekurangannya, langkah cuci yang benar, dan kisaran harga doorsmeer di Medan.',
                'content' => <<<HTML
<p>Hampir semua doorsmeer di Medan sekarang menawarkan "cuci salju". Kendaraan diselimuti busa putih tebal, didiamkan sebentar, lalu dibilas. Apakah memang lebih baik daripada cuci biasa dengan sabun dan spons? Jawabannya: tergantung bagaimana keduanya dikerjakan.</p>

<h2>Bagaimana cuci salju bekerja</h2>
<p>Cuci salju memakai sabun khusus yang dicampur udara lewat alat semprot (<em>foam lance</em>) sehingga menghasilkan busa kental. Busa ini menempel di permukaan bodi, melarutkan dan mengangkat debu serta kotoran sebelum ada kain atau spons yang menyentuh cat.</p>
<p>Prinsip pentingnya adalah <strong>mengurangi gesekan</strong>. Baret halus melingkar yang terlihat di bawah sinar matahari (<em>swirl marks</em>) kebanyakan muncul karena butiran pasir terseret spons saat mencuci. Semakin banyak kotoran terangkat sebelum disentuh, semakin kecil risikonya.</p>

<h2>Cuci biasa tidak selalu buruk</h2>
<p>Cuci biasa dengan sabun dan spons tetap bisa aman, asalkan kendaraan dibilas dengan air yang cukup terlebih dahulu dan spons atau lapnya bersih. Masalah muncul ketika spons yang sama dipakai berulang tanpa dibilas, sehingga pasir dari kendaraan sebelumnya ikut menggores.</p>

<h2>Perbandingan singkat</h2>
<table><thead><tr><th>Aspek</th><th>Cuci salju</th><th>Cuci biasa</th></tr></thead><tbody>
<tr><td>Risiko baret halus</td><td>Lebih rendah bila dikerjakan benar</td><td>Bergantung kebersihan spons</td></tr>
<tr><td>Kotoran tebal / lumpur</td><td>Lebih efektif, busa sempat melunakkan</td><td>Butuh bilasan dan gosokan lebih banyak</td></tr>
<tr><td>Waktu</td><td>Sedikit lebih lama (ada waktu diam busa)</td><td>Lebih cepat</td></tr>
<tr><td>Harga</td><td>Umumnya sedikit lebih mahal</td><td>Umumnya lebih murah</td></tr>
</tbody></table>

<h2>Langkah cuci yang benar</h2>
<ol>
<li><strong>Bilas awal</strong> untuk menjatuhkan debu dan pasir yang menempel longgar.</li>
<li><strong>Semprotkan busa</strong> dari bawah ke atas, lalu diamkan sekitar 3–5 menit. Jangan sampai busa mengering di bawah terik matahari; di siang hari Medan yang panas, kerjakan di tempat teduh.</li>
<li><strong>Bilas busa</strong> dari atas ke bawah.</li>
<li><strong>Cuci tangan dengan dua ember</strong>: satu berisi sabun, satu berisi air bersih untuk membilas mitt sebelum dicelup lagi.</li>
<li><strong>Keringkan dengan kain microfiber</strong> yang bersih, ditepuk atau ditarik searah, bukan digosok memutar.</li>
</ol>

<h2>Seberapa sering sebaiknya mencuci?</h2>
<p>Untuk kendaraan harian di kota, mencuci setiap satu sampai dua minggu sudah cukup. Yang lebih penting adalah tidak membiarkan kotoran tertentu terlalu lama menempel di cat:</p>
<ul>
<li><strong>Kotoran burung dan getah pohon</strong>: bersifat asam dan bisa meninggalkan bekas permanen bila terpanggang matahari. Bersihkan secepatnya.</li>
<li><strong>Lumpur setelah hujan</strong>: mengandung pasir halus yang menggores saat mengering. Bilas, jangan dilap kering.</li>
<li><strong>Bekas air hujan yang mengering</strong>: meninggalkan noda mineral (<em>water spot</em>) yang makin sulit dihapus jika dibiarkan.</li>
</ul>

<h2>Kesalahan umum saat mencuci sendiri</h2>
<ol>
<li><strong>Memakai sabun cuci piring.</strong> Sabun ini dirancang mengangkat lemak dan ikut mengikis lapisan wax pelindung cat.</li>
<li><strong>Mencuci di bawah terik matahari.</strong> Sabun mengering sebelum dibilas dan meninggalkan noda.</li>
<li><strong>Satu lap untuk semua bagian.</strong> Lap yang dipakai untuk velg dan bagian bawah penuh pasir; jangan dipakai lagi untuk kap atau atap.</li>
<li><strong>Menggosok memutar.</strong> Gerakan lurus searah lebih kecil kemungkinannya meninggalkan baret melingkar.</li>
</ol>

<h2>Layanan tambahan yang sering diambil</h2>
<p>Selain cuci luar, banyak pelanggan mengambil add-on seperti cuci kolong setelah musim hujan, wax untuk melindungi cat, atau vakum interior. Untuk MPV, template harga pasar Medan OtoGo mencatat cuci + vakum interior sekitar <strong>{$this->rp((int) $vacuum)}</strong>, cuci kolong sekitar <strong>{$this->rp((int) $underbody)}</strong>, dan wax sekitar <strong>{$this->rp((int) $wax)}</strong>.</p>

<h2>Kisaran harga cuci salju di Medan</h2>
<p>Berikut kisaran harga cuci salju reguler per kategori kendaraan dari template yang sama:</p>
{$this->washTable()}
{$this->disclaimer()}

<h2>Wax, sealant, atau coating?</h2>
<p>Setelah dicuci, cat bisa diberi lapisan pelindung. <strong>Wax</strong> memberi kilap hangat dan perlindungan beberapa minggu. <strong>Sealant</strong> berbasis polimer biasanya bertahan lebih lama, hitungan bulan. <strong>Coating</strong> berbasis keramik paling awet dan membuat air mudah meluncur, tetapi butuh persiapan permukaan yang teliti dan biayanya paling tinggi. Untuk kendaraan harian, wax rutin atau sealant sudah cukup membantu cat tetap terlindung dari panas dan hujan.</p>

<h2>Jadi, pilih yang mana?</h2>
<p>Untuk kendaraan harian yang catnya masih bagus, cuci salju adalah pilihan yang lebih aman untuk jangka panjang. Untuk kendaraan kerja yang sering kotor berat, cuci salju membantu mengangkat lumpur tanpa gosokan berlebihan. Apa pun pilihannya, perhatikan satu hal di doorsmeer langgananmu: apakah lap dan spons mereka bersih. Itu yang paling menentukan.</p>
HTML,
            ],
            [
                'slug' => 'merawat-mobil-musim-hujan-medan',
                'category' => 'perawatan-mobil',
                'date' => '2026-10-16',
                'title' => 'Merawat Mobil saat Musim Hujan di Medan: Daftar Periksa Sebelum Hujan Deras',
                'excerpt' => 'Wiper, ban, rem, lampu, sampai kolong mobil. Daftar periksa praktis supaya mobil siap menghadapi hujan dan genangan.',
                'meta' => 'Checklist perawatan mobil saat musim hujan di Medan: wiper, ban, rem, lampu, aki, AC, kolong, dan cara aman melewati genangan.',
                'content' => <<<HTML
<p>Menjelang akhir tahun, curah hujan di Medan biasanya meningkat. Hujan deras sore hari, genangan di beberapa ruas jalan, dan jalan yang licin menuntut mobil dalam kondisi prima. Kabar baiknya, sebagian besar persiapannya sederhana dan bisa dicek sendiri.</p>

<h2>1. Wiper dan kaca</h2>
<p>Karet wiper mengeras karena panas matahari, apalagi di kota yang terik seperti Medan. Wiper yang sudah keras meninggalkan garis-garis air dan berbunyi saat bergerak. Umumnya karet wiper diganti setiap 6–12 bulan. Template harga pasar Medan OtoGo mencatat wiper blade generik sepasang sekitar <strong>{$this->rp($wiper)}</strong>.</p>
<ul>
<li>Isi tabung air wiper dan pastikan semprotannya mengarah ke kaca.</li>
<li>Bersihkan kaca dari lapisan minyak agar air mudah tersapu.</li>
</ul>

<h2>2. Ban dan kaki-kaki</h2>
<p>Alur ban berfungsi membuang air dari bawah tapak ban. Ban yang alurnya tipis lebih mudah mengalami <em>aquaplaning</em>, yaitu ban "mengambang" di atas lapisan air. Periksa indikator keausan (TWI) di alur ban; bila permukaan ban sudah rata dengan indikator itu, ban sebaiknya diganti.</p>
<p>Jika setir terasa menarik ke satu sisi atau ban aus tidak rata, lakukan spooring dan balancing. Untuk MPV, kisarannya sekitar <strong>{$this->rp((int) $spooring)}</strong> untuk spooring dan <strong>{$this->rp((int) $balancing)}</strong> untuk balancing empat roda.</p>

<h2>3. Rem</h2>
<p>Jalan basah memperpanjang jarak pengereman. Pastikan kampas rem masih tebal dan minyak rem dalam batas normal. Setelah melewati genangan, injak rem pelan beberapa kali agar cakram dan kampas kering kembali.</p>

<h2>4. Lampu dan kelistrikan</h2>
<ul>
<li>Cek lampu utama, lampu kabut, lampu rem, dan lampu sein.</li>
<li>Perhatikan kondisi aki. Aki yang sudah lemah sering "menyerah" di cuaca lembap.</li>
<li>Pastikan karet pintu dan bagasi tidak getas supaya air tidak merembes ke kabin.</li>
</ul>

<h2>5. AC dan defogger</h2>
<p>Kaca berembun di dalam saat hujan adalah hal biasa, tetapi AC yang bekerja baik menghapus embun dengan cepat. Jika AC mulai berbau apek atau kurang dingin, evaporatornya mungkin kotor. Servis AC (cuci evaporator) untuk MPV berkisar <strong>{$this->rp((int) $acService)}</strong> menurut template yang sama.</p>

<h2>6. Kolong mobil</h2>
<p>Lumpur dan air genangan yang menempel di kolong mempercepat karat. Setelah beberapa kali melewati jalan becek, ambil layanan cuci kolong. Untuk MPV, kisarannya sekitar <strong>{$this->rp((int) $underbody)}</strong>.</p>

<h2>Melewati genangan dengan aman</h2>
<ol>
<li>Perkirakan kedalaman air. Bila lebih dari setengah tinggi ban, pertimbangkan mencari jalan lain.</li>
<li>Gunakan gigi rendah dan jaga putaran mesin stabil. Jangan berhenti di tengah genangan.</li>
<li>Jaga jarak dengan kendaraan di depan agar gelombang airnya tidak masuk ke ruang mesin.</li>
<li>Jika mesin mati di dalam genangan, <strong>jangan dicoba distarter ulang</strong>. Air yang terhisap ke ruang bakar bisa merusak mesin (<em>water hammer</em>). Dorong mobil ke tempat aman dan hubungi bengkel.</li>
</ol>
{$this->disclaimer()}

<h2>Kalau mobil sempat terendam</h2>
<p>Jika air sudah sempat masuk ke kabin atau mencapai bagian bawah mesin, jangan buru-buru menganggap semuanya baik-baik saja hanya karena mesin masih bisa hidup. Langkah yang dianjurkan:</p>
<ol>
<li>Jangan menyalakan mesin bila diduga air masuk ke saluran udara. Minta bengkel memeriksa filter udara dan ruang bakar terlebih dahulu.</li>
<li>Lepas terminal negatif aki untuk mencegah korsleting pada sistem kelistrikan.</li>
<li>Periksa oli mesin dan oli transmisi. Oli yang berwarna keruh seperti susu berarti tercampur air dan harus diganti.</li>
<li>Keringkan karpet dan peredam di kabin. Karpet yang lembap memicu bau apek dan karat di lantai mobil.</li>
<li>Periksa rem, bearing roda, dan sambungan kelistrikan di kolong.</li>
</ol>

<h2>Setelah musim hujan berlalu</h2>
<p>Begitu intensitas hujan menurun, lakukan satu kali pemeriksaan menyeluruh: cuci kolong untuk membuang sisa lumpur, periksa karat di sambungan knalpot dan baut kaki-kaki, keringkan dan bersihkan karpet, serta cek ulang spooring bila mobil sering menghantam lubang jalan yang tertutup genangan. Langkah ini membantu mencegah kerusakan yang baru muncul berbulan-bulan kemudian.</p>

<h2>Persiapan kecil di dalam mobil</h2>
<p>Selain kondisi teknis, beberapa barang sederhana di dalam mobil membuat perjalanan saat hujan lebih tenang: payung, kain microfiber untuk mengelap embun, senter, kabel pengisi daya, dan nomor kontak bengkel atau layanan derek yang kamu percaya. Simpan juga segitiga pengaman di tempat yang mudah dijangkau, bukan di dasar bagasi yang tertimbun barang.</p>

<h2>Daftar periksa singkat</h2>
<ul>
<li>Wiper dan air wiper</li>
<li>Alur ban dan tekanan angin</li>
<li>Kampas dan minyak rem</li>
<li>Semua lampu dan kondisi aki</li>
<li>AC dan defogger</li>
<li>Karet pintu, saluran air di kap dan pintu</li>
</ul>
<p>Sepuluh menit pemeriksaan ini bisa mencegah kerepotan di tengah hujan deras.</p>
HTML,
            ],
            [
                'slug' => 'cara-memilih-aki-mobil-dan-motor',
                'category' => 'perawatan-mobil',
                'date' => '2026-10-20',
                'title' => 'Cara Memilih Aki Mobil dan Motor: Jenis, Kode, dan Tanda Aki Lemah',
                'excerpt' => 'Aki basah, kering, atau hybrid? Apa arti kode NS40 dan angka CCA? Panduan memilih aki yang pas supaya tidak salah beli.',
                'meta' => 'Panduan memilih aki mobil dan motor: jenis aki, membaca kode ukuran dan CCA, tanda aki lemah, umur aki di iklim panas, dan kisaran harga.',
                'content' => <<<HTML
<p>Aki jarang dipikirkan sampai pagi hari mobil tidak mau hidup. Di iklim panas seperti Medan, aki bekerja lebih berat karena suhu tinggi mempercepat reaksi kimia di dalamnya, sehingga umurnya cenderung lebih pendek dibanding daerah sejuk.</p>

<h2>Jenis-jenis aki</h2>
<ul>
<li><strong>Aki basah (konvensional)</strong>: harganya paling terjangkau, tetapi air aki perlu dicek dan ditambah secara berkala.</li>
<li><strong>Aki hybrid</strong>: perawatan lebih jarang daripada aki basah, karena penguapan airnya lebih sedikit.</li>
<li><strong>Aki kering / MF (<em>maintenance free</em>)</strong>: tertutup rapat dan tidak perlu menambah air. Paling praktis untuk pemakaian harian dan paling umum di motor modern.</li>
<li><strong>AGM / EFB</strong>: dipakai pada mobil dengan fitur <em>start-stop</em> atau kebutuhan listrik besar. Jika mobilmu memakai jenis ini dari pabrik, gantilah dengan jenis yang sama.</li>
</ul>

<h2>Membaca spesifikasi aki</h2>
<p>Sebelum membeli, catat spesifikasi aki lama atau lihat buku manual:</p>
<ul>
<li><strong>Tegangan</strong>: hampir semua mobil dan motor memakai 12 volt.</li>
<li><strong>Kapasitas (Ah)</strong>: seberapa banyak energi yang bisa disimpan.</li>
<li><strong>CCA (<em>cold cranking amps</em>)</strong>: kemampuan memberi arus besar saat starter. Jangan memilih CCA yang lebih rendah dari standar.</li>
<li><strong>Kode ukuran</strong>, misalnya NS40 atau NS60 pada mobil: menunjukkan dimensi dan kelas aki sehingga pas di dudukannya.</li>
<li><strong>Posisi terminal</strong>: kiri atau kanan. Posisi yang terbalik membuat kabel tidak sampai atau tertekuk berbahaya.</li>
</ul>

<h2>Tanda aki mulai lemah</h2>
<ol>
<li>Starter terdengar berat dan lambat, terutama pagi hari.</li>
<li>Lampu terlihat redup saat mesin langsam.</li>
<li>Klakson melemah atau fitur elektronik sering mati sendiri.</li>
<li>Muncul lapisan putih kebiruan di terminal (korosi).</li>
<li>Bodi aki menggembung. Ini tanda harus segera diganti.</li>
</ol>
<p>Di iklim panas, aki umumnya bertahan sekitar <strong>1,5–3 tahun</strong>, tergantung jenis, kualitas pengisian dari alternator, dan pola pemakaian. Mobil yang jarang dipakai juga lebih cepat menguras aki.</p>

<h2>Mengecek aki dengan multimeter</h2>
<p>Multimeter digital murah sudah cukup untuk mengetahui kondisi dasar aki. Ukur dengan mesin mati dan sebaiknya setelah kendaraan didiamkan beberapa jam:</p>
<table><thead><tr><th>Tegangan (mesin mati)</th><th>Perkiraan kondisi</th></tr></thead><tbody>
<tr><td>sekitar 12,6 V atau lebih</td><td>Terisi penuh</td></tr>
<tr><td>sekitar 12,4 V</td><td>Kira-kira 75%</td></tr>
<tr><td>sekitar 12,2 V</td><td>Kira-kira setengah, perlu diisi</td></tr>
<tr><td>di bawah 12,0 V</td><td>Lemah, periksa aki dan sistem pengisian</td></tr>
</tbody></table>
<p>Lalu ukur lagi saat mesin hidup. Tegangan sekitar 13,7–14,7 V menandakan alternator atau kiprok mengisi dengan normal. Jika tegangan saat mesin hidup tidak naik, masalahnya mungkin bukan pada aki, melainkan pada sistem pengisian. Mengganti aki saja tidak akan menyelesaikannya.</p>
<p>Catatan: angka di atas adalah patokan umum untuk aki 12 V. Tes beban di bengkel memberi gambaran yang lebih akurat karena mengukur kemampuan aki saat benar-benar dibebani starter.</p>

<h2>Cara jumper aki yang aman</h2>
<ol>
<li>Pastikan kedua kendaraan mati dan tegangan akinya sama, 12 V.</li>
<li>Pasang kabel merah ke kutub positif aki yang lemah, lalu ke kutub positif aki pendonor.</li>
<li>Pasang kabel hitam ke kutub negatif aki pendonor, lalu ujung lainnya ke bagian logam tanpa cat di mesin kendaraan yang mogok, bukan langsung ke kutub negatif akinya.</li>
<li>Hidupkan kendaraan pendonor, tunggu beberapa menit, lalu coba starter kendaraan yang mogok.</li>
<li>Lepas kabel dengan urutan terbalik.</li>
</ol>

<h2>Tips saat membeli</h2>
<ul>
<li>Cek <strong>kode produksi</strong>. Aki yang terlalu lama tersimpan di gudang sudah kehilangan sebagian kapasitasnya.</li>
<li>Minta pengukuran tegangan dan tes beban sebelum dipasang.</li>
<li>Tanyakan garansi dan syaratnya.</li>
<li>Untuk motor, cocokkan kode aki dengan yang tertera di aki lama atau buku manual.</li>
</ul>

<h2>Kisaran harga</h2>
<p>Template harga pasar Medan di OtoGo mencatat aki motor kering generik di kisaran <strong>{$this->rp($batteryMotor)}</strong> dan aki mobil NS40 sekitar <strong>{$this->rp($batteryCar)}</strong>. Aki bermerek atau berkapasitas lebih besar tentu berbeda harganya.</p>
{$this->disclaimer()}

<h2>Khusus aki motor</h2>
<p>Motor injeksi modern sangat bergantung pada aki, bukan hanya untuk starter tetapi juga untuk sistem injeksi dan lampu yang selalu menyala. Karena kapasitas aki motor kecil, kebiasaan kecil berpengaruh besar: memakai starter berulang kali dalam waktu singkat, memasang aksesori listrik tambahan tanpa perhitungan, atau membiarkan motor tidak dipakai berminggu-minggu.</p>
<p>Jika motor akan lama tidak dipakai, lepas terminal negatif aki atau hidupkan mesin secara berkala. Bila starter mulai terasa berat, jangan tunggu sampai benar-benar mati; minta bengkel mengukur tegangan aki dan output kiproknya sekaligus.</p>

<h2>Merawat aki supaya awet</h2>
<ul>
<li>Matikan lampu dan audio sebelum mesin dimatikan.</li>
<li>Jika mobil jarang dipakai, nyalakan mesin sesekali atau jalankan beberapa kilometer.</li>
<li>Bersihkan terminal dari korosi dan pastikan klemnya kencang.</li>
<li>Parkir di tempat teduh bila memungkinkan; panas berlebih memperpendek umur aki.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'estimasi-biaya-servis-berkala-mobil-medan-2026',
                'category' => 'biaya-tips-hemat',
                'date' => '2026-10-24',
                'title' => 'Estimasi Biaya Servis Berkala Mobil di Medan 2026',
                'excerpt' => 'Berapa kisaran jasa servis berkala, tune-up, spooring, sampai servis AC per jenis mobil? Tabel estimasi dari template harga pasar Medan.',
                'meta' => 'Tabel estimasi biaya servis berkala mobil di Medan 2026 per kategori mobil: jasa servis, ganti oli, tune-up, spooring, balancing, rem, AC, dan contoh hitungan.',
                'content' => <<<HTML
<p>Pertanyaan pertama sebelum ke bengkel hampir selalu sama: kira-kira habis berapa? Artikel ini merangkum kisaran harga jasa dari template harga pasar Medan 2026 yang kami pakai di OtoGo. Template ini menjadi titik awal bagi bengkel mitra, yang kemudian menyesuaikan harganya sendiri.</p>
<blockquote><p>Angka di bawah adalah <strong>harga jasa</strong>, belum termasuk oli dan sparepart, kecuali disebutkan lain.</p></blockquote>

<h2>Servis rutin</h2>
{$this->carServiceTable(['Servis berkala ringan mobil', 'Ganti oli mobil (jasa)', 'Tune-up mobil'])}
<p>Servis berkala ringan biasanya mencakup pemeriksaan menyeluruh, penggantian oli dan filter oli, pembersihan filter udara, serta pemeriksaan rem, kaki-kaki, dan kelistrikan. Tune-up lebih mendalam: pembersihan throttle body atau injektor, pemeriksaan busi, dan penyetelan ulang.</p>

<h2>Kaki-kaki dan rem</h2>
{$this->carServiceTable(['Spooring', 'Balancing 4 roda', 'Servis rem 2 roda'])}
<p>Spooring dan balancing dianjurkan setiap kali mengganti ban, setelah mobil menghantam lubang cukup keras, atau saat setir terasa menarik ke satu sisi.</p>

<h2>AC, radiator, dan diagnosa</h2>
{$this->carServiceTable(['Servis AC (cuci evaporator)', 'Kuras radiator', 'Scan OBD / diagnosa'])}

<h2>Contoh hitungan: MPV servis berkala ringan</h2>
<p>Misalnya kamu membawa MPV untuk servis berkala ringan dengan ganti oli dan filter oli. Dengan oli dan filter generik dari template yang sama:</p>
<ul>
<li>Jasa servis berkala ringan: {$this->rp((int) $mpvService)}</li>
<li>Oli mobil 4 liter: {$this->rp($carOil)}</li>
<li>Filter oli: {$this->rp($oilFilter)}</li>
</ul>
<p>Totalnya sekitar <strong>{$this->rp($mpvTotal)}</strong>. Jika filter udara juga perlu diganti ({$this->rp($airFilter)}), estimasinya menjadi sekitar <strong>{$this->rp($mpvTotalAir)}</strong>.</p>
{$this->disclaimer()}

<h2>Kapan servis berkala dilakukan?</h2>
<p>Banyak pabrikan memakai patokan setiap <strong>10.000 km atau 6 bulan</strong>, mana yang lebih dulu tercapai, dengan pekerjaan yang lebih lengkap di kelipatan tertentu (misalnya 20.000 dan 40.000 km). Mobil yang sering terjebak macet, dipakai jarak pendek, atau melewati jalan berdebu masuk kategori pemakaian berat, sehingga beberapa item seperti oli bisa diganti lebih sering. Selalu jadikan buku servis mobilmu sebagai acuan utama.</p>

<h2>Komponen yang sering ikut diganti</h2>
<p>Selain jasa, nota servis biasanya berisi beberapa komponen habis pakai. Kisaran harga generik dari template yang sama:</p>
<table><thead><tr><th>Komponen</th><th>Kisaran harga</th></tr></thead><tbody>
<tr><td>Oli mobil 4 liter</td><td>{$this->rp($carOil)}</td></tr>
<tr><td>Filter oli</td><td>{$this->rp($oilFilter)}</td></tr>
<tr><td>Filter udara</td><td>{$this->rp($airFilter)}</td></tr>
<tr><td>Kampas rem depan (set)</td><td>{$this->rp($this->product('Kampas rem depan mobil (set)'))}</td></tr>
<tr><td>Wiper blade (sepasang)</td><td>{$this->rp($wiper)}</td></tr>
<tr><td>Air radiator coolant 1 liter</td><td>{$this->rp($this->product('Air radiator coolant 1 L'))}</td></tr>
<tr><td>Aki mobil NS40</td><td>{$this->rp($batteryCar)}</td></tr>
</tbody></table>
<p>Tidak semua item ini diganti di setiap servis. Filter udara misalnya cukup dibersihkan dan baru diganti bila sudah terlalu kotor atau robek, sedangkan aki diganti hanya bila hasil tesnya memang lemah.</p>

<h2>Cara membaca nota servis</h2>
<ul>
<li><strong>Pisahkan jasa dan sparepart.</strong> Nota yang baik menulis keduanya secara terpisah.</li>
<li><strong>Cek jumlah dan satuan.</strong> Misalnya oli tertulis 4 liter, bukan sekadar "oli".</li>
<li><strong>Tanyakan item yang tidak kamu kenal.</strong> Mekanik yang baik tidak keberatan menjelaskan fungsinya.</li>
<li><strong>Minta komponen lama dikembalikan</strong> bila kamu ingin melihat kondisinya.</li>
</ul>

<h2>Bengkel resmi atau bengkel umum?</h2>
<p>Bengkel resmi dealer biasanya memiliki daftar harga baku per model dan sering memberi jasa gratis di masa garansi untuk servis tertentu. Bengkel umum cenderung lebih fleksibel soal harga dan pilihan sparepart. Yang penting di keduanya: minta rincian pekerjaan dan suku cadang sebelum dikerjakan.</p>

<h2>Kenapa harga bisa berbeda antar bengkel?</h2>
<p>Selisih harga untuk pekerjaan yang sama biasanya berasal dari peralatan yang dipakai (misalnya mesin spooring komputer), pengalaman mekanik pada merek tertentu, garansi pekerjaan, dan lokasi. Harga yang lebih tinggi tidak selalu lebih baik, dan harga yang jauh lebih murah perlu ditanyakan apa saja yang tidak termasuk. Membandingkan rincian pekerjaan lebih adil daripada membandingkan angka total saja.</p>

<h2>Tips agar biaya servis tetap terkendali</h2>
<ol>
<li><strong>Ikuti jadwal servis.</strong> Masalah kecil yang terdeteksi lebih awal hampir selalu lebih murah.</li>
<li><strong>Minta estimasi tertulis</strong> sebelum mobil dikerjakan, termasuk daftar sparepart.</li>
<li><strong>Setujui pekerjaan tambahan secara eksplisit.</strong> Di OtoGo, pekerjaan tambahan harus kamu setujui di aplikasi sebelum dikerjakan.</li>
<li><strong>Simpan riwayat servis.</strong> Riwayat yang rapi juga menaikkan nilai jual kembali mobil.</li>
<li><strong>Bandingkan, tapi jangan hanya harga.</strong> Perhatikan garansi pekerjaan dan kejelasan sparepart yang dipakai.</li>
</ol>
HTML,
            ],
            [
                'slug' => 'home-service-atau-antar-jemput',
                'category' => 'biaya-tips-hemat',
                'date' => '2026-10-28',
                'title' => 'Home Service atau Antar Jemput: Mana yang Cocok untuk Kendaraanmu?',
                'excerpt' => 'Keduanya menghemat waktu, tapi tidak semua pekerjaan bisa dilakukan di rumah. Ini cara memilih, plus contoh hitungan biayanya.',
                'meta' => 'Perbedaan home service dan antar jemput kendaraan, pekerjaan yang cocok untuk masing-masing, cara menghitung biaya per km, dan tips serah terima yang aman.',
                'content' => <<<HTML
<p>Antre di bengkel sambil menunggu berjam-jam bukan pilihan semua orang. Itulah kenapa OtoGo menyediakan dua cara layanan selain datang ke outlet: <strong>layanan di rumah</strong> (petugas datang dan mengerjakan di alamatmu) dan <strong>antar jemput</strong> (kendaraan dijemput, dikerjakan di outlet, lalu diantar kembali). Keduanya praktis, tetapi cocok untuk kebutuhan yang berbeda.</p>

<h2>Kapan layanan di rumah paling pas</h2>
<p>Layanan di rumah cocok untuk pekerjaan ringan yang tidak membutuhkan lift, mesin spooring, atau peralatan besar:</p>
<ul>
<li>Ganti oli dan filter oli</li>
<li>Ganti aki</li>
<li>Servis ringan motor</li>
<li>Cuci dan vakum interior, wax, atau fogging</li>
<li>Ganti wiper, bohlam, atau pemeriksaan ringan</li>
</ul>
<p>Untuk cuci di rumah, siapkan sumber air, colokan listrik, dan lahan yang cukup untuk bekerja. Petugas biasanya menanyakan hal ini saat kamu memesan.</p>

<h2>Kapan antar jemput lebih masuk akal</h2>
<p>Pilih antar jemput untuk pekerjaan yang memerlukan fasilitas bengkel:</p>
<ul>
<li>Spooring dan balancing</li>
<li>Servis AC dan kuras radiator</li>
<li>Servis rem, kaki-kaki, atau CVT yang perlu bongkar</li>
<li>Diagnosa kelistrikan dengan alat khusus</li>
<li>Salon interior atau poles body yang butuh ruang tertutup</li>
</ul>

<h2>Cara biayanya dihitung</h2>
<p>Pada template default OtoGo, biaya logistik terdiri dari biaya dasar yang sudah mencakup beberapa kilometer pertama, lalu tarif per km berikutnya. Jarak dihitung dari titik outlet ke titik alamatmu, dan alamat di luar radius maksimum tidak bisa dipesan.</p>
{$this->logisticsTable()}
<p>Untuk antar jemput, biaya di atas sudah mencakup perjalanan pergi dan pulang.</p>

<h3>Contoh hitungan</h3>
<ul>
<li>Mobil, jarak 6 km, <strong>antar jemput</strong>: {$this->rp($pickupCar6)}.</li>
<li>Mobil, jarak 6 km, <strong>layanan di rumah</strong>: {$this->rp($homeCar6)}.</li>
<li>Motor, jarak 4,5 km, <strong>antar jemput</strong>: {$this->rp($pickupMotor4)}.</li>
<li>Motor, jarak 4,5 km, <strong>layanan di rumah</strong>: {$this->rp($homeMotor4)}.</li>
</ul>
<p>Kilometer di atas batas "termasuk" dibulatkan ke atas, jadi 4,5 km dihitung sebagai 2 km tambahan setelah 3 km pertama. Setiap outlet boleh mengatur tarifnya sendiri, dan biaya final selalu tampil sebelum kamu membayar.</p>
{$this->disclaimer()}

<h2>Perbandingan cepat</h2>
<table><thead><tr><th>Aspek</th><th>Layanan di rumah</th><th>Antar jemput</th></tr></thead><tbody>
<tr><td>Kendaraan berpindah tempat</td><td>Tidak</td><td>Ya, pergi dan pulang</td></tr>
<tr><td>Jenis pekerjaan</td><td>Ringan, alat portabel</td><td>Ringan sampai berat</td></tr>
<tr><td>Kebutuhan di lokasi</td><td>Lahan, kadang air dan listrik</td><td>Akses untuk penjemputan</td></tr>
<tr><td>Kamu perlu menunggu</td><td>Di rumah, selama dikerjakan</td><td>Tidak, kendaraan dibawa</td></tr>
<tr><td>Cocok untuk</td><td>Cuci, ganti oli, aki</td><td>Spooring, AC, rem, bongkar CVT</td></tr>
</tbody></table>

<h2>Pertanyaan sebelum memesan</h2>
<ol>
<li><strong>Apa pekerjaan utamanya?</strong> Jika butuh lift atau alat besar, langsung pilih antar jemput.</li>
<li><strong>Apakah lokasi cukup untuk bekerja?</strong> Gang sempit atau parkiran basement kadang menyulitkan layanan di rumah.</li>
<li><strong>Seberapa mendesak kendaraannya dipakai lagi?</strong> Layanan di rumah biasanya selesai di tempat, sedangkan antar jemput mengikuti antrean di outlet.</li>
<li><strong>Apakah alamat masih di dalam radius?</strong> Aplikasi akan menolak alamat yang terlalu jauh sebelum kamu membayar.</li>
</ol>

<h2>Serah terima yang aman</h2>
<p>Pada antar jemput, kendaraanmu berpindah tangan dua kali. Karena itu serah terima perlu tercatat jelas:</p>
<ol>
<li>Driver memotret kendaraan dari minimal empat sisi.</li>
<li>Odometer dan level bensin dicatat.</li>
<li>Kamu mengonfirmasi catatan itu di aplikasi.</li>
<li>Catatan yang sama dicek ulang saat kendaraan dikembalikan.</li>
</ol>
<p>Jangan tinggalkan barang berharga di dalam kendaraan, dan sebutkan kondisi khusus (misalnya ada lecet lama) supaya ikut tercatat.</p>

<h2>Bagaimana dengan waktu pengerjaan?</h2>
<p>Pada layanan di rumah, waktu yang kamu habiskan kurang lebih sama dengan durasi pekerjaannya: ganti oli belasan menit, cuci lengkap dengan vakum sekitar satu jam. Kamu tetap bisa beraktivitas di rumah sambil sesekali melihat prosesnya.</p>
<p>Pada antar jemput, ada tambahan waktu perjalanan dan antrean di outlet. Sebagai gambaran, servis berkala ringan yang dikerjakan sekitar satu sampai dua jam bisa membuat kendaraan kembali di sore hari bila dijemput pagi. Keuntungannya, kamu tidak perlu menunggu di bengkel sama sekali. Status pengerjaan dan posisi driver bisa dipantau dari aplikasi, sehingga kamu tahu kapan harus bersiap menerima kendaraan.</p>

<h2>Ringkasnya</h2>
<p>Pekerjaan ringan dan cuci: layanan di rumah biasanya lebih hemat waktu. Pekerjaan yang butuh alat bengkel: antar jemput. Dan bila kamu tidak keberatan datang sendiri, datang ke outlet tetap pilihan tanpa biaya tambahan.</p>
HTML,
            ],
        ];
    }
}
