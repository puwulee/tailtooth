<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * 建立預設主辦者帳號與一個示範活動。
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');

        $host = User::updateOrCreate(
            ['email' => $email],
            ['name' => env('ADMIN_NAME', '活動主辦'), 'password' => Hash::make($password)],
        );

        if ($host->events()->exists()) {
            return;
        }

        $event = $host->events()->create([
            'title' => '示範活動：協會年度大會 Q&A',
            'description' => "歡迎在這裡提問！可匿名發問，並為你想聽的問題按讚，\n讚數越高的問題會排在越前面。",
            'status' => 'open',
            'allow_anonymous' => true,
            'require_approval' => false,
        ]);

        $samples = [
            ['今年的活動預算與去年相比有什麼變化？', '財務長', 8],
            ['下半年有沒有規劃跨縣市的聯合活動？', null, 5],
            ['會員續費的優惠方案什麼時候公布？', '小美', 3],
            ['線上參與的成員可以一起投票表決嗎？', null, 1],
        ];

        foreach ($samples as [$body, $name, $votes]) {
            $event->questions()->create([
                'body' => $body,
                'author_name' => $name,
                'author_token' => Str::random(32),
                'status' => 'published',
                'upvotes_count' => $votes,
            ]);
        }

        // 公司名單（主辦者共用，供「需統編驗證」的活動使用）
        $companies = [
            ['12345675', '台灣示範股份有限公司'],
            ['53212539', '原創科技運動行銷'],
            ['04595257', '宏遠興業股份有限公司'],
        ];
        foreach ($companies as [$taxId, $name]) {
            $host->companies()->create(['tax_id' => $taxId, 'name' => $name]);
        }

        // 需統編驗證的示範活動
        $host->events()->create([
            'title' => '示範活動：會員企業表決會（需統編）',
            'description' => "本活動需以公司身分參與，請輸入貴公司統一編號驗證後提問與投票。",
            'status' => 'open',
            'allow_anonymous' => true,
            'require_approval' => false,
            'require_company' => true,
        ]);
    }
}
