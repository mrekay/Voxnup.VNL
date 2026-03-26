<?php

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\BillingPortal\Session as StripeBillingPortalSession;
use Stripe\Subscription;
use Stripe\Webhook;

/**
 * StripeModule
 *
 * Stripe PHP SDK için merkezi sarmalayıcı.
 * Hem subscription (abonelik) hem de one-time payment (tek seferlik ödeme) destekler.
 *
 * ─── Subscription Örneği ────────────────────────────────────────────────────
 * $session = StripeModule::createCheckoutSession([
 *     'mode'        => 'subscription',
 *     'price_id'    => 'price_xxx',
 *     'customer_id' => StripeModule::getOrCreateCustomer($user->Id, $user->Email, $user->fullname()),
 *     'success_url' => SITE_URL . '/dashboard',
 *     'cancel_url'  => SITE_URL . '/pricing',
 *     'metadata'    => ['user_id' => $user->Id],
 * ]);
 * header('Location: ' . $session->url);
 *
 * ─── One-Time Payment Örneği ─────────────────────────────────────────────────
 * $session = StripeModule::createCheckoutSession([
 *     'mode'     => 'payment',
 *     'price_id' => 'price_xxx',
 *     ...
 * ]);
 */
class StripeModule
{
    private static bool $initialized = false;

    private static function init(): void
    {
        if (self::$initialized) return;
        Stripe::setApiKey(STRIPE_SECRET_KEY);
        self::$initialized = true;
    }

    // ─── Customer ─────────────────────────────────────────────────────────────

    /**
     * Kullanıcı için Stripe customer oluştur veya mevcut olanı döndür.
     * Önce DB'ye bakılır; bulunamazsa Stripe'da yeni müşteri oluşturulur.
     */
    public static function getOrCreateCustomer(int $userId, string $email, string $name = ''): string
    {
        self::init();

        $customerId = self::findCustomerId($userId);

        if ($customerId) {
            try {
                $customer = Customer::retrieve($customerId);
                if (!$customer->deleted) return $customer->id;
            } catch (\Exception $e) {
                // Geçersiz veya silinmiş, yeni oluştur
            }
        }

        $customer = Customer::create([
            'email'    => $email,
            'name'     => $name,
            'metadata' => ['user_id' => $userId],
        ]);

        return $customer->id;
    }

    /**
     * DB'de kullanıcıya ait Stripe Customer ID'yi bul.
     * Hem subscription hem payment tablosuna bakar.
     */
    public static function findCustomerId(int $userId): ?string
    {
        $sub = StripeSubscription::where('UserId', $userId)
            ->whereNotNull('StripeCustomerId')
            ->orderBy('Id', 'desc')
            ->first();

        if ($sub) return $sub->StripeCustomerId;

        $pay = StripePayment::where('UserId', $userId)
            ->whereNotNull('StripeCustomerId')
            ->orderBy('Id', 'desc')
            ->first();

        return $pay ? $pay->StripeCustomerId : null;
    }

    // ─── Checkout ─────────────────────────────────────────────────────────────

    /**
     * Stripe Checkout Session oluştur.
     *
     * @param array $params {
     *   mode:        'subscription' | 'payment'   (zorunlu)
     *   price_id:    string  Stripe Price ID       (zorunlu)
     *   success_url: string                        (zorunlu)
     *   cancel_url:  string                        (zorunlu)
     *   customer_id: string  Stripe Customer ID    (opsiyonel)
     *   quantity:    int     default 1             (opsiyonel)
     *   metadata:    array                         (opsiyonel)
     * }
     */
    public static function createCheckoutSession(array $params): StripeCheckoutSession
    {
        self::init();

        $sessionParams = [
            'mode'        => $params['mode'],
            'success_url' => $params['success_url'],
            'cancel_url'  => $params['cancel_url'],
            'line_items'  => [[
                'price'    => $params['price_id'],
                'quantity' => $params['quantity'] ?? 1,
            ]],
            'metadata'    => $params['metadata'] ?? [],
        ];

        if (!empty($params['customer_id'])) {
            $sessionParams['customer'] = $params['customer_id'];
        }

        if ($params['mode'] === 'subscription') {
            $sessionParams['subscription_data'] = [
                'metadata' => $params['metadata'] ?? [],
            ];
        }

        return StripeCheckoutSession::create($sessionParams);
    }

    // ─── Billing Portal ───────────────────────────────────────────────────────

    /**
     * Stripe Müşteri Portalı oturumu oluştur.
     * Kullanıcı burada faturalarını görür, planını değiştirir, iptal eder.
     */
    public static function createBillingPortalSession(string $customerId, string $returnUrl): StripeBillingPortalSession
    {
        self::init();

        return StripeBillingPortalSession::create([
            'customer'   => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    // ─── Subscription ─────────────────────────────────────────────────────────

    /**
     * Aboneliği iptal et.
     *
     * @param bool $immediately true = anında, false = dönem sonunda
     */
    public static function cancelSubscription(string $subscriptionId, bool $immediately = false): Subscription
    {
        self::init();

        if ($immediately) {
            return Subscription::cancel($subscriptionId);
        }

        return Subscription::update($subscriptionId, ['cancel_at_period_end' => true]);
    }

    /**
     * Dönem sonunda iptal edilecek aboneliği geri al.
     */
    public static function resumeSubscription(string $subscriptionId): Subscription
    {
        self::init();
        return Subscription::update($subscriptionId, ['cancel_at_period_end' => false]);
    }

    /**
     * Abonelik detaylarını Stripe'dan çek.
     */
    public static function getSubscription(string $subscriptionId): Subscription
    {
        self::init();
        return Subscription::retrieve($subscriptionId);
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    /**
     * Stripe webhook imzasını doğrula ve Event nesnesini döndür.
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public static function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
    }
}
