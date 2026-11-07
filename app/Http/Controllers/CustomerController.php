<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->orderByDesc('created_at')
            ->orderByDesc('customer_id')
            ->paginate(15);

        return view('crud.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('crud.customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        Customer::create($this->validatedCustomer($request));

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer): View
    {
        return view('crud.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validatedCustomer($request));

        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if (
            Device::query()->where('customer_id', $customer->customer_id)->exists()
            || Booking::query()->where('customer_id', $customer->customer_id)->exists()
            || Payment::query()->where('customer_id', $customer->customer_id)->exists()
        ) {
            return back()->with('error', 'Pelanggan memiliki data perangkat, booking, atau pembayaran dan tidak dapat dihapus.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil dihapus.');
    }

    /**
     * @return array{name: string, phone_number: string, email: string|null, address: string|null}
     */
    private function validatedCustomer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255', 'regex:/^(?=.*\d)[0-9+()\s-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);
    }
}
