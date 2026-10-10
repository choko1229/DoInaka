<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Services\Geo\DnsResolver;
use App\Services\Geo\GeoIpImporter;
use App\Services\Setting\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** 日本の範囲を入れる: 1.0.16.0/20(IPv4)と 2001:200::/32(IPv6) */
function seedJapan(): void
{
    $body = "apnic|JP|ipv4|1.0.16.0|4096|20110412|allocated\napnic|JP|ipv6|2001:200::|32|19990813|allocated\napnic|US|ipv4|8.8.8.0|256|1992|allocated\n";
    app(GeoIpImporter::class)->replace(app(GeoIpImporter::class)->parse($body));
}

function fromIp(string $ip, array $headers = []): array
{
    return ['REMOTE_ADDR' => $ip] + $headers;
}

beforeEach(function (): void {
    app(SettingsService::class)->set(SettingKey::GeoBlockOverseas, true);
    seedJapan();
});

it('日本のIPは通り、米国のIPは403', function (): void {
    $this->call('GET', '/', [], [], [], fromIp('1.0.17.5'))->assertOk();
    $this->call('GET', '/', [], [], [], fromIp('8.8.8.8'))->assertStatus(403)->assertSee('海外からはご利用いただけません');
});

it('IPv6 も判定できる', function (): void {
    $this->call('GET', '/', [], [], [], fromIp('2001:200::1'))->assertOk();
    $this->call('GET', '/', [], [], [], fromIp('2606:4700::1111'))->assertStatus(403);
});

it('海外からでも robots.txt とサイトマップは開ける', function (): void {
    $this->call('GET', '/robots.txt', [], [], [], fromIp('8.8.8.8'))->assertOk();
    $this->call('GET', '/sitemap.xml', [], [], [], fromIp('8.8.8.8'))->assertOk();
});

it('Googlebot を名乗るが逆引きが違うものは403、本物は通る', function (): void {
    $ua = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];

    $this->app->instance(DnsResolver::class, new class extends DnsResolver
    {
        public function reverse(string $ip): ?string
        {
            return $ip === '66.249.66.1' ? 'crawl-66-249-66-1.googlebot.com' : 'evil.example.com';
        }

        public function forward(string $host): array
        {
            return $host === 'crawl-66-249-66-1.googlebot.com' ? ['66.249.66.1'] : ['203.0.113.9'];
        }
    });

    $this->call('GET', '/', [], [], [], fromIp('66.249.66.1', $ua))->assertOk();
    $this->call('GET', '/', [], [], [], fromIp('203.0.113.9', $ua))->assertStatus(403);
});

it('一覧の取得に失敗しても前の一覧で動く。一覧が空なら誰も止めない', function (): void {
    Http::fake([GeoIpImporter::URL => Http::response('error', 500)]);
    expect(fn () => app(GeoIpImporter::class)->import())->toThrow(RuntimeException::class);
    $this->call('GET', '/', [], [], [], fromIp('8.8.8.8'))->assertStatus(403);

    // 空の内容は取り込まない
    Http::fake([GeoIpImporter::URL => Http::response("# header\n", 200)]);
    expect(fn () => app(GeoIpImporter::class)->import())->toThrow(RuntimeException::class);
    expect(DB::table('geo_ip_ranges')->count())->toBe(3 - 1);

    DB::table('geo_ip_ranges')->delete();
    $this->call('GET', '/', [], [], [], fromIp('8.8.8.8'))->assertOk();
});

it('設定でオフにすれば制限しない。管理画面は既定で海外から入れない', function (): void {
    $this->call('GET', '/admin/', [], [], [], fromIp('8.8.8.8'))->assertStatus(403);

    app(SettingsService::class)->set(SettingKey::GeoAllowAdminAbroad, true);
    $this->call('GET', '/admin/', [], [], [], fromIp('8.8.8.8'))->assertRedirect();

    app(SettingsService::class)->set(SettingKey::GeoBlockOverseas, false);
    $this->call('GET', '/', [], [], [], fromIp('8.8.8.8'))->assertOk();
});
