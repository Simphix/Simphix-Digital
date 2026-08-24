<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\PlatformWithdrawalAccount;
use App\Services\PalmPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class PlatformWithdrawalAccountController extends Controller
{
    public function edit()
    {
        $account = PlatformWithdrawalAccount::current();
        $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();

        return view('admin.platform-wallets.withdrawal-account-edit', compact('account', 'banks'));
    }

    public function update(Request $request, PalmPayService $palmPay): RedirectResponse
    {
        $request->validate([
            'bankCode' => ['required', 'string', 'exists:banks,bank_code'],
            'account_no' => ['required', 'digits:10'],
        ]);

        $bank = Bank::where('bank_code', $request->bankCode)->first();

        // Resolve the account name server-side rather than trusting whatever
        // the client submits — this is the account platform revenue will
        // actually be sent to.
        try {
            $response = $palmPay->queryBankAccount($request->bankCode, $request->account_no);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()
                ->withErrors(['account_no' => 'Unable to reach the bank verification service. Please try again shortly.'])
                ->withInput();
        }

        $accountName = null;
        if (isset($response['respCode']) && $response['respCode'] === '00000000' && ($response['data']['Status'] ?? null) === 'Success') {
            $accountName = $response['data']['accountName'] ?? null;
        }

        if (!$accountName) {
            $message = $response['data']['errorMessage'] ?? $response['respMsg'] ?? 'Could not verify that account number.';

            return back()->withErrors(['account_no' => $message])->withInput();
        }

        $attributes = [
            'bank_code' => $request->bankCode,
            'bank_name' => $bank->bank_name ?? 'Bank',
            'account_no' => $request->account_no,
            'account_name' => $accountName,
            'updated_by' => Auth::id(),
        ];

        $account = PlatformWithdrawalAccount::current();
        $account ? $account->update($attributes) : PlatformWithdrawalAccount::create($attributes);

        return Redirect::route('admin.platform-wallets.withdrawal-account.edit')
            ->with('success', 'Platform withdrawal account saved.');
    }
}
