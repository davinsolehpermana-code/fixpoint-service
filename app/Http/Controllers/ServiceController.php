<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::query()
            ->orderByDesc('created_at')
            ->orderByDesc('service_id')
            ->paginate(15);

        return view('crud.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('crud.services.form', ['service' => new Service(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedService($request);
        $validated['is_active'] = $request->boolean('is_active');

        Service::create($validated);

        return redirect()->route('services.index')->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function edit(Service $service): View
    {
        return view('crud.services.form', compact('service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $this->validatedService($request);
        $validated['is_active'] = $request->boolean('is_active');
        $service->update($validated);

        return redirect()->route('services.index')->with('success', 'Data layanan berhasil diperbarui.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if (Booking::query()->where('service_id', $service->service_id)->exists()) {
            return back()->with('error', 'Layanan sudah digunakan pada booking dan tidak dapat dihapus.');
        }

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Layanan berhasil dihapus.');
    }

    /**
     * @return array{name: string, description: string|null, estimated_price: numeric-string|int|float|null}
     */
    private function validatedService(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'estimated_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
