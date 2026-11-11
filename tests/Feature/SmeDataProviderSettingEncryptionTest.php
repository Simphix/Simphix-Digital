<?php

namespace Tests\Feature;

use App\Models\SmeDataProviderSetting;
use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SmeDataProviderSettingEncryptionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_safe_encrypted_returns_null_and_does_not_crash_on_invalid_mac(): void
    {
        // Encrypt with a completely different key to simulate rotated APP_KEY or bad MAC
        $foreignEncrypter = new Encrypter(str_repeat('z', 32), 'AES-256-CBC');
        $foreignCiphertext = $foreignEncrypter->encrypt('foreign-api-key');

        $setting = SmeDataProviderSetting::forProvider('kvdata');
        // Store foreign ciphertext directly in raw database column
        $setting->setRawAttributes(array_merge($setting->getAttributes(), [
            'api_key' => $foreignCiphertext,
        ]));

        // Reading api_key should return null without throwing DecryptException
        $this->assertNull($setting->api_key);
        $this->assertTrue($setting->isKeyCorrupted('api_key'));
    }

    public function test_safe_encrypted_decrypts_validly_encrypted_key(): void
    {
        $setting = SmeDataProviderSetting::forProvider('kvdata');
        $setting->api_key = 'test-valid-key-12345';
        $setting->save();

        $fresh = SmeDataProviderSetting::forProvider('kvdata');
        $this->assertSame('test-valid-key-12345', $fresh->api_key);
        $this->assertFalse($fresh->isKeyCorrupted('api_key'));
    }

    public function test_admin_sme_plans_page_renders_cleanly_even_with_corrupted_mac(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        // Deliberately corrupt a vendor key in the database
        $foreignEncrypter = new Encrypter(str_repeat('x', 32), 'AES-256-CBC');
        $badCipher = $foreignEncrypter->encrypt('broken-key');

        $setting = SmeDataProviderSetting::forProvider('gogoz');
        \Illuminate\Support\Facades\DB::table('sme_data_provider_settings')
            ->where('id', $setting->id)
            ->update(['api_key' => $badCipher]);

        $response = $this->actingAs($admin)->get(route('admin.sme-plans.index'));

        // Page should render HTTP 200 without throwing "The MAC is invalid."
        $response->assertStatus(200);
        $response->assertSee('Key encrypted with outdated APP_KEY');
    }
}
