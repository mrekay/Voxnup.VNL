<?php

use ActionGlobal\Auth;

/**
 * Stripe AJAX Endpoints
 *
 * Tüm endpoint'ler /app/stripe/{method} pattern'ıyla çağrılır.
 *
 * Örnek:
 *   $.post('/app/stripe/createCheckoutSession', {
 *       mode: 'subscription',
 *       price_id: 'price_xxx',
 *       success_url: window.location.origin + '/dashboard',
 *       cancel_url:  window.location.origin + '/pricing',
 *   }, res => { if (res.status === 'success') window.location.href = res.data.url; });
 */
class action_stripe
{
    /**
     * Checkout session başlat.
     *
     * POST: mode ('subscription'|'payment'), price_id, success_url, cancel_url
     */
    public function createCheckoutSession($req)
    {
        Auth::CheckLogin();
        $user = Auth::User();

        $mode        = $req->post('mode');
        $price_id    = $req->post('price_id');
        $success_url = $req->post('success_url');
        $cancel_url  = $req->post('cancel_url');

        DefaultValidator::validateEmpty($mode,        'Ödeme modu');
        DefaultValidator::validateEmpty($price_id,    'Fiyat ID');
        DefaultValidator::validateEmpty($success_url, 'Başarı URL');
        DefaultValidator::validateEmpty($cancel_url,  'İptal URL');

        if (!in_array($mode, ['subscription', 'payment'])) {
            throwErrorMessage("Geçersiz ödeme modu. 'subscription' veya 'payment' olmalıdır.");
        }

        $customerId = StripeModule::getOrCreateCustomer($user->Id, $user->Email, $user->fullname());

        $session = StripeModule::createCheckoutSession([
            'mode'        => $mode,
            'price_id'    => $price_id,
            'customer_id' => $customerId,
            'success_url' => $success_url,
            'cancel_url'  => $cancel_url,
            'metadata'    => ['user_id' => $user->Id],
        ]);

        returnSuccessDataOnly(['url' => $session->url, 'session_id' => $session->id]);
    }

    /**
     * Aktif aboneliği iptal et.
     *
     * POST: immediately (0|1, default 0 = dönem sonunda)
     */
    public function cancelSubscription($req)
    {
        Auth::CheckLogin();
        $user        = Auth::User();
        $immediately = (bool)$req->post('immediately');

        $subscription = StripeSubscription::where('UserId', $user->Id)
            ->whereIn('Status', ['active', 'trialing'])
            ->orderBy('Id', 'desc')
            ->first();

        if (!$subscription) throwErrorMessage('Aktif abonelik bulunamadı.');

        StripeModule::cancelSubscription($subscription->StripeSubscriptionId, $immediately);

        if ($immediately) {
            $subscription->Status = 'canceled';
        } else {
            $subscription->CancelAtPeriodEnd = 1;
        }
        $subscription->UpdateDate = date('Y-m-d H:i:s');
        $subscription->save();

        $msg = $immediately
            ? 'Aboneliğiniz iptal edildi.'
            : 'Aboneliğiniz mevcut dönem sonunda iptal edilecek.';

        returnSuccessMessage($msg);
    }

    /**
     * Dönem sonunda iptal edilecek aboneliği geri al.
     */
    public function resumeSubscription($req)
    {
        Auth::CheckLogin();
        $user = Auth::User();

        $subscription = StripeSubscription::where('UserId', $user->Id)
            ->where('CancelAtPeriodEnd', 1)
            ->whereIn('Status', ['active', 'trialing'])
            ->orderBy('Id', 'desc')
            ->first();

        if (!$subscription) throwErrorMessage('İptal bekleyen abonelik bulunamadı.');

        StripeModule::resumeSubscription($subscription->StripeSubscriptionId);

        $subscription->CancelAtPeriodEnd = 0;
        $subscription->UpdateDate        = date('Y-m-d H:i:s');
        $subscription->save();

        returnSuccessMessage('Abonelik iptaliniz geri alındı.');
    }

    /**
     * Stripe Müşteri Portalı URL'ini al.
     *
     * POST: return_url (opsiyonel)
     */
    public function getBillingPortal($req)
    {
        Auth::CheckLogin();
        $user       = Auth::User();
        $return_url = $req->post('return_url') ?: SITE_URL;

        $customerId = StripeModule::findCustomerId($user->Id);
        if (!$customerId) throwErrorMessage('Stripe müşteri kaydı bulunamadı.');

        $portalSession = StripeModule::createBillingPortalSession($customerId, $return_url);

        returnSuccessDataOnly(['url' => $portalSession->url]);
    }

    /**
     * Kullanıcının abonelik durumunu getir.
     */
    public function getSubscriptionStatus($req)
    {
        Auth::CheckLogin();
        $user = Auth::User();

        $subscription = StripeSubscription::where('UserId', $user->Id)
            ->orderBy('Id', 'desc')
            ->first();

        if (!$subscription) {
            returnSuccessDataOnly(['has_subscription' => false]);
            return;
        }

        returnSuccessDataOnly([
            'has_subscription'     => true,
            'status'               => $subscription->Status,
            'is_active'            => $subscription->isActive(),
            'is_canceling'         => $subscription->isCanceling(),
            'plan'                 => $subscription->PlanName,
            'current_period_end'   => $subscription->CurrentPeriodEnd,
            'cancel_at_period_end' => (bool)$subscription->CancelAtPeriodEnd,
        ]);
    }

    /**
     * Kullanıcının ödeme geçmişini listele.
     */
    public function getPayments($req)
    {
        Auth::CheckLogin();
        $user = Auth::User();

        $payments = StripePayment::SelectList(
            ['UserId' => $user->Id],
            ['order' => 'InsertDate', 'order_by' => 'desc']
        )->get();

        returnSuccessDataOnly(['payments' => $payments]);
    }
}
