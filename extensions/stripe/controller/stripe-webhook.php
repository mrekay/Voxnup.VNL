<?php

/**
 * Stripe Webhook Controller
 *
 * URL: /stripe-webhook
 *
 * Girişsiz erişim boot.php'deki vnl_public_page('stripe-webhook') ile açılır
 * (VNL 3.4.0). İstek Stripe-Signature ile doğrulanır.
 *
 * Stripe Dashboard → Developers → Webhooks → Add endpoint:
 *   https://siteniz.com/stripe-webhook
 *
 * Dinlenen event'ler:
 *   checkout.session.completed       Ödeme veya abonelik tamamlandı
 *   invoice.payment_succeeded        Abonelik yenileme ödemesi başarılı
 *   invoice.payment_failed           Abonelik yenileme ödemesi başarısız
 *   customer.subscription.updated    Abonelik güncellendi
 *   customer.subscription.deleted    Abonelik silindi/iptal edildi
 */


header('Content-Type: application/json; charset=utf-8');

$payload    = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sig_header)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payload or signature']);
    exit;
}

try {
    $event = StripeModule::constructWebhookEvent($payload, $sig_header);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$data = $event->data->object;

switch ($event->type) {
    case 'checkout.session.completed':
        _sw_handleCheckoutCompleted($data);
        break;
    case 'invoice.payment_succeeded':
        _sw_handlePaymentSucceeded($data);
        break;
    case 'invoice.payment_failed':
        _sw_handlePaymentFailed($data);
        break;
    case 'customer.subscription.updated':
        _sw_handleSubscriptionUpdated($data);
        break;
    case 'customer.subscription.deleted':
        _sw_handleSubscriptionDeleted($data);
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
exit;

// ─── Handlers ─────────────────────────────────────────────────────────────────

function _sw_handleCheckoutCompleted(\Stripe\Checkout\Session $session): void
{
    $userId     = $session->metadata->user_id ?? null;
    $customerId = $session->customer;

    if (empty($userId)) return;

    if ($session->mode === 'payment') {
        $payment = new StripePayment();
        $payment->UserId                = $userId;
        $payment->StripeCustomerId      = $customerId;
        $payment->StripeSessionId       = $session->id;
        $payment->StripePaymentIntentId = $session->payment_intent;
        $payment->Amount                = $session->amount_total;
        $payment->Currency              = strtoupper($session->currency);
        $payment->Status                = 'paid';
        $payment->InsertDate            = date('Y-m-d H:i:s');
        $payment->save();

    } elseif ($session->mode === 'subscription' && !empty($session->subscription)) {
        $stripeSub = StripeModule::getSubscription($session->subscription);
        _sw_upsertSubscription((int)$userId, $customerId, $stripeSub);
    }
}

function _sw_handlePaymentSucceeded(\Stripe\Invoice $invoice): void
{
    if (empty($invoice->subscription)) return;

    $sub = StripeSubscription::where('StripeCustomerId', $invoice->customer)
        ->orderBy('Id', 'desc')
        ->first();
    if (!$sub) return;

    if (!empty($invoice->payment_intent)) {
        if (StripePayment::where('StripePaymentIntentId', $invoice->payment_intent)->exists()) return;
    }

    $payment = new StripePayment();
    $payment->UserId                = $sub->UserId;
    $payment->StripeCustomerId      = $invoice->customer;
    $payment->StripePaymentIntentId = $invoice->payment_intent;
    $payment->Amount                = $invoice->amount_paid;
    $payment->Currency              = strtoupper($invoice->currency);
    $payment->Status                = 'paid';
    $payment->Description           = 'Abonelik yenilemesi';
    $payment->InsertDate            = date('Y-m-d H:i:s');
    $payment->save();

    $stripeSub = StripeModule::getSubscription($invoice->subscription);
    _sw_upsertSubscription($sub->UserId, $invoice->customer, $stripeSub);
}

function _sw_handlePaymentFailed(\Stripe\Invoice $invoice): void
{
    if (empty($invoice->subscription)) return;

    StripeSubscription::where('StripeSubscriptionId', $invoice->subscription)
        ->update(['Status' => 'past_due', 'UpdateDate' => date('Y-m-d H:i:s')]);
}

function _sw_handleSubscriptionUpdated(\Stripe\Subscription $subscription): void
{
    $sub = StripeSubscription::where('StripeSubscriptionId', $subscription->id)->first();

    if (!$sub) {
        $existing = StripeSubscription::where('StripeCustomerId', $subscription->customer)
            ->orderBy('Id', 'desc')
            ->first();
        if (!$existing) return;
        _sw_upsertSubscription($existing->UserId, $subscription->customer, $subscription);
    } else {
        _sw_upsertSubscription($sub->UserId, $subscription->customer, $subscription);
    }
}

function _sw_handleSubscriptionDeleted(\Stripe\Subscription $subscription): void
{
    StripeSubscription::where('StripeSubscriptionId', $subscription->id)
        ->update(['Status' => 'canceled', 'UpdateDate' => date('Y-m-d H:i:s')]);
}

// ─── Helper ───────────────────────────────────────────────────────────────────

function _sw_upsertSubscription(int $userId, string $customerId, \Stripe\Subscription $stripeSub): void
{
    $priceId  = $stripeSub->items->data[0]->price->id       ?? null;
    $planName = $stripeSub->items->data[0]->price->nickname ?? '';

    $existing = StripeSubscription::where('StripeSubscriptionId', $stripeSub->id)->first();

    if ($existing) {
        $existing->StripePriceId      = $priceId;
        $existing->PlanName           = $planName;
        $existing->Status             = $stripeSub->status;
        $existing->CurrentPeriodStart = date('Y-m-d H:i:s', $stripeSub->current_period_start);
        $existing->CurrentPeriodEnd   = date('Y-m-d H:i:s', $stripeSub->current_period_end);
        $existing->CancelAtPeriodEnd  = (int)$stripeSub->cancel_at_period_end;
        $existing->UpdateDate         = date('Y-m-d H:i:s');
        $existing->save();
    } else {
        $record = new StripeSubscription();
        $record->UserId               = $userId;
        $record->StripeCustomerId     = $customerId;
        $record->StripeSubscriptionId = $stripeSub->id;
        $record->StripePriceId        = $priceId;
        $record->PlanName             = $planName;
        $record->Status               = $stripeSub->status;
        $record->CurrentPeriodStart   = date('Y-m-d H:i:s', $stripeSub->current_period_start);
        $record->CurrentPeriodEnd     = date('Y-m-d H:i:s', $stripeSub->current_period_end);
        $record->CancelAtPeriodEnd    = (int)$stripeSub->cancel_at_period_end;
        $record->InsertDate           = date('Y-m-d H:i:s');
        $record->save();
    }
}
