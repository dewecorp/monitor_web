<?php
declare(strict_types=1);

namespace App\Services;

class IncidentResponse
{
    private array $analysis = ['summary'=>'','issues'=>[],'recommendations'=>[],'priority'=>'low','status'=>'healthy'];
    private array $kb;

    public function __construct()
    {
        $this->kb = $this->getKnowledgeBase();
    }

    private function getKnowledgeBase(): array
    {
        return [
        'website_down' => ['title'=>'🚨 Website DOWN — Tidak Dapat Diakses','check'=>'HTTP status code menunjukkan website tidak merespon. Server mati, koneksi terputus, atau diblokir firewall.',
        'causes'=>['Server mati atau restart','Koneksi jaringan terputus','DNS tidak merespon','SSL expired — browser block','Resource habis (CPU/RAM/disk 100%)','Firewall/WAF blokir IP','Domain expired'],
        'actions'=>[['p'=>1,'a'=>'Cek status server via panel hosting atau agent'],['p'=>2,'a'=>'Cek resource (CPU, RAM, Disk) — kemungkinan overload'],['p'=>3,'a'=>'Cek SSL — expired? Renew segera'],['p'=>4,'a'=>'Cek DNS — domain terresolve?'],['p'=>5,'a'=>'Cek firewall — IP monitoring diblokir?'],['p'=>6,'a'=>'Restart service (Apache/Nginx/PHP-FPM)']]],
        'slow_response' => ['title'=>'⚠️ Response Time Lambat','check'=>'Waktu respon >2000ms. Pengunjung alami loading lambat, bounce rate tinggi.',
        'causes'=>['Traffic tinggi / banyak pengunjung simultan','Query database lambat — missing index','Plugin/script tidak optimal','Resource server terbatas','Koneksi jaringan lambat'],
        'actions'=>[['p'=>1,'a'=>'Cek resource server (CPU, RAM) via agent'],['p'=>2,'a'=>'Optimasi database — slow query log, index'],['p'=>3,'a'=>'Aktifkan caching (page cache, opcache, Redis)'],['p'=>4,'a'=>'Gunakan CDN untuk static assets'],['p'=>5,'a'=>'Upgrade hosting jika perlu']]],
        'ssl_expiring' => ['title'=>'🔐 SSL Akan Kadaluarsa','check'=>'SSL certificate akan segera habis. Browser tampilkan "Not Secure" jika expired.',
        'causes'=>['SSL tidak di-auto renew','Lets Encrypt/Certbot cron tidak jalan','Manual certificate belum diperbarui'],
        'actions'=>[['p'=>1,'a'=>'Renew SSL sekarang — jangan tunggu expired!'],['p'=>2,'a'=>'Aktifkan auto-renew via Lets Encrypt'],['p'=>3,'a'=>'Verifikasi renewal via browser']]],
        'domain_expiring' => ['title'=>'🌐 Domain Akan Kadaluarsa','check'=>'Domain akan kadaluarsa. Website dan email tidak bisa diakses jika expired.',
        'causes'=>['Domain tidak di-auto renew','Pembayaran hosting/domain belum diperpanjang'],
        'actions'=>[['p'=>1,'a'=>'Perpanjang domain registrasi SEKARANG!'],['p'=>2,'a'=>'Aktifkan auto-renew di registrar']]],
        'low_security' => ['title'=>'🛡️ Security Score Rendah','check'=>'Skor keamanan di bawah standar. Website rentan XSS, injection, atau data breach.',
        'causes'=>['Security headers tidak aktif (HSTS, CSP, XFO)','SSL/TLS konfigurasi lemah','File sensitif terekspos (.env, .git)','Fungsi PHP berbahaya aktif (eval, system)'],
        'actions'=>[['p'=>1,'a'=>'Aktifkan HSTS, CSP, X-Frame-Options'],['p'=>2,'a'=>'Nonaktifkan fungsi PHP berbahaya di php.ini'],['p'=>3,'a'=>'Block akses file sensitif via .htaccess'],['p'=>4,'a'=>'Jalankan Vulnerability Scanner untuk deteksi lanjutan']]],
        'open_incidents' => ['title'=>'📋 Insiden Terbuka','check'=>'Ada insiden belum diselesaikan. Perlu investigasi dan resolusi.',
        'causes'=>['Deteksi otomatis dari monitoring','Insiden lama belum di-resolve'],
        'actions'=>[['p'=>1,'a'=>'Review setiap insiden satu per satu'],['p'=>2,'a'=>'Investigasi penyebab insiden'],['p'=>3,'a'=>'Resolve insiden setelah ditangani']]],
        'high_cpu' => ['title'=>'⚡ CPU Usage Tinggi','check'=>'CPU > ambang batas. Server overload, berpotensi crash atau slow response.',
        'causes'=>['Traffic spike mendadak','Cron job bersamaan','Script/plugin tidak optimal (leak)','Serangan DDoS','Crypto miner tersembunyi'],
        'actions'=>[['p'=>1,'a'=>'Cek proses dengan CPU tertinggi via top/htop'],['p'=>2,'a'=>'Optimasi query database yang berat'],['p'=>3,'a'=>'Nonaktifkan plugin/script tidak perlu'],['p'=>4,'a'=>'Batasi traffic via Cloudflare/WAF/rate limit'],['p'=>5,'a'=>'Upgrade server jika overload terus']]],
        'high_ram' => ['title'=>'💾 RAM Usage Tinggi','check'=>'Memory mendekati batas. Server berisiko kehabisan RAM dan crash.',
        'causes'=>['PHP-FPM terlalu banyak process','MySQL query memory-intensive','Traffic tinggi — banyak koneksi','Memory leak aplikasi'],
        'actions'=>[['p'=>1,'a'=>'Restart PHP-FPM — bersihkan memory'],['p'=>2,'a'=>'Optimasi MySQL — slow query, index'],['p'=>3,'a'=>'Kurangi pm.max_children PHP-FPM'],['p'=>4,'a'=>'Upgrade RAM jika perlu']]],
        'high_disk' => ['title'=>'💽 Disk Hampir Penuh','check'=>'Disk >90%. Server tidak bisa menulis data — website error!',
        'causes'=>['Log file membesar tidak terkontrol','Backup menumpuk','Cache tidak dibersihkan','File upload berlebihan'],
        'actions'=>[['p'=>1,'a'=>'Bersihkan log file (error, access, PHP, MySQL)'],['p'=>2,'a'=>'Hapus backup lama'],['p'=>3,'a'=>'Bersihkan cache aplikasi'],['p'=>4,'a'=>'Hapus file upload tidak perlu'],['p'=>5,'a'=>'Aktifkan log rotation (logrotate)']]],
        'file_changed' => ['title'=>'📄 File Integrity Berubah','check'=>'File penting berubah dari baseline. Bisa update sah atau indikasi kompromi.',
        'causes'=>['Update sistem/plugin/theme sah','Diretas — file dimodifikasi attacker','Konfigurasi diubah manual admin'],
        'actions'=>[['p'=>1,'a'=>'Review diff perubahan — apakah wajar?'],['p'=>2,'a'=>'Jika mencurigakan, jalankan Backdoor Scanner'],['p'=>3,'a'=>'Restore dari backup jika tidak dikenal'],['p'=>4,'a'=>'Update baseline FIM setelah yakin aman']]],
        ];
    }

