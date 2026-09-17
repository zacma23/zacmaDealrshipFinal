<?php

namespace App\Services\Wallet;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\SmmOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Atomically deposit funds into a user's digital wallet
     */
    public function deposit(
        User $user,
        float $amount,
        string $reference,
        Payment|string|null $payment = null,
        string $description = 'Wallet deposit',
        array $metadata = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception("Deposit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $amount, $reference, $payment, $description, $metadata) {
            $wallet = $this->getLockedWallet($user);

            $balanceBefore = (float)$wallet->balance;
            $balanceAfter = round($balanceBefore + $amount, 4);

            $wallet->balance = $balanceAfter;
            $wallet->total_deposited = round((float)$wallet->total_deposited + $amount, 4);
            $wallet->save();

            $paymentId = null;
            if ($payment instanceof Payment) {
                $paymentId = $payment->id;
                $metadata['gateway'] = $payment->gateway ?? 'unknown';
            } elseif (is_string($payment)) {
                $metadata['gateway'] = $payment;
            }

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'type' => WalletTransaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'fee' => 0.0000,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'reference' => $reference,
                'payment_id' => $paymentId,
                'description' => $description,
                'metadata' => $metadata,
                'status' => 'completed',
            ]);

            AuditLog::log(
                'wallet.deposit',
                $transaction,
                ['balance_before' => $balanceBefore],
                ['amount' => $amount, 'reference' => $reference, 'balance_after' => $balanceAfter],
                $user->organization_id,
                $user->id
            );

            return $transaction;
        });
    }

    /**
     * Atomically deduct order charge from user's digital wallet
     */
    public function chargeForOrder(User $user, float $amount, SmmOrder $order): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("Charge amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $amount, $order) {
            $wallet = $this->getLockedWallet($user);

            if ($wallet->isFrozen()) {
                throw new Exception("Your wallet is frozen. Please contact support.");
            }

            if ((float)$wallet->balance < $amount) {
                throw new Exception("Insufficient wallet balance. Please add funds to proceed.");
            }

            $balanceBefore = (float)$wallet->balance;
            $balanceAfter = round($balanceBefore - $amount, 4);

            $wallet->balance = $balanceAfter;
            $wallet->total_spent = round((float)$wallet->total_spent + $amount, 4);
            $wallet->save();

            $reference = 'TXN-ORD-' . strtoupper(Str::random(10));

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'type' => WalletTransaction::TYPE_ORDER_CHARGE,
                'amount' => -$amount,
                'fee' => 0.0000,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'reference' => $reference,
                'smm_order_id' => $order->id,
                'description' => "Payment for SMM Order #{$order->order_number} ({$order->quantity} units)",
                'metadata' => ['order_number' => $order->order_number, 'target' => $order->target],
                'status' => 'completed',
            ]);

            AuditLog::log(
                'wallet.charge',
                $transaction,
                ['balance_before' => $balanceBefore],
                ['amount' => $amount, 'order_id' => $order->id, 'balance_after' => $balanceAfter],
                $user->organization_id,
                $user->id
            );

            return $transaction;
        });
    }

    /**
     * Alias for chargeForOrder
     */
    public function chargeOrder(User $user, float $amount, SmmOrder $order): WalletTransaction
    {
        return $this->chargeForOrder($user, $amount, $order);
    }

    /**
     * Atomically refund an SMM order (full or partial)
     */
    public function refundOrder(mixed $orderOrUser, mixed $amountOrOrder, mixed $reasonOrOrder = null, string $maybeReason = 'Order cancelled / refunded'): WalletTransaction
    {
        if ($orderOrUser instanceof User && is_numeric($amountOrOrder) && $reasonOrOrder instanceof SmmOrder) {
            $user = $orderOrUser;
            $amount = (float)$amountOrOrder;
            $order = $reasonOrOrder;
            $reason = $maybeReason;
        } elseif ($orderOrUser instanceof SmmOrder) {
            $order = $orderOrUser;
            $amount = (float)$amountOrOrder;
            $user = $order->user;
            $reason = is_string($reasonOrOrder) ? $reasonOrOrder : 'Order cancelled / refunded';
        } else {
            throw new Exception("Invalid parameters for refundOrder.");
        }

        if ($amount <= 0) {
            throw new Exception("Refund amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $order, $amount, $reason) {
            $wallet = $this->getLockedWallet($user);

            $balanceBefore = (float)$wallet->balance;
            $balanceAfter = round($balanceBefore + $amount, 4);

            $wallet->balance = $balanceAfter;
            $wallet->total_refunded = round((float)$wallet->total_refunded + $amount, 4);
            $wallet->save();

            $reference = 'TXN-REF-' . strtoupper(Str::random(10));

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'type' => WalletTransaction::TYPE_ORDER_REFUND,
                'amount' => $amount,
                'fee' => 0.0000,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'reference' => $reference,
                'smm_order_id' => $order->id,
                'description' => "Refund for SMM Order #{$order->order_number}: {$reason}",
                'metadata' => ['reason' => $reason, 'order_id' => $order->id],
                'status' => 'completed',
            ]);

            AuditLog::log(
                'wallet.refund',
                $transaction,
                ['balance_before' => $balanceBefore],
                ['amount' => $amount, 'order_id' => $order->id, 'reason' => $reason, 'balance_after' => $balanceAfter],
                $user->organization_id,
                $user->id
            );

            return $transaction;
        });
    }

    /**
     * Admin manual credit
     */
    public function manualCredit(User $user, float $amount, string $notes, ?User $admin = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $amount, $notes, $admin) {
            $wallet = $this->getLockedWallet($user);

            $balanceBefore = (float)$wallet->balance;
            $balanceAfter = round($balanceBefore + $amount, 4);

            $wallet->balance = $balanceAfter;
            $wallet->total_deposited = round((float)$wallet->total_deposited + $amount, 4);
            $wallet->save();

            $reference = 'TXN-MAN-CR-' . strtoupper(Str::random(8));

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'type' => WalletTransaction::TYPE_MANUAL_CREDIT,
                'amount' => $amount,
                'fee' => 0.0000,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'reference' => $reference,
                'description' => "Manual adjustment credit by Admin: {$notes}",
                'metadata' => ['admin_id' => $admin?->id, 'notes' => $notes],
                'status' => 'completed',
            ]);

            AuditLog::log(
                'wallet.manual_credit',
                $transaction,
                ['balance_before' => $balanceBefore],
                ['amount' => $amount, 'admin_id' => $admin?->id, 'notes' => $notes, 'balance_after' => $balanceAfter],
                $user->organization_id,
                $admin?->id
            );

            return $transaction;
        });
    }

    /**
     * Admin manual debit
     */
    public function manualDebit(User $user, float $amount, string $notes, ?User $admin = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new Exception("Debit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $amount, $notes, $admin) {
            $wallet = $this->getLockedWallet($user);

            $balanceBefore = (float)$wallet->balance;
            if ($balanceBefore < $amount) {
                throw new Exception("Cannot debit {$amount}. Current balance is only {$balanceBefore}.");
            }
            $balanceAfter = round($balanceBefore - $amount, 4);

            $wallet->balance = $balanceAfter;
            $wallet->total_spent = round((float)$wallet->total_spent + $amount, 4);
            $wallet->save();

            $reference = 'TXN-MAN-DB-' . strtoupper(Str::random(8));

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'type' => WalletTransaction::TYPE_MANUAL_DEBIT,
                'amount' => -$amount,
                'fee' => 0.0000,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'reference' => $reference,
                'description' => "Manual adjustment debit by Admin: {$notes}",
                'metadata' => ['admin_id' => $admin?->id, 'notes' => $notes],
                'status' => 'completed',
            ]);

            AuditLog::log(
                'wallet.manual_debit',
                $transaction,
                ['balance_before' => $balanceBefore],
                ['amount' => $amount, 'admin_id' => $admin?->id, 'notes' => $notes, 'balance_after' => $balanceAfter],
                $user->organization_id,
                $admin?->id
            );

            return $transaction;
        });
    }

    /**
     * Credit affiliate commission to referrer wallet
     */
    public function creditAffiliateCommission(User $referrer, float $amount, User $referee, string $referralCode): WalletTransaction
    {
        return DB::transaction(function () use ($referrer, $amount, $referee, $referralCode) {
            $wallet = $this->getLockedWallet($referrer);

            $balanceBefore = (float)$wallet->balance;
            $balanceAfter = round($balanceBefore + $amount, 4);

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $reference = 'TXN-AFF-' . strtoupper(Str::random(10));

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $referrer->id,
                'organization_id' => $referrer->organization_id,
                'type' => WalletTransaction::TYPE_AFFILIATE_COMMISSION,
                'amount' => $amount,
                'fee' => 0.0000,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'reference' => $reference,
                'description' => "Affiliate commission from referee {$referee->name} (Code: {$referralCode})",
                'metadata' => ['referee_id' => $referee->id, 'referral_code' => $referralCode],
                'status' => 'completed',
            ]);
        });
    }

    /**
     * Pessimistic row locking for wallet safety
     */
    protected function getLockedWallet(User $user): Wallet
    {
        // First ensure wallet exists
        $user->getOrCreateWallet();

        // Lock row for update
        return Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
    }
}
