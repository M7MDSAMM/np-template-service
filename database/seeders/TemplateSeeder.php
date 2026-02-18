<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key'   => 'welcome_email',
                'name'  => 'Welcome Email',
                'channel' => 'email',
                'subject' => 'Welcome to Notification Platform, {{ $user_name }}',
                'body'    => "Hi {{ \$user_name }},\n\nThanks for joining us! If you have any questions, reach out at {{ \$support_email }}.",
                'variables_schema' => [
                    'required' => ['user_name'],
                    'optional' => ['support_email'],
                    'rules'    => ['support_email' => 'email'],
                ],
            ],
            [
                'key'   => 'otp_verification',
                'name'  => 'OTP Verification',
                'channel' => 'email',
                'subject' => 'Your verification code: {{ $otp }}',
                'body'    => "Hello {{ \$user_name }},\nUse {{ \$otp }} to complete your sign in. It expires in 10 minutes.",
                'variables_schema' => [
                    'required' => ['user_name', 'otp'],
                    'optional' => [],
                    'rules'    => ['otp' => 'string|max:10'],
                ],
            ],
            [
                'key'   => 'password_reset',
                'name'  => 'Password Reset',
                'channel' => 'email',
                'subject' => 'Reset your password, {{ $user_name }}',
                'body'    => "Hi {{ \$user_name }},\nClick the link to reset your password: {{ \$reset_url }}.\nIf you didn’t request this, ignore this email.",
                'variables_schema' => [
                    'required' => ['user_name', 'reset_url'],
                    'optional' => [],
                    'rules'    => ['reset_url' => 'url'],
                ],
            ],
            [
                'key'   => 'weekly_digest',
                'name'  => 'Weekly Digest',
                'channel' => 'email',
                'subject' => 'Your weekly summary, {{ $user_name }}',
                'body'    => "Hello {{ \$user_name }},\nHere’s your weekly digest. See more at {{ \$promo_url }}.",
                'variables_schema' => [
                    'required' => ['user_name'],
                    'optional' => ['promo_url'],
                    'rules'    => ['promo_url' => 'url'],
                ],
            ],
            [
                'key'   => 'invoice_ready',
                'name'  => 'Invoice Ready',
                'channel' => 'email',
                'subject' => 'Your invoice is ready',
                'body'    => "Hi {{ \$user_name }},\nYour invoice is ready: {{ \$invoice_url }}.\nOrder ID: {{ \$order_id }}.",
                'variables_schema' => [
                    'required' => ['user_name', 'invoice_url', 'order_id'],
                    'optional' => [],
                    'rules'    => ['invoice_url' => 'url', 'order_id' => 'string|max:50'],
                ],
            ],
            [
                'key'   => 'order_shipped',
                'name'  => 'Order Shipped',
                'channel' => 'email',
                'subject' => 'Your order {{ $order_id }} has shipped',
                'body'    => "Good news {{ \$user_name }},\nYour order {{ \$order_id }} is on the way. Track it here: {{ \$tracking_url }}.",
                'variables_schema' => [
                    'required' => ['user_name', 'order_id', 'tracking_url'],
                    'optional' => [],
                    'rules'    => ['tracking_url' => 'url', 'order_id' => 'string|max:50'],
                ],
            ],
            [
                'key'   => 'whatsapp_otp',
                'name'  => 'WhatsApp OTP',
                'channel' => 'whatsapp',
                'subject' => null,
                'body'    => "Hi {{ \$user_name }}, your OTP is {{ \$otp }}. Do not share it with anyone.",
                'variables_schema' => [
                    'required' => ['user_name', 'otp'],
                    'optional' => [],
                    'rules'    => ['otp' => 'string|max:10'],
                ],
            ],
            [
                'key'   => 'whatsapp_order_update',
                'name'  => 'WhatsApp Order Update',
                'channel' => 'whatsapp',
                'subject' => null,
                'body'    => "Order {{ \$order_id }} update: Track here {{ \$tracking_url }}.",
                'variables_schema' => [
                    'required' => ['order_id', 'tracking_url'],
                    'optional' => [],
                    'rules'    => ['tracking_url' => 'url', 'order_id' => 'string|max:50'],
                ],
            ],
            [
                'key'   => 'push_promo',
                'name'  => 'Push Promo',
                'channel' => 'push',
                'subject' => 'New offer: {{ $promo_title }}',
                'body'    => "Tap to view: {{ \$promo_url }}",
                'variables_schema' => [
                    'required' => ['promo_title', 'promo_url'],
                    'optional' => [],
                    'rules'    => ['promo_title' => 'string|max:80', 'promo_url' => 'url'],
                ],
            ],
            [
                'key'   => 'push_security_alert',
                'name'  => 'Push Security Alert',
                'channel' => 'push',
                'subject' => 'Security alert',
                'body'    => "New sign-in detected for {{ \$user_name }}. If not you, reset password.",
                'variables_schema' => [
                    'required' => ['user_name'],
                    'optional' => [],
                    'rules'    => ['user_name' => 'string|max:80'],
                ],
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['key' => $template['key']],
                [
                    'uuid'             => (string) Str::uuid(),
                    'name'             => $template['name'],
                    'channel'          => $template['channel'],
                    'subject'          => $template['subject'],
                    'body'             => $template['body'],
                    'variables_schema' => $template['variables_schema'],
                    'version'          => 1,
                    'is_active'        => true,
                ]
            );
        }
    }
}