    public function analyze(int $websiteId): array
    {
        $db = \App\Config\Database::getConnection();
        $issues = [];

        // 1. Health check
        $h = $db->prepare("SELECT is_up, status_code, response_time_ms, checked_at FROM monitor_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
        $h->execute([$websiteId]); $lh = $h->fetch();
        if ($lh) {
            if (!$lh['is_up']) $issues[] = ['type'=>'website_down','se'=>'critical','data'=>$lh];
            elseif ($lh['response_time_ms'] > 2000) $issues[] = ['type'=>'slow_response','se'=>'medium','data'=>$lh];
        }

        // 2. SSL
        $s = $db->prepare("SELECT ssl_remaining_days, ssl_valid FROM ssl_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
        $s->execute([$websiteId]); $ls = $s->fetch();
        if ($ls && $ls['ssl_valid']) {
            if ($ls['ssl_remaining_days'] <= 7) $issues[] = ['type'=>'ssl_expiring','se'=>'critical','data'=>$ls];
            elseif ($ls['ssl_remaining_days'] <= 30) $issues[] = ['type'=>'ssl_expiring','se'=>'high','data'=>$ls];
        }

        // 3. Domain
        $w = $db->prepare("SELECT domain_expired FROM websites WHERE id = ?");
        $w->execute([$websiteId]); $site = $w->fetch();
        if ($site && $site['domain_expired']) {
            $rem = max(0,(int)((strtotime($site['domain_expired'])-time())/86400));
            if ($rem <= 7) $issues[] = ['type'=>'domain_expiring','se'=>'critical','data'=>['r'=>$rem]];
            elseif ($rem <= 30) $issues[] = ['type'=>'domain_expiring','se'=>'high','data'=>['r'=>$rem]];
        }

        // 4. Security score
        $sc = $db->prepare("SELECT score FROM security_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
        $sc->execute([$websiteId]); $lsc = $sc->fetch();
        if ($lsc && $lsc['score'] < 40) $issues[] = ['type'=>'low_security','se'=>'high','data'=>$lsc];
        elseif ($lsc && $lsc['score'] < 60) $issues[] = ['type'=>'low_security','se'=>'medium','data'=>$lsc];

        // 5. Open incidents
        $ic = $db->prepare("SELECT COUNT(*) as c FROM incidents WHERE website_id = ? AND status IN ('open','investigating')");
        $ic->execute([$websiteId]); $inc = $ic->fetch()['c'];
        if ($inc > 0) $issues[] = ['type'=>'open_incidents','se'=>'high','data'=>['c'=>$inc]];

        // 6. FIM changes
        $fc = $db->prepare("SELECT COUNT(*) as c FROM file_changes WHERE website_id = ? AND is_reviewed = 0");
        $fc->execute([$websiteId]); $fim = $fc->fetch()['c'];
        if ($fim > 0) $issues[] = ['type'=>'file_changed','se'=>'medium','data'=>['c'=>$fim]];

        // 7. Agent resources
        $ag = $db->prepare("SELECT cpu_usage, memory_usage, disk_usage FROM agent_reports WHERE website_id = ? ORDER BY collected_at DESC LIMIT 1");
        $ag->execute([$websiteId]); $la = $ag->fetch();
        if ($la) {
            if (($la['cpu_usage']??0) > 90) $issues[] = ['type'=>'high_cpu','se'=>'high','data'=>$la];
            elseif (($la['cpu_usage']??0) > 75) $issues[] = ['type'=>'high_cpu','se'=>'medium','data'=>$la];
            if (($la['memory_usage']??0) > 90) $issues[] = ['type'=>'high_ram','se'=>'high','data'=>$la];
            if (($la['disk_usage']??0) > 90) $issues[] = ['type'=>'high_disk','se'=>'high','data'=>$la];
        }

        return $this->buildAnalysis($issues, $websiteId);
    }

    private function buildAnalysis(array $issues, int $websiteId): array
    {
        $cr=0;$hi=0;$me=0;$recs=[];
        foreach ($issues as $iss) {
            if ($iss['se']==='critical') $cr++;
            if ($iss['se']==='high') $hi++;
            if ($iss['se']==='medium') $me++;
            $kb = $this->kb[$iss['type']] ?? null;
            if ($kb) {
                $entry = ['type'=>$iss['type'],'title'=>$kb['title'],'severity'=>$iss['se'],'check'=>$kb['check'],'causes'=>$kb['causes'],'actions'=>$kb['actions'],'data'=>$iss['data']];
                $recs[] = $entry;
            }
        }
        $total = count($issues);
        $this->analysis = ['total_issues'=>$total,'critical'=>$cr,'high'=>$hi,'medium'=>$me,
            'priority'=>$cr>0?'critical':($hi>0?'high':($me>0?'medium':'low')),
            'status'=>$total===0?'healthy':($cr>0?'danger':($hi>2?'warning':'attention')),
            'issues'=>$issues,'recommendations'=>$recs,
            'summary'=>$total===0 ? '✅ Website dalam kondisi sehat. Tidak ditemukan masalah.' : '⚠️ Ditemukan '.implode(', ', array_filter([$cr>0?"{$cr} kritis":null,$hi>0?"{$hi} tinggi":null,$me>0?"{$me} sedang":null])).'. Perlu tindakan segera.',
        ];
        return $this->analysis;
    }

    public function resolve(int $incidentId): bool
    {
        $db = \App\Config\Database::getConnection();
        return $db->prepare("UPDATE incidents SET status='resolved', resolved_at=NOW() WHERE id=?")->execute([$incidentId]);
    }

    public function getTimeline(int $websiteId, int $limit=20): array
    {
        $db = \App\Config\Database::getConnection();
        $events = [];

        // 1. Incidents
        $s1 = $db->prepare("SELECT id,'incident' as source,title as description,severity,status,created_at as time FROM incidents WHERE website_id=? ORDER BY created_at DESC LIMIT ?");
        $s1->execute([$websiteId, $limit]);
        foreach ($s1->fetchAll() as $e) $events[] = $e;

        // 2. Monitor logs (health checks)
        $s2 = $db->prepare("SELECT id,'health' as source, CONCAT(CASE WHEN m.is_up=1 THEN 'ONLINE' ELSE 'OFFLINE' END,' HTTP ',m.status_code,' ',m.response_time_ms,'ms') as description, CASE WHEN m.is_up=0 THEN 'critical' ELSE 'info' END as severity, '' as status, m.checked_at as time FROM monitor_logs m WHERE m.website_id=? ORDER BY m.checked_at DESC LIMIT ?");
        $s2->execute([$websiteId, $limit]);
        foreach ($s2->fetchAll() as $e) $events[] = $e;

        // 3. Security checks
        $s3 = $db->prepare("SELECT id,'security' as source,
            CONCAT('Security scan — Score: ', score, '%') as description,
            CASE WHEN score<40 THEN 'high' WHEN score<60 THEN 'medium' ELSE 'low' END as severity,
            '' as status,
            checked_at as time
            FROM security_logs WHERE website_id=? ORDER BY checked_at DESC LIMIT ?");
        $s3->execute([$websiteId, $limit]);
        foreach ($s3->fetchAll() as $e) $events[] = $e;

        usort($events, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
        return array_slice($events, 0, $limit);
    }
}
